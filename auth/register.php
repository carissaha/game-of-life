<?php
require_once('../database/db_connection.php');
$username = $email = $password = $confirm_password = "";
$username_err = $email_err = $password_err = $confirm_password_err = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    if (empty(trim($_POST["username"]))) {
        $username_err = "Please enter a username.";
    } else {
        $sql = "SELECT id FROM users WHERE username = ?";
        
        $result = executeQuery($conn, $sql, [trim($_POST["username"])]);
        
        if (mysqli_num_rows($result) == 1) {
            $username_err = "This username is already taken.";
        } else {
            $username = trim($_POST["username"]);
        }
    }
    
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter an email.";
    } else {
        if (!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)) {
            $email_err = "Please enter a valid email address.";
        } else {
            $sql = "SELECT id FROM users WHERE email = ?";
            $result = executeQuery($conn, $sql, [trim($_POST["email"])]);
            
            if (mysqli_num_rows($result) == 1) {
                $email_err = "This email is already registered.";
            } else {
                $email = trim($_POST["email"]);
            }
        }
    }
    
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter a password.";     
    } elseif (strlen(trim($_POST["password"])) < 6) {
        $password_err = "Password must have at least 6 characters.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    if (empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "Please confirm password.";     
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if (empty($password_err) && ($password != $confirm_password)) {
            $confirm_password_err = "Passwords did not match.";
        }
    }
    
    if (empty($username_err) && empty($email_err) && empty($password_err) && empty($confirm_password_err)) {
        
        $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sss", $param_username, $param_email, $param_password);
            
            $param_username = $username;
            $param_email = $email;
            $param_password = password_hash($password, PASSWORD_DEFAULT); // Creates a password hash
            
            if (mysqli_stmt_execute($stmt)) {
                header("location: login.php");
            } else {
                echo "Something went wrong. Please try again later.";
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
  <title>Conway's Game OF Life - Register</title>
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
    .binary {
      font-family: 'Space Mono', monospace;
      font-size: 0.7rem;
      color:var(--cell-glow) ;
      margin-top: 0.5rem;
      letter-spacing: 1px;
      opacity: 0.6;
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
      <p class="form-subtitle">NEW CELL REGISTRATION</p>
      <div class="cell-grid">
        <div class="cell"></div>
        <div class="cell alive"></div>
        <div class="cell"></div>
        <div class="cell alive"></div>
        <div class="cell alive"></div>
        <div class="cell"></div>
        <div class="cell"></div>
        <div class="cell alive"></div>
        <div class="cell"></div>
      </div>
      <p class="binary">01001100 01001111 01000111 01001001 01001110</p>

    </div>

    <div class="form-body">
      <?php if ($error): ?>
        <p class="error-text"><?= htmlspecialchars($error) ?></p>
      <?php endif; ?>
      
      <form method="POST" action="register.php">
        <div class="input-group">
          <label for="fullname">Full Name</label>
          <input id="fullname" name="fullname" type="text" placeholder="Enter your full name" required />
        </div>
        <div class="input-group">
          <label for="email">Email Address</label>
          <input id="email" name="email" type="email" placeholder="Enter your email address" required />
        </div>
        <div class="input-group">
          <label for="username">Username</label>
          <input id="username" name="username" type="text" placeholder="Choose a username" required />
        </div>
        <div class="input-group">
          <label for="password">Password</label>
          <input id="password" name="password" type="password" placeholder="Create a password" required />
          <?php if (!empty($password_err)) echo "<p class='error-text'>$password_err</p>"; ?>
        </div>
        <div class="input-group">
          <label for="confirm_password">Confirm Password</label>
          <input id="confirm_password" name="confirm_password" type="password" placeholder="Confirm your password" required />
          <?php if (!empty($confirm_password_err)) echo "<p class='error-text'>$confirm_password_err</p>"; ?>
        </div>
        <button class="button" type="submit">Generate</button>
      </form>
      
      <div class="link-container">
        <a class="link" href="login.php">Already have an account? Login</a>
      </div>
    </div>
  </div>
</body>
</html>