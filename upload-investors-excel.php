<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require __DIR__ . '/vendor/autoload.php'; // PhpSpreadsheet required
include "db.php";

use PhpOffice\PhpSpreadsheet\IOFactory;

if (!isset($_FILES['file'])) {
    echo json_encode(["success" => false, "message" => "No file uploaded"]);
    exit();
}

$file = $_FILES['file']['tmp_name'];

try {
    $spreadsheet = IOFactory::load($file);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray();

    $inserted = 0;

    // Skip header row (assuming first row is header)
    foreach ($rows as $index => $row) {
        if ($index === 0) continue;

        $name = $conn->real_escape_string($row[0]);
        $contact = $conn->real_escape_string($row[1]);
        $email = $conn->real_escape_string($row[2]);
        $budget = $conn->real_escape_string($row[3]);
        $stage = $conn->real_escape_string($row[4]);

        if ($name === "" && $contact === "" && $email === "") continue;

        $sql = "INSERT INTO inhouse_investors (name, contact, email, investment_budget, stage) 
                VALUES ('$name', '$contact', '$email', '$budget', '$stage')";

        if ($conn->query($sql)) {
            $inserted++;
        }
    }

    echo json_encode([
        "success" => true,
        "message" => "$inserted investors inserted successfully."
    ]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}
