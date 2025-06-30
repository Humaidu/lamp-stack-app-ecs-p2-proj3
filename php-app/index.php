<?php
require_once('config.php');

$log_dir = __DIR__ . '/logs';

if (!is_dir($log_dir)) {
    mkdir($log_dir, 0777, true);
}

$log_file_visits = $log_dir . '/php-app-visits.log';
$log_file_errors = $log_dir . '/php-app-errors.log';

$conn = getDBConnection();
$ip = $_SERVER['REMOTE_ADDR'];

// Create log files if they don't exist (optional fail-safe)

if (!file_exists($log_file_visits)) {
  touch($log_file_visits);
  chmod($log_file_visits, 0666); // Read/write for all
}

if (!file_exists($log_file_errors)) {
  touch($log_file_errors);
  chmod($log_file_errors, 0666);
}

try {
    // Insert visitor IP into DB
    $stmt = $conn->prepare("INSERT INTO visitors (ip_address) VALUES (?)");
    $stmt->bind_param("s", $ip);
    $stmt->execute();
    $stmt->close();

    // Log to visit file
    error_log("[$ip] Visit at " . date("Y-m-d H:i:s") . "\n", 3, $log_file_visits);
} catch (Exception $e) {
    // Log to error file
    error_log("[$ip] DB Error: " . $e->getMessage() . "\n", 3, $log_file_errors);
}

// Get recent visitors
$result = $conn->query("SELECT * FROM visitors ORDER BY visit_time DESC LIMIT 10");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>LAMP Stack Demo - Visitors</title>
  <link rel="stylesheet" href="styles.css" />
</head>
<body>

<header>
  <h1>LAMP Stack Visitor Tracker - ECS</h1>
</header>

<div class="container">
  <h2>Recent Visitors</h2>
  <?php if ($result && $result->num_rows > 0): ?>
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>IP Address</th>
          <th>Visit Time</th>
        </tr>
      </thead>
      <tbody>
        <?php while($row = $result->fetch_assoc()): ?>
        <tr>
          <td><?= htmlspecialchars($row["id"]) ?></td>
          <td><?= htmlspecialchars($row["ip_address"]) ?></td>
          <td><?= htmlspecialchars($row["visit_time"]) ?></td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  <?php else: ?>
    <p>No visitors recorded yet.</p>
  <?php endif; ?>
</div>

<footer>
  &copy; <?= date("Y") ?> LAMP Stack Demo
</footer>

</body>
</html>

<?php $conn->close(); ?>
