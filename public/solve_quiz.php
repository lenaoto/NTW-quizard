<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); 
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$quizId = $_GET['id'];


$stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = ?");
$stmt->execute([$quizId]);
$quiz = $stmt->fetch();

if (!$quiz) {
    echo "Kviz nije pronađen!";
    exit;
}


$stmt = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = ?");
$stmt->execute([$quizId]);
$questions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($quiz['title']) ?> - Riješi kviz</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
  </style>
</head>
<body>

<div class="container py-5">
  <h1 class="mb-4"><?= htmlspecialchars($quiz['title']) ?></h1>

  <form action="submit_quiz.php" method="POST">
    <input type="hidden" name="quiz_id" value="<?= $quizId ?>">
    
    <?php foreach ($questions as $index => $question): ?>
      <div class="mb-4">
        <h5><?= ($index + 1) . '. ' . htmlspecialchars($question['question_text']) ?></h5>

        <?php if ($question['question_type'] === 'MULTIPLE_CHOICE'): ?>
          <?php
          $stmt = $pdo->prepare("SELECT * FROM answers WHERE question_id = ?");
          $stmt->execute([$question['id']]);
          $answers = $stmt->fetchAll();
          foreach ($answers as $answer):
          ?>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="answers[<?= $question['id'] ?>]" value="<?= $answer['id'] ?>" required>
              <label class="form-check-label"><?= htmlspecialchars($answer['answer_text']) ?></label>
            </div>
          <?php endforeach; ?>

        <?php elseif ($question['question_type'] === 'TRUE_FALSE'): ?>
          <div class="form-check">
            <input class="form-check-input" type="radio" name="answers[<?= $question['id'] ?>]" value="true" required>
            <label class="form-check-label">Točno</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="radio" name="answers[<?= $question['id'] ?>]" value="false" required>
            <label class="form-check-label">Krivo</label>
          </div>

        <?php elseif ($question['question_type'] === 'SHORT_ANSWER'): ?>
          <input type="text" name="answers[<?= $question['id'] ?>]" class="form-control" required>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>

    <button type="submit" class="btn btn-purple">Pošaljite kviz</button>
  </form>
</div>

</body>
</html>
