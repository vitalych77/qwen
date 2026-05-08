<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userAnswer = intval($_POST['answer']);
    $correctAnswer = $_SESSION['correctCount'];
    
    if ($userAnswer === $correctAnswer) {
        $_SESSION['correct_answers']++;
    } else {
        $_SESSION['wrong_answers']++;
    }
    
    echo json_encode(['status' => 'ok']);
}
?>