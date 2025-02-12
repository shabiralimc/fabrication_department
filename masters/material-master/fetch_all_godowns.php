<?php
include_once('../../../../include/php/connect.php');
ini_set('session.gc_maxlifetime', 43200);

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);


if (!isset($_SESSION['user']) || $_SESSION['role'] !== '4') {
    echo "<script>alert('You are not authorised to view the URL - Please login using your username and password before accessing URL...'); window.location = '$app_url';</script>";
    exit();
}

// Calculate the remaining time
$sessionStart = $_SESSION['session_start'];
$sessionLifetime = $_SESSION['session_lifetime'];
$currentTime = time();
$remainingTime = ($sessionStart + $sessionLifetime) - $currentTime;

// Query to fetch all godowns
$sql = "SELECT godownName FROM master_godown";
$result = mysqli_query($conn, $sql);

// Array to hold godowns
$godowns = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $godowns[] = $row['godownName'];
    }
}

// Return the godowns as JSON
echo json_encode(['success' => true, 'godowns' =>$godowns]);
?>