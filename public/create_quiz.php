<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$errors = [];

function h($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

function is_blank($s) {
    return !isset($s) || trim((string)$s) === '';
}

function require_field($value, $msg, &$errors) {
    if (is_blank($value)) $errors[] = $msg;
}

function count_correct_answers(array $answers): int {
    $count = 0;
    foreach ($answers as $a) {
        if (!empty($a['is_correct'])) $count++;
    }
    return $count;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $userId = (int)$_SESSION['user_id'];

    require_field($title, "Quiz title is required.", $errors);
    require_field($description, "Description is required.", $errors);

    $questions = $_POST['questions'] ?? [];
    if (!is_array($questions) || count($questions) === 0) {
        $errors[] = "Morate dodati barem jedno pitanje.";
    }

    if (!$errors) {
        foreach ($questions as $qi => $question) {
            $qText = trim($question['text'] ?? '');
            $qType = $question['type'] ?? '';

            if ($qText === '') {
                $errors[] = "Broj pitanja" . ($qi + 1) . ": Tekst pitanja je obavezan.";
                continue;
            }

            if (!in_array($qType, ['MULTIPLE_CHOICE','TRUE_FALSE','SHORT_ANSWER'], true)) {
                $errors[] = "Broj pitanja" . ($qi + 1) . ": Nevažeći tip pitanja.";
                continue;
            }

            if ($qType === 'MULTIPLE_CHOICE') {
                $answers = $question['answers'] ?? [];
                if (!is_array($answers) || count($answers) < 2) {
                    $errors[] = "Question #" . ($qi + 1) . ": Pitanje s više od jednog odgovora mora imati barem 2 ponuđena odgovora.";
                    continue;
                }

                $nonEmpty = [];
                foreach ($answers as $ai => $a) {
                    $txt = trim($a['text'] ?? '');
                    if ($txt !== '') {
                        $nonEmpty[] = $a;
                    }
                }

                if (count($nonEmpty) < 2) {
                    $errors[] = "Question #" . ($qi + 1) . ": Stavite barem 2 neprazna odgovora.";
                    continue;
                }

                $correctCount = count_correct_answers($answers);
                if ($correctCount < 1) {
                    $errors[] = "Question #" . ($qi + 1) . ": Označite bar jedan točan odgovor.";
                    continue;
                }
            }

            if ($qType === 'TRUE_FALSE') {
                $answers = $question['answers'] ?? [];
                $correctIndex = isset($question['correct_tf']) ? (int)$question['correct_tf'] : -1;

                if (!is_array($answers) || count($answers) !== 2) {
                    $errors[] = "Question #" . ($qi + 1) . ": Točno/Netočno mora imati točno 2 odgovora.";
                    continue;
                }

                $a0 = strtolower(trim($answers[0]['text'] ?? ''));
                $a1 = strtolower(trim($answers[1]['text'] ?? ''));

                if (!($a0 === 'true' && $a1 === 'false')) {
                    $errors[] = "Question #" . ($qi + 1) . ": Točno/Netočno odgovori moraju biti 'točno' i 'netočno'.";
                    continue;
                }

                if (!in_array($correctIndex, [0,1], true)) {
                    $errors[] = "Question #" . ($qi + 1) . ": Označite točno jedan točan odgovor za Točno/Netočno.";
                    continue;
                }
            }

            if ($qType === 'SHORT_ANSWER') {
                $short = trim($question['short_answer'] ?? '');
                if ($short === '') {
                    $errors[] = "Question #" . ($qi + 1) . ": Kratki odgovor zahtijeva tekst točnog odgovora.";
                    continue;
                }
            }
        }
    }

    if (!$errors) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO quizzes (title, description, user_id) VALUES (?, ?, ?)");
            $stmt->execute([$title, $description, $userId]);
            $quizId = (int)$pdo->lastInsertId();

            $qInsert = $pdo->prepare("INSERT INTO questions (quiz_id, question_text, question_type) VALUES (?, ?, ?)");
            $aInsert = $pdo->prepare("INSERT INTO answers (question_id, answer_text, is_correct) VALUES (?, ?, ?)");

            foreach ($questions as $question) {
                $qText = trim($question['text']);
                $qType = $question['type'];

                $qInsert->execute([$quizId, $qText, $qType]);
                $questionId = (int)$pdo->lastInsertId();

                if ($qType === 'MULTIPLE_CHOICE') {
                    foreach ($question['answers'] as $answer) {
                        $aText = trim($answer['text'] ?? '');
                        if ($aText === '') continue; 
                        $isCorrect = !empty($answer['is_correct']) ? 1 : 0;
                        $aInsert->execute([$questionId, $aText, $isCorrect]);
                    }
                } elseif ($qType === 'TRUE_FALSE') {
                    $correctIndex = (int)$question['correct_tf']; 

                    foreach ($question['answers'] as $i => $answer) {
                        $aText = strtolower(trim($answer['text'] ?? ''));
                        $isCorrect = ((int)$i === $correctIndex) ? 1 : 0;
                        $aInsert->execute([$questionId, $aText, $isCorrect]);
                    }
                } elseif ($qType === 'SHORT_ANSWER') {
                    $aText = trim($question['short_answer']);
                    $aInsert->execute([$questionId, $aText, 1]);
                }
            }

            $pdo->commit();
            header("Location: index.php");
            exit;

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Kreiraj kviz</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color: #f9f7fd; }
    .question-block {
      border: 1px solid #ddd;
      padding: 15px;
      margin-bottom: 20px;
      border-radius: 10px;
      background: #fff;
    }
    .hint { font-size: 0.9rem; color: #666; }
  </style>
</head>
<body>

<div class="container py-5">
  <h1 class="mb-4">Kreiraj kviz</h1>

  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
      <div class="fw-bold mb-2">Molim ispravite sljedeće:</div>
      <ul class="mb-0">
        <?php foreach ($errors as $err): ?>
          <li><?= h($err) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="POST" novalidate>
    <div class="mb-3">
      <label class="form-label">Naziv kviza</label>
      <input type="text" name="title" class="form-control" required value="<?= h($_POST['title'] ?? '') ?>">
    </div>

    <div class="mb-4">
      <label class="form-label">Opis</label>
      <textarea name="description" class="form-control" required><?= h($_POST['description'] ?? '') ?></textarea>
    </div>

    <div id="questions"></div>

    <div class="d-flex justify-content-end gap-2">
      <button type="button" class="btn btn-outline-secondary" onclick="addQuestion()">Dodaj pitanje</button>
      <button type="submit" class="btn btn-primary">Spremi kviz</button>
    </div>
  </form>
</div>

<script>
let questionCount = 0;

function addQuestion() {
  const questionsDiv = document.getElementById('questions');
  const qIndex = questionCount++;

  const qBlock = document.createElement('div');
  qBlock.classList.add('question-block');

  qBlock.innerHTML = `
    <div class="d-flex justify-content-between align-items-center mb-2">
      <h5 class="mb-0">Question #${qIndex + 1}</h5>
      <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.question-block').remove()">Remove</button>
    </div>

    <div class="mb-3">
      <label class="form-label">Question Text</label>
      <input type="text" name="questions[${qIndex}][text]" class="form-control" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Question Type</label>
      <select name="questions[${qIndex}][type]" class="form-select" onchange="toggleAnswers(this, ${qIndex})" required>
        <option value="">Select Type</option>
        <option value="MULTIPLE_CHOICE">Multiple Choice</option>
        <option value="TRUE_FALSE">True / False</option>
        <option value="SHORT_ANSWER">Short Answer</option>
      </select>
    </div>

    <div class="answers" id="answers-${qIndex}"></div>
  `;

  questionsDiv.appendChild(qBlock);
}

function toggleAnswers(select, index) {
  const answersDiv = document.getElementById(`answers-${index}`);
  answersDiv.innerHTML = '';

  if (select.value === 'MULTIPLE_CHOICE') {
    addMultipleChoiceAnswers(answersDiv, index);
  } else if (select.value === 'TRUE_FALSE') {
    answersDiv.innerHTML = `
      <label class="form-label">Correct Answer</label>

      <input type="hidden" name="questions[${index}][answers][0][text]" value="true">
      <input type="hidden" name="questions[${index}][answers][1][text]" value="false">

      <div class="form-check">
        <input class="form-check-input" type="radio"
               name="questions[${index}][correct_tf]" value="0" required>
        <label class="form-check-label">True</label>
      </div>

      <div class="form-check">
        <input class="form-check-input" type="radio"
               name="questions[${index}][correct_tf]" value="1" required>
        <label class="form-check-label">False</label>
      </div>
    `;
  } else if (select.value === 'SHORT_ANSWER') {
    answersDiv.innerHTML = `
      <div class="mb-3">
        <label class="form-label">Correct Answer</label>
        <input type="text" name="questions[${index}][short_answer]" class="form-control" required>
      </div>
    `;
  }
}

function addMultipleChoiceAnswers(container, qIndex) {
  container.innerHTML = `
    <label class="form-label">Answers</label>
    <div class="hint mb-2">Mark one or more correct answers.</div>
  `;

  for (let i = 0; i < 4; i++) {
    container.innerHTML += `
      <div class="input-group mb-2">
        <div class="input-group-text">
          <input type="checkbox" name="questions[${qIndex}][answers][${i}][is_correct]">
        </div>
        <input type="text" name="questions[${qIndex}][answers][${i}][text]"
               class="form-control" placeholder="Answer option" required>
      </div>
    `;
  }
}
</script>

</body>
</html>
