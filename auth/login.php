<?php
session_start();
 if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: ../index.php");
    exit;
}
 require_once "../database/db_connection.php";
 
$username = $password = "";
$username_err = $password_err = $login_err = "";
 if($_SERVER["REQUEST_METHOD"] == "POST"){
 
    if(empty(trim($_POST["username"]))){
        $username_err = "Please enter username.";
    } else{
        $username = trim($_POST["username"]);
    }
    
    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter your password.";
    } else{
        $password = trim($_POST["password"]);
    }
    
    if(empty($username_err) && empty($password_err)){
        $sql = "SELECT id, username, password FROM users WHERE username = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            
            $param_username = $username;
            
            if(mysqli_stmt_execute($stmt)){
                mysqli_stmt_store_result($stmt);
                
                if(mysqli_stmt_num_rows($stmt) == 1){                    
                    mysqli_stmt_bind_result($stmt, $id, $username, $hashed_password);
                    if(mysqli_stmt_fetch($stmt)){
                        if(password_verify($password, $hashed_password)){
                            session_start();
                            
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["username"] = $username;                            
                            
                            $admin_check = "SELECT * FROM admin_users WHERE user_id = ?";
                            $result = executeQuery($conn, $admin_check, [$id]);
                            
                            if(mysqli_num_rows($result) > 0){
                                $_SESSION["admin"] = true;
                                header("location: ../admin/dashboard.php");
                            } else {
                                header("location: ../game.php");
                            }
                        } else{
                            $login_err = "Invalid username or password.";
                        }
                    }
                } else{
                    $login_err = "Invalid username or password.";
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }
            mysqli_stmt_close($stmt);
        }
    }
        mysqli_close($conn);
}
?>
 
 <!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Conway's Game OF Life - Login</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&family=Space+Mono:wght@400;700&display=swap');
    
    :root {
      --grid-dark: #0b132b;
      --grid-light: #1c2541;
      --cell-color: #3a86ff;
      --cell-glow: #00f5d4;
      --text-dark: #0f172a;
      --text-light: #64748b;
      --text-white: #f8fafc;
      --form-bg: rgba(255, 255, 255, 0.9);
      --input-bg: #f1f5f9;
      --input-border: #e2e8f0;
      --input-focus: #3a86ff;
      --button-bg: #3a86ff;
      --button-hover: #2667e0;
      --link-color: #3a86ff;
      --error-color: #ef4444;
    }
    
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    html, body {
      height: 100%;
    }
    
    body {
      font-family: 'Poppins', sans-serif;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      padding: 1.5rem;
      position: relative;
    }
        body::before {
      content: "";
      background-image: url('images/giphy.gif');
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: -2;
    }
        body::after {
      content: "";
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(11, 19, 43, 0.7);
      z-index: -1;
    }
    
    .form-container {
      width: 100%;
      max-width: 450px;
      background-color: var(--form-bg);
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
    }
    
    .form-header {
      background: linear-gradient(135deg, var(--grid-light), var(--grid-dark));
      padding: 1.5rem;
      text-align: center;
      position: relative;
    }
    
    .form-header::after {
      content: "";
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: linear-gradient(90deg, var(--cell-color), var(--cell-glow));
    }
    
    .form-title {
      color: var(--text-white);
      font-family: 'Space Mono', monospace;
      font-size: 1.5rem;
      font-weight: 700;
      letter-spacing: -0.5px;
      margin-bottom: 0.25rem;
    }
    
    .form-subtitle {
      color: var(--cell-glow);
      font-size: 0.85rem;
      font-family: 'Space Mono', monospace;
      letter-spacing: 1px;
    }
    
    .binary {
      font-family: 'Space Mono', monospace;
      font-size: 0.7rem;
      color:var(--cell-glow) ;
      margin-top: 0.5rem;
      letter-spacing: 1px;
      opacity: 0.6;
    }
    
    .form-body {
      padding: 2rem 1.5rem;
    }
    
    .input-group {
      margin-bottom: 1.25rem;
    }
    
    .input-group label {
      display: block;
      font-size: 0.85rem;
      font-weight: 500;
      color: var(--text-dark);
      margin-bottom: 0.5rem;
    }
    
    .input-group input {
      width: 100%;
      padding: 0.8rem 1rem;
      background-color: var(--input-bg);
      border: 1px solid var(--input-border);
      border-radius: 8px;
      font-family: 'Poppins', sans-serif;
      font-size: 0.95rem;
      color: var(--text-dark);
      transition: all 0.2s ease;
    }
    
    .input-group input:focus {
      outline: none;
      border-color: var(--input-focus);
      box-shadow: 0 0 0 3px rgba(58, 134, 255, 0.15);
    }
    
    .button {
      width: 100%;
      background: linear-gradient(135deg, var(--button-bg), var(--button-hover));
      color: white;
      border: none;
      border-radius: 8px;
      padding: 0.9rem;
      font-family: 'Space Mono', monospace;
      font-size: 1rem;
      font-weight: 700;
      cursor: pointer;
      text-transform: uppercase;
      letter-spacing: 1px;
    }
    
    .button:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(58, 134, 255, 0.2);
    }
    
    .link-container {
      text-align: center;
      margin-top: 1.5rem;
      padding-top: 1.5rem;
      border-top: 1px solid var(--input-border);
    }
    
    .link {
      color: var(--link-color);
      text-decoration: none;
      font-size: 0.9rem;
      font-weight: 500;
    }
    
    .link:hover {
      text-decoration: underline;
    }
    
    .error-text {
      background-color: rgba(239, 68, 68, 0.1);
      color: var(--error-color);
      padding: 0.75rem;
      border-radius: 8px;
      margin-bottom: 1.5rem;
      font-size: 0.85rem;
      text-align: center;
      border-left: 3px solid var(--error-color);
    }
    
    .cell-grid {
      display: grid;
      grid-template-columns: repeat(3, 10px);
      grid-template-rows: repeat(3, 10px);
      gap: 2px;
      margin: 1rem auto 0;
    }
    
    .cell {
      width: 10px;
      height: 10px;
      background-color: var(--grid-light);
      border-radius: 1px;
    }
    
    .cell.alive {
      background-color: var(--cell-color);
      box-shadow: 0 0 8px 2px rgba(0, 245, 212, 0.3);
    }
    
    @media (max-width: 480px) {
      .form-container {
        max-width: 100%;
      }
      
      .form-body {
        padding: 1.5rem 1.25rem;
      }
    }
  </style>
</head>
<body>
  <div class="form-container">
    <div class="form-header">
      <h1 class="form-title">Conway's Game OF Life</h1>
      <p class="form-subtitle">CELL ACTIVATION</p>
      <div class="cell-grid">
        <div class="cell"></div>
        <div class="cell alive"></div>
        <div class="cell"></div>
        <div class="cell"></div>
        <div class="cell alive"></div>
        <div class="cell"></div>
        <div class="cell"></div>
        <div class="cell"></div>
        <div class="cell alive"></div>
      </div>
      <p class="binary">01001100 01001111 01000111 01001001 01001110</p>
    </div>
    
    <div class="form-body">
      <?php if ($error): ?>
        <p class="error-text"><?= htmlspecialchars($error) ?></p>
      <?php endif; ?>
      
      <form method="POST" action="login.php">
        <div class="input-group">
          <label for="username">Username</label>
          <input id="username" name="username" type="text" placeholder="Enter your username" required autofocus />
        </div>
        <div class="input-group">
          <label for="password">Password</label>
          <input id="password" name="password" type="password" placeholder="Enter your password" required />
        </div>
        <button class="button" type="submit">Activate</button>
      </form>
      
      <div class="link-container">
        <a class="link" href="register.php">Don't have an account? Register</a>
      </div>
    </div>
  </div>
</body>
</html>