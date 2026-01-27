<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: profile.php");
    exit;
}

$userId = $_SESSION['user_id'];
$quizId = $_POST['quiz_id'] ?? null;

$stmt = $pdo->prepare("SELECT user_id FROM quizzes WHERE id = ?");
$stmt->execute([$quizId]);
$ownerId = $stmt->fetchColumn();

if (!$ownerId || $ownerId != $userId) {
    echo "Access denied.";
    exit;
}

$title = $_POST['title'] ?? '';
$description = $_POST['description'] ?? '';

$pdo->prepare("UPDATE quizzes SET title = ?, description = ? WHERE id = ?")->execute([$title, $description, $quizId]);

$stmt = $pdo->prepare("SELECT id FROM questions WHERE quiz_id = ?");
$stmt->execute([$quizId]);
$existingQuestionIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

$formQuestionIds = isset($_POST['questions']) ? array_column($_POST['questions'], 'id') : [];


$questionsToDelete = array_diff($existingQuestionIds, $formQuestionIds);

foreach ($questionsToDelete as $questionId) {
    $pdo->prepare("DELETE FROM answers WHERE question_id = ?")->execute([$questionId]);
    
    $pdo->prepare("DELETE FROM questions WHERE id = ?")->execute([$questionId]);
}


if (isset($_POST['questions']) && is_array($_POST['questions'])) {
    foreach ($_POST['questions'] as $question) {
        $questionText = $question['question_text'];
        $questionType = $question['question_type'];

       
        if (!empty($question['id'])) {
            $questionId = $question['id'];
            $pdo->prepare("UPDATE questions SET question_text = ?, question_type = ? WHERE id = ?")->execute([$questionText, $questionType, $questionId]);

            $stmt = $pdo->prepare("SELECT id FROM answers WHERE question_id = ?");
            $stmt->execute([$questionId]);
            $existingAnswerIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $formAnswerIds = isset($question['answers']) ? array_column($question['answers'], 'id') : [];
            $answersToDelete = array_diff($existingAnswerIds, $formAnswerIds);

            foreach ($answersToDelete as $answerId) {
                $pdo->prepare("DELETE FROM answers WHERE id = ?")->execute([$answerId]);
            }

        
            if (isset($question['answers'])) {
                foreach ($question['answers'] as $answer) {
                    $answerText = $answer['answer_text'];
                    $isCorrect = isset($answer['is_correct']) ? (int)$answer['is_correct'] : 0;

                    if (!empty($answer['id'])) {
                     
                        $pdo->prepare("UPDATE answers SET answer_text = ?, is_correct = ? WHERE id = ?")->execute([$answerText, $isCorrect, $answer['id']]);
                    } else {
                      
                        $pdo->prepare("INSERT INTO answers (question_id, answer_text, is_correct) VALUES (?, ?, ?)")->execute([$questionId, $answerText, $isCorrect]);
                    }
                }
            }

        } else {
          
            $pdo->prepare("INSERT INTO questions (quiz_id, question_text, question_type) VALUES (?, ?, ?)")->execute([$quizId, $questionText, $questionType]);
            $newQuestionId = $pdo->lastInsertId();

       
            if (isset($question['answers'])) {
                foreach ($question['answers'] as $answer) {
                    $answerText = $answer['answer_text'];
                    $isCorrect = isset($answer['is_correct']) ? (int)$answer['is_correct'] : 0;

                    $pdo->prepare("INSERT INTO answers (question_id, answer_text, is_correct) VALUES (?, ?, ?)")->execute([$newQuestionId, $answerText, $isCorrect]);
                }
            }
        }
    }
}

header("Location: profile.php");
exit;
