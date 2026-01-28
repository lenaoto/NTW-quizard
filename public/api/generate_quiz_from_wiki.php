<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

ini_set('display_errors', 0);
error_reporting(E_ALL);
ob_start();

if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  ob_clean();
  echo json_encode(['error' => 'Not logged in'], JSON_UNESCAPED_UNICODE);
  exit;
}

require __DIR__ . '/../db.php';

$payload = json_decode(file_get_contents('php://input'), true);
$title = trim($payload['title'] ?? '');
$lang  = $payload['lang'] ?? 'hr';
$lang  = preg_match('/^[a-z]{2}$/', $lang) ? $lang : 'hr';

if ($title === '') {
  http_response_code(400);
  ob_clean();
  echo json_encode(['error' => 'Missing title'], JSON_UNESCAPED_UNICODE);
  exit;
}

function http_get_json(string $url): array {

  $ctx = stream_context_create([
    'http' => [
      'method' => 'GET',
      'timeout' => 10,
      'header' => "User-Agent: Quizzard/1.0\r\n"
    ],
    'ssl' => [
      'verify_peer' => false,
      'verify_peer_name' => false
    ]
  ]);

  $res = @file_get_contents($url, false, $ctx);
  if ($res !== false) {
    $data = json_decode($res, true);
    if (is_array($data)) return $data;
  }


  if (function_exists('curl_init')) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_TIMEOUT => 10,
      CURLOPT_USERAGENT => 'Quizzard/1.0',
      CURLOPT_SSL_VERIFYPEER => false,
      CURLOPT_SSL_VERIFYHOST => false
    ]);
    $res2 = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($res2 === false || $code >= 400) {
      throw new Exception($err ?: "HTTP error $code");
    }

    $data2 = json_decode($res2, true);
    if (!is_array($data2)) {
      throw new Exception("Invalid JSON from Wikipedia");
    }
    return $data2;
  }

  throw new Exception("Failed to fetch Wikipedia summary (no curl, file_get_contents failed)");
}

function first_sentence(string $text): string {
  $text = preg_replace('/\s+/', ' ', trim($text));
  if ($text === '') return '';
  $parts = preg_split('/(?<=[\.\?\!])\s+/', $text);
  return trim($parts[0] ?? $text);
}

try {
  $url = "https://{$lang}.wikipedia.org/api/rest_v1/page/summary/" . rawurlencode($title);
  $wiki = http_get_json($url);

  $pageTitle = $wiki['title'] ?? $title;
  $extract = trim($wiki['extract'] ?? '');
  $pageUrl = $wiki['content_urls']['desktop']['page'] ?? '';

  if ($extract === '') {
    http_response_code(400);
    ob_clean();
    echo json_encode(['error' => 'Wikipedia summary empty'], JSON_UNESCAPED_UNICODE);
    exit;
  }


  $q1 = [
    'type' => 'TRUE_FALSE',
    'text' => first_sentence($extract) !== '' ? first_sentence($extract) : "{$pageTitle} is described in a Wikipedia article.",
    'answers' => [
      ['text' => 'true',  'correct' => 1],
      ['text' => 'false', 'correct' => 0],
    ]
  ];

  $yearQ = null;
  if (preg_match_all('/\b(1[6-9]\d{2}|20\d{2})\b/', $extract, $m) && !empty($m[1])) {
    $years = array_values(array_unique(array_map('intval', $m[1])));
    sort($years);
    $y = $years[0];
    $opts = [$y, $y + 5, $y - 7, $y + 12];
    $opts = array_values(array_unique($opts));
    while (count($opts) < 4) $opts[] = $y + rand(13, 30);
    $opts = array_slice($opts, 0, 4);
    shuffle($opts);

    $yearQ = [
      'type' => 'MULTIPLE_CHOICE',
      'text' => "Which year is mentioned in the summary of “{$pageTitle}”?",
      'answers' => array_map(fn($a) => [
        'text' => (string)$a,
        'correct' => ((int)$a === $y)
      ], $opts)
    ];
  }

  $q3 = [
    'type' => 'MULTIPLE_CHOICE',
    'text' => "What best describes “{$pageTitle}”?",
    'answers' => [
      ['text' => 'A person / concept described in the article', 'correct' => 1],
      ['text' => 'A random movie character', 'correct' => 0],
      ['text' => 'A football club', 'correct' => 0],
      ['text' => 'A brand of smartphones', 'correct' => 0],
    ]
  ];

  $questions = [$q1];
  if ($yearQ) $questions[] = $yearQ;
  $questions[] = $q3;

  $pdo->beginTransaction();

  $quizTitle = "Wiki quiz: " . $pageTitle;
  $quizDesc  = mb_substr($extract, 0, 400);

  $stmt = $pdo->prepare("INSERT INTO quizzes (title, description, user_id, created_at) VALUES (?, ?, ?, NOW())");
  $stmt->execute([$quizTitle, $quizDesc, (int)$_SESSION['user_id']]);
  $quizId = (int)$pdo->lastInsertId();

  $qStmt = $pdo->prepare("INSERT INTO questions (quiz_id, question_text, question_type) VALUES (?, ?, ?)");
  $aStmt = $pdo->prepare("INSERT INTO answers (question_id, answer_text, is_correct) VALUES (?, ?, ?)");

  foreach ($questions as $q) {
    $qStmt->execute([$quizId, $q['text'], $q['type']]);
    $questionId = (int)$pdo->lastInsertId();

    foreach ($q['answers'] as $a) {
      $aStmt->execute([$questionId, $a['text'], (int)$a['correct']]);
    }
  }

  $pdo->commit();

  ob_clean();
  echo json_encode([
    'ok' => true,
    'quizId' => $quizId,
    'title' => $quizTitle,
    'sourceUrl' => $pageUrl
  ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500);
  ob_clean();
  echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
