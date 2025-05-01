<?php
$host = "localhost";
$username = "tnguyen437"; 
$password = "tnguyen437"; 
$database = "tnguyen437";

$conn = mysqli_connect($host, $username, $password);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$sql = "CREATE DATABASE IF NOT EXISTS $database";
if (mysqli_query($conn, $sql)) {
    echo "Database created successfully or already exists<br>";
} else {
    echo "Error creating database: " . mysqli_error($conn) . "<br>";
}

mysqli_select_db($conn, $database);

$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $sql)) {
    echo "Table 'users' created successfully or already exists<br>";
} else {
    echo "Error creating table 'users': " . mysqli_error($conn) . "<br>";
}

$sql = "CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
)";

if (mysqli_query($conn, $sql)) {
    echo "Table 'admin_users' created successfully or already exists<br>";
} else {
    echo "Error creating table 'admin_users': " . mysqli_error($conn) . "<br>";
}

$sql = "CREATE TABLE IF NOT EXISTS game_sessions (
    session_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    start_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    end_time TIMESTAMP NULL,
    generations INT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id)
)";

if (mysqli_query($conn, $sql)) {
    echo "Table 'game_sessions' created successfully or already exists<br>";
} else {
    echo "Error creating table 'game_sessions': " . mysqli_error($conn) . "<br>";
}
$sql = "CREATE TABLE IF NOT EXISTS game_sessions (
    session_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    start_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    end_time TIMESTAMP NULL,
    last_activity TIMESTAMP NULL,
    generations INT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id)
)";
$sql = "CREATE TABLE IF NOT EXISTS patterns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    pattern_data TEXT NOT NULL,
    category VARCHAR(50) NOT NULL,
    user_id INT,
    FOREIGN KEY (user_id) REFERENCES users(id)
)";

if (mysqli_query($conn, $sql)) {
    echo "Table 'patterns' created successfully or already exists<br>";
} else {
    echo "Error creating table 'patterns': " . mysqli_error($conn) . "<br>";
}

$username = "admin";
$email = "admin@example.com";
$password = password_hash("admin123", PASSWORD_DEFAULT);

$check_sql = "SELECT id FROM users WHERE username = '$username'";
$result = mysqli_query($conn, $check_sql);

if (mysqli_num_rows($result) == 0) {
    $sql = "INSERT INTO users (username, email, password) VALUES ('$username', '$email', '$password')";
    
    if (mysqli_query($conn, $sql)) {
        $user_id = mysqli_insert_id($conn);
        echo "Admin user created successfully with ID: $user_id<br>";
        
        $sql = "INSERT INTO admin_users (user_id) VALUES ($user_id)";
        
        if (mysqli_query($conn, $sql)) {
            echo "User added to admin_users table successfully<br>";
        } else {
            echo "Error creating admin user: " . mysqli_error($conn) . "<br>";
        }
    } else {
        echo "Error creating user: " . mysqli_error($conn) . "<br>";
    }
} else {
    echo "Admin user already exists<br>";
}

echo "All tables created successfully. You can now use the system!";
mysqli_close($conn);
?>