<?php
// Include the database connection configuration
include_once("../../../../include/php/connect.php");
ini_set('session.gc_maxlifetime', 43200);

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user']) || $_SESSION['role'] !== '4') {
    echo "<script>alert('You are not authorised to view this page...'); window.location = '$app_url';</script>";
    exit();
}

if (isset($_GET['returnNumber'])) {
    $returnNumber = $_GET['returnNumber'];

    echo "<div id='printArea'>"; // Start print area

    // Prepare a query to fetch all records with the provided return number
    $sql_pur_return = "SELECT * FROM mat_pur_return WHERE returnNumber = ?";
    $stmt_pur_return = $conn->prepare($sql_pur_return);
    $stmt_pur_return->bind_param("s", $returnNumber);
    $stmt_pur_return->execute();
    $result_pur_return = $stmt_pur_return->get_result();

    // Display the data from `mat_pur_return`
    if ($result_pur_return->num_rows > 0) {
        echo "<h2>Material Purchase Return Details for Return Number: " . htmlspecialchars($returnNumber) . "</h2>";
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr>
                <th>Return Date</th>
                <th>Return Remarks</th>
                <th>Retrun Quantity</th>
                <th>Return Amount</th>
              </tr>";

        while ($row_pur_return = $result_pur_return->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row_pur_return['mat_ret_date']) . "</td>";
            echo "<td>" . htmlspecialchars($row_pur_return['ret_remarks']) . "</td>";
            echo "<td>" . htmlspecialchars($row_pur_return['mat_ret_quanity']) . "</td>";
            echo "<td>" . htmlspecialchars($row_pur_return['mat_ret_amount']) . "</td>";
            echo "</tr>";
        }

        echo "</table>";
    } else {
        echo "<p>No data found in mat_pur_return table for Return Number: " . htmlspecialchars($returnNumber) . "</p>";
    }

    echo "</div>"; // End print area

    // Free results and close statements
    $stmt_pur_return->close();
} else {
    echo "<p>Invalid or missing Return Number.</p>";
}
?>



<!-- jQuery -->
<script src="https://work.chakracom.net/plugins/jquery/jquery.min.js"></script>
<!-- jQuery UI 1.11.4 -->
<script src="https://work.chakracom.net/plugins/jquery-ui/jquery-ui.min.js"></script>
<!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
<script>
  $.widget.bridge('uibutton', $.ui.button)
</script>
<!-- Bootstrap 4 -->
<script src="https://work.chakracom.net/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>


<!-- overlayScrollbars -->
<script src="https://work.chakracom.net/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
<!-- AdminLTE App -->
<script src="https://work.chakracom.net/dist/js/adminlte.js"></script>

<!-- DataTables  & Plugins -->
<script src="https://work.chakracom.net/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="https://work.chakracom.net/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="https://work.chakracom.net/plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="https://work.chakracom.net/plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
<script src="https://work.chakracom.net/plugins/datatables-buttons/js/dataTables.buttons.min.js"></script>
<script src="https://work.chakracom.net/plugins/datatables-buttons/js/buttons.bootstrap4.min.js"></script>
<script src="https://work.chakracom.net/plugins/jszip/jszip.min.js"></script>
<script src="https://work.chakracom.net/plugins/pdfmake/pdfmake.min.js"></script>
<script src="https://work.chakracom.net/plugins/pdfmake/vfs_fonts.js"></script>
<script src="https://work.chakracom.net/plugins/datatables-buttons/js/buttons.html5.min.js"></script>
<script src="https://work.chakracom.net/plugins/datatables-buttons/js/buttons.print.min.js"></script>
<script src="https://work.chakracom.net/plugins/datatables-buttons/js/buttons.colVis.min.js"></script>
<!-- Ekko Lightbox -->
<script src="https://work.chakracom.net/plugins/ekko-lightbox/ekko-lightbox.min.js"></script>
<!-- Toastr -->
<script src="https://work.chakracom.net/plugins/toastr/toastr.min.js"></script>

<!-- Select2 -->
<script src="https://work.chakracom.net/plugins/select2/js/select2.full.min.js"></script>

<!-- bs-custom-file-input -->
<script src="https://work.chakracom.net/plugins/bs-custom-file-input/bs-custom-file-input.min.js"></script>

<!-- Bootstrap Switch -->
<script src="https://work.chakracom.net/plugins/bootstrap-switch/js/bootstrap-switch.min.js"></script>

<!-- AdminLTE dashboard demo (This is only for demo purposes) -->
<script src="https://work.chakracom.net/dist/js/pages/dashboard.js"></script>

<!-- date-range-picker -->
<script src="https://work.chakracom.net/plugins/daterangepicker/daterangepicker.js"></script>

<!-- Flatpickr Date -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
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
