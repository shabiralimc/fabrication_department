<?php
// Include the database connection configuration
include_once("../../../../include/php/connect.php");
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

if (isset($_POST['materialName'])) {
    $materialName = $_POST['materialName']; // Use materialName from the request

    $query = "SELECT materialUnit, alternativeUnitvalue, alternativeUnit 
              FROM material_master_creates 
              WHERE materialName = ?"; // Use materialName for the query
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $materialName); // Bind the materialName parameter
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        echo json_encode($data); // Return the fetched data
    } else {
        echo json_encode(['materialUnit' => '', 'alternativeUnitvalue' => '', 'alternativeUnit' => '']); // Return empty values if no match
    }

    $stmt->close();
} else {
    echo json_encode(['materialUnit' => '', 'alternativeUnitvalue' => '', 'alternativeUnit' => '']); // Return empty if no materialName was sent
}
?>
