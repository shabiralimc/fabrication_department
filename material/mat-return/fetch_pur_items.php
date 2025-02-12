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

if (isset($_POST['mat_pur_number'])) {
    $mat_pur_number = $_POST['mat_pur_number'];
    
    // Check if the selected poNumber and its respective material names exist in the material_purchase table
    $checkQuery = "SELECT COUNT(*) as count FROM mat_pur_item WHERE mat_pur_number = '$mat_pur_number'";
    $checkResult = $conn->query($checkQuery);

    if ($checkResult) {
        $row = $checkResult->fetch_assoc();
        $materialCount = $row['count'];

        if ($materialCount > 0) {
            // Fetch data from mat_pur_item table along with details from mat_purs table based on the selected purchase order
            $sql = "SELECT mpi.*, mp.mat_pur_date, mp.mat_pur_supplier, mp.invoice_number 
                    FROM mat_pur_item mpi
                    JOIN mat_purs mp ON mpi.mat_pur_number = mp.mat_pur_number
                    WHERE mpi.mat_pur_number = '$mat_pur_number'";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                // Start form and table markup
                echo "<form id='dataForm'>";
                echo '<div class="content-header">';
                echo '<div class="container-fluid">';
                echo '<div class="row mb-2">';
                echo '<div class="col-sm-6">';
                echo '<h4 class="m-0">All PURCHASES</h4>';
                echo '</div></div></div></div>';
                echo "<table class='table table-striped' border='1'>";
                echo "<tr><th><th>Material Item ID</th></th><th>Material Name</th><th>Alternative Unit</th><th>Converted Quantity</th><th>Material Quantity</th><th>Purchase Per Unit</th><th>Purchase Price</th><th>Purchase Date</th><th>Supplier</th><th>Invoice Number</th></tr>";
                
                // Output data of each row
                while($row = $result->fetch_assoc()) {
                    // Add table row with data
                    echo "<tr>";
                    echo "<td><input type='checkbox' name='checkbox[]' value='" . $row["mat_pur_item_id"] . "'></td>";
                    echo "<td>" . $row["mat_pur_item_id"] . "</td>";
                    echo "<td>" . $row["mat_pur_item_matname"] . "</td>";
                    echo "<td>" . $row["alternativeUnit"] . "</td>";
                    echo "<td>" . $row["convertedQuantity"] . "</td>";
                    echo "<td>" . $row["mat_pur_item_quant"] . "</td>";
                    echo "<td>" . $row["perUnit"] . "</td>";
                    echo "<td>" . $row["mat_pur_item_price"] . "</td>";
                    echo "<td>" . $row["mat_pur_date"] . "</td>";  // Display the purchase date
                    echo "<td>" . $row["mat_pur_supplier"] . "</td>";  // Display the supplier
                    echo "<td>" . $row["invoice_number"] . "</td>";  // Display the invoice number
                    echo "</tr>";
                }
                
                // End table markup
                echo "</table>";
                echo "<input type='button' class='btn btn-secondary' name='insert_data' value='INSERT'>";
                echo "</form>";
            } else {
                echo "No data found for the selected purchase order.";
            }
        } else {
            echo "No material exists for the selected purchase order in the material purchase database table.";
        }
    } else {
        echo "Error: " . $conn->error;
    }
} 
?>
