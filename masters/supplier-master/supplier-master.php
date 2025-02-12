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

if ($_SERVER["REQUEST_METHOD"] == "POST") {

if (isset($_POST['supplierName'], $_POST['supplierAddress'], $_POST['supplierGst'], $_POST['supplierPan'], $_POST['supplierContact'], $_POST['supplierCp'], $_POST['supplierTerms'])) {
        // Handle create supplier form submission
        // Process the form data
        $supplierName = $_POST['supplierName'];
        $supplierAddress = $_POST['supplierAddress'];
        $supplierGst = $_POST['supplierGst'];
        $supplierPan = $_POST['supplierPan'];
        $supplierContact = $_POST['supplierContact'];
        $supplierCp = $_POST['supplierCp'];
        $supplierTerms = $_POST['supplierTerms'];

        // Prepare and execute the insert query
        $sql = "INSERT INTO master_supplier (supplier_name, supplier_address, supplier_gst, supplier_pan, supplier_cont, supplier_cp, supplier_terms)
                VALUES ('$supplierName', '$supplierAddress', '$supplierGst', '$supplierPan', '$supplierContact', '$supplierCp', '$supplierTerms')";

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
    $supplier_id = isset($_POST['supplier_id']) ? intval($_POST['supplier_id']) : 0;
    $supplier_name = isset($_POST['supplier-name']) ? $_POST['supplier-name'] : '';
    $supplier_address = isset($_POST['supplier-address']) ? $_POST['supplier-address'] : '';
    $supplier_gst = isset($_POST['supplier-gst']) ? $_POST['supplier-gst'] : '';
    $supplier_pan = isset($_POST['supplier-pan']) ? $_POST['supplier-pan'] : '';
    $supplier_contact = isset($_POST['supplier-contact']) ? $_POST['supplier-contact'] : '';
    $supplier_cp = isset($_POST['supplier-cp']) ? $_POST['supplier-cp'] : '';
    $supplier_terms = isset($_POST['supplier-terms']) ? $_POST['supplier-terms'] : '';

    // Prepare and execute the update query using prepared statements
    $sql_update = "UPDATE master_supplier SET supplier_name=?, supplier_address=?, supplier_gst=?, supplier_pan=?, supplier_cont=?, supplier_cp=?, supplier_terms=? WHERE supplier_id=?";
    $stmt_main = $conn->prepare($sql_update);
    $stmt_main->bind_param("sssssssi", $supplier_name, $supplier_address, $supplier_gst, $supplier_pan, $supplier_contact, $supplier_cp, $supplier_terms, $supplier_id);

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
            <h3 class="m-0">MANAGE SUPPLIER</h3>
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
        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#supplier-create">
            Create Supplier
        </button>
    </div>
    <div class="card-body">
        <table class="table table-bordered table-striped" id="table-supplier">
            <thead>
                <tr>
                    <th style="text-align: center;">Supplier ID</th>
                    <th style="text-align: center;">Name</th>
                    <th style="text-align: center;">Address</th>
                    <th style="text-align: center;">GST No.</th>
                    <th style="text-align: center;">PAN No.</th>
                    <th style="text-align: center;">Contact No.</th>
                    <th style="text-align: center;">Contact Person</th>
                    <th style="text-align: center;">Terms of Payment</th>
                    <th style="text-align: center;">Edit</th>
                    <th style="text-align: center;">Delete</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql_fetch = mysqli_query($conn, "SELECT * FROM master_supplier");

                while ($row = mysqli_fetch_assoc($sql_fetch)) {
                ?>
                    <tr>
                        <td style="text-align: center;"><?php echo $row['supplier_id']; ?></td>
                        <td style="text-align: center;"><?php echo $row['supplier_name']; ?></td>
                        <td style="text-align: center;"><?php echo $row['supplier_address']; ?></td>
                        <td style="text-align: center;"><?php echo $row['supplier_gst']; ?></td>
                        <td style="text-align: center;"><?php echo $row['supplier_pan']; ?></td>
                        <td style="text-align: center;"><?php echo $row['supplier_cont']; ?></td>
                        <td style="text-align: center;"><?php echo $row['supplier_cp']; ?></td>
                        <td style="text-align: center;"><?php echo $row['supplier_terms']; ?></td>
                        <td style="text-align: center;"><button type="button" class="btn btn-xs btn-primary btn-block btn-edit" data-supplier-id="<?php echo $row['supplier_id']; ?>">Edit</button></td>
                        <td style="text-align: center;"><button type="button" class="btn btn-xs btn-danger btn-block btn-delete" data-suppliers-id="<?php echo $row['supplier_id']; ?>">Delete</button></td>                    
                    </tr>
                <?php
                }
                ?>
            </tbody>
        </table>
    </div>
</div>
      </div>
    </section>

<!-- MODAL FOR CREATE SUPPLIER -->
<div class="modal fade show" id="supplier-create" aria-modal="true" role="dialog" style="display: none;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Create Supplier</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">

                
                <div class="form-group">
          <label for="supplierName"> Name<span class="text-danger">*</span></label>
          <select name="supplierName" class="form-control" id="supplierName" required>
            <option value="">Select supplier</option>
          </select>
        </div>
                <div class="form-group">
                    <label for="supplierAddress">Address</label>
                    <textarea name="supplierAddress" class="form-control" id="supplierAddress"required></textarea>
                </div>

                <div class="form-group">
                    <label for="supplierGst">GST No.</label>
                    <input type="text" name="supplierGst" class="form-control" id="supplierGst"required>
                </div>

                <div class="form-group">
                    <label for="supplierPan">PAN No.</label>
                    <input type="text" name="supplierPan" class="form-control" id="supplierPan"required>
                </div>

                <div class="form-group">
                    <label for="supplierContact">Contact No.</label>
                    <input type="text" name="supplierContact" class="form-control" id="supplierContact"required>
                </div>

                <div class="form-group">
                    <label for="supplierCp">Contact Person</label>
                    <input type="text" name="supplierCp" class="form-control" id="supplierCp"required>
                </div>

                <div class="form-group">
                    <label for="supplierTerms">Terms of Payment</label>
                    <input type="text" name="supplierTerms" class="form-control" id="supplierTerms"required>
                </div>

            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary" id="createNewSupplier">Create</button>
            </div>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>


<!-- MODAL FOR EDIT SUPPLIER -->

<div class="modal fade show" id="supplier-edit" aria-modal="true" role="dialog" style="display: none;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Edit Supplier</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="supplier_id" class="form-control" id="supplier_id">

        <div class="form-group">
            <label for="supplier-name">Name</label>
            <input type="text" name="supplier-name" class="form-control" id="supplier-name">
        </div>

        <div class="form-group">
          <label for="supplier-address">Address</label>
          <textarea name="supplier-address" class="form-control" id="supplier-address"></textarea>
        </div>

        <div class="form-group">
          <label for="supplier-gst">GST No.</label>
          <input type="text" name="supplier-gst" class="form-control" id="supplier-gst">
        </div>

        <div class="form-group">
          <label for="supplier-pan">PAN No.</label>
          <input type="text" name="supplier-pan" class="form-control" id="supplier-pan">
        </div>

        <div class="form-group">
          <label for="supplier-contact">Contact No.</label>
          <input type="text" name="supplier-contact" class="form-control" id="supplier-contact">
        </div>

        <div class="form-group">
          <label for="supplier-cp">Contact Person</label>
          <input type="text" name="supplier-cp" class="form-control" id="supplier-cp">
        </div>

        <div class="form-group">
          <label for="supplier-terms">Terms of Payment</label>
          <input type="text" name="supplier-terms" class="form-control" id="supplier-terms">
        </div>

      </div>
      <div class="modal-footer justify-content-between">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="createEditSupplier">Save Edits</button>
      </div>
    </div>
    <!-- /.modal-content -->
  </div>
  <!-- /.modal-dialog -->
</div>
</div>

<!-- Include Footer File -->
<?php include_once ('../../../../include/php/footer.php') ?>

<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('createNewSupplier').addEventListener('click', function () {
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
                var supplierName = document.getElementById('supplierName').value;
                var supplierAddress = document.getElementById('supplierAddress').value;
                var supplierGst = document.getElementById('supplierGst').value;
                var supplierPan = document.getElementById('supplierPan').value;
                var supplierContact = document.getElementById('supplierContact').value;
                var supplierCp = document.getElementById('supplierCp').value;
                var supplierTerms = document.getElementById('supplierTerms').value;

                // Send AJAX request to insert_supplier.php
                var xhr = new XMLHttpRequest();
                xhr.open("POST", " ", true); // Update the URL to point to the correct PHP file
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.onreadystatechange = function () {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        // On success, show success message and reload the page
                        Swal.fire({
                            title: "Created!",
                            text: "New Supplier Created.",
                            icon: "success"
                        }).then(() => {
                            location.reload(); // Reload the page
                        });
                    }
                };
                xhr.send("supplierName=" + encodeURIComponent(supplierName) +
                    "&supplierAddress=" + encodeURIComponent(supplierAddress) +
                    "&supplierGst=" + encodeURIComponent(supplierGst) +
                    "&supplierPan=" + encodeURIComponent(supplierPan) +
                    "&supplierContact=" + encodeURIComponent(supplierContact) +
                    "&supplierCp=" + encodeURIComponent(supplierCp) +
                    "&supplierTerms=" + encodeURIComponent(supplierTerms));
            }
        });
    });
});
</script>

<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('createEditSupplier').addEventListener('click', function () {
        Swal.fire({
            title: "Are you sure?",
            text: "You won't be able to revert this!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, Save!"
        }).then((result) => {
            if (result.isConfirmed) {
                // Get input values
                var supplier_id = document.getElementById('supplier_id').value;
                var supplier_Name = document.getElementById('supplier-name').value;
                var supplier_Address = document.getElementById('supplier-address').value;
                var supplier_Gst = document.getElementById('supplier-gst').value;
                var supplier_Pan = document.getElementById('supplier-pan').value;
                var supplier_Contact = document.getElementById('supplier-contact').value;
                var supplier_Cp = document.getElementById('supplier-cp').value;
                var supplier_Terms = document.getElementById('supplier-terms').value;

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
                xhr.send("supplier_id=" + encodeURIComponent(supplier_id) +
                    "&supplier-name=" + encodeURIComponent(supplier_Name) +
                    "&supplier-address=" + encodeURIComponent(supplier_Address) +
                    "&supplier-gst=" + encodeURIComponent(supplier_Gst) +
                    "&supplier-pan=" + encodeURIComponent(supplier_Pan) +
                    "&supplier-contact=" + encodeURIComponent(supplier_Contact) +
                    "&supplier-cp=" + encodeURIComponent(supplier_Cp) +
                    "&supplier-terms=" + encodeURIComponent(supplier_Terms));
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
            var supplierId = this.getAttribute('data-supplier-id');

            // Set the supplier_id value in the URL
            var url = new URL(window.location.href);
            url.searchParams.set('supplier_id', supplierId);
            window.history.pushState({}, '', url);

            // Set the supplier_id value in the modal input field
            document.getElementById('supplier_id').value = supplierId;
            
            // Open the modal using Bootstrap's modal function
            $('#supplier-edit').modal('show');
        });
    });
});
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var editButtons = document.querySelectorAll('.btn-edit');
        editButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                var supplierId = button.getAttribute('data-supplier-id');
                // Fetch supplier details using AJAX
                var xhr = new XMLHttpRequest();
                xhr.open('GET', 'fetch_supplier_details.php?supplier_id=' + supplierId, true);
                xhr.onload = function () {
                    if (xhr.status === 200) {
                        var data = JSON.parse(xhr.responseText);
                        // Populate modal fields with supplier details
                        document.getElementById('supplier-name').value = data.supplier_name;
                        document.getElementById('supplier-address').value = data.supplier_address;
                        document.getElementById('supplier-gst').value = data.supplier_gst;
                        document.getElementById('supplier-pan').value = data.supplier_pan;
                        document.getElementById('supplier-contact').value = data.supplier_cont;
                        document.getElementById('supplier-cp').value = data.supplier_cp;
                        document.getElementById('supplier-terms').value = data.supplier_terms;
                    } else {
                        console.error('Error fetching supplier details: ' + xhr.statusText);
                    }
                };
                xhr.onerror = function () {
                console.error('Error fetching supplier details.');
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
            var supplierId = this.getAttribute('data-suppliers-id');
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
                    xhr.open("POST", "delete_supplier.php", true); // Endpoint for handling the deletion within the same file
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
                    xhr.send("delete_supplier=true&supplier_id=" + encodeURIComponent(supplierId));
                }
            });
        });
    });
});
</script>


<script>
  $(document).ready(function() {
    $('#table-supplier').DataTable({
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
                filename: 'Supplier Master Data Export',
                title: 'Supplier Master Data Export',
                exportOptions: {
                    columns: ':visible'
                }
            },
            {
                extend: 'excelHtml5',
                filename: 'Supplier Master Data Export',
                title: 'Supplier Master Data Export',
                exportOptions: {
                    columns: ':visible'
                }
            },
            {
                extend: 'pdfHtml5',
                filename: 'Supplier Master Data Export',
                exportOptions: {
                    columns: ':visible'
                },
                title: 'Supplier Master Data Export', 
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
$sql = "SELECT DISTINCT supplier_name FROM master_supplier";

$result = mysqli_query($conn, $sql);

if (!$result) {
    // If there's an error in the query, return an empty array
    echo json_encode([]);
    exit;
}

// Fetch category names and store them in an array
$supplierNames = [];
while ($row = mysqli_fetch_assoc($result)) {
    $supplierNames[] = $row['supplier_name'];
}

// Close the database connection
mysqli_close($conn);

// Return the category names as a JSON array
$supplierNamesJSON = json_encode($supplierNames);
?>

<script>
$(function () {
  // Initialize Select2 with data from PHP
  var supplierNamesData = <?php echo $supplierNamesJSON; ?>;

  $('#supplierName').select2({
    theme: 'bootstrap4',
    placeholder: 'Select or type category name',
    allowClear: true,
    minimumInputLength: 1, // Minimum length of input before triggering AJAX
    data: supplierNamesData, // Populate with existing categories
    tags: true // Allow custom tags (new categories)
  });
});
</script>
</body>
</html>