<?php
// Set CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header('Content-Type: application/json');

// Handle pre-flight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ===================== UPLOAD PATH CONFIGURATION =====================
// Define the path to your uploads folder. This path is relative to the PHP script.
// Ensure this folder has write permissions (e.g., 0777)
$uploadDir = "uploads/"; // Ensure this folder exists and is writable

// Create the directory if it doesn't exist
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0777, true)) {
        http_response_code(500);
        echo json_encode(array("success" => false, "message" => "Failed to create upload directory. Check server permissions."));
        exit();
    }
}

// ===================== DATABASE CONFIGURATION =====================
include "db.php";

// ===================== MAIN SCRIPT LOGIC =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'add':
            // ===================== ADD NEW NEWSLETTER =====================
            if (!isset($_POST['title']) || empty(trim($_POST['title'])) || !isset($_POST['description']) || empty(trim($_POST['description'])) || !isset($_FILES['image'])) {
                http_response_code(400);
                echo json_encode(array("success" => false, "message" => "All fields (title, description, image) are required."));
                exit();
            }

            $title = $conn->real_escape_string($_POST['title']);
            $description = $_POST['description']; // Do not sanitize HTML from rich text editor
            
            $image_tmp_name = $_FILES['image']['tmp_name'];
            $image_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $unique_filename = uniqid() . '_' . time() . '.' . $image_extension;
            $imagePath = $uploadDir . $unique_filename;

            if (move_uploaded_file($image_tmp_name, $imagePath)) {
                $sql = "INSERT INTO news (title, image, description, created_at) VALUES (?, ?, ?, NOW())";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sss", $title, $imagePath, $description);

                if ($stmt->execute()) {
                    http_response_code(201); // 201 Created
                    echo json_encode(array("success" => true, "message" => "Newsletter added successfully!"));
                } else {
                    http_response_code(500);
                    echo json_encode(array("success" => false, "message" => "Database execution failed: " . $stmt->error));
                }
                $stmt->close();
            } else {
                http_response_code(500);
                echo json_encode(array("success" => false, "message" => "Failed to move uploaded image. Check permissions."));
            }
            break;

        case 'edit':
            // ===================== EDIT NEWSLETTER =====================
            $id = $_POST['id'] ?? null;
            $title = $_POST['title'] ?? null;
            $description = $_POST['description'] ?? null;
            
            if (empty($id) || empty(trim($title)) || empty(trim($description))) {
                http_response_code(400);
                echo json_encode(array("success" => false, "message" => "ID, title, and description are required for editing."));
                exit();
            }
            
            $sql = "UPDATE news SET title = ?, description = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            
            if (!$stmt) {
                http_response_code(500);
                echo json_encode(array("success" => false, "message" => "SQL prepare failed: " . $conn->error));
                exit();
            }
            
            $stmt->bind_param("ssi", $title, $description, $id);
            
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    echo json_encode(array("success" => true, "message" => "Newsletter updated successfully."));
                } else {
                    echo json_encode(array("success" => false, "message" => "Newsletter not found or no changes were made."));
                }
            } else {
                http_response_code(500);
                echo json_encode(array("success" => false, "message" => "Database execution failed: " . $stmt->error));
            }
            $stmt->close();
            break;

        case 'delete':
            // ===================== DELETE NEWSLETTER =====================
            $id = $_POST['id'] ?? null;
            
            if (empty($id)) {
                http_response_code(400);
                echo json_encode(array("success" => false, "message" => "ID is required for deletion."));
                exit();
            }
            
            // First, get the image path to delete the file from the server
            $sql_select = "SELECT image FROM news WHERE id = ?";
            $stmt_select = $conn->prepare($sql_select);
            $stmt_select->bind_param("i", $id);
            $stmt_select->execute();
            $result = $stmt_select->get_result();
            $row = $result->fetch_assoc();
            $stmt_select->close();

            if ($row) {
                $imagePath = $row['image'];
                
                // Then, delete the database record
                $sql_delete = "DELETE FROM news WHERE id = ?";
                $stmt_delete = $conn->prepare($sql_delete);
                $stmt_delete->bind_param("i", $id);

                if ($stmt_delete->execute()) {
                    if ($stmt_delete->affected_rows > 0) {
                        // Delete the image file from the server
                        if (!empty($imagePath) && file_exists($imagePath)) {
                            unlink($imagePath);
                        }
                        echo json_encode(array("success" => true, "message" => "Newsletter deleted successfully."));
                    } else {
                        echo json_encode(array("success" => false, "message" => "Newsletter not found."));
                    }
                } else {
                    http_response_code(500);
                    echo json_encode(array("success" => false, "message" => "Database execution failed: " . $stmt_delete->error));
                }
                $stmt_delete->close();
            } else {
                echo json_encode(array("success" => false, "message" => "Newsletter not found."));
            }
            break;

        default:
            // ===================== INVALID ACTION =====================
            http_response_code(400);
            echo json_encode(array("success" => false, "message" => "Invalid action specified."));
            break;
    }
} else {
    // ===================== INVALID REQUEST METHOD =====================
    http_response_code(405);
    echo json_encode(array("success" => false, "message" => "Invalid request method. Only POST is allowed."));
}

$conn->close();
?>