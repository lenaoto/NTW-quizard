<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$score = $_GET['score'] ?? 0;
$total = $_GET['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Quiz Result</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-5 text-center">
  <h1>Vaš rezultat: <?= htmlspecialchars($score) ?> / <?= htmlspecialchars($total) ?></h1>
  <a href="index.php" class="btn btn-primary mt-3">Nazad na početnu</a>
</div>
</body>
</html>
