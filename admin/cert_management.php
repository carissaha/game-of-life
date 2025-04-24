<?php
require_once "database/db_connection.php";

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["admin"]) || $_SESSION["admin"] !== true) {
    header("location: ../auth/login.php");
    exit;
}

$sql = "CREATE TABLE IF NOT EXISTS valid_certificates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    certificate_cn VARCHAR(255) NOT NULL,
    issued_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    revoked BOOLEAN DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id)
)";

mysqli_query($conn, $sql);

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["generate"])) {
    $user_id = intval($_POST["user_id"]);
    
    $sql = "SELECT username FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        $username = $user["username"];
        
        $cn = "CN=" . $username . "." . time() . ".example.com";
        
        $sql = "INSERT INTO valid_certificates (user_id, certificate_cn) VALUES (?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "is", $user_id, $cn);
        
        if (mysqli_stmt_execute($stmt)) {
            $success_message = "Certificate created for user " . htmlspecialchars($username);
        } else {
            $error_message = "Error creating certificate: " . mysqli_error($conn);
        }
    } else {
        $error_message = "User not found.";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["revoke"])) {
    $cert_id = intval($_POST["cert_id"]);
    
    $sql = "UPDATE valid_certificates SET revoked = 1 WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $cert_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $success_message = "Certificate revoked successfully.";
    } else {
        $error_message = "Error revoking certificate: " . mysqli_error($conn);
    }
}

$sql = "SELECT c.id, c.certificate_cn, c.issued_date, c.revoked, u.username, u.id as user_id
        FROM valid_certificates c
        JOIN users u ON c.user_id = u.id
        ORDER BY c.issued_date DESC";
$result = mysqli_query($conn, $sql);

$sql = "SELECT id, username FROM users ORDER BY username";
$users_result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate Management - Conway's Game of Life</title>
    <link rel="stylesheet" href="admin_styles.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <a class="navbar-brand" href="#">Admin Dashboard</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="manage_users.php">Manage Users</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="view_sessions.php">Game Sessions</a>
                </li>
                <li class="nav-item active">
                    <a class="nav-link" href="cert_management.php">Certificates</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../game.php">Play Game</a>
                </li>
            </ul>
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <a class="nav-link" href="../auth/logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </nav>
    
    <div class="container-fluid mt-4">
        <h2>Certificate Management</h2>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4>Generate Certificate</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="user_id">Select User:</label>
                                <select class="form-control" id="user_id" name="user_id" required>
                                    <option value="">-- Select User --</option>
                                    <?php while ($user = mysqli_fetch_assoc($users_result)): ?>
                                        <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['username']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <button type="submit" name="generate" class="btn btn-primary">Generate Certificate</button>
                        </form>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-header bg-info text-white">
                        <h4>About Client Certificates</h4>
                    </div>
                    <div class="card-body">
                        <p>Client certificates provide a way for users to authenticate without using passwords. The browser presents the certificate during connection, and the server verifies it.</p>
                        <p>This implementation is simplified for demonstration purposes. In a production environment, you would:</p>
                        <ul>
                            <li>Use a proper Certificate Authority (CA)</li>
                            <li>Implement certificate revocation lists (CRL)</li>
                            <li>Configure your web server for certificate authentication</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h4>Issued Certificates</h4>
                    </div>
                    <div class="card-body">
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>User</th>
                                            <th>Certificate CN</th>
                                            <th>Issued Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                            <tr>
                                                <td><?php echo $row['id']; ?></td>
                                                <td><?php echo htmlspecialchars($row['username']); ?></td>
                                                <td><?php echo htmlspecialchars($row['certificate_cn']); ?></td>
                                                <td><?php echo $row['issued_date']; ?></td>
                                                <td>
                                                    <?php if ($row['revoked']): ?>
                                                        <span class="badge badge-danger">Revoked</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-success">Active</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (!$row['revoked']): ?>
                                                        <form method="POST" action="" style="display: inline;">
                                                            <input type="hidden" name="cert_id" value="<?php echo $row['id']; ?>">
                                                            <button type="submit" name="revoke" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to revoke this certificate?')">
                                                                Revoke
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">No certificates have been issued yet.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>