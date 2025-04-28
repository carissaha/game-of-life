<?php
session_start();
require_once "database/db_connection.php";
require_once "game_tracking.php";  

// Check if the user is logged in
if (!isLoggedIn()) {
    header("Location: auth/login.php");
    exit(); // Prevent further execution if not logged in
}

$user_id = $_SESSION['id'];
$sql = "SELECT username FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

// Fetch user stats or game data as needed
$stats = getUserStats($user_id);

?>




<!DOCTYPE html>
<html>
<head>
  <title>User Dashboard</title>
  <link rel="stylesheet" href="dashboard.css">
</head>
<body>
  <div class="userdash">
  <h1>Welcome, <?php echo htmlspecialchars($user['username']); ?>!</h1>
  <button onclick="logOut()">Log Out</button>

    
    <div class="stats">
      <p>Games Played: <?php echo $stats['total_games']; ?></p>
      <p>Total Time: <?php echo $stats['total_time']; ?> minutes</p>
      <p>Average Generations: <?php echo $stats['avg_generations']; ?></p>
      <p>Max Generations: <?php echo $stats['max_generations']; ?></p>
    </div>



    <button onclick="startGame()">Start Game</button>

  <script src="userdash.js"></script>
  <?php $conn->close(); ?>
</body>
</html>
