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

if (isset($_POST['supplier_name'])) {
    $supplier_name = $_POST['supplier_name'];

    $query = "SELECT supplier_address, supplier_gst, supplier_pan, supplier_cont, supplier_cp, supplier_terms FROM master_supplier WHERE supplier_name = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $supplier_name);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        echo json_encode($data);
    } else {
        echo json_encode([
            'supplier_address' => '',
            'supplier_gst' => '',
            'supplier_pan' => '',
            'supplier_cont' => '',
            'supplier_cp' => '',
            'supplier_terms' => ''
        ]);
    }

    $stmt->close();
}
?>
