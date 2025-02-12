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

// Check if the GET parameter 'id' is set
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    echo "<div id='printArea'>"; // Start print area

    // Fetch records from `mat_purs` table based on `id`
    $sql_purs = "SELECT * FROM mat_purs WHERE mat_pur_number = ?";
    $stmt_purs = $conn->prepare($sql_purs);
    $stmt_purs->bind_param("s", $id);
    $stmt_purs->execute();
    $result_purs = $stmt_purs->get_result();

    // Display the data from `mat_purs`
    if ($result_purs->num_rows > 0) {
        echo "<h2>Material Purchase Details</h2>";
        echo "<table border='1'>";
        echo "<tr><th>Purchase Number</th><th>Purchase Date</th><th>Supplier Name</th><th>Purchase PO</th><th>Godown</th><th>Remarks</th><th>Invoice Number</th><th>Total Amount</th><th>GST Amount</th><th>Other Expenses</th><th>Grand Total</th></tr>";

        while ($row_purs = $result_purs->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row_purs['mat_pur_number'] . "</td>";
            echo "<td>" . $row_purs['mat_pur_date'] . "</td>";
            echo "<td>" . $row_purs['mat_pur_supplier'] . "</td>";
            echo "<td>" . $row_purs['mat_pur_po'] . "</td>";
            echo "<td>" . $row_purs['mat_pur_godown'] . "</td>";
            echo "<td>" . $row_purs['pur_remarks'] . "</td>";
            echo "<td>" . $row_purs['invoice_number'] . "</td>";
            echo "<td>" . $row_purs['mat_pur_totalamt'] . "</td>";
            echo "<td>" . $row_purs['mat_pur_gst_amnt'] . "</td>";
            echo "<td>" . $row_purs['mat_pur_other_exp'] . "</td>";
            echo "<td>" . $row_purs['mat_pur_grant_total'] . "</td>";
            echo "</tr>";
        }

        echo "</table>";
    } else {
        echo "<p>No data found in mat_purs table for ID: $id</p>";
    }

    // Fetch records from `mat_pur_item` table based on `id`
    $sql_pur_item = "SELECT * FROM mat_pur_item WHERE mat_pur_number = ?";
    $stmt_pur_item = $conn->prepare($sql_pur_item);
    $stmt_pur_item->bind_param("s", $id);
    $stmt_pur_item->execute();
    $result_pur_item = $stmt_pur_item->get_result();

    // Display the data from `mat_pur_item`
    if ($result_pur_item->num_rows > 0) {
        echo "<h2>Material Purchase Items</h2>";
        echo "<table border='1'>";
        echo "<tr><th>Material Name</th><th>Purchase Unit</th><th>Quantity</th><th>Converted Quantity</th><th>Unit Price</th><th> Price</th></tr>";

        while ($row_pur_item = $result_pur_item->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row_pur_item['mat_pur_item_matname'] . "</td>";
            echo "<td>" . $row_pur_item['alternativeUnit'] . "</td>";
            echo "<td>" . $row_pur_item['mat_pur_item_quant'] . "</td>";
            echo "<td>" . $row_pur_item['convertedQuantity'] . "</td>";
            echo "<td>" . $row_pur_item['perUnit'] . "</td>";
            echo "<td>" . $row_pur_item['mat_pur_item_price'] . "</td>";

            echo "</tr>";
        }

        echo "</table>";
    } else {
        echo "<p>No data found in mat_pur_item table for ID: $id</p>";
    }

    echo "</div>"; // End print area

    // Free results and close statements
    $stmt_purs->close();
    $stmt_pur_item->close();
} else {
    echo "<p>Invalid or missing ID.</p>";
}
?>


<!-- Add a Print Button -->
<button onclick="printPage()" class="btn btn-primary">Print</button>

<!-- JavaScript Function to Print Page -->
<script>
function printPage() {
    var printContent = document.getElementById('printArea').innerHTML;
    var originalContent = document.body.innerHTML;
    
    document.body.innerHTML = printContent;
    window.print();
    document.body.innerHTML = originalContent;
}
</script>
