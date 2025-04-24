<?php
require_once "database/db_connection.php";
require_once "game_tracking.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $session_id = isset($_POST["session_id"]) ? intval($_POST["session_id"]) : 0;
    $generations = isset($_POST["generations"]) ? intval($_POST["generations"]) : 0;
    
    if ($session_id > 0) {
        updateGenerations($session_id, $generations);
        
        echo json_encode(["success" => true]);
        exit;
    }
}

echo json_encode(["success" => false, "error" => "Invalid request"]);
?>