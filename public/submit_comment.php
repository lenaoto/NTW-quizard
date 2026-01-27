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
    $commentText = $_POST['comment_text'];
    $rating = $_POST['rating'];

    $stmt = $pdo->prepare("INSERT INTO comments (user_id, quiz_id, comment_text, rating) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userId, $quizId, $commentText, $rating]);

    header("Location: quiz_details.php?id=" . $quizId);
    exit;
}
?>
