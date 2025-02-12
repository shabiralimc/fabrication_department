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


if (isset($_GET['id'])) {
// Assuming you have a database connection in $conn
$id = $_GET['id'];
$query = "SELECT * FROM material_master_creates WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if ($data) {
    echo json_encode([
        "id" => $data['id'],
        "materialID" => $data['materialID'],
        "materialName" => $data['materialName'],
        "materialUnit" => $data['materialUnit'],
        "material_alias" => $data['material_alias'],
        "alternativeUnit" => $data['alternativeUnit'],
        "alternativeUnitvalue" => $data['alternativeUnitvalue'],
        "materialCategory" => $data['materialCategory'],
        "materialBrand" => $data['materialBrand'],
        "negativeStockCheckbox" => $data['negativeStockCheckbox'],
        "negativeStock" => $data['negativeStock'],
        "warrantyCheckbox" => $data['warrantyCheckbox'],
        "warrantyYear" => $data['warrantyYear'],
        // "reorderLevel" => $data['reorderLevel'],
        // "openingStock" => $data['openingStock'],
        // "from_os" => $data['from_os'],

        "materialAlias" => $data['material_alias'] // Make sure this field exists
    ]);
} else {
    echo json_encode(["error" => "Material not found"]);
}
    $stmt->close();
} else {
    echo json_encode(['error' => 'Invalid ID']);
}
?>
