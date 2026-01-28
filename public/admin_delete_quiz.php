<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: admin_dashboard.php");
    exit;
}

$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$role = $stmt->fetchColumn();

if ($role !== 'admin') {
    header("Location: index.php");
    exit;
}

$quizId = (int)($_POST['quiz_id'] ?? 0);
if (!$quizId) {
    header("Location: admin_dashboard.php");
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT id FROM questions WHERE quiz_id = ?");
    $stmt->execute([$quizId]);
    $qIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($qIds as $qid) {
        $pdo->prepare("DELETE FROM answers WHERE question_id = ?")->execute([(int)$qid]);
    }

    $pdo->prepare("DELETE FROM questions WHERE quiz_id = ?")->execute([$quizId]);

    $pdo->prepare("DELETE FROM comments WHERE quiz_id = ?")->execute([$quizId]);

    $pdo->prepare("DELETE FROM results WHERE quiz_id = ?")->execute([$quizId]);

    $pdo->prepare("DELETE FROM quizzes WHERE id = ?")->execute([$quizId]);

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    die("Greška kod brisanja: " . $e->getMessage());
}

header("Location: admin_dashboard.php");
exit;
