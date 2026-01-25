<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quiz_id'])) {
    $quizId = $_POST['quiz_id'];
    $userId = $_SESSION['user_id'];

    $stmt = $pdo->prepare("SELECT user_id FROM quizzes WHERE id = ?");
    $stmt->execute([$quizId]);
    $quiz = $stmt->fetch();

    if ($quiz && ($quiz['user_id'] == $userId || $_SESSION['role'] === 'admin')) {
        $pdo->prepare("DELETE FROM answers WHERE question_id IN (SELECT id FROM questions WHERE quiz_id = ?)")->execute([$quizId]);
        $pdo->prepare("DELETE FROM questions WHERE quiz_id = ?")->execute([$quizId]);
        $pdo->prepare("DELETE FROM results WHERE quiz_id = ?")->execute([$quizId]);
        $pdo->prepare("DELETE FROM comments WHERE quiz_id = ?")->execute([$quizId]);
        $pdo->prepare("DELETE FROM quizzes WHERE id = ?")->execute([$quizId]);
    }
}

header("Location: profile.php");
exit;
?>
