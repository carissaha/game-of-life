<?php
function basicAuth() {
    if (!isset($_SERVER['PHP_AUTH_USER'])) {
        header('WWW-Authenticate: Basic realm="Conway\'s Game of Life"');
        header('HTTP/1.0 401 Unauthorized');
        echo '<div style="text-align:center; padding:30px; font-family:Arial;">
              <h1>Authentication Required</h1>
              <p>Please provide valid credentials to continue.</p>
              <a href="../index.php">Return to Home</a>
              </div>';
        exit;
    } else {
        require_once "../database/db_connection.php";
        
        $username = $_SERVER['PHP_AUTH_USER'];
        $password = $_SERVER['PHP_AUTH_PW'];
        
        $sql = "SELECT id, username, password FROM users WHERE username = ?";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) == 1) {
            $row = mysqli_fetch_assoc($result);
            if (password_verify($password, $row['password'])) {
                return true;
            }
        }
        
        header('WWW-Authenticate: Basic realm="Conway\'s Game of Life"');
        header('HTTP/1.0 401 Unauthorized');
        echo '<div style="text-align:center; padding:30px; font-family:Arial;">
              <h1>Invalid Credentials</h1>
              <p>The username or password you provided is incorrect.</p>
              <a href="../index.php">Return to Home</a>
              </div>';
        exit;
    }
}

function digestAuth(): bool {
    $realm = 'Conway\'s Game of Life';
    $users = array(
        'admin' => 'admin123',
        'user' => 'password123'
    );

    if (empty($_SERVER['PHP_AUTH_DIGEST'])) {
        header('HTTP/1.1 401 Unauthorized');
        header('WWW-Authenticate: Digest realm="'.$realm.'",qop="auth",nonce="'.uniqid().'",opaque="'.md5($realm).'"');
        echo '<div style="text-align:center; padding:30px; font-family:Arial;">
              <h1>Digest Authentication Required</h1>
              <p>Please provide valid credentials to continue.</p>
              <a href="../index.php">Return to Home</a>
              </div>';
        die();
    }

    if (!($data = http_digest_parse($_SERVER['PHP_AUTH_DIGEST'])) || !isset($users[$data['username']]))
        die('Wrong Credentials!');

    $A1 = md5($data['username'] . ':' . $realm . ':' . $users[$data['username']]);
    $A2 = md5($_SERVER['REQUEST_METHOD'] . ':' . $data['uri']);
    $valid_response = md5($A1 . ':' . $data['nonce'] . ':' . $data['nc'] . ':' . $data['cnonce'] . ':' . $data['qop'] . ':' . $A2);

    if ($data['response'] != $valid_response) {
        echo '<div style="text-align:center; padding:30px; font-family:Arial;">
              <h1>Invalid Digest Credentials</h1>
              <p>The digest authentication credentials provided are incorrect.</p>
              <a href="../index.php">Return to Home</a>
              </div>';
        die();
    }

    return true;
}

function http_digest_parse($txt) {
    $needed_parts = array('nonce'=>1, 'nc'=>1, 'cnonce'=>1, 'qop'=>1, 'username'=>1, 'uri'=>1, 'response'=>1);
    $data = array();
    $keys = implode('|', array_keys($needed_parts));

    preg_match_all('@(' . $keys . ')=(?:([\'"])([^\2]+?)\2|([^\s,]+))@', $txt, $matches, PREG_SET_ORDER);

    foreach ($matches as $m) {
        $data[$m[1]] = $m[3] ? $m[3] : $m[4];
        unset($needed_parts[$m[1]]);
    }

    return $needed_parts ? false : $data;
}

function isLoggedIn() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    return isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true;
}

function isAdmin() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    return isset($_SESSION["admin"]) && $_SESSION["admin"] === true;
}

function requireLogin() {
    if (!isLoggedIn()) {
        echo '<div style="text-align:center; padding:30px; font-family:Arial;">
              <h1>Login Required</h1>
              <p>You need to be logged in to access this page.</p>
              <a href="auth/login.php">Login Now</a>
              </div>';
        echo '<script>setTimeout(function() { window.location.href = "auth/login.php"; }, 2000);</script>';
        exit;
    }
}

function requireAdmin() {
    if (!isLoggedIn() || !isAdmin()) {
        echo '<div style="text-align:center; padding:30px; font-family:Arial;">
              <h1>Admin Access Required</h1>
              <p>You need administrator privileges to access this page.</p>
              <a href="auth/login.php">Login as Admin</a>
              </div>';
        echo '<script>setTimeout(function() { window.location.href = "auth/login.php"; }, 2000);</script>';
        exit;
    }
}

function clientCertAuth() {
    if (!isset($_SERVER['SSL_CLIENT_CERT']) || empty($_SERVER['SSL_CLIENT_CERT'])) {
        header('HTTP/1.0 403 Forbidden');
        echo '<div style="text-align:center; padding:30px; font-family:Arial;">
              <h1>Certificate Required</h1>
              <p>A valid client certificate is required for access.</p>
              <a href="../index.php">Return to Home</a>
              </div>';
        exit;
    }    
    $cert_details = openssl_x509_parse($_SERVER['SSL_CLIENT_CERT']);
    
    if (!$cert_details || empty($cert_details['subject']['CN'])) {
        header('HTTP/1.0 403 Forbidden');
        echo '<div style="text-align:center; padding:30px; font-family:Arial;">
              <h1>Invalid Certificate</h1>
              <p>The provided certificate is not valid.</p>
              <a href="../index.php">Return to Home</a>
              </div>';
        exit;
    }
        $cert_cn = $cert_details['subject']['CN'];
    
    require_once "../database/db_connection.php";
        $sql = "SELECT user_id FROM valid_certificates WHERE certificate_cn = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $cert_cn);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        
        session_start();
        $_SESSION["loggedin"] = true;
        $_SESSION["id"] = $row['user_id'];
        
        $sql = "SELECT username FROM users WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $row['user_id']);
        mysqli_stmt_execute($stmt);
        $user_result = mysqli_stmt_get_result($stmt);
        $user_row = mysqli_fetch_assoc($user_result);
        
        $_SESSION["username"] = $user_row['username'];
        
        return true;
    }
    
    header('HTTP/1.0 403 Forbidden');
    echo '<div style="text-align:center; padding:30px; font-family:Arial;">
          <h1>Certificate Not Recognized</h1>
          <p>The certificate is not recognized by our system.</p>
          <a href="../index.php">Return to Home</a>
          </div>';
    exit;
}

function mutualAuth() {
    clientCertAuth();    
    return true;
}

function logAuthAttempt($username, $success, $ip, $auth_type) {
    global $conn;
    
    $create_table_sql = "CREATE TABLE IF NOT EXISTS auth_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(255) NOT NULL,
        ip_address VARCHAR(45) NOT NULL,
        auth_type VARCHAR(20) NOT NULL,
        success BOOLEAN NOT NULL,
        timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    mysqli_query($conn, $create_table_sql);
    
    $sql = "INSERT INTO auth_logs (username, ip_address, auth_type, success) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sssi", $username, $ip, $auth_type, $success);
    mysqli_stmt_execute($stmt);
}
?>