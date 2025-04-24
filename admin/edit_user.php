<?php
require_once "../auth/auth_functions.php";
require_once "../database/db_connection.php";

requireAdmin();

if(!isset($_GET["id"]) || empty(trim($_GET["id"]))){
    header("location: manage_users.php");
    exit();
}

$username = $email = $is_admin = "";
$username_err = $email_err = $password_err = "";
$user_id = trim($_GET["id"]);

if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(empty(trim($_POST["username"]))){
        $username_err = "Please enter a username.";
    } else{
        $sql = "SELECT id FROM users WHERE username = ? AND id != ?";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "si", $param_username, $user_id);
            
            $param_username = trim($_POST["username"]);
            
            if(mysqli_stmt_execute($stmt)){
                mysqli_stmt_store_result($stmt);
                
                if(mysqli_stmt_num_rows($stmt) == 1){
                    $username_err = "This username is already taken.";
                } else{
                    $username = trim($_POST["username"]);
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }

            mysqli_stmt_close($stmt);
        }
    }
    
    if(empty(trim($_POST["email"]))){
        $email_err = "Please enter an email.";
    } else{
        $sql = "SELECT id FROM users WHERE email = ? AND id != ?";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "si", $param_email, $user_id);
            
            $param_email = trim($_POST["email"]);
            
            if(mysqli_stmt_execute($stmt)){
                mysqli_stmt_store_result($stmt);
                
                if(mysqli_stmt_num_rows($stmt) == 1){
                    $email_err = "This email is already taken.";
                } else{
                    $email = trim($_POST["email"]);
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }

            mysqli_stmt_close($stmt);
        }
    }
    
    $is_admin = isset($_POST["is_admin"]) ? 1 : 0;
    
    if(empty($username_err) && empty($email_err)){
        $sql = "UPDATE users SET username = ?, email = ? WHERE id = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "ssi", $param_username, $param_email, $param_id);
            
            $param_username = $username;
            $param_email = $email;
            $param_id = $user_id;
            
            if(mysqli_stmt_execute($stmt)){
                if(!empty(trim($_POST["password"]))){
                    $sql = "UPDATE users SET password = ? WHERE id = ?";
                    
                    if($stmt2 = mysqli_prepare($conn, $sql)){
                        mysqli_stmt_bind_param($stmt2, "si", $param_password, $param_id);
                        
                        $param_password = password_hash(trim($_POST["password"]), PASSWORD_DEFAULT);
                        
                        mysqli_stmt_execute($stmt2);
                        mysqli_stmt_close($stmt2);
                    }
                }
                
                $check_admin_sql = "SELECT user_id FROM admin_users WHERE user_id = ?";
                $check_stmt = mysqli_prepare($conn, $check_admin_sql);
                mysqli_stmt_bind_param($check_stmt, "i", $user_id);
                mysqli_stmt_execute($check_stmt);
                mysqli_stmt_store_result($check_stmt);
                
                $is_currently_admin = mysqli_stmt_num_rows($check_stmt) > 0;
                mysqli_stmt_close($check_stmt);
                
                if($is_admin && !$is_currently_admin) {
                    $sql = "INSERT INTO admin_users (user_id) VALUES (?)";
                    $admin_stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($admin_stmt, "i", $user_id);
                    mysqli_stmt_execute($admin_stmt);
                    mysqli_stmt_close($admin_stmt);
                } else if(!$is_admin && $is_currently_admin) {
                    if($user_id != $_SESSION["id"]) {
                        $sql = "DELETE FROM admin_users WHERE user_id = ?";
                        $admin_stmt = mysqli_prepare($conn, $sql);
                        mysqli_stmt_bind_param($admin_stmt, "i", $user_id);
                        mysqli_stmt_execute($admin_stmt);
                        mysqli_stmt_close($admin_stmt);
                    }
                }
                
                header("location: manage_users.php?success=User updated successfully");
                exit();
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }
        }
        
        mysqli_stmt_close($stmt);
    }
    
} else {
    $sql = "SELECT u.username, u.email, CASE WHEN a.id IS NOT NULL THEN 1 ELSE 0 END as is_admin
            FROM users u
            LEFT JOIN admin_users a ON u.id = a.user_id
            WHERE u.id = ?";
            
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $param_id);
        
        $param_id = $user_id;
        
        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
    
            if(mysqli_num_rows($result) == 1){
                $row = mysqli_fetch_array($result);
                
                $username = $row["username"];
                $email = $row["email"];
                $is_admin = $row["is_admin"];
            } else{
                header("location: manage_users.php");
                exit();
            }
            
        } else{
            echo "Oops! Something went wrong. Please try again later.";
        }
    }
    
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - Conway's Game of Life</title>
    <link rel="stylesheet" href="admin_styles.css">
</head>
<body>
    <div class="navbar">
        <div class="container">
            <span style="font-weight: bold; font-size: 20px;">Admin Dashboard</span>
            <span style="float: right;">
                <a href="dashboard.php">Dashboard</a>
                <a href="manage_users.php">Manage Users</a>
                <a href="view_sessions.php">Game Sessions</a>
                <a href="../game.php">Play Game</a>
                <a href="../auth/logout.php">Logout</a>
            </span>
        </div>
    </div>
    
    <div class="container">
        <div style="max-width: 800px; margin: 0 auto;">
            <div class="card">
                <div class="card-header header-primary">Edit User</div>
                <div class="card-body">
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?id=' . $user_id; ?>" method="post">
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>">
                            <?php if(!empty($username_err)): ?>
                            <div class="invalid-feedback"><?php echo $username_err; ?></div>
                            <?php endif; ?>
                        </div>    
                        
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $email; ?>">
                            <?php if(!empty($email_err)): ?>
                            <div class="invalid-feedback"><?php echo $email_err; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" name="password" class="form-control" placeholder="Leave blank to keep current password">
                            <small style="color: #6c757d; font-size: 12px;">Leave blank if you don't want to change the password.</small>
                        </div>
                        
                        <div class="form-group">
                            <label style="display: inline-block; margin-right: 10px;">Admin Privileges</label>
                            <input type="checkbox" id="adminSwitch" name="is_admin" <?php echo $is_admin ? 'checked' : ''; ?> <?php echo ($user_id == $_SESSION["id"]) ? 'disabled' : ''; ?>>
                            <?php if($user_id == $_SESSION["id"]): ?>
                            <small style="color: #6c757d; font-size: 12px; display: block; margin-top: 5px;">You cannot remove your own admin privileges.</small>
                            <?php endif; ?>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; margin-top: 20px;">
                            <a href="manage_users.php" class="btn btn-secondary">Back to Users</a>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header header-info">User Game Statistics</div>
                <div class="card-body">
                    <?php
                    $stats_sql = "SELECT 
                        COUNT(*) as total_games,
                        COALESCE(SUM(TIMESTAMPDIFF(MINUTE, start_time, COALESCE(end_time, NOW()))), 0) as total_time,
                        COALESCE(AVG(generations), 0) as avg_generations,
                        COALESCE(MAX(generations), 0) as max_generations
                        FROM game_sessions WHERE user_id = ?";
                    
                    $stats_stmt = mysqli_prepare($conn, $stats_sql);
                    mysqli_stmt_bind_param($stats_stmt, "i", $user_id);
                    mysqli_stmt_execute($stats_stmt);
                    $stats_result = mysqli_stmt_get_result($stats_stmt);
                    $stats = mysqli_fetch_assoc($stats_result);
                    ?>
                    
                    <div style="display: flex; flex-wrap: wrap; margin: 0 -10px;">
                        <div style="flex: 1; min-width: 100px; margin: 10px; text-align: center;">
                            <h5>Total Games</h5>
                            <h3><?php echo $stats['total_games']; ?></h3>
                        </div>
                        <div style="flex: 1; min-width: 100px; margin: 10px; text-align: center;">
                            <h5>Play Time</h5>
                            <h3><?php echo round($stats['total_time']); ?> min</h3>
                        </div>
                        <div style="flex: 1; min-width: 100px; margin: 10px; text-align: center;">
                            <h5>Avg. Generations</h5>
                            <h3><?php echo round($stats['avg_generations']); ?></h3>
                        </div>
                        <div style="flex: 1; min-width: 100px; margin: 10px; text-align: center;">
                            <h5>Max Generations</h5>
                            <h3><?php echo $stats['max_generations']; ?></h3>
                        </div>
                    </div>
                    
                    <?php if($stats['total_games'] > 0): ?>
                    <div style="text-align: center; margin-top: 15px;">
                        <a href="view_sessions.php?username=<?php echo urlencode($username); ?>" class="btn btn-primary" style="font-size: 14px;">View Game History</a>
                    </div>
                    <?php else: ?>
                    <p style="text-align: center; color: #6c757d; margin-top: 15px;">This user has not played any games yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>