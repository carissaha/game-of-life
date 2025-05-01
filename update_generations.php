<?php
require_once "database/db_connection.php";
require_once "game_tracking.php";

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $session_id = isset($_POST["session_id"]) ? intval($_POST["session_id"]) : 0;
    $generations = isset($_POST["generations"]) ? intval($_POST["generations"]) : 0;
    
    if ($session_id > 0) {
        $sql = "UPDATE game_sessions SET generations = ?, last_activity = NOW() WHERE session_id = ? AND end_time IS NULL";
        $stmt = mysqli_prepare($conn, $sql);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ii", $generations, $session_id);
            if (mysqli_stmt_execute($stmt)) {
                echo json_encode(["success" => true]);
                exit;
            } else {
                echo json_encode(["success" => false, "error" => "Failed to update: " . mysqli_error($conn)]);
                exit;
            }
        } else {
            echo json_encode(["success" => false, "error" => "Failed to prepare statement: " . mysqli_error($conn)]);
            exit;
        }
    } else {
        echo json_encode(["success" => false, "error" => "Invalid session ID"]);
        exit;
    }
} else {
    echo json_encode(["success" => false, "error" => "Invalid request method"]);
    exit;
}