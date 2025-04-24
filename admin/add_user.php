<?php
require_once "../auth/auth_functions.php";
require_once "../database/db_connection.php";

requireAdmin();

$username = $email = $password = $confirm_password = "";
$username_err = $email_err = $password_err = $confirm_password_err = "";
$is_admin = false;

if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(empty(trim($_POST["username"]))){
        $username_err = "Please enter a username.";
    } else{
        $sql = "SELECT id FROM users WHERE username = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            
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
        $sql = "SELECT id FROM users WHERE email = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "s", $param_email);
            
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
    
    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter a password.";     
    } elseif(strlen(trim($_POST["password"])) < 6){
        $password_err = "Password must have at least 6 characters.";
    } else{
        $password = trim($_POST["password"]);
    }
    
    if(empty(trim($_POST["confirm_password"]))){
        $confirm_password_err = "Please confirm password.";     
    } else{
        $confirm_password = trim($_POST["confirm_password"]);
        if(empty($password_err) && ($password != $confirm_password)){
            $confirm_password_err = "Password did not match.";
        }
    }
    
    $is_admin = isset($_POST["is_admin"]) ? true : false;
    
    if(empty($username_err) && empty($email_err) && empty($password_err) && empty($confirm_password_err)){
        
        $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
         
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "sss", $param_username, $param_email, $param_password);
            
            $param_username = $username;
            $param_email = $email;
            $param_password = password_hash($password, PASSWORD_DEFAULT); // Creates a password hash
            
            if(mysqli_stmt_execute($stmt)){
                $new_user_id = mysqli_insert_id($conn);
                
                if($is_admin){
                    $admin_sql = "INSERT INTO admin_users (user_id) VALUES (?)";
                    $admin_stmt = mysqli_prepare($conn, $admin_sql);
                    mysqli_stmt_bind_param($admin_stmt, "i", $new_user_id);
                    mysqli_stmt_execute($admin_stmt);
                }
                
                header("location: manage_users.php?success=User created successfully");
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }

            mysqli_stmt_close($stmt);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New User - Conway's Game of Life</title>
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
                <div class="card-header header-success">Add New User</div>
                <div class="card-body">
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
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
                            <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $password; ?>">
                            <?php if(!empty($password_err)): ?>
                            <div class="invalid-feedback"><?php echo $password_err; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label>Confirm Password</label>
                            <input type="password" name="confirm_password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $confirm_password; ?>">
                            <?php if(!empty($confirm_password_err)): ?>
                            <div class="invalid-feedback"><?php echo $confirm_password_err; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label style="display: inline-block; margin-right: 10px;">Grant Admin Privileges</label>
                            <input type="checkbox" id="adminSwitch" name="is_admin" <?php echo $is_admin ? 'checked' : ''; ?>>
                            <div style="color: #6c757d; font-size: 12px; margin-top: 5px;">Admin users can access the admin dashboard and manage other users.</div>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; margin-top: 20px;">
                            <a href="manage_users.php" class="btn btn-secondary">Back to Users</a>
                            <button type="submit" class="btn btn-success">Create User</button>
                        </div>
                    </form>
                </div>
                <div class="card-footer">
                    <small style="color: #6c757d;">New users will be able to log in immediately after creation.</small>
                </div>
            </div>
        </div>
    </div>
</body>
</html>