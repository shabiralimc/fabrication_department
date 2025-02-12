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

$sql = "SELECT * FROM master_godown ORDER BY godownName ASC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Fetch all rows
$row_sql = mysqli_fetch_all($result, MYSQLI_ASSOC);

?>

<?php

if ($_SERVER["REQUEST_METHOD"] == "POST") {

if (isset($_POST['godownName'], $_POST['godownDesc'])) {
        // Handle create unit form submission
        // Process the form data
        $godownName = $_POST['godownName'];
        $godownDesc = $_POST['godownDesc'];

        // Prepare and execute the insert query
        $sql = "INSERT INTO master_godown (godownName, godownDesc)
                VALUES ('$godownName', '$godownDesc')";

        if ($conn->query($sql) === TRUE) {
            echo "New Godown created successfully";
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
        exit; // Add this to prevent the rest of the page from executing
    }
}
?>

<?php

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize input
    $godown_id   = isset($_POST['godown_id']) ? intval($_POST['godown_id']) : 0;
    $godown_Name = isset($_POST['godown-Name']) ? $_POST['godown-Name'] : '';
    $godown_Desc = isset($_POST['godown-Desc']) ? $_POST['godown-Desc'] : '';
    
    // Prepare and execute the update query using prepared statements
    $sql_update = "UPDATE master_godown SET godownName=?, godownDesc=? WHERE godown_id=?";
    $stmt_main = $conn->prepare($sql_update);
    $stmt_main->bind_param("ssi", $godown_Name, $godown_Desc,$godown_id);

    if ($stmt_main->execute()) {
        echo "Godown updated successfully"; // Output success message
    } else {
        echo "Error updating record: " . $stmt_main->error; // Output error message
    }
    
    // Close the prepared statement
    $stmt_main->close();
    exit; // Stop further execution
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
            <h3 class="m-0">MANAGE GODOWN</h3>
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
      <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#godown-create">
        Create godown
      </button>
    </div>
    <div class="card-body">
      <table class="table table-bordered table-striped" id="table-godown">
        <thead>
          <tr>
            <th style="text-align: center;">Godown ID</th>
            <th style="text-align: center;">Godown Name</th>
            <th style="text-align: center;">Description</th>
            <th style="text-align: center;">Edit</th>
            <th style="text-align: center;">Delete</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($row_sql as $row): ?>
          <tr>
            <td style="text-align: center;"><?php echo $row['godown_id']; ?></td>
            <td style="text-align: center;"><?php echo $row['godownName']; ?></td>
            <td style="text-align: center;"><?php echo $row['godownDesc']; ?></td>
            <td style="text-align: center; width:20%;"><button type="button" class="btn btn-xs btn-primary btn-block btn-edit" data-godown-id="<?php echo $row['godown_id']; ?>">Edit</button></td>
            <td style="text-align: center; width:20%;"><button type="button" class="btn btn-xs btn-danger btn-block btn-delete" data-godowns-id="<?php echo $row['godown_id']; ?>">Delete</button></td>
          </tr>
          <?php endforeach; ?>

        </tbody>
      </table>

<!-- --------------------------------------------------------------------------------------
  -------------------------- YOUR BODY CONTENT ENDS HERE ----------------------------------
  ------------------------------------------------------------------------------------- -->

      </div>
    </section>
  </div>


<!-- MODAL FOR CREATE CATERGORY -->

<div class="modal fade show" id="godown-create" aria-modal="true" role="dialog" style="display: none;">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">Create Godown</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">×</span>
        </button>
      </div>
      <div class="modal-body">

      <div class="form-group">
          <label for="godownName">Godown Name<span class="text-danger">*</span></label>
          <select name="godownName" class="form-control" id="godownName" required>
            <option value="">Select Godown</option>
          </select>
        </div>

        <div class="form-group">
          <label for="godownDesc">Description</label>
          <input type="text" name="godownDesc" class="form-control" id="godownDesc">
        </div>

      </div>
      <div class="modal-footer justify-content-between">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="createNewgodown">Create</button>
      </div>
    </div>
    <!-- /.modal-content -->
  </div>
  <!-- /.modal-dialog -->
</div>

<!-- MODAL FOR EDIT godown -->

<div class="modal fade show" id="godown-edit" aria-modal="true" role="dialog" style="display: none;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Edit Godown</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="godown_id" class="form-control" id="godown_id">

                <div class="form-group">
                    <label for="godown-Name">Godown Name</label>
                    <input type="text" name="godown-Name" class="form-control" id="godown-Name">
                </div>

                <div class="form-group">
                    <label for="godown-Desc">Description</label>
                    <input type="text" name="godown-Desc" class="form-control" id="godown-Desc">
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="createEditgodown">Save Edits</button>
            </div>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>

<!-- Include Footer File -->
<?php include_once ('../../../../include/php/footer.php') ?>

<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function () {
  document.getElementById('createNewgodown').addEventListener('click', function () {

    Swal.fire({
      title: "Are you sure?",
      text: "You won't be able to revert this!",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#3085d6",
      cancelButtonColor: "#d33",
      confirmButtonText: "Yes, Create!"
    }).then((result) => {
      if (result.isConfirmed) {
        // Get input values
        var godownName = document.getElementById('godownName').value;
        var godownDesc = document.getElementById('godownDesc').value;

        var xhr = new XMLHttpRequest();
          xhr.open("POST", " ", true); // Update the URL to point to the correct PHP file
          xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
          xhr.onreadystatechange = function () {
          if (xhr.readyState === 4 && xhr.status === 200) {
        Swal.fire({
          title: "Created!",
          text: "New Unit Created.",
          icon: "success"
        }).then(() => {
          location.reload(); // Reload the page
        });
                    }
                };
                xhr.send("godownName=" + encodeURIComponent(godownName) +
                          "&godownDesc=" + encodeURIComponent(godownDesc));
                   
            }
        });
    });
});
</script>

<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function () {
  document.getElementById('createEditgodown').addEventListener('click', function () {
    Swal.fire({
      title: "Are you sure?",
      text: "You won't be able to revert this!",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#3085d6",
      cancelButtonColor: "#d33",
      confirmButtonText: "Yes,Save!"
    }).then((result) => {
            if (result.isConfirmed) {
                // Get input values
                var godown_id = document.getElementById('godown_id').value;
                var godown_Name = document.getElementById('godown-Name').value;
                var godown_Desc = document.getElementById('godown-Desc').value;

                // Send AJAX request to update the supplier details
                var xhr = new XMLHttpRequest();
                xhr.open("POST", " ", true); // Update the URL to point to the correct PHP file
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.onreadystatechange = function () {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        Swal.fire({
                            title: "Saved?",
                            text: "Your edits saved successfully",
                            icon: "success"
                        }).then(() => { 
                            location.reload(); // Reload the page
                        });
                    }
                };

               // Construct the data to be sent in the request
               xhr.send("godown_id=" + encodeURIComponent(godown_id) +
                    "&godown-Name=" + encodeURIComponent(godown_Name) +
                    "&godown-Desc=" + encodeURIComponent(godown_Desc));

                  }
        });
    });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get all elements with the class 'btn-edit'
    var editButtons = document.querySelectorAll('.btn-edit');

    // Add click event listener to each edit button
    editButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            var GodownId = this.getAttribute('data-godown-id');

            // Set the supplier_id value in the URL
            var url = new URL(window.location.href);
            url.searchParams.set('godown_id', GodownId);
            window.history.pushState({}, '', url);

            // Set the supplier_id value in the modal input field
            document.getElementById('godown_id').value = GodownId;
            
            // Open the modal using Bootstrap's modal function
            $('#godown-edit').modal('show');
        });
    });
});
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
    var editButtons = document.querySelectorAll('.btn-edit');
    editButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            var godownId = button.getAttribute('data-godown-id');

            // Fetch godown details using AJAX
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'fetch_godown_details.php?godown_id=' + godownId, true);
            xhr.onload = function () {
                if (xhr.status === 200) {
                    var data = JSON.parse(xhr.responseText);
                    // Check if data is valid
                    if (data && !data.error) {
                        // Populate modal fields with godown details
                        document.getElementById('godown_id').value = godownId;
                        document.getElementById('godown-Name').value = data.godownName;
                        document.getElementById('godown-Desc').value = data.godownDesc;
                        // Open the modal using Bootstrap's modal function
                        $('#godown-edit').modal('show');
                    } else {
                        console.error('Error: ' + (data ? data.error : 'Invalid response'));
                    }
                } else {
                    console.error('Error fetching godown details: ' + xhr.statusText);
                }
            };
            xhr.onerror = function () {
                console.error('Error fetching godown details.');
            };
            xhr.send();
        });
    });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    var deleteButtons = document.querySelectorAll('.btn-delete');

    deleteButtons.forEach(function (button) {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            var godownId = this.getAttribute('data-godowns-id');
            var row = this.closest('tr'); // Get the parent <tr> element

            Swal.fire({
                title: "Are you sure?",
                text: "You won't be able to revert this!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#3085d6",
                confirmButtonText: "Yes, Delete!"
            }).then((results) => {
                if (results.isConfirmed) {
                    // Send AJAX request to delete the supplier
                    var xhr = new XMLHttpRequest();
                    xhr.open("POST", "delete_godown.php", true); // Specify your PHP file name or endpoint here
                    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                    xhr.onreadystatechange = function () {
                        if (xhr.readyState === 4) {
                            if (xhr.status === 200) {
                                try {
                                    var response = JSON.parse(xhr.responseText);
                                    if (response.status === "success") {
                                        // Remove the entire row from the table
                                        row.remove();

                                        // Display Swal.fire for successful deletion
                                        Swal.fire({
                                            title: "Deleted!",
                                            text: "The selected Godown has been deleted.",
                                            icon: "success"
                                        });
                                    } else {
                                        // Display Swal.fire for deletion error
                                        Swal.fire({
                                            title: "Error!",
                                            text: "Failed to delete the Godown.",
                                            icon: "error"
                                        });
                                    }
                                } catch (error) {
                                    console.error("Error parsing JSON response:", error);
                                }
                            } else {
                                // Display Swal.fire for network error
                                Swal.fire({
                                    title: "Error!",
                                    text: "Failed to delete the Godown due to a network error.",
                                    icon: "error"
                                });
                            }
                        }
                    };
                    xhr.send("delete_godowns=true&godown_id=" + encodeURIComponent(godownId));
                }
            });
        });
    });
});
</script>

<script>
  $(document).ready(function() {
    $('#table-godown').DataTable({
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
                filename: 'Godown Master Data Export',
                title: 'Godown Master Data Export',
                exportOptions: {
                    columns: ':visible'
                }
            },
            {
                extend: 'excelHtml5',
                filename: 'Godown Master Data Export',
                title: 'Godown Master Data Export',
                exportOptions: {
                    columns: ':visible'
                }
            },
            {
                extend: 'pdfHtml5',
                filename: 'Godown Master Data Export',
                title: 'Godown Master Data Export',
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

<?php


// Query to fetch existing category names from the master_supplier table
$sql = "SELECT DISTINCT godownName FROM master_godown";

$result = mysqli_query($conn, $sql);

if (!$result) {
    // If there's an error in the query, return an empty array
    echo json_encode([]);
    exit;
}

// Fetch category names and store them in an array
$godownNames = [];
while ($row = mysqli_fetch_assoc($result)) {
    $godownNames[] = $row['godownName'];
}

// Close the database connection
mysqli_close($conn);

// Return the category names as a JSON array
$godownsNamesJSON = json_encode($godownNames);
?>

<script>
$(function () {
  // Initialize Select2 with data from PHP
  var godownNamesData = <?php echo $godownsNamesJSON; ?>;

  $('#godownName').select2({
    theme: 'bootstrap4',
    placeholder: 'Select or type Unit Name',
    allowClear: true,
    minimumInputLength: 1, // Minimum length of input before triggering AJAX
    data: godownNamesData, // Populate with existing categories
    tags: true // Allow custom tags (new categories)
  });
});
</script>

</body>
</html>