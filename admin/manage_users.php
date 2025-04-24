<?php
require_once "../auth/auth_functions.php";
require_once "../database/db_connection.php";

requireAdmin();

if(isset($_GET["action"]) && $_GET["action"] == "delete" && !empty(trim($_GET["id"]))){
    if($_GET["id"] == $_SESSION["id"]){
        $delete_error = "You cannot delete your own account.";
    } else {
        $sql = "DELETE FROM users WHERE id = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "i", $param_id);
            
            $param_id = trim($_GET["id"]);
            
            if(mysqli_stmt_execute($stmt)){
                header("location: manage_users.php?success=User deleted successfully");
                exit();
            } else{
                $delete_error = "Error deleting user. Please try again later.";
            }
        }
         
        mysqli_stmt_close($stmt);
    }
}

if(isset($_GET["action"]) && $_GET["action"] == "toggle_admin" && !empty(trim($_GET["id"]))){
    $user_id = trim($_GET["id"]);
    
    $check_sql = "SELECT * FROM admin_users WHERE user_id = ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "i", $user_id);
    mysqli_stmt_execute($check_stmt);
    $result = mysqli_stmt_get_result($check_stmt);
    
    if(mysqli_num_rows($result) > 0){
        if($user_id == $_SESSION["id"]){
            $role_error = "You cannot remove your own admin privileges.";
        } else {
            $sql = "DELETE FROM admin_users WHERE user_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            
            if(mysqli_stmt_execute($stmt)){
                header("location: manage_users.php?success=Admin privileges removed");
                exit();
            } else {
                $role_error = "Error changing user role. Please try again later.";
            }
        }
    } else {
        $sql = "INSERT INTO admin_users (user_id) VALUES (?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        
        if(mysqli_stmt_execute($stmt)){
            header("location: manage_users.php?success=Admin privileges granted");
            exit();
        } else {
            $role_error = "Error changing user role. Please try again later.";
        }
    }
}

$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$search_condition = '';
if(!empty($search)){
    $search_condition = " WHERE u.username LIKE '%{$search}%' OR u.email LIKE '%{$search}%'";
}

$count_sql = "SELECT COUNT(*) as total FROM users u".$search_condition;
$count_result = mysqli_query($conn, $count_sql);
$total_records = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_records / $records_per_page);

$sql = "SELECT u.id, u.username, u.email, u.created_at, 
        CASE WHEN a.id IS NOT NULL THEN 'Yes' ELSE 'No' END as is_admin
        FROM users u
        LEFT JOIN admin_users a ON u.id = a.user_id
        $search_condition
        ORDER BY u.created_at DESC
        LIMIT $offset, $records_per_page";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Conway's Game of Life</title>
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
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2>Manage Users</h2>
            <a href="add_user.php" class="btn btn-success">Add New User</a>
        </div>
        
        <?php
        if(isset($_GET['success'])){
            echo '<div class="alert alert-success">' . htmlspecialchars($_GET['success']) . '</div>';
        }
        
        if(isset($delete_error)){
            echo '<div class="alert alert-danger">' . $delete_error . '</div>';
        }
        if(isset($role_error)){
            echo '<div class="alert alert-danger">' . $role_error . '</div>';
        }
        ?>
        
        <div class="card">
            <div class="card-body">
                <form action="manage_users.php" method="GET" style="text-align: center;">
                    <div style="display: inline-block; width: 70%;">
                        <input type="text" name="search" style="width: 70%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" placeholder="Search by username or email" value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn btn-primary">Search</button>
                        <?php if(!empty($search)): ?>
                        <a href="manage_users.php" class="btn btn-secondary">Clear</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header header-primary">Users List</div>
            <div class="card-body">
                <?php
                if(mysqli_num_rows($result) > 0){
                ?>
                <div style="overflow-x: auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Admin</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            while($row = mysqli_fetch_array($result)){
                                echo "<tr>";
                                echo "<td>" . $row['id'] . "</td>";
                                echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                                echo "<td>" . $row['is_admin'] . "</td>";
                                echo "<td>" . $row['created_at'] . "</td>";
                                echo "<td>";
                                
                                echo '<a href="edit_user.php?id='. $row['id'] .'" class="btn btn-primary" style="padding: 5px 8px; font-size: 12px;">Edit</a>';
                                
                                if($row['is_admin'] == 'Yes'){
                                    echo '<a href="manage_users.php?action=toggle_admin&id='. $row['id'] .'" class="btn btn-warning" style="padding: 5px 8px; font-size: 12px;">Remove Admin</a>';
                                } else {
                                    echo '<a href="manage_users.php?action=toggle_admin&id='. $row['id'] .'" class="btn btn-secondary" style="padding: 5px 8px; font-size: 12px;">Make Admin</a>';
                                }
                                
                                if($row['id'] != $_SESSION["id"]){
                                    echo '<a href="manage_users.php?action=delete&id='. $row['id'] .'" class="btn btn-danger" style="padding: 5px 8px; font-size: 12px;" onclick="return confirm(\'Are you sure you want to delete this user?\')">Delete</a>';
                                }
                                
                                echo "</td>";
                                echo "</tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                    
                    <?php if($total_pages > 1): ?>
                    <div style="margin-top: 20px;">
                        <ul class="pagination">
                            <?php if($page > 1): ?>
                            <li>
                                <a href="?page=1<?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>">First</a>
                            </li>
                            <li>
                                <a href="?page=<?php echo $page-1; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>">Previous</a>
                            </li>
                            <?php endif; ?>
                            
                            <?php 
                            $range = 2; 
                            $start_page = max(1, $page - $range);
                            $end_page = min($total_pages, $page + $range);
                            
                            for($i = $start_page; $i <= $end_page; $i++): 
                            ?>
                            <li <?php echo $i == $page ? 'class="active"' : ''; ?>>
                                <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>"><?php echo $i; ?></a>
                            </li>
                            <?php endfor; ?>
                            
                            <?php if($page < $total_pages): ?>
                            <li>
                                <a href="?page=<?php echo $page+1; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>">Next</a>
                            </li>
                            <li>
                                <a href="?page=<?php echo $total_pages; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>">Last</a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                    
                <?php
                } else {
                    echo '<div class="alert alert-info"><em>No users found';
                    if(!empty($search)) {
                        echo ' matching "' . htmlspecialchars($search) . '"';
                    }
                    echo '.</em></div>';
                }
                ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>