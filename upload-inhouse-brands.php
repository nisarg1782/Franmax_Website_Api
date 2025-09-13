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

    foreach ($rows as $index => $row) {
        if ($index === 0) continue; // skip header

        $masterCategory       = intval($row[0]);
        $category             = intval($row[1]);
        $subCategory          = intval($row[2]);
        $state                = intval($row[3]);
        $city                 = intval($row[4]);
        $brandName            = $conn->real_escape_string($row[5]);
        $brandContact         = $conn->real_escape_string($row[6]);
        $ownerName            = $conn->real_escape_string($row[7]);
        $ownerContact         = $conn->real_escape_string($row[8]);
        $contactPersonName    = $conn->real_escape_string($row[9]);
        $contactPersonNumber  = $conn->real_escape_string($row[10]);
        $status               = $conn->real_escape_string($row[12]);

        // Skip if brandName is empty
        if ($brandName === "") continue;

        $sql = "INSERT INTO inhouse_brands 
                (masterCategory, category, subCategory, state, city, brandName, brandContact, 
                ownerName, ownerContact, contactPersonName, contactPersonNumber,status) 
                VALUES 
                ($masterCategory, $category, $subCategory, $state, $city, 
                '$brandName', '$brandContact', '$ownerName', '$ownerContact', 
                '$contactPersonName', '$contactPersonNumber','$status')";

        if ($conn->query($sql)) {
            $inserted++;
        }
    }

    echo json_encode([
        "success" => true,
        "message" => "$inserted records inserted successfully."
    ]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}
