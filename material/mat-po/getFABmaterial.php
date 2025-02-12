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
    $materialName = $_POST['materialName'];

    $query = "SELECT materialCategory, materialUnit, alternativeUnit,materialBrand, alternativeUnitvalue FROM material_master_creates WHERE materialName = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $materialName);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        echo json_encode($data);
    } else {
        echo json_encode(['materialCategory' => '', 'materialUnit' => '', 'alternativeUnit' => '', 'materialBrand' => '', 'alternativeUnitvalue' => '']);
    }

    $stmt->close();
}
?>
