<?php
require_once "database/db_connection.php";
require_once "game_tracking.php";
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$user_id = isset($_SESSION["id"]) ? $_SESSION["id"] : 0;
$username = isset($_SESSION["username"]) ? $_SESSION["username"] : "Guest";
$session_id = 0;

if ($user_id > 0) {
    $session_id = startGameSession($user_id);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Conway's Game of Life</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="game.css" />
</head>
<body>
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container-fluid">
      <a class="navbar-brand" href="#">Game of Life</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item"><a class="nav-link" href="home.html">Home</a></li>
          <li class="nav-item"><a class="nav-link" href="game.php">Game</a></li>
          <?php if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
            <li class="nav-item"><a class="nav-link" href="userdash.php">Dashboard</a></li>
            
            <?php if (isset($_SESSION["admin"]) && $_SESSION["admin"] === true): ?>
              <li class="nav-item"><a class="nav-link" href="admin/dashboard.php">Admin Dashboard</a></li>
            <?php endif; ?>
            
            <li class="nav-item"><a class="nav-link" href="auth/logout.php">Logout</a></li>
          <?php else: ?>
            <li class="nav-item"><a class="nav-link" href="auth/login.php">Login</a></li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </nav>

  <div class="container text-center mb-4">
    <?php if ($user_id > 0): ?>
      <p class="badge">Playing as: <strong><?php echo htmlspecialchars($username); ?></strong></p>
    <?php else: ?>
      <p class="badge">Playing as Guest - <a href="auth/login.php" style="color:#000;">Login</a> to save your progress</p>
    <?php endif; ?>
  </div>
  <div class="container text-center mb-4 controls">
    <div class="row g-2 justify-content-center">
      <div class="col-auto">
        <button class="btn btn-outline-success" onclick="startGame()">▶ Start</button>
      </div>
      <div class="col-auto">
        <button class="btn btn-outline-danger" onclick="stopGame()">⏸ Stop</button>
      </div>
      <div class="col-auto">
        <button class="btn btn-outline-primary" onclick="nextGen()">⏭ Next Gen</button>
      </div>
      <div class="col-auto">
        <button class="btn btn-outline-warning" onclick="advance23()"> +23 Gens</button>
      </div>
      <div class="col-auto">
        <button class="btn btn-outline-light" onclick="resetGame()"> Reset</button>
      </div>
      <div class="col-auto">
        <select id="patternSelect" class="form-select" onchange="loadPattern(this.value)">
          <option value="">Load Pattern</option>
          <option value="block">Block</option>
          <option value="blinker">Blinker</option>
          <option value="beacon">Beacon</option>
          <option value="toad">Toad</option>
          <option value="glider">Glider</option>
        </select>
      </div>
    </div>
    <div class="mt-3">
      <span class="badge">Generation: <span id="generation">0</span></span>
    </div>
  </div>
  <div id="grid" class="grid container"></div>

  <script>
    const sessionId = <?php echo $session_id; ?>;
    const isLoggedIn = <?php echo ($user_id > 0) ? 'true' : 'false'; ?>;
  </script>
  <script src="game.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>