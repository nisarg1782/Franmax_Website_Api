<?php
// Set CORS headers to allow requests from your React app
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Database connection details
include "db.php";

// Check if the request method is POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Get the action from the request
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($action === 'delete') {
        // --- DELETE Operation ---
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

        if ($id <= 0) {
            echo json_encode(["success" => false, "message" => "Invalid FAQ ID."]);
            exit;
        }

        $sql = "DELETE FROM faq WHERE id = ?";
        $stmt = $conn->prepare($sql);
        
        if ($stmt === false) {
            echo json_encode(["success" => false, "message" => "Prepare failed: " . $conn->error]);
            exit;
        }
        
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode(["success" => true, "message" => "FAQ deleted successfully."]);
            } else {
                echo json_encode(["success" => false, "message" => "FAQ not found."]);
            }
        } else {
            echo json_encode(["success" => false, "message" => "Failed to delete FAQ: " . $stmt->error]);
        }
        $stmt->close();
    
    } else {
        // --- ADD or EDIT Operation ---
        $id = isset($_POST['id']) ? (int)$_POST['id'] : null;
        $question = isset($_POST['question']) ? $_POST['question'] : '';
        $answer = isset($_POST['answer']) ? $_POST['answer'] : '';
    
        // Validate the input for add/edit
        if (empty($question) || empty($answer)) {
            echo json_encode(["success" => false, "message" => "Question and answer cannot be empty."]);
            exit;
        }
    
        // Check for duplicate questions (excluding the current item if editing)
        $checkSql = "SELECT id FROM faq WHERE question = ?";
        if ($id) {
            $checkSql .= " AND id != ?";
        }
        
        $checkStmt = $conn->prepare($checkSql);
        if ($checkStmt === false) {
            echo json_encode(["success" => false, "message" => "Prepare failed: " . $conn->error]);
            exit;
        }
    
        if ($id) {
            $checkStmt->bind_param("si", $question, $id);
        } else {
            $checkStmt->bind_param("s", $question);
        }
        
        $checkStmt->execute();
        $checkStmt->store_result();
    
        if ($checkStmt->num_rows > 0) {
            echo json_encode(["success" => false, "message" => "This question already exists. Please enter a unique question."]);
            $checkStmt->close();
            exit;
        }
        $checkStmt->close();
    
        // Perform the main database operation (INSERT or UPDATE)
        if ($id) {
            $sql = "UPDATE faq SET question = ?, answer = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssi", $question, $answer, $id);
        } else {
            $sql = "INSERT INTO faq (question, answer, created_at) VALUES (?, ?, NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $question, $answer);
        }
    
        if ($stmt === false) {
            echo json_encode(["success" => false, "message" => "Prepare failed: " . $conn->error]);
            exit;
        }
    
        if ($stmt->execute()) {
            if ($id) {
                 echo json_encode(["success" => true, "message" => "FAQ updated successfully."]);
            } else {
                echo json_encode(["success" => true, "message" => "FAQ added successfully."]);
            }
        } else {
            echo json_encode(["success" => false, "message" => "Failed to manage FAQ: " . $stmt->error]);
        }
    
        $stmt->close();
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
}

$conn->close();
?>