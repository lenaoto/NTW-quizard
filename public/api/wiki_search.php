<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

$q = trim($_GET['q'] ?? '');
if ($q === '' || mb_strlen($q) < 2) {
  echo json_encode(['results' => []], JSON_UNESCAPED_UNICODE);
  exit;
}

$lang = $_GET['lang'] ?? 'hr';
$lang = preg_match('/^[a-z]{2}$/', $lang) ? $lang : 'hr';
$limit = (int)($_GET['limit'] ?? 8);
$limit = max(1, min(15, $limit));

$url = "https://{$lang}.wikipedia.org/w/api.php?" . http_build_query([
  'action' => 'opensearch',
  'search' => $q,
  'limit'  => $limit,
  'namespace' => 0,
  'format' => 'json'
]);

$ch = curl_init($url);
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_TIMEOUT => 10,
  CURLOPT_USERAGENT => 'Quizzard/1.0'
]);

$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err = curl_error($ch);
curl_close($ch);

if ($res === false || $code >= 400) {
  http_response_code(500);
  echo json_encode(['error' => $err ?: 'Wikipedia search failed'], JSON_UNESCAPED_UNICODE);
  exit;
}

$data = json_decode($res, true);
$titles = $data[1] ?? [];
$descs  = $data[2] ?? [];
$links  = $data[3] ?? [];

$results = [];
for ($i = 0; $i < count($titles); $i++) {
  $results[] = [
    'title' => $titles[$i],
    'desc'  => $descs[$i] ?? '',
    'url'   => $links[$i] ?? ''
  ];
}

echo json_encode(['results' => $results], JSON_UNESCAPED_UNICODE);
