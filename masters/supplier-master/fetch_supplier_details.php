<?php
// Include the database connection configuration
include_once("../../../../include/php/connect.php");

// Initialize $rows variable
$rows = array();

// Check if supplier_id is provided in the request
if (isset($_GET['supplier_id'])) {
    $supplierId = $_GET['supplier_id'];

    // Prepare and execute SQL query to fetch supplier details based on supplier_id
    $sql = "SELECT * FROM master_supplier WHERE supplier_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $supplierId);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if any rows are returned
    if ($result->num_rows > 0) {
        // Fetch the supplier details
        $rows = $result->fetch_assoc();
        
        // Return the supplier details as JSON
        header('Content-Type: application/json');
        echo json_encode($rows);
    } else {
        // No supplier found with the given supplier_id
        echo json_encode(array('error' => 'Supplier not found'));
    }
} else {
    // No supplier_id provided in the request
    echo json_encode(array('error' => 'Supplier ID not provided'));
}

// Close the database connection
$stmt->close();
?>
