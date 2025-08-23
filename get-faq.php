<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// Database credentials

// include 'db.php'; // Assuming you have a separate file for DB credentials

// // Create connection
include "db.php";

// Fetch news from the database
$sql = "SELECT id, question,posted_at,answer FROM faq";
$result = $conn->query($sql);

$faq = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $faq[] = [
            'id' => $row['id'],
            'question' => $row['question'],
            'answer' => $row['answer'],
            'created_at' => $row['posted_at'] ? $row['posted_at'] : null
        ];
    }

    echo json_encode([
        'success' => true,
        'faq' => $faq
    ]);
} else {
    echo json_encode([
        'success' => true,
        'faq' => []
    ]);
}

$conn->close();
