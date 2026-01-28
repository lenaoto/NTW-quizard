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

$allQuizzesStmt = $pdo->query("
    SELECT q.id, q.title, q.created_at, u.username
    FROM quizzes q
    LEFT JOIN users u ON q.user_id = u.id
    ORDER BY q.created_at DESC
");
$allQuizzes = $allQuizzesStmt->fetchAll();
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
    .btn-purple { background-color: #a259ff; color: white; }
    .btn-purple:hover { background-color: #843ee6; color: white; }
  </style>
</head>
<body>

<div class="container py-5">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0">Nadzorna ploča administratora</h1>
    <a href="index.php" class="btn btn-outline-secondary">Natrag na početnu</a>
  </div>

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
            <td><?= (int)$quiz['num_comments'] ?></td>
            <td><?= $quiz['avg_rating'] ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <p class="text-muted">Još nema ocjena za kviz</p>
  <?php endif; ?>

  <hr class="my-5">

  <h3 class="mb-3">Svi kvizovi</h3>

  <?php if ($allQuizzes): ?>
    <table class="table table-bordered bg-white align-middle">
      <thead class="table-light">
        <tr>
          <th>ID</th>
          <th>Naslov</th>
          <th>Autor</th>
          <th>Datum</th>
          <th style="width: 220px;">Akcije</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($allQuizzes as $q): ?>
          <tr>
            <td><?= (int)$q['id'] ?></td>
            <td><?= htmlspecialchars($q['title']) ?></td>
            <td><?= $q['username'] ? htmlspecialchars($q['username']) : 'Guest/Admin' ?></td>
            <td><?= $q['created_at'] ? date("d.m.Y H:i", strtotime($q['created_at'])) : '-' ?></td>
            <td>
              <div class="d-flex gap-2">
                <a class="btn btn-sm btn-outline-primary" href="admin_edit_quiz.php?id=<?= (int)$q['id'] ?>">Edit</a>

                <form method="POST" action="admin_delete_quiz.php"
                      onsubmit="return confirm('Sigurno želiš obrisati ovaj kviz? Ovo briše i pitanja/odgovore/rezultate/komentare.');">
                  <input type="hidden" name="quiz_id" value="<?= (int)$q['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                </form>

                <a class="btn btn-sm btn-outline-secondary" href="quiz_details.php?id=<?= (int)$q['id'] ?>">View</a>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <p class="text-muted">Nema kvizova.</p>
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
