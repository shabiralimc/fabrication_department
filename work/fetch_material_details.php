<?php
include_once('../../../include/php/connect.php');
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if the material name is provided
if (isset($_POST['material_name'])) {
    $selectedMaterialName = $_POST['material_name'];
    $branchOfUser = $_SESSION['branch'];

    // Query the fab_material_master table for the selected material
    $query = "SELECT mm.mat_measu_unit, mi.mat_sales_price 
          FROM fab_material_master mm 
          LEFT JOIN fab_mat_inventory mi ON mm.mat_name = mi.mat_name
          WHERE mm.mat_name = ? AND mi.mat_godown = ?";
    $stmt = mysqli_prepare($conn, $query);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ss", $selectedMaterialName, $branchOfUser);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        // Check if a row is returned
        if ($row = mysqli_fetch_assoc($result)) {   
            // If material found, send the data back as JSON
            echo json_encode(array(
                'success' => true,
                'measuring_unit' => $row['mat_measu_unit'],
                'per_cost' => $row['mat_sales_price']
            ));
        } else {
            // If material not found, send error message
            echo json_encode(array(
                'success' => false,
                'message' => 'Material not found'
            ));
        }
    } else {
        // If query execution fails, send error message
        echo json_encode(array(
            'success' => false,
            'message' => 'Query execution failed: ' . mysqli_error($conn)
        ));
    }
} else {
    // If material name not provided, send error message
    echo json_encode(array(
        'success' => false,
        'message' => 'Material name not provided'
    ));
}
?>