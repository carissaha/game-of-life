<?php
$host = "localhost";
$username = "tnguyen437"; 
$password = "tnguyen437"; 
$database = "tnguyen437";

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
function executeQuery($conn, $sql, $params = []) {
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!empty($params)) {
        $types = "";
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= "i";
            } elseif (is_double($param)) {
                $types .= "d";
            } else {
                $types .= "s";
            }
        }
        
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return $result;
}
?>