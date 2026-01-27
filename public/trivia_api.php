<?php
header('Content-Type: application/json');

$uri = 'https://opentdb.com/api.php?amount=1';

$response = file_get_contents($uri);

$triviaData = json_decode($response, true);

if (isset($triviaData['results'][0])) {
    $q = $triviaData['results'][0];

    $output = [
        'category' => $q['category'],
        'question' => html_entity_decode($q['question']),
        'correct_answer' => html_entity_decode($q['correct_answer']),
        'incorrect_answers' => array_map('html_entity_decode', $q['incorrect_answers'])
    ];

    echo json_encode($output);
} else {
    echo json_encode(['error' => 'Nije pronađeno pitanje']);
}
