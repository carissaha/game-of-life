<?php
require_once "../auth/auth_functions.php";
require_once "../database/db_connection.php";

requireAdmin();

$sql = "SELECT COUNT(*) as total_users FROM users";
$result = mysqli_query($conn, $sql);
$total_users = mysqli_fetch_assoc($result)['total_users'];

$sql = "SELECT COUNT(*) as total_sessions FROM game_sessions";
$result = mysqli_query($conn, $sql);
$total_sessions = mysqli_fetch_assoc($result)['total_sessions'];

$sql = "SELECT AVG(generations) as avg_generations FROM game_sessions";
$result = mysqli_query($conn, $sql);
$avg_generations = round(mysqli_fetch_assoc($result)['avg_generations']);

$sql = "SELECT AVG(TIMESTAMPDIFF(MINUTE, start_time, COALESCE(end_time, NOW()))) as avg_play_time 
        FROM game_sessions 
        WHERE end_time IS NOT NULL";
$result = mysqli_query($conn, $sql);
$avg_play_time = round(mysqli_fetch_assoc($result)['avg_play_time']);

$sql = "SELECT u.username, COUNT(g.session_id) as games_played 
        FROM users u 
        JOIN game_sessions g ON u.id = g.user_id 
        GROUP BY u.id 
        ORDER BY games_played DESC 
        LIMIT 5";
$top_players_result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Conway's Game of Life</title>
    <link rel="stylesheet" href="admin_styles.css">
</head>
<body>
    <div class="navbar">
        <div class="container">
            <strong>Admin Dashboard</strong>
            <span style="float: right;">
                <a href="dashboard.php">Dashboard</a>
                <a href="manage_users.php">Manage Users</a>
                <a href="view_sessions.php">Game Sessions</a>
                <a href="../game.html">Play Game</a>
                <a href="../auth/logout.php">Logout</a>
            </span>
        </div>
    </div>
    
    <div class="container">
        <h2>Admin Dashboard</h2>
        <p>Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?>. Here's an overview of the system.</p>
        
        <div class="stats-row">
            <div class="stat-card primary">
                <h3>Total Users</h3>
                <h2><?php echo $total_users; ?></h2>
            </div>
            
            <div class="stat-card success">
                <h3>Games Played</h3>
                <h2><?php echo $total_sessions; ?></h2>
            </div>
            
            <div class="stat-card info">
                <h3>Avg. Generations</h3>
                <h2><?php echo $avg_generations; ?></h2>
            </div>
            
            <div class="stat-card warning">
                <h3>Avg. Play Time</h3>
                <h2><?php echo $avg_play_time; ?> min</h2>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">Top Players - Games Played</div>
            <div class="card-body">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Username</th>
                            <th>Games Played</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $rank = 1;
                        while ($row = mysqli_fetch_assoc($top_players_result)) {
                            echo "<tr>";
                            echo "<td>" . $rank++ . "</td>";
                            echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                            echo "<td>" . $row['games_played'] . "</td>";
                            echo "</tr>";
                        }
                        
                        if (mysqli_num_rows($top_players_result) == 0) {
                            echo "<tr><td colspan='3' style='text-align: center'>No data available</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">Quick Actions</div>
            <div class="card-body">
                <p><a href="manage_users.php" style="display: block; padding: 10px; background: #f4f4f4; margin-bottom: 5px; text-decoration: none; color: #333; border-radius: 3px;">Manage Users</a></p>
                <p><a href="view_sessions.php" style="display: block; padding: 10px; background: #f4f4f4; margin-bottom: 5px; text-decoration: none; color: #333; border-radius: 3px;">View Game Sessions</a></p>
                <p><a href="../game.html" style="display: block; padding: 10px; background: #f4f4f4; text-decoration: none; color: #333; border-radius: 3px;">Play Game</a></p>
            </div>
        </div>
    </div>
</body>
</html>