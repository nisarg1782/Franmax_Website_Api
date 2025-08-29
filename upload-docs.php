<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json");

// Define database connection details


// Define file upload settings
$uploadDirName = 'uploads/';
$base_url = $uploadDirName;
$uploadDir = __DIR__ . '/' . $uploadDirName;

$allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
$maxFileSize = 2 * 1024 * 1024; // 2MB

// Create the uploads directory if it doesn't exist
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Database connection
include "db.php";
// Function to handle file upload and saving
function saveImage($key, $uploadDir, $allowedTypes, $maxFileSize)
{
    if (!isset($_FILES[$key]) || $_FILES[$key]['error'] !== UPLOAD_ERR_OK) {
        return ["success" => false, "message" => "Missing or invalid file for $key"];
    }

    $fileTmp  = $_FILES[$key]['tmp_name'];
    $fileName = basename($_FILES[$key]['name']);
    $fileSize = $_FILES[$key]['size'];
    $fileType = mime_content_type($fileTmp);

    if (!in_array($fileType, $allowedTypes)) {
        return ["success" => false, "message" => "Invalid file type for $key. Only JPG, PNG, WEBP allowed."];
    }

    if ($fileSize > $maxFileSize) {
        return ["success" => false, "message" => "File size for $key exceeds 2MB limit."];
    }

    $uniqueName = uniqid('image_', true) . '_' . preg_replace("/[^a-zA-Z0-9\.]/", "_", $fileName);
    $destination = $uploadDir . $uniqueName;

    if (!move_uploaded_file($fileTmp, $destination)) {
        return ["success" => false, "message" => "Failed to save $key"];
    }

    return ["success" => true, "filename" => $uniqueName];
}

// Validate brand_id
if (!isset($_POST['brand_id']) || !is_numeric($_POST['brand_id'])) {
    echo json_encode(["success" => false, "message" => "Missing or invalid brand_id."]);
    exit;
}

$brand_id = (int)$_POST['brand_id'];
$keys = ['logo', 'primaryImage', 'listingImage', 'detailImage1', 'detailImage2'];
$uploaded = [];
$errors = [];

foreach ($keys as $key) {
    // Only process files that were actually sent in the request
    if (!isset($_FILES[$key])) {
        continue;
    }

    $result = saveImage($key, $uploadDir, $allowedTypes, $maxFileSize);
    
    if (!$result['success']) {
        // Collect errors instead of exiting
        $errors[] = $result['message'];
        continue; 
    }

    $filename = $result['filename'];
    $fullUrl = $base_url . $filename;
    $uploaded[$key] = $fullUrl;

    $checkSql = "SELECT id, photo_url FROM brand_photos WHERE brand_id = ? AND photo_type = ?";
    $stmt = $conn->prepare($checkSql);
    $stmt->bind_param("is", $brand_id, $key);
    $stmt->execute();
    $dbResult = $stmt->get_result();

    if ($row = $dbResult->fetch_assoc()) {
        $oldPath = $uploadDir . $row['photo_url'];
        if (file_exists($oldPath)) {
            unlink($oldPath);
        }
        $updateSql = "UPDATE brand_photos SET photo_url = ? WHERE id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("si", $filename, $row['id']);
        $updateStmt->execute();
        $updateStmt->close();
    } else {
        $insertSql = "INSERT INTO brand_photos (brand_id, photo_url, photo_type) VALUES (?, ?, ?)";
        $insertStmt = $conn->prepare($insertSql);
        $insertStmt->bind_param("iss", $brand_id, $filename, $key);
        $insertStmt->execute();
        $insertStmt->close();
    }
    $stmt->close();
}

$conn->close();

// Respond based on whether there were errors or not
if (!empty($errors)) {
    echo json_encode([
        "success" => false,
        "message" => "Some files failed to upload.",
        "errors" => $errors
    ]);
} else {
    echo json_encode([
        "success" => true,
        "message" => "Files uploaded and saved successfully.",
        "files" => $uploaded
    ]);
}
?>