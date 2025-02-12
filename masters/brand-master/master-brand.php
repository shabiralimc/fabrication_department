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





$sql = "SELECT * FROM master_brand ORDER BY brand_name ASC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Fetch all rows
$row_sql = mysqli_fetch_all($result, MYSQLI_ASSOC);

?>

<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {

if (isset($_POST['brandName'], $_POST['brandDesc'])) {
        // Handle create unit form submission
        // Process the form data
        $brandName = $_POST['brandName'];
        $brandDesc = $_POST['brandDesc'];

        // Prepare and execute the insert query
        $sql = "INSERT INTO master_brand (brand_name,brand_desc)
                VALUES ('$brandName', '$brandDesc')";

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
    $brand_id   = isset($_POST['brand_id']) ? intval($_POST['brand_id']) : 0;
    $brand_Name = isset($_POST['brand-Name']) ? $_POST['brand-Name'] : '';
    $brand_Desc = isset($_POST['brand-Desc']) ? $_POST['brand-Desc'] : '';
    
    // Prepare and execute the update query using prepared statements
    $sql_update = "UPDATE master_brand SET brand_name=?, brand_desc=? WHERE brand_id=?";
    $stmt_main = $conn->prepare($sql_update);
    $stmt_main->bind_param("ssi", $brand_Name, $brand_Desc,$brand_id);

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
            <h3 class="m-0">MANAGE BRAND</h3>
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
      <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#brand-create">
        Create New Brand
      </button>
    </div>
    <div class="card-body">
      <table class="table table-bordered table-striped" id="table-brand">
        <thead>
          <tr>
            <th style="text-align: center;">Brand ID</th>
            <th style="text-align: center;">Brand Name</th>
            <th style="text-align: center;">Description</th>
            <th style="text-align: center;">Edit</th>
            <th style="text-align: center;">Delete</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($row_sql as $row): ?>

          <tr>
            <td style="text-align: center;"><?php echo $row['brand_id']; ?></td>
            <td style="text-align: center;"><?php echo $row['brand_name']; ?></td>
            <td style="text-align: center;"><?php echo $row['brand_desc']; ?></td>
            <td style="text-align: center; width:20%;"><button type="button" class="btn btn-xs btn-primary btn-block btn-edit" data-brand-id="<?php echo $row['brand_id']; ?>">Edit</button></td>
            <td style="text-align: center; width:20%;"><button type="button" class="btn btn-xs btn-danger btn-block btn-delete" data-brands-id="<?php echo $row['brand_id']; ?>">Delete</button></td>
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

<div class="modal fade show" id="brand-create" aria-modal="true" role="dialog" style="display: none;">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">Create brand</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">×</span>
        </button>
      </div>
      <div class="modal-body">

        <div class="form-group">
          <label for="brandName">Brand Name<span class="text-danger">*</span></label>
          <select name="brandName" class="form-control" id="brandName" required>
            <option value="">Select Unit</option>
          </select>
          <div id="validationFeedback" class="text-danger"></div>
        </div>
        
        <div class="form-group">
          <label for="brandDesc">Description</label>
          <input type="text" name="brandDesc" class="form-control" id="brandDesc">
        </div>

      </div>
      <div class="modal-footer justify-content-between">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="createNewbrand">Create</button>
      </div>
    </div>
    <!-- /.modal-content -->
  </div>
  <!-- /.modal-dialog -->
</div>

<!-- MODAL FOR EDIT brand -->

<div class="modal fade show" id="brand-edit" aria-modal="true" role="dialog" style="display: none;">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">Edit brand</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">×</span>
        </button>
      </div>
      <div class="modal-body">
      <input type="hidden" name="brand_id" class="form-control" id="brand_id">

        <div class="form-group">
          <label for="brand-Name">Brand Name</label>
          <input type="text" name="brand-Name" class="form-control" id="brand-Name">
          <div id="validationFeedbacks" class="text-danger"></div>
        </div>

        <div class="form-group">
          <label for="brand-Desc">Description</label>
          <input type="text" name="brand-Desc" class="form-control" id="brand-Desc">
        </div>
      </div>
      
      <div class="modal-footer justify-content-between">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="createEditbrand">Save Edits</button>
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
    document.getElementById('createNewbrand').addEventListener('click', function () {
        // Get input values
        var brandName = document.getElementById('brandName').value.trim();
        var brandDesc = document.getElementById('brandDesc').value.trim();

        // Check if the required fields are not empty
        if (brandName === "" ) {
            Swal.fire({
                title: "Required Fields Empty",
                text: "Please fill  the Brand Name.",
                icon: "warning",
                confirmButtonColor: "#3085d6",
                confirmButtonText: "OK"
            });
            return; // Stop further execution if validation fails
        }

        // Define a regex to match allowed characters (letters, numbers, spaces, hyphens, and slashes)
        var regex = /^[a-zA-Z0-9\s-\/]*$/;

        // Validate the brandName input
        if (!regex.test(brandName)) {
            Swal.fire({
                title: "Invalid Input",
                text: "Special characters are not allowed in the Brand Name.",
                icon: "error",
                confirmButtonColor: "#3085d6",
                confirmButtonText: "OK"
            });
            return; // Stop further execution if validation fails
        }

        // Show confirmation dialog if validation passes
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
                
                var xhr = new XMLHttpRequest();
                xhr.open("POST", "", true); // Update the URL to point to the correct PHP file
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.onreadystatechange = function () {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        // On success, show success message and reload the page
                        Swal.fire({
                            title: "Created!",
                            text: "New Brand Created.",
                            icon: "success"
                        }).then(() => {
                            location.reload(); // Reload the page
                        });
                    }
                };

                xhr.send("brandName=" + encodeURIComponent(brandName) +
                          "&brandDesc=" + encodeURIComponent(brandDesc));
            }
        });
    });
});
</script>

<?php

// Query to fetch existing category names from the master_supplier table
$sql = "SELECT DISTINCT brand_name FROM master_brand";

$result = mysqli_query($conn, $sql);

if (!$result) {
    // If there's an error in the query, return an empty array
    echo json_encode([]);
    exit;
}

// Fetch category names and store them in an array
$brandNames = [];
while ($row = mysqli_fetch_assoc($result)) {
    $brandNames[] = $row['brand_name'];
}

// Close the database connection
mysqli_close($conn);

// Return the category names as a JSON array
$brandsNamesJSON = json_encode($brandNames);
?>


<script>
$(document).ready(function() {
    var $brandNameSelect = $('#brandName');
    var $validationFeedback = $('#validationFeedback');
    var regex = /^[a-zA-Z0-9\s-\/]*$/; // Allow only letters, numbers, and spaces

    // Initialize Select2
    var brandNamesData = <?php echo $brandsNamesJSON; ?>;
    $brandNameSelect.select2({
        theme: 'bootstrap4',
        placeholder: 'Select or type Brand Name',
        allowClear: true,
        tags: true,
        data: brandNamesData.map(function(name) {
            return { id: name, text: name };
        })
    });

    // Live validation on input
    $brandNameSelect.on('select2:open', function() {
        var $searchField = $($('.select2-search__field')[0]);

        $searchField.on('input', function() {
            var value = $searchField.val();

            if (regex.test(value)) {
                $validationFeedback.text('');
                $brandNameSelect.removeClass('is-invalid');
            } else {
                $validationFeedback.text('Special characters are not allowed. Only letters and numbers.');
                $brandNameSelect.addClass('is-invalid');
            }
        });
    });
});
</script>

<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('createEditbrand').addEventListener('click', function () {
        // Get input values
        var brand_Name = document.getElementById('brand-Name').value;

        // Define a regex to match allowed characters (letters, numbers, and spaces)
        var regex = /^[a-zA-Z0-9\s-\/]*$/;

        // Validate the brand_Name input
        if (!regex.test(brand_Name)) {
            Swal.fire({
                title: "Invalid Input",
                text: "Special characters are not allowed in the Brand Name.",
                icon: "error",
                confirmButtonColor: "#3085d6",
                confirmButtonText: "OK"
            });
            return; // Stop further execution if validation fails
        }

        // Show confirmation dialog if validation passes
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
                // Get remaining input values
                var brand_id = document.getElementById('brand_id').value;
                var brand_Desc = document.getElementById('brand-Desc').value;

                // Send AJAX request to update the brand details
                var xhr = new XMLHttpRequest();
                xhr.open("POST", "", true); // Update the URL to point to the correct PHP file
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.onreadystatechange = function () {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        // On success, show success message and reload the page
                        Swal.fire({
                            title: "Saved!",
                            text: "Your edits saved successfully.",
                            icon: "success"
                        }).then(() => { 
                            location.reload(); // Reload the page
                        });
                    }
                };

                // Construct the data to be sent in the request
                xhr.send("brand_id=" + encodeURIComponent(brand_id) +
                         "&brand-Name=" + encodeURIComponent(brand_Name) +
                         "&brand-Desc=" + encodeURIComponent(brand_Desc));
            }
        });
    });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var brandNameInput = document.getElementById('brand-Name');
    var validationFeedbacks = document.getElementById('validationFeedbacks');
    
    brandNameInput.addEventListener('input', function() {
        var value = brandNameInput.value;
        var regex = /^[a-zA-Z0-9\s-\/]*$/; // Allow only letters, numbers, and spaces

        if (regex.test(value)) {
            validationFeedbacks.textContent = '';
            brandNameInput.classList.remove('is-invalid');
        } else {
            validationFeedbacks.textContent = 'Special characters are not allowed.Only allowed letters and Numbers';
            brandNameInput.classList.add('is-invalid');
        }
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
            var brandId = this.getAttribute('data-brand-id');

            // Set the supplier_id value in the URL
            var url = new URL(window.location.href);
            url.searchParams.set('unit_id', brandId);
            window.history.pushState({}, '', url);

            // Set the supplier_id value in the modal input field
            document.getElementById('brand_id').value = brandId;
            
            // Open the modal using Bootstrap's modal function
            $('#brand-edit').modal('show');
        });
    });
});
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
    var editButtons = document.querySelectorAll('.btn-edit');
    editButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            var brandId = button.getAttribute('data-brand-id');
            // Fetch category details using AJAX
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'fetch_brand_details.php?brand_id=' + brandId, true);
            xhr.onload = function () {
                if (xhr.status === 200) {
                    var data = JSON.parse(xhr.responseText);
                    // Check if data is valid
                    if (data && !data.error) {
                        // Populate modal fields with category details
                        document.getElementById('brand_id').value = brandId;
                        document.getElementById('brand-Name').value = data.brand_name;
                        document.getElementById('brand-Desc').value = data.brand_desc;
                        // Open the modal using Bootstrap's modal function
                        $('#brand-edit').modal('show');
                    } else {
                        console.error('Error: ' + (data ? data.error : 'Invalid response'));
                    }
                } else {
                    console.error('Error fetching brand details: ' + xhr.statusText);
                }
            };
            xhr.onerror = function () {
                console.error('Error fetching brand details.');
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
            var brandsId = this.getAttribute('data-brands-id');
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
                    // Send AJAX request to delete the brand
                    var xhr = new XMLHttpRequest();
                    xhr.open("POST", "delete_brands.php", true); // Specify your PHP file name or endpoint here
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
                                                text: "The selected brand has been deleted.",
                                                icon: "success"
                                            }).then((result) => {
                                                if (result.isConfirmed) {
                                                    // Reload the page after the alert is confirmed
                                                    location.reload();
                                                }
                                            });
                                            
                                    } else {
                                        // Display Swal.fire for deletion error
                                        Swal.fire({
                                            title: "Error!",
                                            text: "Failed to delete the brand.",
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
                                    text: "Failed to delete the brand due to a network error.",
                                    icon: "error"
                                });
                            }
                        }
                    };
                    xhr.send("delete_brands=true&brand_id=" + encodeURIComponent(brandsId));
                }
            });
        });
    });
});
</script>
<script>
  $(document).ready(function() {
    $('#table-brand').DataTable({
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
                filename: 'Brand Master Data Export',
                title: 'Brand Master Data Export',
                exportOptions: {
                    columns: ':visible'
                }
            },
            {
                extend: 'excelHtml5',
                filename: 'Brand Master Data Export',
                title: 'Brand Master Data Export',
                exportOptions: {
                    columns: ':visible'
                }
            },
            {
                extend: 'pdfHtml5',
                filename: 'Brand Master Data Export',
                title: 'Brand Master Data Export',
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