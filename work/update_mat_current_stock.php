<?php
// Include your database connection file
include_once('../../../include/php/connect.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the POST data for the deleted row
    $materialName = $_POST["materialName"];
    $quantity = $_POST["quantity"];
    $godown = $_POST["fama_branch"];

    // Fetch the current stock for the material
    $fetch_stock_sql = "SELECT mat_current_stock FROM fab_mat_inventory WHERE mat_name=? AND mat_godown LIKE '%$godown%'";
    $stmt_fetch_stock = $conn->prepare($fetch_stock_sql);
    $stmt_fetch_stock->bind_param("s", $materialName);
    $stmt_fetch_stock->execute();
    $stmt_fetch_stock->bind_result($currentStock);
    $stmt_fetch_stock->fetch();
    $stmt_fetch_stock->close();

    // Calculate the new stock value
    $newStock = $currentStock + $quantity;

    // Update the stock in the database for the specific material name
    $update_stock_sql = "UPDATE fab_mat_inventory SET mat_current_stock=? WHERE mat_name=? AND mat_godown LIKE '%$godown%'";
    $stmt_update_stock = $conn->prepare($update_stock_sql);
    $stmt_update_stock->bind_param("ds", $newStock, $materialName);
    $stmt_update_stock->execute();
    $stmt_update_stock->close();

    // Echo a response indicating success
    echo "Stock updated successfully!";
} else {
    // If the request method is not POST, return an error
    http_response_code(405);
    echo "Method Not Allowed";
}
?>
