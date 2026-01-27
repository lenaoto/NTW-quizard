<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'];
    $quizId = $_POST['quiz_id'];
    $answers = $_POST['answers'] ?? [];

 
    $stmt = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = ?");
    $stmt->execute([$quizId]);
    $questions = $stmt->fetchAll();

    $score = 0;
    $total = count($questions);

    foreach ($questions as $question) {
        $qId = $question['id'];
        $type = $question['question_type'];
        $correct = false;

        if (!isset($answers[$qId])) continue; 

        $userAnswer = $answers[$qId];

        if ($type === 'MULTIPLE_CHOICE') {
      
            $stmt = $pdo->prepare("SELECT id FROM answers WHERE question_id = ? AND is_correct = 1 LIMIT 1");
            $stmt->execute([$qId]);
            $correctAnswer = $stmt->fetchColumn();

            if ((int)$userAnswer === (int)$correctAnswer) {
                $score++;
            }
        } elseif ($type === 'TRUE_FALSE') {
            $stmt = $pdo->prepare("SELECT answer_text FROM answers WHERE question_id = ? AND is_correct = 1 LIMIT 1");
            $stmt->execute([$qId]);
            $correctAnswer = strtolower($stmt->fetchColumn());

            if (strtolower($userAnswer) === $correctAnswer) {
                $score++;
            }
        } elseif ($type === 'SHORT_ANSWER') {
            $stmt = $pdo->prepare("SELECT answer_text FROM answers WHERE question_id = ? AND is_correct = 1 LIMIT 1");
            $stmt->execute([$qId]);
            $correctAnswer = strtolower(trim($stmt->fetchColumn()));

            if (strtolower(trim($userAnswer)) === $correctAnswer) {
                $score++;
            }
        }
    }

 
    $stmt = $pdo->prepare("INSERT INTO results (user_id, quiz_id, score, total_questions) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userId, $quizId, $score, $total]);

    header("Location: result.php?score=$score&total=$total");
    exit;
}
?>
