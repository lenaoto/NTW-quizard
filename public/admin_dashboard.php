<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$role = $stmt->fetchColumn();

if ($role !== 'admin') {
    header("Location: index.php");
    exit;
}

$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalQuizzes = $pdo->query("SELECT COUNT(*) FROM quizzes")->fetchColumn();
$totalComments = $pdo->query("SELECT COUNT(*) FROM comments")->fetchColumn();
$avgRating = $pdo->query("SELECT ROUND(AVG(rating), 2) FROM comments")->fetchColumn();


$topQuizzesStmt = $pdo->query("
    SELECT q.title, COUNT(c.id) AS num_comments, ROUND(AVG(c.rating), 2) AS avg_rating
    FROM quizzes q
    JOIN comments c ON q.id = c.quiz_id
    GROUP BY q.id
    ORDER BY avg_rating DESC
    LIMIT 5
");
$topQuizzes = $topQuizzesStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color: #f9f7fd; }
    .card { background: #f3edfc; border: 1px solid #e0d4f7; }
  .btn-purple {
    background-color: #a259ff;
    color: white;
  }
  .btn-purple:hover {
    background-color: #843ee6;
    color: white;
  }
</style>
  </style>
</head>
<body>

<div class="container py-5">
  <h1 class="mb-4">Nadzorna ploča administratora</h1>

  <div class="row mb-4">
    <div class="col-md-3">
      <div class="card p-3 text-center">
        <h5>Svi korisnici</h5>
        <p class="fs-3"><?= $totalUsers ?></p>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card p-3 text-center">
        <h5>Svi kvizovi</h5>
        <p class="fs-3"><?= $totalQuizzes ?></p>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card p-3 text-center">
        <h5>Svi komentari</h5>
        <p class="fs-3"><?= $totalComments ?></p>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card p-3 text-center">
        <h5>Prosječna ocjena</h5>
        <p class="fs-3"><?= $avgRating ? $avgRating : 'N/A' ?> / 5</p>
      </div>
    </div>
  </div>
  <div class="mb-4 text-end">
  <a href="admin_user_management.php" class="btn btn-purple">Upravljanje korisnicima</a>
</div>
  <h3 class="mb-3">Najbolje ocjenjeni kvizovi</h3>
  <?php if ($topQuizzes): ?>
    <table class="table table-bordered bg-white">
      <thead class="table-light">
        <tr>
          <th>Naslov kviza</th>
          <th>Broj komentara</th>
          <th>Prosječna ocjena</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($topQuizzes as $quiz): ?>
          <tr>
            <td><?= htmlspecialchars($quiz['title']) ?></td>
            <td><?= $quiz['num_comments'] ?></td>
            <td><?= $quiz['avg_rating'] ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <p class="text-muted">Još nema ocjena za kviz</p>
  <?php endif; ?>
</div>

</body>
</html>
