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

?>

<!-- Include Header File -->
<?php include_once ('../../../../include/php/header.php') ?>

<!-- Include Sidebar File -->
<?php include_once ('../../../../include/php/sidebar-fab.php') ?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h3 class="m-0">MANAGE RETURNS</h3>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="container">
                <div class="card card-info card-outline">
                    <div class="card-header">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="mat_pur_number">Select Purchase Number</label><br>
                                    <select name="mat_pur_number" id="mat_pur_number" class="form-control mat_pur_number">
                                        <option value="" selected disabled>Select One</option>
                                        <!-- Add PO options here -->
                                    </select><br>
                                    <div class="col-md-10 col-xs-12">
                                            <div class="form-group">
                                                <label for="mat_pur_godowns" class="form-label">Godown</label>
                                                <input type="text"style="width: 200px;" name="mat_pur_godowns" class="form-control mat_pur_godowns" id="mat_pur_godowns" readonly>
                                            </div>
                                        </div>
                                    <button type="button" id="ok" name="ok" class="btn btn-primary">OK</button>
                                </div>
                               
                            </div>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
</section>

        <div id="dataContainer"></div>
        <div id="selectedDataContainer"></div>

</div>

<!-- Include Footer File -->
<?php include_once ('../../../../include/php/footer.php') ?>

<?php
// Include the database connection file
include_once("../../../../include/php/connect.php");

// Query to fetch existing PO from the fab_po_create table
$sql = "SELECT DISTINCT mat_pur_number FROM mat_pur_item";

$result = mysqli_query($conn, $sql);

if (!$result) {
    // If there's an error in the query, return an empty array
    echo json_encode([]);
    exit;
}

// Fetch PO and store them in an array
$purchaseNumbers = [];
while ($row = mysqli_fetch_assoc($result)) {
    $purchaseNumbers[] = $row['mat_pur_number'];
}

// Return the PO as a JSON array
$purchaseNumberJSON = json_encode($purchaseNumbers);
?>

<script>
$(function () {
  // Initialize Select2 with data from PHP
  var purchaseNumberData = <?php echo $purchaseNumberJSON; ?>;

  $('#mat_pur_number').select2({
    theme: 'bootstrap4',
    placeholder: 'Select One',
    allowClear: true,
    minimumInputLength: 1, // Minimum length of input before triggering AJAX
    data: purchaseNumberData, // Populate with existing PO
    tags: true // Allow custom tags (new PO)
  });
});
</script>

<script>
    $(document).ready(function(){
        // When OK button is clicked
        $('#ok').on('click', function() {
            // Fetch data from the database based on the selected purchase order
            var selectedPU = $('#mat_pur_number').val();
            if (selectedPU) {
                // Perform AJAX request to fetch data
                $.ajax({
                    url: 'fetch_pur_items.php', // Assuming fetch_data.php is in the same directory
                    method: 'POST',
                    data: {mat_pur_number: selectedPU},
                    success: function(response) {
                        // Display the fetched data in the dataContainer div
                        $('#dataContainer').html(response);
                     },
                     error: function(xhr, status, error) {
                        // Handle errors
                        console.error(xhr.responseText);
                    }
                });
            } else {
                alert('Please select a purchase Number');
            }
        });
    });
</script>

<script>
$(document).ready(function() {
    $('#ok').click(function() {
        var mat_pur_number = $('#mat_pur_number').val();

        if (mat_pur_number) {
            $.ajax({
                type: 'POST',
                url: 'fetch_godown.php',
                data: { mat_pur_number: mat_pur_number },
                success: function(response) {
                    var data = JSON.parse(response);
                    if (data.success) {
                        $('#mat_pur_godowns').val(data.mat_pur_godowns);
                    } else {
                        alert(data.message);
                    }
                }
            });
        } else {
            alert("Please select a PO number.");
        }
    });
});
</script>

<script>
    // Handle Insert button click
    $(document).on('click', 'input[name="insert_data"]', function() {
            var selectedIds = [];
            $('input[name="checkbox[]"]:checked').each(function() {
                selectedIds.push($(this).val());
            });

            if (selectedIds.length > 0) {
                // Perform AJAX request to fetch and display selected rows
                $.ajax({
                    url: 'insert_pur_items.php', // Assuming insert_data.php handles the selected data
                    method: 'POST',
                    data: {mat_pur_item_id: selectedIds},
                    success: function(response) {
                        // Display the selected data in the selectedDataContainer div
                        $('#selectedDataContainer').html(response);
                    },
                    error: function(xhr, status, error) {
                        // Handle errors
                        console.error(xhr.responseText);
                    }
                });
            } else {
                alert('Please select at least one row');
            }
        });
</script>

<script>
// Bind the save button click event
$(document).on('click', '#save', function() {
    Swal.fire({
        title: 'Are you sure?',
        text: "Do you want to return this?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, return it!'
    }).then((result) => {
        if (result.isConfirmed) {
            var formData = $('#editForm').serialize();
            // Perform AJAX request to save the edited data
            $.ajax({
                url: 'save_returns.php', // Assuming save_data.php handles the saving of edited data
                method: 'POST',
                data: formData,
                success: function(response) {
                    Swal.fire(
                        'Saved!',
                        'Your data has been returned.',
                        'success'
                    ).then(() => {
                        location.reload(); // Reload the page after the alert
                    });
                },
                error: function(xhr, status, error) {
                    // Handle errors
                    Swal.fire(
                        'Error!',
                        'An error occurred while returning your data.',
                        'error'
                    );
                    console.error(xhr.responseText);
                }
            });
        }
    });
});

</script>
</body>
</html>
