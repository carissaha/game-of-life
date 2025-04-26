<?php
session_start();
$username = $_SESSION["username"] ?? "User";
$_SESSION = array();
session_destroy();
$logged_out = true;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logged Out - Conway's Game of Life</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #1a2a6c, #b21f1f, #fdbb2d);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: Arial, sans-serif;
        }
        .logout-container {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
            padding: 30px;
            width: 400px;
            max-width: 100%;
            text-align: center;
        }
        .logout-icon {
            font-size: 3rem;
            color: #2ecc71;
            margin-bottom: 20px;
        }
        .redirect-counter {
            font-size: 0.85rem;
            color: #95a5a6;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="logout-container">
        <div class="logout-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <h2>Successfully Logged Out</h2>
        <p>Thank you for visiting Conway's Game of Life!</p>
        <div class="mt-4">
            <a href="login.php" class="btn btn-primary">
                <i class="fas fa-sign-in-alt mr-2"></i>Login Again
            </a>
            <a href="../userdash.php" class="btn btn-outline-secondary ml-2">
                <i class="fas fa-home mr-2"></i>Home
            </a>
        </div>
        <div class="redirect-counter">
            Redirecting to home page in <span id="counter">5</span> seconds...
        </div>
    </div>
    
    <script>
        let counter = 5;
        const counterElement = document.getElementById('counter');
        
        const timer = setInterval(function() {
            counter--;
            counterElement.textContent = counter;
            
            if (counter <= 0) {
                clearInterval(timer);
                window.location.href = '../userdash.php';
            }
        }, 1000);
    </script>
</body>
</html>