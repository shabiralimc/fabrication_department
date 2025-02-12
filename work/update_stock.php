<?php
include_once('../../../include/php/connect.php'); // Assuming your database connection script
session_start();

$userbranch = $_SESSION['branch'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check for material names and quantities (for adding new rows)
    if (isset($_POST["material_name"]) && isset($_POST["quantity"])) {
        $materialNames = $_POST['material_name'];
        $quantities = $_POST['quantity'];

        // Loop through each material and update its stock
        for ($i = 0; $i < count($materialNames); $i++) {
            $materialName = $materialNames[$i];
            $quantityUsed = $quantities[$i];

            // Fetch current stock for this material
            $fetch_stock_sql = "SELECT mat_current_stock FROM fab_mat_inventory WHERE mat_name=? AND mat_godown LIKE '%$userbranch%'";
            $stmt_fetch_stock = $conn->prepare($fetch_stock_sql);
            $stmt_fetch_stock->bind_param("s", $materialName);
            $stmt_fetch_stock->execute();
            $stmt_fetch_stock->bind_result($currentStock);
            $stmt_fetch_stock->fetch();
            $stmt_fetch_stock->close();

            // Calculate new stock (deducting quantity for added rows)
            $newStock = $currentStock - $quantityUsed;

            // Update stock in database for this material
            $update_stock_sql = "UPDATE fab_mat_inventory SET mat_current_stock=? WHERE mat_name=? AND mat_godown LIKE '%$userbranch%'";
            $stmt_update_stock = $conn->prepare($update_stock_sql);
            $stmt_update_stock->bind_param("ds", $newStock, $materialName);
            $stmt_update_stock->execute();
            $stmt_update_stock->close();
        }
    }

    echo "Stock updated successfully!"; // Success message
} else {
    http_response_code(405);
    echo "Method Not Allowed"; // Error for non-POST requests
}
?>
