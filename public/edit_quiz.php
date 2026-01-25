<?php
session_start();
require 'db.php';

if (!isset($_GET['id']) || !isset($_SESSION['user_id'])) {
    header("Location: profile.php");
    exit;
}

$quizId = $_GET['id'];
$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = ? AND user_id = ?");
$stmt->execute([$quizId, $userId]);
$quiz = $stmt->fetch();

if (!$quiz) {
    echo "Quiz not found or access denied!";
    exit;
}
$stmt = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = ?");
$stmt->execute([$quizId]);
$questions = $stmt->fetchAll();

foreach ($questions as &$question) {
    $stmt = $pdo->prepare("SELECT * FROM answers WHERE question_id = ?");
    $stmt->execute([$question['id']]);
    $question['answers'] = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Quiz</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color: #f9f7fd; }
    .btn-purple {
      background-color: #a259ff;
      color: white;
    }
    .btn-purple:hover {
      background-color: #843ee6;
      color: white;
    }
    .card { background: #f3edfc; border: 1px solid #e0d4f7; }
  </style>
</head>
<body>

<div class="container py-5">
  <h1 class="mb-4">Uredi kviz</h1>
  <form method="POST" action="update_quiz.php">
    <input type="hidden" name="quiz_id" value="<?= $quizId ?>">

    <div class="mb-3">
      <label class="form-label">Naslov kviza
      </label>
      <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($quiz['title']) ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Opis</label>
      <textarea name="description" class="form-control" rows="2"><?= htmlspecialchars($quiz['description']) ?></textarea>
    </div>

    <h4 class="mt-4">Pitanja</h4>
    <div id="questions-section">
      <?php foreach ($questions as $qIndex => $question): ?>
        <div class="card p-3 mb-3">
          <input type="hidden" name="questions[<?= $qIndex ?>][id]" value="<?= $question['id'] ?>">
          <div class="mb-2">
            <label>Tekst pitanja</label>
            <input type="text" name="questions[<?= $qIndex ?>][question_text]" class="form-control" value="<?= htmlspecialchars($question['question_text']) ?>" required>
          </div>
          <div class="mb-2">
            <label>Tip pitanja</label>
            <select name="questions[<?= $qIndex ?>][question_type]" class="form-select" required>
              <?php $types = ['MULTIPLE_CHOICE', 'TRUE_FALSE', 'SHORT_ANSWER']; ?>
              <?php foreach ($types as $type): ?>
                <option value="<?= $type ?>" <?= $question['question_type'] === $type ? 'selected' : '' ?>><?= $type ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="answers-section">
            <label>Odgovori:</label>
            <?php foreach ($question['answers'] as $aIndex => $answer): ?>
              <div class="input-group mb-2">
                <input type="hidden" name="questions[<?= $qIndex ?>][answers][<?= $aIndex ?>][id]" value="<?= $answer['id'] ?>">
                <input type="text" name="questions[<?= $qIndex ?>][answers][<?= $aIndex ?>][answer_text]" class="form-control" value="<?= htmlspecialchars($answer['answer_text']) ?>" required>
                <select name="questions[<?= $qIndex ?>][answers][<?= $aIndex ?>][is_correct]" class="form-select">
                  <option value="1" <?= $answer['is_correct'] ? 'selected' : '' ?>>Točan</option>
                  <option value="0" <?= !$answer['is_correct'] ? 'selected' : '' ?>>Netočan</option>
                </select>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <button type="submit" class="btn btn-purple mt-3">Spremi promjene</button>
  </form>
</div>

</body>
</html>
