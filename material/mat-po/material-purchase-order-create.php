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

function generatePOId($conn) {
    // Query the database to get the current maximum PO number
    $query = "SELECT MAX(mat_po_number) AS max_po_number FROM mat_pos";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $maxId = $row['max_po_number'];

        // Determine the starting point
        $startNumber = '0001';

        // If no PO numbers exist yet, start from the specified value
        if ($maxId === null) {
            return 'FAB-MAT-PO-' . $startNumber;
        }

        // Extract the numeric part from the current max ID
        $numericPart = (int)substr($maxId, -4) + 1;

        // Generate the next PO number by using the incremented numeric part
        $nextId = 'FAB-MAT-PO-' . str_pad($numericPart, 4, '0', STR_PAD_LEFT);

        return $nextId;
    } else {
        // Handle errors or initial case when no PO numbers exist
        return 'FAB-MAT-PO-0001';
    }
}

$newPOId = generatePOId($conn);
?>

<?php

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // Check if all required fields are set
  if (isset($_POST['mat_po_number'], $_POST['mat_po_date'], $_POST['mat_po_supplier'], $_POST['mat_po_godown'], $_POST['po_remarks'], $_POST['mat_po_totalamt'], $_POST['mat_po_gst_amnt'], $_POST['mat_po_other_exp'], $_POST['mat_po_grant_total'])) {

      // Sanitize input values
      $mat_po_number = htmlspecialchars($_POST['mat_po_number']);
      $mat_po_date = htmlspecialchars($_POST['mat_po_date']);
      $mat_po_supplier = htmlspecialchars($_POST['mat_po_supplier']);
      $mat_po_godown = htmlspecialchars($_POST['mat_po_godown']);
      $po_remarks = htmlspecialchars($_POST['po_remarks']);
      $mat_po_totalamt = $_POST['mat_po_totalamt'];
      $mat_po_gst_amnt = $_POST['mat_po_gst_amnt'];
      $mat_po_other_exp = $_POST['mat_po_other_exp'];
      $mat_po_grant_total = $_POST['mat_po_grant_total'];

      // Convert mat_po_other_exp from JSON array to string like "travel:100,food:200"
      $mat_po_other_exp_array = json_decode($mat_po_other_exp, true);
      $mat_po_other_exp_string = '';
      if ($mat_po_other_exp_array) {
          $other_exp_parts = [];
          foreach ($mat_po_other_exp_array as $exp) {
              $other_exp_parts[] = $exp['value'];  // Use the 'value' part of each tag
          }
          $mat_po_other_exp_string = implode(',', $other_exp_parts);
      }

      // Prepare and execute the insert statement
      $insert_sql = "INSERT INTO mat_pos (mat_po_number, mat_po_date, mat_po_supplier, mat_po_godown, po_remarks, mat_po_totalamt, mat_po_gst_amnt, mat_po_other_exp, mat_po_grant_total) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
      $stmt = $conn->prepare($insert_sql);

      if ($stmt === false) {
          die("Error preparing statement: " . $conn->error);
      }

      // Bind parameters to the statement
      $stmt->bind_param("sssssddsd", $mat_po_number, $mat_po_date, $mat_po_supplier, $mat_po_godown, $po_remarks, $mat_po_totalamt, $mat_po_gst_amnt, $mat_po_other_exp_string, $mat_po_grant_total);

      // Execute the statement
      if ($stmt->execute()) {
          // echo "Purchase order inserted successfully.";
      } else {
          echo "Error inserting data: " . $stmt->error;
      }

      // Close the statement
      $stmt->close();
  } else {
      echo "Error: Required fields are missing.";
  }

    // Check if all required fields are set for materials
    if (isset($_POST['mat_po_item_name'], $_POST['perUnit'], $_POST['mat_po_item_quan'], $_POST['mat_po_item_price'])) {
        // Retrieve and sanitize form data for materials
        $mat_po_number = $_POST['mat_po_number'];
        $mat_po_item_names = $_POST['mat_po_item_name'];
        $perUnits = $_POST['perUnit'];
        $mat_po_item_quans = $_POST['mat_po_item_quan'];
        $mat_po_item_prices = $_POST['mat_po_item_price'];

        // Prepare SQL for inserting material items
        $insert_item_sql = "INSERT INTO mat_po_item (mat_po_item_ponum, mat_po_item_name, perUnit, mat_po_item_quan, mat_po_item_price) VALUES (?, ?, ?, ?, ?)";
        $stmts = $conn->prepare($insert_item_sql);

        if ($stmts === false) {
            die('Prepare failed: ' . htmlspecialchars($conn->error));
        }

        // Loop through each item and insert into the mat_po_item table
        for ($i = 0; $i < count($mat_po_item_names); $i++) {
            // Bind the purchase order number and other item data
            $stmts->bind_param("ssddd", $mat_po_number, $mat_po_item_names[$i], $perUnits[$i], $mat_po_item_quans[$i], $mat_po_item_prices[$i]);

            if (!$stmts->execute()) {
                echo "Error: " . $stmts->error;
            }
        }

        $stmts->close();
    }

}
?>


<?php
function getMaterialOptions($conn) {
  // Query to fetch unit names from the master_unit table
  $query = "SELECT DISTINCT materialName FROM material_master_creates";
  $result = mysqli_query($conn, $query);

  // Check if the query was successful
  if ($result && mysqli_num_rows($result) > 0) {
      // Loop through the result set and generate the options
      while ($row = mysqli_fetch_assoc($result)) {
          echo '<option value="' . htmlspecialchars($row['materialName']) . '">' . htmlspecialchars($row['materialName']) . '</option>';
      }
  } else {
      // Handle case when no data is returned or query fails
      echo '<option value="">No Material Name available</option>';
  }
}
?>

<?php
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

    if (isset($_POST['fullName'], $_POST['godownName'], $_POST['godownDesc'])) {
        // Handle create unit form submission
        // Process the form data
        $fullName = $_POST['fullName']; // Convert to lowercase
        $godownName = strtolower($_POST['godownName']);
        $godownDesc = $_POST['godownDesc'];

        // Prepare and execute the insert query
        $sql = "INSERT INTO master_godown (fullName, godownName, godownDesc)
                VALUES ('$fullName', '$godownName', '$godownDesc')";

        if ($conn->query($sql) === TRUE) {
            echo "New Godown created successfully";
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
        exit; // Add this to prevent the rest of the page from executing
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

        <form method="POST" id="purchaseOrderForm">
          <div class="card card-info card-outline">
            <div class="card-header">
              <h3 class="m-0">CREATE NEW FABRICATION PURCHASE ORDER</h3>
            </div>
            <div class="card-body">
              <div class="row">
                <div class="col-md-4">
                  <div class="form-group">
                    <label for="mat_po_number">PO Number<span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="mat_po_number" id="mat_po_number" value="<?php echo $newPOId; ?>" readonly required>
                  </div>

                  <div class="form-container">
    <div class="form-group">
        <label for="mat_po_date">PO Date<span class="text-danger">*</span></label>
        <input type="date" class="form-control mat_po_date" name="mat_po_date"id="mat_po_date" required>
    </div>

                  <div class="form-group">
                    <label for="mat_po_supplier">Supplier Name<span class="text-danger">*</span></label>
                    <button style="width:25px; height:25px; border-radius:100px;" class="btn btn-primary btn-xs" title="Add New Client" data-toggle="modal" data-target="#supplier-create">+</button>
                    <select name="mat_po_supplier" id="smat_po_supplier" class="form-control" required>
                      <option value="" disabled selected>Choose Supplier</option>
                      <?php

                      // SQL query to fetch supplier names from master_supplier table
                      $sql = "SELECT DISTINCT supplier_name FROM master_supplier";

                      // Execute query
                      $result = $conn->query($sql);

                      // Check if query was successful
                      if ($result && $result->num_rows > 0) {
                          // Loop through each row of the result set
                          while ($row = $result->fetch_assoc()) {
                              // Output an option for each supplier name
                              echo '<option value="' . $row['supplier_name'] . '">' . $row['supplier_name'] . '</option>';
                          }
                      } else {
                          // If no suppliers found, display a message
                          echo '<option disabled>No suppliers found</option>';
                      }
                      ?>
                    </select>
                    
                  </div>

                  <div class="form-group">
        <label for="mat_po_godown">Godown Name<span class="text-danger">*</span></label>
        <button style="width:25px; height:25px; border-radius:100px;" class="btn btn-primary btn-xs" title="Add New Godown" data-toggle="modal" data-target="#godown-create">+</button>
        <select name="mat_po_godown" class="form-control mat_po_godown"id="mat_po_godown" required>
            <option value="" disabled selected>Choose Godown</option>
            <?php
            // SQL query to fetch godown names from master_godown table
            $sql = "SELECT DISTINCT godownName FROM master_godown";
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo '<option value="' . $row['godownName'] . '">' . $row['godownName'] . '</option>';
                }
            } else {
                echo '<option disabled>No Godown found</option>';
            }
            ?>
        </select>
    </div>

                  <div class="form-group">
                    <label for="po_remarks">Remarks</label>
                    <textarea class="form-control" name="po_remarks" id="po_remarks" ></textarea>
                  </div>

                </div>
              </div>
            </div>
          </div>

          <div class="card card-info card-outline">
            <div class="card-header">
              <div class="row">
                <div class="col-md-6">
                  <h4 class="m-0">PURCHASE DETAILS</h4>
                </div>
                
              </div>
            </div>

            <div class="card-body duplicatecontainer" id="materialContainer">
              <div class="form-group purchaseRow" id="purchaseRow">
                <div class="row materialRow" style="border-bottom: 2px solid rgba(0,0,0,.125);">
                  
                <div class="col-lg-2 col-md-4">
                <div class="form-group">
        <label for="mat_po_item_name" class="form-label">Material Name</label><span style="color:red">*</span>
        <select name="mat_po_item_name[]" class="form-control mat_name"id="mat_po_item_name" required>
            <option value="" selected disabled>Choose a Material</option>
            <!-- Options will be dynamically loaded here via AJAX -->
        </select>
    </div>
                </div>

                  <div class="col-lg-2 col-md-4">
                    <div class="form-group">
                      <label for="materialCategory" class="form-label">Material Category</label>
                      <input type="text" class="form-control materialCategory" name="materialCategory[]" placeholder="Material Category" readonly>
                    </div>
                  </div>

                  <div class="col-lg-2 col-md-4">
                    <div class="form-group">
                      <label for="materialBrand" class="form-label">Material Brand</label>
                      <input type="text" class="form-control materialBrand" name="materialBrand[]" placeholder="Material Brand" readonly>
                    </div>
                  </div>

                  <div class="col-lg-2 col-md-4">
                    <div class="form-group">
                      <label for="mat_po_item_quan" class="form-label">Material Quantity</label><span style="color:red">*</span>
                      <input type="number" class="form-control mat_po_item_quan" name="mat_po_item_quan[]" placeholder="Material Quantity" required>
                    </div>
                  </div>

                  <div class="col-lg-2 col-md-4">
                    <div class="form-group">
                      <label for="materialUnit" class="form-label">Material Unit</label>
                      <input type="text" class="form-control materialUnit" name="materialUnit[]" placeholder="Material Unit" readonly>
                    </div>
                  </div>

                  <div class="col-lg-2 col-md-4">
                    <div class="form-group">
                      <label for="alternativeUnit" class="form-label">Alternative Unit</label>
                      <select id="alternativeUnit" class="form-control alternativeUnit" name="alternativeUnit[]">
                        <option value="" selected disabled>Select alternative Unit</option>
                      </select>
                      <input type="hidden"class ="alternativeUnitvalue"name="alternativeUnitvalue[]"id="alternativeUnitvalue"value="">
                    </div>
                  </div>

                  <div class="col-lg-2 col-md-4">
                    <div class="form-group">
                      <label for="convertedQuantity" class="form-label">Converted Quantity</label>
                      <input type="number" class="form-control convertedQuantity"id="convertedQuantity" name="convertedQuantity[]" placeholder="Converted Quantity"readonly >
                      </div>
                  </div>

                  <div class="col-lg-1 col-md-2">
                    <div class="form-group">
                      <label for="perUnit" class="form-label">Per Unit</label><span style="color:red">*</span>
                      <input type="number" class="form-control perUnit" name="perUnit[]" placeholder="Per Unit" required>
                    </div>
                  </div>

                  <div class="col-lg-2 col-md-4">
                    <div class="form-group">
                      <label for="mat_po_item_price" class="form-label">Material Total</label>
                      <input type="number" class="form-control mat_po_item_price" name="mat_po_item_price[]"id="mat_po_item_price" placeholder="Material Total" readonly>
                    </div>
                  </div>

                  <div class="form-group">
        <label for="current_stock">Current Stock</label>
        <input type="text" class="form-control current_stock"name="current_stock[]"id="current_stock" placeholder="Current Stock" readonly>
    </div>

                  <div class="col-lg-1 col-md-2" style="margin-top: 32px;">
                      <button type="button" class="btn btn-danger btn-xs deleteCurrRow">&nbsp;X&nbsp;</button>
                  </div>
                </div>
              </div>

              
            </div>
            <div class="card-footer">
              <button type="button" class="btn btn-primary duplicateButton" id="duplicateButton">Duplicate Row</button>
            </div>
            

          <div class="card card-info card-outline">
            <div class="card-header">
              <div class="row">
                <div class="col-md-6">
                  <h4 class="m-0">PURCHASE TOTALS</h4>
                </div>
              </div>
            </div>

            <div class="card-body">
              <div class="row">
                <div class="col-lg-2 col-md-4">
                  <div class="form-group">
                    <label>Total Amount</label>
                    <input type="number" step="0.01" class="form-control mat_po_totalamt" name="mat_po_totalamt" id="mat_po_totalamt" placeholder="Material Total"readonly required oninput="calculateTotalAmount()">
                  </div>
                </div>
                
                <div class="col-lg-2 col-md-4">
                  <div class="form-group">
                    <label>GST Amount</label>
                    <input type="number" step="0.01" class="form-control" name="mat_po_gst_amnt" id="mat_po_gst_amnt" placeholder="GST Amount" >
                  </div>
                </div>
                <div class="col-lg-6 col-md-4">
                  <div class="form-group">
                    <label>Other Expense</label><span style="color:red">*</span>
                    <input type="text" class="form-control mat_po_other_exp"style="width: 200px;" name="mat_po_other_exp" id="mat_po_other_exp" placeholder="Other Expense"/>
                  </div>
                </div>
                <div class="col-lg-2 col-md-4">
                  <div class="form-group">
                    <label>Grand Total</label>
                    <input type="number" step="0.01" class="form-control" name="mat_po_grant_total" id="mat_po_grant_total" placeholder="Grand Total"readonly>
                  </div>
                </div>
              </div>
            </div>
             <center>
            <div class="card-footer">
              <div class="row">
                <div class="col-md-3">
                  <button type="submit" class="btn btn-primary" name="savePO" id="savePO">Save Purchase Order</button>
                </div>
                <div class="col-md-3"></div>
                <div class="col-md-3"></div>
                <div class="col-md-3">
                  <button type="button" class="btn btn-danger" id="closeWsave">Close Without Save</button>
                </div>
              </div>
            </div>
            </center>
          </div>
        </form>
      </div>
    </section>
</div>

<!-- --------------------------------------------------------------------------------------
  -------------------------- YOUR BODY CONTENT ENDS HERE ----------------------------------
  ------------------------------------------------------------------------------------- -->

      </div>
      
    </section>
  </div>

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
          <label for="supplierName">Name</label>
          <select name="supplierName" class="form-control" id="supplierName" required>
            <option value="">Select supplier</option>
          </select>
          <div id="validationFeedback" class="text-danger"></div>
        </div>

        <div class="form-group">
          <label for="supplierAddress">Address</label>
          <textarea name="supplierAddress" class="form-control" id="supplierAddress"></textarea>
        </div>

        <div class="form-group">
          <label for="supplierGst">GST No.</label>
          <input type="text" name="supplierGst" class="form-control" id="supplierGst">
        </div>

        <div class="form-group">
          <label for="supplierPan">PAN No.</label>
          <input type="text" name="supplierPan" class="form-control" id="supplierPan">
        </div>

        <div class="form-group">
          <label for="supplierContact">Contact No.</label>
          <input type="text" name="supplierContact" class="form-control" id="supplierContact">
        </div>

        <div class="form-group">
          <label for="supplierCp">Contact Person</label>
          <input type="text" name="supplierCp" class="form-control" id="supplierCp">
        </div>

        <div class="form-group">
          <label for="supplierTerms">Terms of Payment</label>
          <input type="text" name="supplierTerms" class="form-control" id="supplierTerms">
        </div>

      </div>
      <div class="modal-footer justify-content-between">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="createNewSupplier">Create</button>
      </div>
    </div>
    <!-- /.modal-content -->
  </div>
  <!-- /.modal-dialog -->
</div>

<!-- MODAL FOR CREATE GODOWN -->

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
          <label for="fullName">Godown Name<span class="text-danger">*</span></label>
          <select name="fullName" class="form-control" id="fullName" required>
            <option value="">Select Godown</option>
          </select>
          <div id="validationFeedbacks" class="text-danger"></div>
        </div>

      <div class="form-group">
          <label for="godownName">Short Name<span class="text-danger">*</span></label>
          <select name="godownName" class="form-control" id="godownName" required>
            <option value="">Select Godown</option>
          </select>
          <div id="validationFeedback" class="text-danger"></div>
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


<!-- Include Footer File -->
<?php include_once ('../../../../include/php/footer.php') ?>


<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('createNewSupplier').addEventListener('click', function () {
        // Get input values
        var supplierName = document.getElementById('supplierName').value;
        var supplierAddress = document.getElementById('supplierAddress').value;
        var supplierGst = document.getElementById('supplierGst').value;
        var supplierPan = document.getElementById('supplierPan').value;
        var supplierContact = document.getElementById('supplierContact').value;
        var supplierCp = document.getElementById('supplierCp').value;
        var supplierTerms = document.getElementById('supplierTerms').value;

        // Define a regex to match allowed characters (letters, numbers, spaces, and some punctuation)
        var regex = /^[a-zA-Z0-9\s-\/]*$/;

        // Validate each input field
        if (!regex.test(supplierName)) {
            Swal.fire({
                title: "Error!",
                text: "Please enter valid details without special characters.",
                icon: "error"
            });
            return; // Prevent further execution if validation fails
        }

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

// Return the category names as a JSON array
$supplierNamesJSON = json_encode($supplierNames);
?>

<script>
$(document).ready(function() {
    var $supplierNameSelect = $('#supplierName');
    var $validationFeedback = $('#validationFeedback');
    var regex = /^[a-zA-Z0-9\s-\/]*$/; // Allow only letters, numbers, and spaces

    // Initialize Select2
    var supplierNamesData = <?php echo $supplierNamesJSON; ?>;
    $supplierNameSelect.select2({
        theme: 'bootstrap4',
        placeholder: 'Select or type Supplier Name',
        allowClear: true,
        tags: true,
        data: supplierNamesData.map(function(name) {
            return { id: name, text: name };
        })
    });

    // Live validation on input
    $supplierNameSelect.on('select2:open', function() {
        var $searchField = $($('.select2-search__field')[0]);

        $searchField.on('input', function() {
            var value = $searchField.val();

            if (regex.test(value)) {
                $validationFeedback.text('');
                $supplierNameSelect.removeClass('is-invalid');
            } else {
                $validationFeedback.text('Special characters are not allowed. Only letters and numbers.');
                $supplierNameSelect.addClass('is-invalid');
            }
        });
    });
});
</script>

<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('createNewgodown').addEventListener('click', function () {
        var fullName = document.getElementById('fullName').value.trim();
        var godownName = document.getElementById('godownName').value.trim();
        var godownDesc = document.getElementById('godownDesc').value.trim();

        // Define a regex to match allowed characters (letters, numbers, and spaces)
        var regex = /^[a-zA-Z0-9\s-\/]*$/;

        // Validate the inputs
        if (fullName === '' || godownName === '') {
            Swal.fire({
                title: "Missing Required Fields",
                text: "Please fill in all required fields.",
                icon: "error",
                confirmButtonColor: "#3085d6",
                confirmButtonText: "OK"
            });
        } else if (!regex.test(godownName) || !regex.test(fullName)) {
            Swal.fire({
                title: "Invalid Input",
                text: "Special characters are not allowed. Only letters, numbers, and spaces are allowed.",
                icon: "error",
                confirmButtonColor: "#3085d6",
                confirmButtonText: "OK"
            });
        } else {
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
                            Swal.fire({
                                title: "Created!",
                                text: "New Unit Created.",
                                icon: "success"
                            }).then(() => {
                                location.reload(); // Reload the page
                            });
                        }
                    };
                    xhr.send("fullName=" + encodeURIComponent(fullName) +
                              "&godownName=" + encodeURIComponent(godownName) +
                              "&godownDesc=" + encodeURIComponent(godownDesc));
                }
            });
        }
    });
});
</script>


<?php

// Query to fetch existing category names from the master_supplier table
$sqls = "SELECT DISTINCT godownName,fullName FROM master_godown";

$results = mysqli_query($conn, $sqls);

if (!$results) {
    // If there's an error in the query, return an empty array
    echo json_encode([]);
    exit;
}

// Fetch category names and store them in an array
$godownNames = [];
$fullNames = [];

while ($row = mysqli_fetch_assoc($results)) {
    $godownNames[] = $row['godownName'];
    $fullNames[] = $row['fullName'];
}


// Return the category names as a JSON array
$godownsNamesJSON = json_encode($godownNames);
$fullNamesJSON = json_encode($fullNames);

?>

<script>
$(document).ready(function() {
    var $godownNameSelect = $('#godownName');
    var $validationFeedback = $('#validationFeedback');
    var regex = /^[a-zA-Z0-9\s-\/]*$/; // Allow only letters, numbers, and spaces

    // Initialize Select2
    var godownNamesData = <?php echo $godownsNamesJSON; ?>;
    $godownNameSelect.select2({
        theme: 'bootstrap4',
        placeholder: 'Select or type Godown Name',
        allowClear: true,
        tags: true,
        data: godownNamesData.map(function(name) {
            return { id: name, text: name };
        })
    });

    // Live validation on input
    $godownNameSelect.on('select2:open', function() {
        var $searchField = $($('.select2-search__field')[0]);

        $searchField.on('input', function() {
            var value = $searchField.val();

            if (regex.test(value)) {
                $validationFeedback.text('');
                $godownNameSelect.removeClass('is-invalid');
            } else {
                $validationFeedback.text('Special characters are not allowed. Only letters and numbers.');
                $godownNameSelect.addClass('is-invalid');
            }
        });
    });
});
</script>

<script>
$(document).ready(function() {
    var $fullNameSelect = $('#fullName');
    var $validationFeedbacks = $('#validationFeedbacks');
    var regex = /^[a-zA-Z0-9\s-\/]*$/; // Allow only letters, numbers, and spaces

    // Initialize Select2
    var fullNamesData = <?php echo $fullNamesJSON; ?>;
    $fullNameSelect.select2({
        theme: 'bootstrap4',
        placeholder: 'Select or type Full Name',
        allowClear: true,
        tags: true,
        data: fullNamesData.map(function(name) {
            return { id: name, text: name };
        })
    });

    // Live validation on input
    $fullNameSelect.on('select2:open', function() {
        var $searchField = $($('.select2-search__field')[0]);

        $searchField.on('input', function() {
            var value = $searchField.val();

            if (regex.test(value)) {
                $validationFeedbacks.text('');
                $fullNameSelect.removeClass('is-invalid');
            } else {
                $validationFeedbacks.text('Special characters are not allowed. Only letters and numbers.');
                $fullNameSelect.addClass('is-invalid');
            }
        });
    });
});
</script>

<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('purchaseOrderForm');

    // Prevent form from submitting by default
    form.addEventListener('submit', function (e) {
        e.preventDefault();
    });

    // Save PO event listener
    document.getElementById('savePO').addEventListener('click', function () {
        const requiredFields = form.querySelectorAll('[required]');
        let allFieldsFilled = true;

        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                allFieldsFilled = false;
                field.style.borderColor = "red"; 
            } else {
                field.style.borderColor = ""; 
            }
        });

        if (allFieldsFilled) {
            Swal.fire({
                title: "Are you sure?",
                text: "Do you want to save your details?",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, save it!",
                cancelButtonText: "Cancel"
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: "Saved",
                        text: "Your data has been saved!",
                        icon: "success",
                        showCancelButton: false,
                        confirmButtonColor: "#3085d6",
                        confirmButtonText: "Ok"
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                }
            });
        } else {
            Swal.fire({
                title: "Error",
                text: "Please fill in all required fields.",
                icon: "error",
                showCancelButton: false,
                confirmButtonColor: "#d33",
                confirmButtonText: "Ok"
            });
        }
    });
    
  // Function to update converted quantity based on material unit and alternative unit
function updateConvertedQuantity() {
    // Get all the relevant elements
    const materialUnits = document.querySelectorAll('.materialUnit');
    const alternativeUnits = document.querySelectorAll('.alternativeUnit');
    const matPoItemQuantities = document.querySelectorAll('.mat_po_item_quan');
    const convertedQuantities = document.querySelectorAll('.convertedQuantity');
    const alternativeUnitValues = document.querySelectorAll('#alternativeUnitvalue');

    for (let i = 0; i < materialUnits.length; i++) {
        const materialUnit = materialUnits[i].value;
        const alternativeUnit = alternativeUnits[i].value;
        const materialQuantity = parseFloat(matPoItemQuantities[i].value);
        let alternativeUnitValue = parseFloat(alternativeUnitValues[i].value) || 1; // Default conversion value to 1 if not specified

        // Check if the material unit and alternative unit are the same
        if (materialUnit === alternativeUnit) {
            // If they are the same, copy material quantity to converted quantity
            convertedQuantities[i].value = materialQuantity;
        } else if (alternativeUnit && alternativeUnitValue > 0) {
            // If they are different, divide material quantity by alternative unit value
            const convertedValue = materialQuantity / alternativeUnitValue;
            convertedQuantities[i].value = convertedValue.toFixed(2); // Round to 2 decimal places
        } else {
            // Handle case when alternative unit value is missing or 0
            convertedQuantities[i].value = ''; // Clear the converted quantity field if no valid conversion
        }
    }
}

        // Add event listener for changes to alternative unit and material quantity inputs
        document.querySelectorAll('.alternativeUnit').forEach((select) => {
            select.addEventListener('change', updateConvertedQuantity); // When alternative unit is changed
        });

        document.querySelectorAll('.mat_po_item_quan').forEach((input) => {
            input.addEventListener('input', updateConvertedQuantity); // When material quantity is changed
        });

        document.querySelectorAll('#alternativeUnitvalue').forEach((input) => {
            input.addEventListener('input', updateConvertedQuantity); // When alternative unit value is updated
        });


// Function to handle row duplication
function duplicateRow() {
    const materialContainer = document.querySelector('.duplicatecontainer');
    const firstRow = document.querySelector('.purchaseRow');

    // Destroy Select2 on the first row before cloning to avoid issues
    $(firstRow).find('.mat_name').select2('destroy');

    // Clone the first row
    const newRow = firstRow.cloneNode(true);

    // Clear all input values in the new row
    newRow.querySelectorAll('input').forEach(input => input.value = '');

    // Append the new row to the container
    materialContainer.appendChild(newRow);

    // Re-initialize Select2 for the new row
    initSelect2ForMaterial($(newRow));

    // Reattach event listeners for converted quantity and other inputs
    attachUpdateConvertedQuantityListeners(newRow);
    
}

// Function to attach updateConvertedQuantity listeners to new row
function attachUpdateConvertedQuantityListeners(row) {
    row.querySelectorAll('.alternativeUnit').forEach((select) => {
        select.addEventListener('change', updateConvertedQuantity);
    });
    row.querySelectorAll('.mat_po_item_quan').forEach((input) => {
        input.addEventListener('input', updateConvertedQuantity);
    });
    row.querySelectorAll('#alternativeUnitvalue').forEach((input) => {
        input.addEventListener('input', updateConvertedQuantity);
    });
}


// Ensure Select2 is re-initialized properly after each new row is created
function initSelect2ForMaterial(context) {
    context.find('.mat_name').each(function () {
        // Destroy any existing Select2 instance on this element
        if ($(this).data('select2')) {
            $(this).select2('destroy');  
        }

        // Initialize Select2
        $(this).select2({
    placeholder: 'Search Material Alias or Material Name',
    ajax: {
        url: 'get_materials.php',
        type: 'POST',
        data: function (params) {
            return {
                search: params.term,
                godownName: $('#mat_po_godown').val()
            };
        },
        processResults: function (response) {
            var results = JSON.parse(response);
            return {
                results: results.map(function (item) {
                    return {
                        id: item.materialName,
                        text: `${item.materialName} (${item.material_alias})` // Combine name and aliases
                    };
                })
            };
        },
        cache: true
    },
    templateResult: function (item) {
        if (item.loading) return item.text;
        return $('<div>').text(item.text); // Render text with all aliases in brackets
    },
    templateSelection: function (item) {
        return item.text; // Display the selected name with aliases
    }

        }).on('select2:select', function (e) {
            var selectedMaterialName = e.params.data.id;
            fetchMaterialDetails(context, selectedMaterialName);
        });
    });
}

// Function to fetch material details based on selected materialName
function fetchMaterialDetails(context, materialName) {
    $.ajax({
        url: 'getFABmaterial.php',
        type: 'POST',
        data: { materialName: materialName },
        dataType: 'json',
        success: function(data) {
            context.find('.materialCategory').val(data.materialCategory);
            context.find('.materialBrand').val(data.materialBrand);
            context.find('.alternativeUnitvalue').val(data.alternativeUnitvalue);
            context.find('.materialUnit').val(data.materialUnit); // Keep materialUnit as an input

            const materialUnits = (data.materialUnit || '').split(',').map(unit => unit.trim());
            const alternativeUnits = (data.alternativeUnit || '').split(',').map(unit => unit.trim());

            // Populate alternativeUnit dropdown
            const alternativeUnitSelect = context.find('.alternativeUnit');
            alternativeUnitSelect.empty();
            alternativeUnitSelect.append('<option value="" selected disabled>Select Alternative Unit</option>');
            materialUnits.concat(alternativeUnits).forEach(unit => {
                alternativeUnitSelect.append(`<option value="${unit}">${unit}</option>`);
            });
        },
        error: function(xhr, status, error) {
            console.error('Error fetching material details:', error);
        }
    });
}

// Call the function to initialize Select2 for existing rows
initSelect2ForMaterial($(document));

// Duplicate button event listener
document.getElementById('duplicateButton').addEventListener('click', function (event) {
    event.stopImmediatePropagation();
    duplicateRow(); // Call the function to create a new row
});
});
</script>

<script>
  $(document).ready(function () {
    // Attach event listener dynamically for perUnit and convertedQuantity
    $(document).on('input', '.perUnit, .convertedQuantity', function () {
        var row = $(this).closest('.materialRow'); // Get the current row
        updatePrice(row);
    });

    // Function to update the price field
    function updatePrice(row) {
        var perUnit = parseFloat(row.find('.perUnit').val()) || 0;
        var convertedQuantity = parseFloat(row.find('.convertedQuantity').val()) || 0;
        var totalPrice = perUnit * convertedQuantity;

        row.find('.mat_po_item_price').val(totalPrice.toFixed(2)); // Update price field
        calculateTotalAmount(); // Update total amount
    }

    // Function to calculate the total amount of all item prices
    function calculateTotalAmount() {
        var totalAmount = 0;
        $('.mat_po_item_price').each(function () {
            totalAmount += parseFloat($(this).val()) || 0;
        });
        $('#mat_po_totalamt').val(totalAmount.toFixed(2));
    }

    // Duplicate Row Event
    $(document).on('click', '#duplicateButton', function (event) {
        event.preventDefault();
        var originalRow = $('.purchaseRow:first'); // Select the first row
        var newRow = originalRow.clone(); // Clone the row

        // Clear input values in the new row but keep dropdown options
        newRow.find('input[type="text"], input[type="number"]').val('');
        newRow.find('.current_stock, .mat_po_item_price').val('');
        newRow.find('select').prop('selectedIndex', 0);

        $('.duplicatecontainer').append(newRow);

        // Attach event listeners for calculations
        newRow.find('.perUnit, .convertedQuantity, .mat_po_item_price').on('change', function () {
            updatePrice(newRow);
        });
    });

    // Handle row deletion
    $(document).on('click', '.deleteCurrRow', function () {
        $(this).closest('.purchaseRow').remove();
        calculateTotalAmount();
    });
});

</script>

<script>
  $(document).ready(function() {
    // Store PO Date & Godown values globally
    var poData = {
        poDate: $('.mat_po_date').val(),
        godown: $('.mat_po_godown').val()
    };

    // Update global PO values when changed
    $(document).on('change', '.mat_po_date, .mat_po_godown', function() {
        poData.poDate = $('.mat_po_date').val();
        poData.godown = $('.mat_po_godown').val();

        // Update stock for all existing material rows
        $('.materialRow').each(function() {
            var row = $(this);
            var materialName = row.find('.mat_name').val();
            if (materialName) {
                calculateCurrentStock(row, materialName, poData.godown, poData.poDate);
            }
        });
    });

    // Event listener for material selection
    $(document).on('change', '.mat_name', function() {
        var row = $(this).closest('.materialRow'); // Correctly target the row
        var materialName = row.find('.mat_name').val();

        if (materialName && poData.godown && poData.poDate) {
            calculateCurrentStock(row, materialName, poData.godown, poData.poDate);
        }
    });

    // Duplicate Row Event
    $(document).on('click', '.duplicateButton', function(event) {
        event.preventDefault();
        var originalRow = $(this).closest('.materialRow'); // Get the current row
        var newRow = originalRow.clone(); // Clone the row

        // Clear input values in new row but keep dropdown options
        newRow.find('input[type="text"], input[type="number"]').val('');
        newRow.find('select').prop('selectedIndex', 0);
        newRow.find('.current_stock').val(''); // Clear current stock field

        // Append the cloned row
        originalRow.after(newRow);
    });

    // Function to fetch current stock
    function calculateCurrentStock(row, materialName, godown, poDate) {
        $.ajax({
            url: 'getCurrentStock.php',
            type: 'POST',
            data: {
                materialName: materialName,
                godown: godown,
                poDate: poDate
            },
            dataType: 'json',
            success: function(response) {
                row.find('.current_stock').val(response.current_stock); // Update only this row
            },
            error: function(xhr, status, error) {
                console.error('Error fetching current stock:', error);
            }
        });
    }
});

</script>


<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function () {
  document.getElementById('closeWsave').addEventListener('click', function () {

    Swal.fire({
      title: "Are you sure?",
      text: "Please confirm before you exit, Your data will not be saved!",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#3085d6",
      cancelButtonColor: "#d33",
      confirmButtonText: "Yes, Close!"
    }).then((result) => {
      if (result.isConfirmed) {        
          window.location.href = 'material-purchase-order.php';
      }
    });
  });
});
</script>

<!-- <script>
  $(document).ready(function() {
    $('#mat_po_godown').change(function() {
      var godownName = $(this).val();

      $.ajax({
        url: 'get_materials.php',
        type: 'POST',
        data: {godownName: godownName},
        success: function(data) {
          $('#mat_po_item_name').html(data);
        },
        error: function() {
          alert('Error retrieving materials.');
        }
      });
    });
  });
</script> -->

<!-- <script>
  $(function () {
    //Initialize Select2 Elements
    $('.select2bs4').select2({
      theme: 'bootstrap4'
    })
  })
</script> -->

<script>
// Initialize Tagify on the input element with custom pattern
const inputElement = document.getElementById('mat_po_other_exp');
const tagify = new Tagify(inputElement, {
    pattern: /^[a-zA-Z]+\:\d+$/,  // Enforce "label:value" format
    dropdown: {
        enabled: 0 // Disable dropdown
    },
    tagTextProp: 'value',  // Text displayed on the tag is 'value'
    tag: {
        backgroundColor: '#007bff',
        color: 'white',
        borderRadius: '3px',
        padding: '5px 10px',
        border: '1px solid #007bff'
    }
});

// Function to calculate the grand total
function calculateGrandTotal() {
    var totalAmounts = parseFloat(document.getElementById('mat_po_totalamt').value) || 0;
    var gstAmount = parseFloat(document.getElementById('mat_po_gst_amnt').value) || 0;

    // Extract "value" part from tags and sum it
    var otherExpTags = tagify.value || [];
    var otherExpTotal = otherExpTags.length > 0 ? extractNumbersFromTags(otherExpTags).reduce((acc, num) => acc + num, 0) : 0;

    // Calculate grand total
    var grandTotal = totalAmounts + gstAmount + otherExpTotal;

    // Update grand total field
    document.getElementById('mat_po_grant_total').value = grandTotal.toFixed(2);
}

// Function to extract numbers from the tags
function extractNumbersFromTags(tags) {
    return tags.map(tag => {
        let valuePart = tag.value.split(':')[1];  // Get the part after ':'
        return parseFloat(valuePart) || 0;  // Convert to float or return 0
    });
}

// Event listeners for manual input changes
document.getElementById('mat_po_gst_amnt').addEventListener('input', calculateGrandTotal);
document.getElementById('mat_po_other_exp').addEventListener('input', calculateGrandTotal);

// Tagify event listeners to detect changes in other expenses
tagify.on('change', calculateGrandTotal);
tagify.on('add', calculateGrandTotal);
tagify.on('remove', calculateGrandTotal);

// Instead of relying solely on a MutationObserver (which watches attributes),
// use a setInterval to detect changes to the value property of mat_pur_totalamt.
let lastTotalAmt = document.getElementById('mat_po_totalamt').value;
setInterval(function() {
    let currentTotalAmt = document.getElementById('mat_po_totalamt').value;
    if (currentTotalAmt !== lastTotalAmt) {
         lastTotalAmt = currentTotalAmt;
         calculateGrandTotal();
    }
}, 500); // Check every 500ms
</script>

<!-- <script>
$(document).ready(function() {
    // Initialize Select2 for material alias and materialName search
    function initSelect2ForMaterial(context) {
        context.find('.mat_name').each(function () {
            if ($(this).data('select2')) {
                $(this).select2('destroy');  // Destroy previous Select2 instance if it exists
            }

            $(this).select2({
                placeholder: 'Search Material Alias or Material Name',
                ajax: {
                    url: 'get_materials.php', // Fetch the materials using material_alias or materialName
                    type: 'POST',
                    data: function (params) {
                        return {
                            search: params.term, // Search term entered in the input
                            godownName: $('#mat_po_godown').val() // Get selected godown value
                        };
                    },
                    processResults: function (response) {
                        var results = JSON.parse(response);
                        return {
                            results: results.map(function (item) {
                                return {
                                    id: item.materialName, // materialName as id
                                    text: item.materialName, // materialName as text (for storing and submitting)
                                    alias: item.material_alias // material_alias for dropdown display
                                };
                            })
                        };
                    },
                    cache: true
                },
                templateResult: function (item) {
                    if (item.loading) return item.text;
                    // Display material_alias in the dropdown
                    return $('<div>').text(item.alias);
                },
                templateSelection: function (item) {
                    return item.text; // After selection, display materialName in the input
                }
            }).on('select2:select', function (e) {
                // Get the selected materialName and store it in hidden input
                var selectedMaterialName = e.params.data.id;
                
                // Fetch material details for the current row (context)
                fetchMaterialDetails(context, selectedMaterialName);
            });
        });
    }

    // Call the function to initialize Select2
    initSelect2ForMaterial($(document));
});
</script> -->

</body>
</html>