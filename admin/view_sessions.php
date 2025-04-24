<?php
require_once "../auth/auth_functions.php";
require_once "../database/db_connection.php";

requireAdmin();

$username_filter = isset($_GET['username']) ? trim($_GET['username']) : '';
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';
$min_generations = isset($_GET['min_generations']) ? intval($_GET['min_generations']) : '';
$max_generations = isset($_GET['max_generations']) ? intval($_GET['max_generations']) : '';

if(isset($_GET['reset'])) {
    header("Location: view_sessions.php");
    exit();
}

$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$records_per_page = 15;
$offset = ($page - 1) * $records_per_page;

$sql_conditions = [];
$params = [];
$param_types = "";

if(!empty($username_filter)) {
    $sql_conditions[] = "u.username LIKE ?";
    $params[] = "%$username_filter%";
    $param_types .= "s";
}

if(!empty($date_from)) {
    $sql_conditions[] = "DATE(g.start_time) >= ?";
    $params[] = $date_from;
    $param_types .= "s";
}
if(!empty($date_to)) {
    $sql_conditions[] = "DATE(g.start_time) <= ?";
    $params[] = $date_to;
    $param_types .= "s";
}

if($min_generations !== '') {
    $sql_conditions[] = "g.generations >= ?";
    $params[] = $min_generations;
    $param_types .= "i";
}
if($max_generations !== '') {
    $sql_conditions[] = "g.generations <= ?";
    $params[] = $max_generations;
    $param_types .= "i";
}

$where_clause = "";
if(!empty($sql_conditions)) {
    $where_clause = " WHERE " . implode(" AND ", $sql_conditions);
}

$count_sql = "SELECT COUNT(*) as total 
              FROM game_sessions g
              JOIN users u ON g.user_id = u.id" . $where_clause;

$count_stmt = mysqli_prepare($conn, $count_sql);
if(!empty($params)) {
    mysqli_stmt_bind_param($count_stmt, $param_types, ...$params);
}
mysqli_stmt_execute($count_stmt);
$count_result = mysqli_stmt_get_result($count_stmt);
$total_records = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_records / $records_per_page);

$sql = "SELECT g.session_id, u.username, g.start_time, g.end_time, 
        g.generations, 
        TIMESTAMPDIFF(MINUTE, g.start_time, COALESCE(g.end_time, NOW())) AS duration
        FROM game_sessions g
        JOIN users u ON g.user_id = u.id" . 
        $where_clause . 
        " ORDER BY g.start_time DESC
        LIMIT ?, ?";

$params[] = $offset;
$params[] = $records_per_page;
$param_types .= "ii";

$stmt = mysqli_prepare($conn, $sql);
if(!empty($params)) {
    mysqli_stmt_bind_param($stmt, $param_types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Game Sessions - Conway's Game of Life</title> 
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
        <h2>Game Sessions</h2>        
        <div class="card">
            <div class="card-header header-primary">Filter Sessions</div>
            <div class="card-body">
                <form action="view_sessions.php" method="GET">
                    <div style="display: flex; flex-wrap: wrap; margin: 0 -10px;">
                        <div style="flex: 1; min-width: 200px; padding: 0 10px; margin-bottom: 15px;">
                            <label for="username">Username:</label>
                            <input type="text" name="username" id="username" class="form-control" value="<?php echo htmlspecialchars($username_filter); ?>" placeholder="Enter username">
                        </div>
                        
                        <div style="flex: l; min-width: 200px; padding: 0 10px; margin-bottom: 15px;">
                            <label for="date_from">Date From:</label>
                            <input type="date" name="date_from" id="date_from" class="form-control" value="<?php echo $date_from; ?>">
                        </div>
                        
                        <div style="flex: 1; min-width: 200px; padding: 0 10px; margin-bottom: 15px;">
                            <label for="date_to">Date To:</label>
                            <input type="date" name="date_to" id="date_to" class="form-control" value="<?php echo $date_to; ?>">
                        </div>
                    </div>
                    
                    <div style="display: flex; flex-wrap: wrap; margin: 0 -10px;">
                        <div style="flex: 1; min-width: 200px; padding: 0 10px; margin-bottom: 15px;">
                            <label for="min_generations">Min Generations:</label>
                            <input type="number" name="min_generations" id="min_generations" class="form-control" value="<?php echo $min_generations; ?>" min="0">
                        </div>
                        
                        <div style="flex: 1; min-width: 200px; padding: 0 10px; margin-bottom: 15px;">
                            <label for="max_generations">Max Generations:</label>
                            <input type="number" name="max_generations" id="max_generations" class="form-control" value="<?php echo $max_generations; ?>" min="0">
                        </div>
                    </div>
                    
                    <div style="text-align: center; margin-top: 10px;">
                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                        <button type="submit" name="reset" class="btn btn-secondary" style="margin-left: 10px;">Reset Filters</button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header header-success" style="display: flex; justify-content: space-between; align-items: center;">
                <span>Sessions List</span>
                <span>Total: <?php echo $total_records; ?> sessions</span>
            </div>
            <div class="card-body">
                <div style="overflow-x: auto;">
                    <?php
                    if(mysqli_num_rows($result) > 0){
                    ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Session ID</th>
                                <th>Username</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                                <th>Duration (mins)</th>
                                <th>Generations</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            while($row = mysqli_fetch_array($result)){
                                echo "<tr>";
                                echo "<td>" . $row['session_id'] . "</td>";
                                echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                                echo "<td>" . $row['start_time'] . "</td>";
                                
                                if($row['end_time']) {
                                    echo "<td>" . $row['end_time'] . "</td>";
                                } else {
                                    echo "<td class='status-active'>● Active</td>";
                                }
                                
                                echo "<td>" . $row['duration'] . "</td>";
                                echo "<td>" . $row['generations'] . "</td>";
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
                                <a href="?page=1<?php 
                                    echo !empty($username_filter) ? '&username='.urlencode($username_filter) : ''; 
                                    echo !empty($date_from) ? '&date_from='.$date_from : '';
                                    echo !empty($date_to) ? '&date_to='.$date_to : '';
                                    echo $min_generations !== '' ? '&min_generations='.$min_generations : '';
                                    echo $max_generations !== '' ? '&max_generations='.$max_generations : '';
                                ?>">First</a>
                            </li>
                            <li>
                                <a href="?page=<?php echo $page-1; ?><?php 
                                    echo !empty($username_filter) ? '&username='.urlencode($username_filter) : ''; 
                                    echo !empty($date_from) ? '&date_from='.$date_from : '';
                                    echo !empty($date_to) ? '&date_to='.$date_to : '';
                                    echo $min_generations !== '' ? '&min_generations='.$min_generations : '';
                                    echo $max_generations !== '' ? '&max_generations='.$max_generations : '';
                                ?>">Previous</a>
                            </li>
                            <?php endif; ?>
                            
                            <?php 
                            $range = 2; 
                            $start_page = max(1, $page - $range);
                            $end_page = min($total_pages, $page + $range);
                            
                            for($i = $start_page; $i <= $end_page; $i++): 
                            ?>
                            <li <?php echo $i == $page ? 'class="active"' : ''; ?>>
                                <a href="?page=<?php echo $i; ?><?php 
                                    echo !empty($username_filter) ? '&username='.urlencode($username_filter) : ''; 
                                    echo !empty($date_from) ? '&date_from='.$date_from : '';
                                    echo !empty($date_to) ? '&date_to='.$date_to : '';
                                    echo $min_generations !== '' ? '&min_generations='.$min_generations : '';
                                    echo $max_generations !== '' ? '&max_generations='.$max_generations : '';
                                ?>"><?php echo $i; ?></a>
                            </li>
                            <?php endfor; ?>
                            
                            <?php if($page < $total_pages): ?>
                            <li>
                                <a href="?page=<?php echo $page+1; ?><?php 
                                    echo !empty($username_filter) ? '&username='.urlencode($username_filter) : ''; 
                                    echo !empty($date_from) ? '&date_from='.$date_from : '';
                                    echo !empty($date_to) ? '&date_to='.$date_to : '';
                                    echo $min_generations !== '' ? '&min_generations='.$min_generations : '';
                                    echo $max_generations !== '' ? '&max_generations='.$max_generations : '';
                                ?>">Next</a>
                            </li>
                            <li>
                                <a href="?page=<?php echo $total_pages; ?><?php 
                                    echo !empty($username_filter) ? '&username='.urlencode($username_filter) : ''; 
                                    echo !empty($date_from) ? '&date_from='.$date_from : '';
                                    echo !empty($date_to) ? '&date_to='.$date_to : '';
                                    echo $min_generations !== '' ? '&min_generations='.$min_generations : '';
                                    echo $max_generations !== '' ? '&max_generations='.$max_generations : '';
                                ?>">Last</a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                    
                    <?php
                    } else {
                        echo '<div style="padding: 15px; background-color: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; border-radius: 4px;"><em>No game sessions found';
                        if(!empty($username_filter) || !empty($date_from) || !empty($date_to) || 
                          $min_generations !== '' || $max_generations !== '') {
                            echo ' matching your filter criteria';
                        }
                        echo '.</em></div>';
                    }
                    ?>
                </div>
            </div>
        </div>
        
        <?php if(mysqli_num_rows($result) > 0): ?>
        <div class="card">
            <div class="card-header header-info">Statistics for Filtered Sessions</div>
            <div class="card-body">
                <?php
                $stats_sql = "SELECT 
                    COUNT(*) as total_sessions,
                    AVG(TIMESTAMPDIFF(MINUTE, start_time, COALESCE(end_time, NOW()))) as avg_duration,
                    AVG(generations) as avg_generations,
                    MAX(generations) as max_generations,
                    COUNT(CASE WHEN end_time IS NULL THEN 1 END) as active_sessions
                    FROM game_sessions g
                    JOIN users u ON g.user_id = u.id" . $where_clause;
                
                $stats_stmt = mysqli_prepare($conn, $stats_sql);
                if(!empty($params)) {
                    $stats_params = array_slice($params, 0, -2);
                    $stats_param_types = substr($param_types, 0, -2);
                    
                    if(!empty($stats_params)) {
                        mysqli_stmt_bind_param($stats_stmt, $stats_param_types, ...$stats_params);
                    }
                }
                mysqli_stmt_execute($stats_stmt);
                $stats_result = mysqli_stmt_get_result($stats_stmt);
                $stats_data = mysqli_fetch_assoc($stats_result);
                ?>
                
                <div style="display: flex; flex-wrap: wrap; margin: 0 -10px;">
                    <div style="flex: 1; min-width: 150px; margin: 0 10px 15px 10px;">
                        <div style="background-color: #f8f9fa; padding: 15px; border-radius: 4px; text-align: center;">
                            <h5>Total Sessions</h5>
                            <h3><?php echo $stats_data['total_sessions']; ?></h3>
                        </div>
                    </div>
                    <div style="flex: 1; min-width: 150px; margin: 0 10px 15px 10px;">
                        <div style="background-color: #f8f9fa; padding: 15px; border-radius: 4px; text-align: center;">
                            <h5>Avg. Duration</h5>
                            <h3><?php echo round($stats_data['avg_duration']); ?> mins</h3>
                        </div>
                    </div>
                    <div style="flex: 1; min-width: 150px; margin: 0 10px 15px 10px;">
                        <div style="background-color: #f8f9fa; padding: 15px; border-radius: 4px; text-align: center;">
                            <h5>Avg. Generations</h5>
                            <h3><?php echo round($stats_data['avg_generations']); ?></h3>
                        </div>
                    </div>
                    <div style="flex: 1; min-width: 150px; margin: 0 10px 15px 10px;">
                        <div style="background-color: #f8f9fa; padding: 15px; border-radius: 4px; text-align: center;">
                            <h5>Max Generations</h5>
                            <h3><?php echo $stats_data['max_generations']; ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>