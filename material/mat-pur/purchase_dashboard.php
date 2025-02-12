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

// CREATIVE MAIN TABLE DATA FETCH

$sql = "SELECT * FROM mat_purs";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Fetch all rows
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
          <h3 class="m-0">MANAGE PURCHASE</h3>
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



      <div class="card card-info card-outline">
        <div class="card-header">
          <a href="material-purchase.php" class="btn btn-primary">Create Purchase</a>
        </div>
        <div class="card-body">
          <table class="table table-bordered table-striped" id="table-Purchase">
        <thead>
          <tr>
            <th style="text-align: center;"> ID</th>
            <th style="text-align: center;">Purchase Number</th>
            <th style="text-align: center;">Purchase Order</th>
            <th style="text-align: center;">Godown</th>
            <th style="text-align: center;">Invoice Number</th>
            <th style="text-align: center;">View File</th>
            <th style="text-align: center;">View & Print</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($row_sql as $row): ?>
          <tr>
            <td style="text-align: center;"><?php echo $row['id']; ?></td>
            <td style="text-align: center;"><?php echo $row['mat_pur_number']; ?></td>
            <td style="text-align: center;"><?php echo $row['mat_pur_po']; ?></td>
            <td style="text-align: center;"><?php echo $row['mat_pur_godown']; ?></td>
            <td style="text-align: center;"><?php echo $row['invoice_number']; ?></td>

            <td style="text-align: center; width:20%;">
            <a href="#" data-id="<?php echo $row['id']; ?>" class="btn btn-xs btn-success btn-block view-file-btn">View</a>
            </td>
            
            <td style="text-align: center; width:20%;"><a href="purchase_print.php?id=<?php echo $row['mat_pur_number']; ?>" class="btn btn-xs btn-info btn-block">View & Print</a></td>

          </tr>
          <?php endforeach; ?>

        </tbody>
      </table>

        </div>
      </div>
    </div>
  </section>
</div>


<!-- Modal Structure -->
<div class="modal fade" id="fileModal" tabindex="-1" role="dialog" aria-labelledby="fileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="fileModalLabel">View File</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Iframe for PDFs -->
                <iframe id="fileFrame" src="" style="display: none; width:100%; height: 600px;"></iframe>
                
                <!-- Image for PNG/JPEG/JPG -->
                <img id="fileImage" src="" alt="File Image" style="width:500px; display: none;" />
            </div>
        </div>
    </div>
</div>




<!-- Include Footer File -->
<?php include_once ('../../../../include/php/footer.php') ?>


<script>
  $(document).ready(function() {
    $('#table-Purchase').DataTable({
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
$(document).on('click', '.view-file-btn', function(e) {
    e.preventDefault();  // Prevent default behavior of the link

    var fileId = $(this).data('id'); // Get the file ID from the button's data-id attribute
    var fileFrame = $('#fileFrame'); // Get the iframe element
    var fileImage = $('#fileImage'); // Get the image element
    var errorMessage = "This file format is not supported for display.";
    var fileModalBody = $('#fileModal .modal-body');

    // Perform an AJAX request to get the file type and filename without downloading
    $.ajax({
        url: 'fetch_file.php', // PHP file that returns file info
        method: 'GET',
        data: { id: fileId },
        dataType: 'json',  // Expect JSON response
        success: function(response) {
            if (response.error) {
                // Handle any errors (like file not found)
                fileFrame.hide(); // Hide iframe
                fileImage.hide(); // Hide image if previously shown
                fileModalBody.append('<p>' + response.error + '</p>');
            } else {
                var fileExtension = response.fileExt.trim().toLowerCase(); // Get the file extension
                var fileName = response.fileName; // Get the file name

                // Construct the URL using the filename instead of fileId
                var fileUrl = 'uploaded_files/' + fileName;

                // Reset the modal body content
                fileModalBody.find('p').remove(); // Remove any previous error messages

                if (fileExtension === 'pdf') {
                    // Display the PDF in the iframe
                    fileFrame.attr('src', fileUrl).show(); // Show iframe for PDF
                    fileImage.hide(); // Hide image if previously shown
                } else if (fileExtension === 'jpg' || fileExtension === 'jpeg' || fileExtension === 'png') {
                    // Display the image in an <img> tag
                    fileImage.attr('src', fileUrl).show(); // Show image for JPEG, PNG
                    fileFrame.hide(); // Hide iframe if previously shown
                } else {
                    // Display an error message inside the modal for unsupported file formats
                    fileFrame.hide(); // Hide iframe
                    fileImage.hide(); // Hide image if previously shown
                    fileModalBody.append('<p>' + errorMessage + '</p>');
                }

                // Show the modal
                $('#fileModal').modal('show');
            }
        },
        error: function() {
            alert('Error fetching file info.');
        }
    });
});
</script>
</body>
</html>