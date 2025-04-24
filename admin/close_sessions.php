<?php
require_once "../auth/auth_functions.php";
require_once "../database/db_connection.php";
require_once "../game_tracking.php";

requireAdmin();

$message = "";
$error = "";

if (isset($_GET['close_session']) && !empty($_GET['close_session'])) {
    $session_id = intval($_GET['close_session']);
    if (endGameSession($session_id)) {
        $message = "Session #$session_id has been closed successfully.";
    } else {
        $error = "Failed to close session #$session_id.";
    }
}

if (isset($_GET['close_all'])) {
    $sql = "UPDATE game_sessions SET end_time = NOW() WHERE end_time IS NULL";
    if (mysqli_query($conn, $sql)) {
        $affected = mysqli_affected_rows($conn);
        $message = "$affected session(s) have been closed successfully.";
    } else {
        $error = "Failed to close sessions: " . mysqli_error($conn);
    }
}

if (isset($_POST['close_by_username']) && !empty($_POST['username'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $sql = "UPDATE game_sessions g 
            JOIN users u ON g.user_id = u.id 
            SET g.end_time = NOW() 
            WHERE u.username = ? AND g.end_time IS NULL";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $username);
    
    if (mysqli_stmt_execute($stmt)) {
        $affected = mysqli_stmt_affected_rows($stmt);
        $message = "$affected session(s) for user '$username' have been closed.";
    } else {
        $error = "Failed to close sessions: " . mysqli_error($conn);
    }
}

$sql = "SELECT g.session_id, u.username, g.start_time, 
        TIMESTAMPDIFF(MINUTE, g.start_time, NOW()) AS duration
        FROM game_sessions g
        JOIN users u ON g.user_id = u.id
        WHERE g.end_time IS NULL
        ORDER BY g.start_time DESC";

$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Active Sessions - Conway's Game of Life</title>
    <link rel="stylesheet" href="admin_styles.css">
</head>
<body>
    <div class="navbar">
        <div class="container">
            <span style="font-weight: bold;">Admin Dashboard</span>
            <span style="float: right;">
                <a href="dashboard.php">Dashboard</a>
                <a href="manage_users.php">Manage Users</a>
                <a href="view_sessions.php">Game Sessions</a>
                <a href="close_sessions.php">Active Sessions</a>
                <a href="../game.php">Play Game</a>
                <a href="../auth/logout.php">Logout</a>
            </span>
        </div>
    </div>
    
    <div class="container">
        <h2>Manage Active Sessions</h2>
        
        <?php if(!empty($message)): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if(!empty($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div style="margin-bottom: 20px;">
            <a href="close_sessions.php?close_all=1" class="btn btn-danger" 
               onclick="return confirm('Are you sure you want to close ALL active sessions?')">
                Close All Active Sessions
            </a>
        </div>
        
        <form method="POST" action="">
            <input type="text" name="username" placeholder="Username" required>
            <button type="submit" name="close_by_username" class="btn">Close Sessions by Username</button>
        </form>
        
        <h3>Active Sessions</h3>
        <?php if(mysqli_num_rows($result) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Session ID</th>
                        <th>Username</th>
                        <th>Start Time</th>
                        <th>Duration (mins)</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?php echo $row['session_id']; ?></td>
                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                            <td><?php echo $row['start_time']; ?></td>
                            <td><?php echo $row['duration']; ?></td>
                            <td>
                                <a href="close_sessions.php?close_session=<?php echo $row['session_id']; ?>" 
                                   class="btn btn-danger" style="font-size: 12px;">Close</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No active sessions found.</p>
        <?php endif; ?>
    </div>
</body>
</html>