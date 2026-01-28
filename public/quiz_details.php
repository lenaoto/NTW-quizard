<?php
session_start();
require 'db.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$quizId = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = ?");
$stmt->execute([$quizId]);
$quiz = $stmt->fetch();

if (!$quiz) {
    echo "Quiz not found!";
    exit;
}

$isLoggedIn = isset($_SESSION['user_id']);
$isGuest = isset($_SESSION['guest']) && $_SESSION['guest'] === true;
$isModerator = false;
if ($isLoggedIn) {
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $userRole = $stmt->fetchColumn();
    $isModerator = ($userRole === 'moderator' || $userRole === 'admin'); 
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($quiz['title']) ?> - Detalji kviza</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

  <style>
    body {
      background-color: #f9f7fd;
    }
    .btn-purple {
      background-color: #a259ff;
      color: white;
    }
    .btn-purple:hover {
      background-color: #843ee6;
      color: white;
    }
    .star {
      color: #ffc107;
    }
    .comment-box {
      border: 1px solid #e0d4f7;
      background: #f3edfc;
    }
  </style>
</head>
<body>

<div class="container py-5">
  <h1 class="mb-3"><?= htmlspecialchars($quiz['title']) ?></h1>
  <p class="mb-4"><?= htmlspecialchars($quiz['description']) ?></p>

  <div class="d-flex gap-2 mb-5">
    <?php if ($isLoggedIn): ?>
      <a href="solve_quiz.php?id=<?= $quiz['id'] ?>" class="btn btn-purple">Započni kviz</a>
    <?php elseif ($isGuest): ?>
      <button class="btn btn-secondary" disabled>Prijavite se za početak</button>
    <?php else: ?>
      <a href="login.php" class="btn btn-outline-primary">Prijavite se za početak</a>
    <?php endif; ?>
    <a href="index.php<?= $isGuest ? '?guest=1' : '' ?>" class="btn btn-outline-secondary">Nazad na kvizove</a>
  </div>

  <hr class="my-5">
  <h3 class="mb-4">Komentari & ocjene</h3>

  <?php
$stmt = $pdo->prepare("SELECT c.id, c.comment_text, c.rating, c.created_at, u.username
FROM comments c
JOIN users u ON c.user_id = u.id
WHERE c.quiz_id = ?
ORDER BY c.created_at DESC");
  $stmt->execute([$quizId]);
  $comments = $stmt->fetchAll();
  ?>

  <?php if ($comments): ?>
    <?php foreach ($comments as $comment): ?>
  <div class="mb-3 p-3 rounded comment-box d-flex justify-content-between align-items-start">
    <div>
      <strong><?= htmlspecialchars($comment['username']) ?></strong>
      <span class="star">
        <?php for ($i = 1; $i <= 5; $i++): ?>
          <?php if ($i <= $comment['rating']): ?>
            <i class="bi bi-star-fill text-warning"></i>
          <?php else: ?>
            <i class="bi bi-star"></i>
          <?php endif; ?>
        <?php endfor; ?>
      </span>
      <p class="mb-1"><?= htmlspecialchars($comment['comment_text']) ?></p>
      <small class="text-muted"><?= date("F j, Y, H:i", strtotime($comment['created_at'])) ?></small>
    </div>
    <?php if ($isModerator): ?>
      <form method="POST" action="delete_comment.php" onsubmit="return confirm('Are you sure you want to delete this comment?');">
        <input type="hidden" name="comment_id" value="<?= $comment['id'] ?>">
        <input type="hidden" name="quiz_id" value="<?= $quizId ?>">
        <button type="submit" class="btn btn-sm btn-outline-danger ms-3">Obriši</button>
      </form>
    <?php endif; ?>
  </div>
<?php endforeach; ?>

  <?php else: ?>
    <p class="text-muted">Još nema komentara</p>
  <?php endif; ?>

  <?php if ($isLoggedIn): ?>
    <form method="POST" action="submit_comment.php" class="mt-4">
      <div class="mb-3">
        <label class="form-label">Vaš komentar</label>
        <textarea name="comment_text" class="form-control" required></textarea>
      </div>
      <div class="mb-3">
        <label class="form-label">Ocjena</label>
        <select name="rating" class="form-select" required>
          <option value="">Označi ocjenu</option>
          <?php for ($i = 1; $i <= 5; $i++): ?>
            <option value="<?= $i ?>">
  <?= $i ?> <?= $i == 1 ? 'Zvjezdica' : 'Zvjezdice' ?>
</option>
          <?php endfor; ?>
        </select>
      </div>
      <input type="hidden" name="quiz_id" value="<?= $quizId ?>">
      <button type="submit" class="btn btn-purple">Pošaljite komentar</button>
    </form>
  <?php else: ?>
    <p class="mt-4 text-muted">Molim <a href="login.php">prijavite se</a> za ostavljanje komentara.</p>
  <?php endif; ?>
</div>

</body>
</html>
