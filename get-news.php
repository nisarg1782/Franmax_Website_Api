<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// Database credentials

include 'db.php'; // Assuming you have a separate file for DB credentials

// // Create connection


// Fetch news from the database
$sql = "SELECT id, title, image, description, created_at FROM news ORDER BY created_at DESC";
$result = $conn->query($sql);

$news = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $news[] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'image' => $row['image'] ? $row['image'] : null,
            'description' => $row['description'],
            'created_at' => $row['created_at']
        ];
    }

    echo json_encode([
        'success' => true,
        'news' => $news
    ]);
} else {
    echo json_encode([
        'success' => true,
        'news' => []
    ]);
}

$conn->close();
