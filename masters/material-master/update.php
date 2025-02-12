<?php
include_once('../../../../include/php/connect.php');
ini_set('session.gc_maxlifetime', 43200);
session_start();

// Disable error output to prevent corrupting JSON
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_SESSION['role'] !== '4') {
    echo json_encode(["success" => false, "message" => "Unauthorized access"]);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    echo json_encode(["success" => false, "message" => "Invalid request method"]);
    exit();
}

try {
    // Input validation
    $required_fields = ['id', 'material-ID', 'material-Name'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    $id = intval($_POST['id']);

    // Decode openingstock_ary if it's a JSON string
    $openingstock_ary = $_POST['openingstock_ary'] ?? '[]';
    if (is_string($openingstock_ary)) {
        $openingstock_ary = json_decode($openingstock_ary, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid openingstock_ary format");
        }
    }

    // Get checked godowns only (ignore unchecked ones)
    $checkedGodowns = !empty($_POST['godownUpdateCheckbox']) ? $_POST['godownUpdateCheckbox'] : '';

    // Build update query
    $sql = "UPDATE material_master_creates SET 
            materialID = ?,
            materialName = ?,
            materialUnit = ?,
            material_alias = ?,
            alternativeUnit = ?,
            alternativeUnitvalue = ?,
            materialCategory = ?,
            materialBrand = ?,
            godown = ?,  -- Now updating godown column correctly
            negativeStockCheckbox = ?,
            negativeStock = ?,
            warrantyCheckbox = ?,
            warrantyYear = ?,
            openingstock_ary = ?
            WHERE id = ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }

    // Bind parameters
    $stmt->bind_param(
        'sssssssssiisssi',
        $_POST['material-ID'],
        $_POST['material-Name'],
        $_POST['material-Unit'],
        $_POST['material-alias'],
        $_POST['alternative-Unit'],
        $_POST['alternativeUnit-value'],
        $_POST['material-Category'],
        $_POST['material-Brand'],
        $checkedGodowns,  // Only checked godowns are saved
        $_POST['negativeStockCheckboxAll'],
        $_POST['negative-Stock'],
        $_POST['warrantyCheckboxAll'],
        $_POST['warrantyYearAll'],
        json_encode($openingstock_ary), 
        $id
    );

    if (!$stmt->execute()) {
        throw new Exception("Update failed: " . $stmt->error);
    }

    echo json_encode(["success" => true, "message" => "Material updated successfully"]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
} finally {
    if (isset($stmt)) $stmt->close();
}
?>
