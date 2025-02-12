<?php
include_once('../../../../include/php/connect.php');
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

$sql = "SELECT * FROM master_unit ORDER BY unit_name ASC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Fetch all rows
$row_sql = mysqli_fetch_all($result, MYSQLI_ASSOC);

?>
<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {

if (isset($_POST['unitName'], $_POST['unitDesc'])) {
        // Handle create unit form submission
        // Process the form data
        $unitName = $_POST['unitName'];
        $unitDesc = $_POST['unitDesc'];

        // Prepare and execute the insert query
        $sql = "INSERT INTO master_unit (unit_name, unit_desc)
                VALUES ('$unitName', '$unitDesc')";

        if ($conn->query($sql) === TRUE) {
            echo "New record created successfully";
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
    $unit_id   = isset($_POST['unit_id']) ? intval($_POST['unit_id']) : 0;
    $unit_Name = isset($_POST['unit-Name']) ? $_POST['unit-Name'] : '';
    $unit_Desc = isset($_POST['unit-Desc']) ? $_POST['unit-Desc'] : '';
    
    // Prepare and execute the update query using prepared statements
    $sql_update = "UPDATE master_unit SET unit_name=?, unit_desc=? WHERE unit_id=?";
    $stmt_main = $conn->prepare($sql_update);
    $stmt_main->bind_param("ssi", $unit_Name, $unit_Desc,$unit_id);

    if ($stmt_main->execute()) {
        echo "Record updated successfully"; // Output success message
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
            <h3 class="m-0">MANAGE UNIT</h3>
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
      <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#unit-create">
        Create Unit
      </button>
    </div>
    <div class="card-body">
      <table class="table table-bordered table-striped" id="table-unit">
        <thead>
          <tr>
            <th style="text-align: center;">Unit ID</th>
            <th style="text-align: center;">Unit Name</th>
            <th style="text-align: center;">Description</th>
            <th style="text-align: center;">Edit</th>
            <th style="text-align: center;">Delete</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($row_sql as $row): ?>
            <tr>
              <td style="text-align: center;"><?php echo $row['unit_id']; ?></td>
              <td style="text-align: center;"><?php echo $row['unit_name']; ?></td>
              <td style="text-align: center;"><?php echo $row['unit_desc']; ?></td>
                <td style="text-align: center; width:20%;"><button type="button" class="btn btn-xs btn-primary btn-block btn-edit" data-unit-id="<?php echo $row['unit_id']; ?>">Edit</button></td>
                <td style="text-align: center; width:20%;"><button type="button" class="btn btn-xs btn-danger btn-block btn-delete" data-units-id="<?php echo $row['unit_id']; ?>">Delete</button></td>
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


<!-- MODAL FOR CREATE UNIT -->

<div class="modal fade show" id="unit-create" aria-modal="true" role="dialog" style="display: none;">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">Create Unit</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">×</span>
        </button>
      </div>
      <div class="modal-body">
      
      <div class="form-group">
          <label for="unitName">Unit Name<span class="text-danger">*</span></label>
          <select name="unitName" class="form-control" id="unitName" required>
            <option value="">Select Unit</option>
          </select>
        </div>

        <div class="form-group">
          <label for="unitDesc">Description<span class="text-danger">*</span></label>
          <input type="text" name="unitDesc" class="form-control" id="unitDesc"required>
        </div>

      </div>
      <div class="modal-footer justify-content-between">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="createNewUnit">Create</button>
      </div>
    </div>
    <!-- /.modal-content -->
  </div>
  <!-- /.modal-dialog -->
</div>

<!-- MODAL FOR EDIT UNIT -->

<div class="modal fade show" id="unit-edit" aria-modal="true" role="dialog" style="display: none;">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">Edit Unit</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">×</span>
        </button>
      </div>
      <div class="modal-body">
      <input type="hidden" name="unit_id" class="form-control" id="unit_id">

        <div class="form-group">
          <label for="unit-Name">Unit Name</label>
          <input type="text" name="unit-Name" class="form-control" id="unit-Name">
        </div>

        <div class="form-group">
          <label for="unit-Desc">Description</label>
          <input type="text" name="unit-Desc" class="form-control" id="unit-Desc">
        </div>

      </div>
      <div class="modal-footer justify-content-between">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="createEditUnit">Save Edits</button>
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
  document.getElementById('createNewUnit').addEventListener('click', function () {

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
        var unitName = document.getElementById('unitName').value;
        var unitDesc = document.getElementById('unitDesc').value;

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
                xhr.send("unitName=" + encodeURIComponent(unitName) +
                          "&unitDesc=" + encodeURIComponent(unitDesc));
                   
            }
        });
    });
});
</script>

<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function () {
  document.getElementById('createEditUnit').addEventListener('click', function () {
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
                var unit_id = document.getElementById('unit_id').value;
                var unit_Name = document.getElementById('unit-Name').value;
                var unit_Desc = document.getElementById('unit-Desc').value;

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
               xhr.send("unit_id=" + encodeURIComponent(unit_id) +
                    "&unit-Name=" + encodeURIComponent(unit_Name) +
                    "&unit-Desc=" + encodeURIComponent(unit_Desc));

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
            var unitId = this.getAttribute('data-unit-id');

            // Set the supplier_id value in the URL
            var url = new URL(window.location.href);
            url.searchParams.set('unit_id', unitId);
            window.history.pushState({}, '', url);

            // Set the supplier_id value in the modal input field
            document.getElementById('unit_id').value = unitId;
            
            // Open the modal using Bootstrap's modal function
            $('#unit-edit').modal('show');
        });
    });
});
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
    var editButtons = document.querySelectorAll('.btn-edit');
    editButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            var unitId = button.getAttribute('data-unit-id');
            // Fetch category details using AJAX
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'fetch_unit_details.php?unit_id=' + unitId, true);
            xhr.onload = function () {
                if (xhr.status === 200) {
                    var data = JSON.parse(xhr.responseText);
                    // Check if data is valid
                    if (data && !data.error) {
                        // Populate modal fields with category details
                        document.getElementById('unit_id').value = unitId;
                        document.getElementById('unit-Name').value = data.unit_name;
                        document.getElementById('unit-Desc').value = data.unit_desc;
                        // Open the modal using Bootstrap's modal function
                        $('#unit-edit').modal('show');
                    } else {
                        console.error('Error: ' + (data ? data.error : 'Invalid response'));
                    }
                } else {
                    console.error('Error fetching unit details: ' + xhr.statusText);
                }
            };
            xhr.onerror = function () {
                console.error('Error fetching unit details.');
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
            var unitsId = this.getAttribute('data-units-id');
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
                    xhr.open("POST", "delete_units.php", true); // Specify your PHP file name or endpoint here
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
                                            text: "The selected supplier has been deleted.",
                                            icon: "success"
                                        });
                                    } else {
                                        // Display Swal.fire for deletion error
                                        Swal.fire({
                                            title: "Error!",
                                            text: "Failed to delete the supplier.",
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
                                    text: "Failed to delete the supplier due to a network error.",
                                    icon: "error"
                                });
                            }
                        }
                    };
                    xhr.send("delete_units=true&unit_id=" + encodeURIComponent(unitsId));
                }
            });
        });
    });
});
</script>
<script>
  $(document).ready(function() {
    $('#table-unit').DataTable({
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
                filename: 'Unit Master Data Export',
                title: 'Unit Master Data Export',
                exportOptions: {
                    columns: ':visible'
                }
            },
            {
                extend: 'excelHtml5',
                filename: 'Unit Master Data Export',
                title: 'Unit Master Data Export',
                exportOptions: {
                    columns: ':visible'
                }
            },
            {
                extend: 'pdfHtml5',
                filename: 'Unit Master Data Export',
                title: 'Unit Master Data Export',
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
$sql = "SELECT DISTINCT unit_name FROM master_unit";

$result = mysqli_query($conn, $sql);

if (!$result) {
    // If there's an error in the query, return an empty array
    echo json_encode([]);
    exit;
}

// Fetch category names and store them in an array
$unitNames = [];
while ($row = mysqli_fetch_assoc($result)) {
    $unitNames[] = $row['unit_name'];
}

// Close the database connection
mysqli_close($conn);

// Return the category names as a JSON array
$unitsNamesJSON = json_encode($unitNames);
?>

<script>
$(function () {
  // Initialize Select2 with data from PHP
  var unitNamesData = <?php echo $unitsNamesJSON; ?>;

  $('#unitName').select2({
    theme: 'bootstrap4',
    placeholder: 'Select or type Unit Name',
    allowClear: true,
    minimumInputLength: 1, // Minimum length of input before triggering AJAX
    data: unitNamesData, // Populate with existing categories
    tags: true // Allow custom tags (new categories)
  });
});
</script>

</body>
</html>