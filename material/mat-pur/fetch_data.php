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

if (isset($_POST['mat_po_item_ponum'])) {
    $mat_po_item_ponum = $_POST['mat_po_item_ponum'];
    
    // Check if the selected poNumber exists in the mat_po_items table
    $checkQuery = "SELECT COUNT(*) as count FROM mat_po_item WHERE mat_po_item_ponum = '$mat_po_item_ponum'";
    $checkResult = $conn->query($checkQuery);

    if ($checkResult) { 
        $row = $checkResult->fetch_assoc();
        $materialCount = $row['count'];

        if ($materialCount > 0) {
            // Define the SQL with placeholders
            $sql = "SELECT mpi.mat_po_item_name, 
            AVG(mpi.perUnit) AS avgPerUnit, 
            mmc.materialCategory, 
            mmc.alternativeUnit, 
            mmc.alternativeUnitvalue, 
            mmc.materialUnit,
            mpi.mat_po_item_quan,
            mpi.id,
            mpi.mat_po_item_price
            FROM mat_po_item mpi
            JOIN material_master_creates mmc 
            ON mpi.mat_po_item_name = mmc.materialName
            WHERE mpi.mat_po_item_ponum = ?
            GROUP BY mpi.mat_po_item_name, 
            mmc.materialCategory, 
            mmc.alternativeUnit, 
            mmc.alternativeUnitvalue, 
            mmc.materialUnit";

            // Prepare the statement
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
            die("SQL error: " . $conn->error);
            }

            // Bind the parameter
            $stmt->bind_param("s", $mat_po_item_ponum);

            // Execute the statement
            $stmt->execute();

            // Fetch the result
            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {
            echo "<form id='dataForm' action='insert_data.php' method='POST'>";
            echo "<table class='table table-striped' border='1'>";
            echo "<tr><th></th><th>Material Name</th><th>Material Category</th><th>Material Unit</th><th>Material Quantity</th><th>Per Unit</th><th>Purchase Price</th></tr>";

            $noAvailableQuantity = true;
            while ($row = $result->fetch_assoc()) {
            $mat_po_item_id = $row["id"];
            $mat_po_item_name = $row["mat_po_item_name"];
            $materialCategory = $row["materialCategory"];
            $alternativeUnit = $row["alternativeUnit"];
            $alternativeUnitvalue = $row["alternativeUnitvalue"];
            $materialUnit = $row["materialUnit"];
            $original_quantity = $row["mat_po_item_quan"];
            $avgPerUnit = number_format($row["avgPerUnit"], 2);
            $orginal_price = $row["mat_po_item_price"];

            $purchaseQuery = "SELECT SUM(mat_pur_item_quant) as total_purchased, SUM(mat_pur_item_price) as total_price FROM mat_pur_item WHERE mat_pur_po = ? AND mat_pur_item_matname = ?";
            $purchaseStmt = $conn->prepare($purchaseQuery);
            $purchaseStmt->bind_param("ss", $mat_po_item_ponum, $mat_po_item_name);
            $purchaseStmt->execute();
            $purchaseResult = $purchaseStmt->get_result();
            $total_purchased = 0;
            $total_price = 0;

            if ($purchaseResult && $purchaseResult->num_rows > 0) {
            $purchaseRow = $purchaseResult->fetch_assoc();
            $total_purchased = $purchaseRow['total_purchased'] ?? 0;
            $total_price = $purchaseRow['total_price'] ?? 0;
            }

            $remaining_quantity = $original_quantity - $total_purchased;
            $remaining_price = $orginal_price - $total_price;

            if ($remaining_quantity > 0) {
            $noAvailableQuantity = false;
            echo "<tr>";
            echo "<td><input type='checkbox' name='checkbox[]' value='" . $mat_po_item_id . "'>";
            echo "<input type='hidden' name='remaining_quantity[" . $mat_po_item_id . "]' value='" . $remaining_quantity . "'></td>";
            echo "<td>" . $mat_po_item_name . "</td>";
            echo "<td>" . $materialCategory . "</td>";
            echo "<td style='display:none;'>" . $alternativeUnit . "</td>";
            echo "<td style='display:none;'>" . $alternativeUnitvalue . "</td>";
            echo "<td>" . $materialUnit . "</td>";
            echo "<td>" . $remaining_quantity . "</td>";
            echo "<td>" . $avgPerUnit . "</td>";
            echo "<td>" . $remaining_price . "</td>";
            echo "</tr>";
            }
            }

            echo "</table>";
            echo "<input type='button' class='btn btn-secondary' name='insert' value='Insert' id='insertButton'>";
            echo "</form>";
            echo "<div id='selectedRows'></div>";

            if ($noAvailableQuantity) {
            echo "<p>No available quantity for the selected purchase order.</p>";
            }
            } else {
            echo "No quantity found for the selected purchase order for new purchase.";
            }

        // Close the statement
        $stmt->close();

        } else {
            echo "No material exists for the selected purchase order in the material purchase database table.";
        }
    } else {
        echo "Error: " . $conn->error;
    }
} 
?>