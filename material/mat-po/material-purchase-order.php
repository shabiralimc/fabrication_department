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

$sql = "SELECT * FROM mat_pos";

$stmts = mysqli_prepare($conn, $sql);
mysqli_stmt_execute($stmts);
$result = mysqli_stmt_get_result($stmts);

// Fetch all rows
$row_sql = mysqli_fetch_all($result, MYSQLI_ASSOC);

?>

<?php

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['mat_po_number'])) {
        $mat_po_number = $_POST['mat_po_number'];

        // Update the mat_pos table to mark the PO as cancelled
        $update_sql = "UPDATE mat_pos SET status = 'cancelled' WHERE mat_po_number = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("s", $mat_po_number);

        if ($stmt->execute()) {
            echo "PO cancelled successfully!";
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Invalid request.";
    }
}
?>

<!-- Include Header File -->
<?php include_once ('../../../../include/php/header.php') ?>

<!-- Include Sidebar File -->
<?php include_once ('../../../../include/php/sidebar-fab.php') ?>

  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h3 class="m-0">MANAGE PURCHASE ORDERS</h3>
          </div>
        </div>
        <!-- /.row -->
      </div>
      <!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->
    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">


<!-- --------------------------------------------------------------------------------------
  -------------------------- YOUR BODY CONTENT START HERE ---------------------------------
  ------------------------------------------------------------------------------------- -->



  <div class="card card-info card-outline">
  <div class="card-body">
    <a href="material-purchase-order-create.php" class="btn btn-primary" id="create-po">Create New PO</a>
  </div>
  <div class="card-body">
    <table class="table table-bordered table-striped" id="po-table">
      <thead>
        <tr>
          <th style="text-align: center;">PO Number</th>
          <th style="text-align: center;">PO Date</th>
          <th style="text-align: center;">Supplier Name</th>
          <th style="text-align: center;">Godown</th>
          <th style="text-align: center;">View & Print</th>
          <th style="text-align: center;">Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($row_sql as $row): ?>
        <tr>
          <td style="text-align: center;"><?php echo $row['mat_po_number']; ?></td>
          <td style="text-align: center;"><?php echo $row['mat_po_date']; ?></td>
          <td style="text-align: center;"><?php echo $row['mat_po_supplier']; ?></td>
          <td style="text-align: center;"><?php echo $row['mat_po_godown']; ?></td>
          <td><a href="new-po-print.php?mat_po_number=<?php echo $row['mat_po_number']; ?>" class="btn btn-info">View & Print</a></td>
          <td style="text-align: center; width:20%;">
            <?php if ($row['status'] == 'cancelled'): ?>
              <span class="text-danger">Cancelled</span>
            <?php else: ?>
              <a class="btn btn-xs btn-danger btn-block cancel-po-btn" href="#" data-mat_po_number="<?php echo $row['mat_po_number']; ?>">CANCEL PO</a>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

      </div>
    </section>
  </div>
  

  <script type="text/javascript">
  document.addEventListener('DOMContentLoaded', function () {
    const cancelPoButtons = document.querySelectorAll('.cancel-po-btn');

    cancelPoButtons.forEach(button => {
      button.addEventListener('click', function (e) {
        e.preventDefault();
        const matPoNumber = this.getAttribute('data-mat_po_number');

        Swal.fire({
          title: "Are you sure?",
          text: "Do you want to cancel this PO?",
          icon: "warning",
          showCancelButton: true,
          confirmButtonColor: "#3085d6",
          cancelButtonColor: "#d33",
          confirmButtonText: "Yes, cancel it!",
          cancelButtonText: "No, keep it"
        }).then((result) => {
          if (result.isConfirmed) {
            fetch('', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
              },
              body: `mat_po_number=${matPoNumber}`
            })
            .then(response => response.text())
            .then(data => {
              Swal.fire({
                title: "Cancelled",
                text: "The PO has been cancelled.",
                icon: "success",
                confirmButtonColor: "#3085d6",
                confirmButtonText: "Ok"
              }).then(() => {
                // Update the cancel button to display "Cancelled" text
                const cancelButton = document.querySelector(`[data-mat_po_number="${matPoNumber}"]`);
                cancelButton.parentElement.innerHTML = '<span class="text-danger">Cancelled</span>';
              });
            })
            .catch(error => {
              console.error('Error:', error);
              Swal.fire({
                title: "Error",
                text: "There was an error cancelling the PO.",
                icon: "error",
                confirmButtonColor: "#d33",
                confirmButtonText: "Ok"
              });
            });
          }
        });
      });
    });
  });
</script>

<!-- Include Footer File -->
<?php include_once ('../../../../include/php/footer.php') ?>

<script>
  $(document).ready(function() {
    $('#po-table').DataTable({
        'responsive': true,
        'lengthMenu': [[50, 100, 500, -1], [50, 100, 500, 'All']],
        dom: 'Bfrtip',
        buttons: [
            'pageLength',
            {
                extend: 'spacer',
                style: 'bar',
                text: 'Export files:'
            },
            {
                extend: 'copyHtml5',
                filename: 'Material Purchase Order Data Export',
                title: 'Material Purchase Order Data Export',
                exportOptions: {
                    columns: ':visible'
                }
            },
            {
                extend: 'excelHtml5',
                filename: 'Material Purchase Order Data Export',
                title: 'Material Purchase Order Data Export',
                exportOptions: {
                    columns: ':visible'
                }
            },
            {
                extend: 'pdfHtml5',
                filename: 'Material Purchase Order Data Export',
                title: 'Material Purchase Order Data Export',
                exportOptions: {
                    columns: ':visible'
                },
                customize: function(doc) {
                    // Check if content[1] exists and is an object with a table property
                    if (doc.content[1] && typeof doc.content[1] === 'object' && doc.content[1].table) {
                        // Set widths of each column to '*' for auto width
                        doc.content[1].table.widths = Array(doc.content[1].table.body[0].length + 1).join('*').split('');
                        // Set alignment of all cells to center
                        doc.content[1].table.body.forEach(function(row) {
                            row.forEach(function(cell) {
                                cell.alignment = 'center';
                            });
                        });

                        // Set table width to 100%
                        doc.content[1].table.width = '100%';
                    } else {
                        console.error('Content structure does not match expected format.');
                        // Log the content structure for debugging
                        console.log(doc.content);
                    }
                }
            }
        ]
    });
  });
</script>

<script>
  $(function () {
    //Initialize Select2 Elements
    $('.select2bs4').select2({
      theme: 'bootstrap4'
    })
  })

</script>

</body>
</html>