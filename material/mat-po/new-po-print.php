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


// Check if the GET parameter is set
if (isset($_GET['mat_po_number'])) {
    $mat_po_number = $_GET['mat_po_number'];

    // SQL query to fetch records from `mat_pos` table based on `mat_po_number`
    $sql_pos = "SELECT * FROM mat_pos WHERE mat_po_number = ?";
    $stmt_pos = $conn->prepare($sql_pos);
    $stmt_pos->bind_param("s", $mat_po_number);
    $stmt_pos->execute();
    $result_pos = $stmt_pos->get_result();

    echo "<div id='printArea'>"; // Start print area
    // Display the data from `mat_pos`
    if ($result_pos->num_rows > 0) {
        echo "<h2>Material PO Details</h2>";
        echo "<table border='1'>";
        echo "<tr><th>Po Number</th><th>Po Date</th><th>Po Supplier</th><th>Po Godown</th><th>Po Remarks</th><th>Po Total Amount</th><th>Po Gst Amount</th><th>Po Other Expenses</th><th>Po Grand Total</th></tr>";

        while ($row_pos = $result_pos->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row_pos['mat_po_number'] . "</td>";
            echo "<td>" . $row_pos['mat_po_date'] . "</td>";
            echo "<td>" . $row_pos['mat_po_supplier'] . "</td>";
            echo "<td>" . $row_pos['mat_po_godown'] . "</td>";
            echo "<td>" . $row_pos['po_remarks'] . "</td>";
            echo "<td>" . $row_pos['mat_po_totalamt'] . "</td>";
            echo "<td>" . $row_pos['mat_po_gst_amnt'] . "</td>";
            echo "<td>" . $row_pos['mat_po_other_exp'] . "</td>";
            echo "<td>" . $row_pos['mat_po_grant_total'] . "</td>";
            echo "</tr>";
        }

        echo "</table>";
    } else {
        echo "<p>No data found in mat_pos table for PO Number: $mat_po_number</p>";
    }

    // SQL query to fetch records from `mat_po_item` table based on `mat_po_number`
    $sql_po_item = "SELECT * FROM mat_po_item WHERE mat_po_item_ponum = ?";
    $stmt_po_item = $conn->prepare($sql_po_item);
    $stmt_po_item->bind_param("s", $mat_po_number);
    $stmt_po_item->execute();
    $result_po_item = $stmt_po_item->get_result();

    // Display the data from `mat_po_item`
    if ($result_po_item->num_rows > 0) {
        echo "<h2>Material PO Items</h2>";
        echo "<table border='1'>";
        echo "<tr><th>Material Name</th><th>Per Unit Price</th><th>Quantity</th><th>Price</th></tr>";

        while ($row_po_item = $result_po_item->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row_po_item['mat_po_item_name'] . "</td>";
            echo "<td>" . $row_po_item['perUnit'] . "</td>";
            echo "<td>" . $row_po_item['mat_po_item_quan'] . "</td>";
            echo "<td>" . $row_po_item['mat_po_item_price'] . "</td>";
            echo "</tr>";
        }

        echo "</table>";
    } else {
        echo "<p>No data found in mat_po_item table for PO Number: $mat_po_number</p>";
    }

    echo "</div>"; // End print area

    // Free results and close statements
    $stmt_pos->close();
    $stmt_po_item->close();
} else {
    echo "<p>Invalid or missing PO Number.</p>";
}
?>

<button onclick="printPage()" class="btn btn-primary">Print </button>

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
