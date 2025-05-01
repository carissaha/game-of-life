<?php
require_once "database/db_connection.php";
require_once "game_tracking.php";

header('Content-Type: application/json');

$session_id = 0;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $session_id = isset($_POST["session_id"]) ? intval($_POST["session_id"]) : 0;
}

if ($session_id == 0 && isset($_GET["session_id"])) {
    $session_id = intval($_GET["session_id"]);
}

if ($session_id == 0) {
    $input = file_get_contents('php://input');
    parse_str($input, $data);
    if (isset($data["session_id"])) {
        $session_id = intval($data["session_id"]);
    }
}

if ($session_id > 0) {
    error_log("Ending session ID: $session_id");
    
    $sql = "UPDATE game_sessions SET end_time = NOW() WHERE session_id = ? AND end_time IS NULL";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $session_id);
        if (mysqli_stmt_execute($stmt)) {
            if (mysqli_affected_rows($conn) > 0) {
                echo json_encode(["success" => true]);
            } else {
                echo json_encode(["success" => false, "error" => "No active session found with ID: $session_id"]);
            }
        } else {
            echo json_encode(["success" => false, "error" => "Failed to execute query: " . mysqli_error($conn)]);
        }
    } else {
        echo json_encode(["success" => false, "error" => "Failed to prepare statement: " . mysqli_error($conn)]);
    }
} else {
    error_log("Invalid session ID in end_session.php");
    echo json_encode(["success" => false, "error" => "Invalid session ID"]);
}