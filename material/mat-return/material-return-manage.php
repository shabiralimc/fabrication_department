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

// Group the returns by returnNumber so that each unique return number is shown once.
// You can also get a comma-separated list of IDs if needed, e.g., GROUP_CONCAT(id) as ids
$sql = "SELECT returnNumber FROM mat_pur_return GROUP BY returnNumber";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Fetch all grouped rows
$row_sql = mysqli_fetch_all($result, MYSQLI_ASSOC);
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
          <h3 class="m-0">MANAGE RETURNS</h3>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Main content -->
  <section class="content">
    <div class="container-fluid">
      <div class="card card-info card-outline">
        <div class="col-md-3" style="margin-top:50px;">
          <a href="material-return.php" class="btn btn-primary btn-block">New Returns</a>
        </div>
        <div class="card-body">
          <table class="table table-bordered table-striped" id="ReturnsTable">
            <thead>
              <tr>
                <th style="text-align: center;">Return Number</th>
                <th style="text-align: center;">View & Print</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($row_sql as $row): ?>
                <tr>
                  <td style="text-align: center;"><?php echo $row['returnNumber']; ?></td>
                  <td style="text-align: center; width:20%;">
                    <!-- Pass the returnNumber as a GET parameter -->
                    <a href="material-return-print.php?returnNumber=<?php echo urlencode($row['returnNumber']); ?>" class="btn btn-xs btn-info btn-block">
                      View & Print
                    </a>
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


<!-- Include Footer File -->
<?php include_once ('../../../../include/php/footer.php') ?>

<script>
  $(document).ready(function() {
    $('#ReturnsTable').DataTable({
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

</body>
</html>