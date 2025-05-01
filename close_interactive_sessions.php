<?php
require_once "database/db_connection.php";
require_once "game_tracking.php";
$timeout_minutes = 30; 
$closed_count = closeInactiveSessions($timeout_minutes);

echo "Closed $closed_count inactive sessions.";