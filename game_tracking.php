<?php
require_once "database/db_connection.php";

function startSession() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
}

function isLoggedIn() {
    startSession();
    return isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true;
}

function isAdmin() {
    startSession();
    return isset($_SESSION["admin"]) && $_SESSION["admin"] === true;
}

function requireLogin() {
    if (!isLoggedIn()) {
        echo '<div style="text-align:center; padding:30px; font-family:Arial;">
              <h1>Login Required</h1>
              <p>You need to be logged in to access this page.</p>
              <a href="auth/login.php">Login Now</a>
              </div>';
        echo '<script>setTimeout(function() { window.location.href = "auth/login.php"; }, 2000);</script>';
        exit;
    }
}

function requireAdmin() {
    if (!isLoggedIn() || !isAdmin()) {
        echo '<div style="text-align:center; padding:30px; font-family:Arial;">
              <h1>Admin Access Required</h1>
              <p>You need administrator privileges to access this page.</p>
              <a href="auth/login.php">Login as Admin</a>
              </div>';
        echo '<script>setTimeout(function() { window.location.href = "auth/login.php"; }, 2000);</script>';
        exit;
    }
}

/**
 * @param int $user_id 
 * @return int|bool 
 */
function startGameSession($user_id) {
    global $conn;
    
    $sql = "INSERT INTO game_sessions (user_id, start_time, generations) VALUES (?, NOW(), 0)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    
    if (mysqli_stmt_execute($stmt)) {
        return mysqli_insert_id($conn);
    } else {
        error_log("Failed to start game session: " . mysqli_error($conn));
        return false;
    }
}

/**
 * @param int $session_id 
 * @param int $generations 
 * @return bool 
 */
function updateGenerations($session_id, $generations) {
    global $conn;
    
    $sql = "UPDATE game_sessions SET generations = ? WHERE session_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $generations, $session_id);
    
    return mysqli_stmt_execute($stmt);
}

/**
 * @param int $session_id 
 * @return bool 
 */
function endGameSession($session_id) {
    global $conn;
    
    $sql = "UPDATE game_sessions SET end_time = NOW() WHERE session_id = ? AND end_time IS NULL";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $session_id);
    
    return mysqli_stmt_execute($stmt);
}

/**
 * @param int $session_id 
 * @return bool 
 */
function updateSessionActivity($session_id) {
    global $conn;
    
    $sql = "UPDATE game_sessions SET last_activity = NOW() WHERE session_id = ? AND end_time IS NULL";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $session_id);
    
    return mysqli_stmt_execute($stmt);
}

/**
 * @param int $timeout_minutes
 * @return int 
 */
function closeInactiveSessions($timeout_minutes = 30) {
    global $conn;
    
    $sql = "UPDATE game_sessions 
            SET end_time = COALESCE(last_activity, start_time) 
            WHERE end_time IS NULL 
            AND TIMESTAMPDIFF(MINUTE, COALESCE(last_activity, start_time), NOW()) > ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $timeout_minutes);
    mysqli_stmt_execute($stmt);
    
    return mysqli_affected_rows($conn);
}

/**
 * @param int $user_id 
 * @return array 
 */
function getUserStats($user_id) {
    global $conn;
    
    $stats = [
        'total_games' => 0,
        'total_time' => 0,
        'avg_generations' => 0,
        'max_generations' => 0
    ];
    
    $sql = "SELECT COUNT(*) as total_games FROM game_sessions WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $stats['total_games'] = $row['total_games'];
    }
    
    if ($stats['total_games'] > 0) {
        $sql = "SELECT 
                AVG(generations) as avg_generations,
                MAX(generations) as max_generations
                FROM game_sessions 
                WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($row = mysqli_fetch_assoc($result)) {
            $stats['avg_generations'] = round($row['avg_generations']);
            $stats['max_generations'] = $row['max_generations'];
        }
        
        $sql = "SELECT 
                SUM(TIMESTAMPDIFF(MINUTE, start_time, COALESCE(end_time, NOW()))) as total_time
                FROM game_sessions 
                WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($row = mysqli_fetch_assoc($result)) {
            $stats['total_time'] = $row['total_time'];
        }
    }
    
    return $stats;
}
?>