<?php
include_once('../../../../include/php/connect.php');
ini_set('session.gc_maxlifetime', 43200);

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user']) || $_SESSION['role'] !== '4') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Validate material ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Material ID not provided']);
    exit();
}

$materialId = $_GET['id'];

// Fetch godowns and opening stock details
$sql = "SELECT godown, openingstock_ary FROM material_master_creates WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $materialId);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // Fetch assigned godowns
    $godowns = !empty($row['godown']) ? explode(',', $row['godown']) : [];

    // Decode opening stock array (if exists)
    $openingStockArray = !empty($row['openingstock_ary']) ? json_decode($row['openingstock_ary'], true) : [];

    echo json_encode([
        'success' => true,
        'godowns' => $godowns,
        'openingstock_ary' => $openingStockArray
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'No data found for the given ID']);
}

$stmt->close();
?>