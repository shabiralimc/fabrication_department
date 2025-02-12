<?php
include_once('../../../include/php/connect.php');
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$branchname = $_SESSION['branch'];

// Fetch materials from fab_mat_inventory where mat_godown is equal to session branch
$sql = "SELECT mat_name FROM fab_mat_inventory WHERE mat_godown LIKE '%$branchname%' ORDER BY mat_name ASC";

$result = $conn->query($sql);

$materials = array();

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $materials[] = $row['mat_name'];
    }
}

// Output materials as JSON
echo json_encode($materials);

?>
