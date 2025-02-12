<?php
include_once('../../../include/php/connect.php');

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Fetch staff options from the database
$staffOptions = [];
$userbranch = $_SESSION['branch'];

$query = "SELECT * FROM staffs_masters WHERE department='dept_fab' AND branch LIKE '%$userbranch%' AND status LIKE 'active' ORDER BY staff_name ASC";
$result = mysqli_query($conn, $query);
while ($row = mysqli_fetch_assoc($result)) {
$staffOptions[] = ['name' => $row['staff_name']];
}

// Encode staff options as JSON and echo the response
echo json_encode($staffOptions);
?>
