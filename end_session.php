<?php
require_once "database/db_connection.php";
require_once "game_tracking.php";

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
    
    if (endGameSession($session_id)) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => "Failed to end session"]);
    }
} else {
    error_log("Invalid session ID in end_session.php");
    echo json_encode(["success" => false, "error" => "Invalid session ID"]);
}
?>