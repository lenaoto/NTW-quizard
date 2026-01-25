<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

$title = trim($_GET['title'] ?? '');
if ($title === '') {
  http_response_code(400);
  echo json_encode(['error' => 'Missing title'], JSON_UNESCAPED_UNICODE);
  exit;
}

$lang = $_GET['lang'] ?? 'hr';
$lang = preg_match('/^[a-z]{2}$/', $lang) ? $lang : 'hr';

$url = "https://{$lang}.wikipedia.org/api/rest_v1/page/summary/" . rawurlencode($title);

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
  echo json_encode(['error' => $err ?: 'Wikipedia summary failed'], JSON_UNESCAPED_UNICODE);
  exit;
}

$data = json_decode($res, true);

echo json_encode([
  'title'   => $data['title'] ?? $title,
  'extract' => $data['extract'] ?? '',
  'pageUrl' => $data['content_urls']['desktop']['page'] ?? '',
  'thumb'   => $data['thumbnail']['source'] ?? null,
], JSON_UNESCAPED_UNICODE);
