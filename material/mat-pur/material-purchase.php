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


function getSupplierOptions($conn) {
    // Query to fetch supplier names from the master_supplier table
    $query = "SELECT DISTINCT supplier_name FROM master_supplier";
    $result = mysqli_query($conn, $query);

    // Check if the query was successful
    if ($result && mysqli_num_rows($result) > 0) {
        // Loop through the result set and generate the options
        while ($row = mysqli_fetch_assoc($result)) {
            echo '<option value="' . htmlspecialchars($row['supplier_name']) . '">' . htmlspecialchars($row['supplier_name']) . '</option>';
        }
    } else {
        // Handle case when no data is returned or query fails
        echo '<option value="">No Supplier Name available</option>';
    }
}
?>

<?php

function generatePOId($conn) {
    // Query the database to get the current maximum PO number from the mat_pur table
    $query = "SELECT MAX(mat_pur_number) AS max_purchase_number FROM mat_purs";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $maxPurchaseNumber = $row['max_purchase_number'];

        // If no PO numbers exist yet, start from the specified value
        if ($maxPurchaseNumber === null) {
            return 'PU-NUM-001';
        }

        // Extract the numeric part from the current max ID
        $numericPartPurchase = (int)substr($maxPurchaseNumber, strrpos($maxPurchaseNumber, '-') + 1);

        // Increment the numeric part to generate the next PO number
        $nextNumericPart = $numericPartPurchase + 1;

        // Generate the next PO number by using the incremented numeric part
        $nextId = 'PU-NUM-' . str_pad($nextNumericPart, 3, '0', STR_PAD_LEFT);

        return $nextId;
    } else {
        // Handle the initial case when no PO numbers exist
        return 'PU-NUM-001';
    }
}

// Generate the new PO ID
$newPOId = generatePOId($conn);
?>

<?php
function getGodownOptions($conn) {
  // Query to fetch unit names from the master_unit table
  $query = "SELECT DISTINCT godownName FROM master_godown";
  $result = mysqli_query($conn, $query);

  // Check if the query was successful
  if ($result && mysqli_num_rows($result) > 0) {
      // Loop through the result set and generate the options
      while ($row = mysqli_fetch_assoc($result)) {
          echo '<option value="' . htmlspecialchars($row['godownName']) . '">' . htmlspecialchars($row['godownName']) . '</option>';
      }
  } else {
      // Handle case when no data is returned or query fails
      echo '<option value="">No Godown available</option>';
  }
}
?>
 
 <?php

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if all required fields are set for materials

if (isset($_POST['mat_pur_supplier'], $_POST['mat_pur_number'], $_POST['mat_pur_date'], $_POST['mat_pur_po'], $_POST['mat_pur_godown'], $_POST['pur_remarks'], $_POST['invoice_number'], $_POST['mat_pur_totalamt'], $_POST['mat_pur_gst_amnt'], $_POST['mat_pur_other_exp'], $_POST['mat_pur_grant_total'])) {

    // Retrieve and sanitize form data
    $mat_pur_supplier = $_POST['mat_pur_supplier'];
    $mat_pur_number = $_POST['mat_pur_number'];
    $mat_pur_date = $_POST['mat_pur_date'];
    $mat_pur_po = $_POST['mat_pur_po'] ? $_POST['mat_pur_po'] : NULL;
    $mat_pur_godown = $_POST['mat_pur_godown'];
    $pur_remarks = $_POST['pur_remarks'];
    $invoice_number = $_POST['invoice_number'];
    $mat_pur_totalamt = $_POST['mat_pur_totalamt'];
    $mat_pur_gst_amnt = $_POST['mat_pur_gst_amnt'] ?? 0;
    $mat_pur_other_exp = $_POST['mat_pur_other_exp'];
    $mat_pur_grant_total = $_POST['mat_pur_grant_total'];

    // Convert mat_pur_other_exp from JSON array to string like "travel:100,food:200"
    $mat_pur_other_exp_array = json_decode($mat_pur_other_exp, true);
    $mat_pur_other_exp_string = '';
    if ($mat_pur_other_exp_array) {
        $other_exp_parts = [];
        foreach ($mat_pur_other_exp_array as $exp) {
            $other_exp_parts[] = $exp['value']; // Use the 'value' part of each tag
        }
        $mat_pur_other_exp_string = implode(',', $other_exp_parts);
    }

    // Initialize file upload handling
    $filePath = null;
    if (isset($_FILES['fileUpload']) && $_FILES['fileUpload']['error'] == 0) {
        $fileTmpPath = $_FILES['fileUpload']['tmp_name'];
        $fileName = $_FILES['fileUpload']['name'];
        $uploadFileDir = 'uploaded_files/';
        $filePath = $uploadFileDir . basename($fileName);

        // Ensure the directory exists
        if (!is_dir($uploadFileDir)) {
            mkdir($uploadFileDir, 0755, true);
        }

        // Move the uploaded file
        if (!move_uploaded_file($fileTmpPath, $filePath)) {
            echo "Error moving the uploaded file.";
            exit;
        }
    } else {
        $filePath = null; // No file uploaded
    }

    // Start SQL transaction to ensure atomicity
    $conn->begin_transaction();

    try {
        // Insert into mat_purs table
        $insert_sql = "INSERT INTO mat_purs (mat_pur_supplier, mat_pur_number, mat_pur_date, mat_pur_po, mat_pur_godown, pur_remarks, invoice_number, mat_pur_totalamt, mat_pur_gst_amnt, mat_pur_other_exp, mat_pur_grant_total, file_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_sql);

        if ($stmt === false) {
            throw new Exception('Prepare failed: ' . $conn->error);
        }

        $stmt->bind_param("sssssssddsds", $mat_pur_supplier, $mat_pur_number, $mat_pur_date, $mat_pur_po, $mat_pur_godown, $pur_remarks, $invoice_number, $mat_pur_totalamt, $mat_pur_gst_amnt, $mat_pur_other_exp_string, $mat_pur_grant_total, $fileName);

        if (!$stmt->execute()) {
            throw new Exception('Execute failed: ' . $stmt->error);
        }

        // Commit the transaction
        $conn->commit();

        // Now, insert into mat_pur_item table only once per item, ensuring uniqueness
        if (isset($_POST['mat_pur_item_matname'], $_POST['alternativeUnit'], $_POST['mat_pur_item_quant'], $_POST['convertedQuantity'], $_POST['perUnit'], $_POST['mat_pur_item_price'])) {
            $mat_pur_item_matnames = $_POST['mat_pur_item_matname'];
            $alternativeUnits = $_POST['alternativeUnit'];
            $mat_pur_item_quants = $_POST['mat_pur_item_quant'];
            $convertedQuantitys = $_POST['convertedQuantity'];
            $perUnits = $_POST['perUnit'];
            $mat_pur_item_prices = $_POST['mat_pur_item_price'];

            // Create a temporary array to store unique item combinations

            $stmt_item = $conn->prepare("INSERT INTO mat_pur_item (mat_pur_po, mat_pur_number,mat_pur_date, mat_pur_item_matname, alternativeUnit, mat_pur_item_quant, convertedQuantity, perUnit, mat_pur_item_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

            if ($stmt_item === false) {
                throw new Exception('Prepare failed: ' . $conn->error);
            }

            $uniqueItemCombinations = [];

for ($i = 0; $i < count($mat_pur_item_matnames); $i++) {
$itemCombination = [
$mat_pur_po,
$mat_pur_number,
$mat_pur_date,
$mat_pur_item_matnames[$i],
$alternativeUnits[$i],
$mat_pur_item_quants[$i],
$convertedQuantitys[$i],
$perUnits[$i],
$mat_pur_item_prices[$i]
];

// Check if the combination already exists
if (!in_array($itemCombination, $uniqueItemCombinations)) {
// Insert into mat_pur_item
$stmt_item->bind_param("sssssdddd", $mat_pur_po, $mat_pur_number,$mat_pur_date, $mat_pur_item_matnames[$i], $alternativeUnits[$i], $mat_pur_item_quants[$i], $convertedQuantitys[$i], $perUnits[$i], $mat_pur_item_prices[$i]);

if (!$stmt_item->execute()) {
    throw new Exception('Execute failed: ' . $stmt_item->error);
}

// Add the combination to the temporary array
$uniqueItemCombinations[] = $itemCombination;
} else {
// Handle duplicate item (e.g., log a warning or prevent insertion)
echo "Duplicate item detected: " . implode(', ', $itemCombination);
}
}

            $stmt_item->close();
        }

        // Commit the transaction
        $conn->commit();

        echo "Data inserted successfully!";
    } catch (Exception $e) {
        $conn->rollback(); // Rollback transaction if anything fails
        echo "Error: " . $e->getMessage();
    }
}

if (isset($_POST['mat_pur_item_matname'], $_POST['convertedQuantity'])) {

  $mat_names = $_POST['mat_pur_item_matname'];
  $convertedQuantity = $_POST['convertedQuantity'];
  $purchase_date = $_POST['mat_pur_date']; // Purchase date from form
  $mat_pur_godown = $_POST['mat_pur_godown']; // Assuming you have this value from the form

  // Check if there's already an entry for the current date
  $stmt_check_current_date = $conn->prepare("SELECT stk_date, mat_stock_ary FROM mat_stocks WHERE stk_date = ?");
  $stmt_check_current_date->bind_param("s", $purchase_date);
  $stmt_check_current_date->execute();
  $stmt_check_current_date->store_result();

  if ($stmt_check_current_date->num_rows > 0) {
      // Case 1: Entry exists for the current date, update it
      $stmt_check_current_date->bind_result($existing_date, $existing_mat_stock_ary_json);
      $stmt_check_current_date->fetch();
      $existing_mat_stock_ary = json_decode($existing_mat_stock_ary_json, true);

      // Ensure unique materials and quantities
      $processed_materials = [];

      foreach ($mat_names as $index => $mat_name) {
          // Convert the quantity to float without converting to int
          $quantity_to_add = (float)$convertedQuantity[$index];

          // Check if material has already been processed
          if (!in_array($mat_name, $processed_materials)) {
              $found = false;

              foreach ($existing_mat_stock_ary as &$material) {
                  if ($material['mn'] === $mat_name && $material['gd'] === $mat_pur_godown) {
                      // Add the float quantity
                      $material['pq'] += $quantity_to_add;
                      $found = true;
                      break;
                  }
              }

              // If the material is new, append it to the array
              if (!$found) {
                  $existing_mat_stock_ary[] = [
                      'mn' => $mat_name,
                      'gd' => $mat_pur_godown,
                      'pq' => $quantity_to_add,
                      'pr' => 0,
                      'co' => 0
                  ];
              }

              $processed_materials[] = $mat_name;
          } else {
              echo "Duplicate material detected: $mat_name";
          }
      }

      // Update the mat_stocks table for the current date
      $updated_mat_stock_ary_json = json_encode($existing_mat_stock_ary);
      $stmt_update_stock = $conn->prepare("UPDATE mat_stocks SET mat_stock_ary = ? WHERE stk_date = ?");
      $stmt_update_stock->bind_param("ss", $updated_mat_stock_ary_json, $purchase_date);

      if ($stmt_update_stock->execute()) {
          echo "Stock updated successfully for date $purchase_date!";
      } else {
          echo "Error: " . $stmt_update_stock->error;
      }

      $stmt_update_stock->close();

  } else {
      // Case 2: No entry for the given purchase date, create a new entry
      $new_mat_stock_ary = [];

      foreach ($mat_names as $index => $mat_name) {
          // Convert the quantity to float without converting to int
          $quantity_to_add = (float)$convertedQuantity[$index];

          // Avoid duplicate entries in the new array
          $already_exists = false;
          foreach ($new_mat_stock_ary as $material) {
              if ($material['mn'] === $mat_name && $material['gd'] === $mat_pur_godown) {
                  $already_exists = true;
                  break;
              }
          }

          if (!$already_exists) {
              $new_mat_stock_ary[] = [
                  'mn' => $mat_name,
                  'gd' => $mat_pur_godown,
                  'pq' => $quantity_to_add,
                  'pr' => 0,
                  'co' => 0
              ];
          }
      }

      // Insert new entry into mat_stocks
      $new_mat_stock_ary_json = json_encode($new_mat_stock_ary);
      $stmt_insert_stock = $conn->prepare("INSERT INTO mat_stocks (stk_date, mat_stock_ary) VALUES (?, ?)");
      $stmt_insert_stock->bind_param("ss", $purchase_date, $new_mat_stock_ary_json);

      if ($stmt_insert_stock->execute()) {
          echo "New stock entry created successfully for date $purchase_date!";
      } else {
          echo "Error: " . $stmt_insert_stock->error;
      }

      $stmt_insert_stock->close();
  }

  // Close the statement after use
  $stmt_check_current_date->close();
}


}

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
          <h3 class="m-0">MANAGE PURCHASE</h3>
        </div>
      </div>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">
      <div class="card card-info card-outline">
        <div class="card-header">
          <div class="form-group">
            <label for="hasPoCheck">Material Has Purchase Order</label><br>
            <input type="checkbox" name="hasPoCheck" id="hasPoCheck" data-bootstrap-switch>
          </div>
        </div>

        <div class="card-body">
          <div class="row" id="poSelect">

            <div class="col-md-4">
              <div class="form-group">
                <label for="mat_pur_po">Select Purchase Order</label><br>
                <select name="mat_pur_po" id="mat_pur_po" class="form-control mat_pur_po">
                  <option value="" selected disabled>Select PO</option>
                  <!-- Add PO options here -->
                </select>
              </div>
            </div>

            <div class="col-md-2">
              <div class="form-group">
                <label for="mat_pur_numbers" class="form-label">Purchase Number</label>
                <input type="text" class="form-control mat_pur_numbers" name="mat_pur_numbers" id="mat_pur_numbers"value="<?php echo $newPOId; ?>" readonly>
              </div>
            </div>

            <div class="col-md-2">
              <div class="form-group">
                <label for="mat_pur_godowns" class="form-label">Godown</label>
                <input type="text" name="mat_pur_godowns" class="form-control mat_pur_godowns" id="mat_pur_godowns" readonly>
              </div>
            </div>

            <div class="col-md-2">
              <div class="form-group">
                <label for="supplier_name" class="form-label">Supplier Name</label>
                <input type="text" class="form-control supplier_name" name="supplier_name"id="supplier_name" readonly>
              </div>
            </div>

            <div class="col-md-2" style="padding-top: 31px;">
              <button type="button" id="okButton" class="btn btn-primary btn-block">OK</button>
            </div>

            <div class="col-md-12">
              <div class="card card-info card-outline">
                <div class="card-header">
                  <h3 class="m-0">PO ISSUED ITEMS</h3>
                </div>
                <div class="card-body">
                  <div id="dataContainer"></div>
                </div>
              </div>
            </div>

            <div class="col-md-12">
              <div class="card card-info card-outline">
                <div class="card-header">
                  <h3 class="m-0">SELECTED PURCHASE</h3>
                </div>
                <div class="card-body">
                  <div id="selectedDataContainer"></div>
                </div>
              </div>
            </div>
          </div>

          <div class="row">
            <form id="purchaseForm" method="POST" enctype="multipart/form-data">
              <div class="card card-success card-outline" id="purchaseDetailsSection">
                <div class="card-header"id="purchaseDetails">
                  <div class="row">
                    <div class="col-md-6">
                      <h4 class="m-0">PURCHASE DETAILS</h4>
                    </div>
                  </div>
                </div>

                <div class="card-body" id="purchaseRow">
                  <div class="form-group purchaseRow">
                    <div class="row" style="border-bottom: 3px solid rgba(0,0,0,.125);">

                      <div class="col-md-3">
                        <div class="form-group">
                          <label for="mat_pur_supplier" class="form-label">Supplier Name</label><span style="color:red">*</span>
                          <select name="mat_pur_supplier" class="form-control mat_pur_supplier" id="mat_pur_supplier" required>
                            <option value="" selected disabled>Choose a Supplier</option>
                            <?php getSupplierOptions($conn); ?>
                          </select>
                        </div>
                      </div>

                      <div class="col-md-3">
                        <div class="form-group">
                          <label for="supplier_address" class="form-label">Supplier Address</label>
                          <input type="text" class="form-control supplier_address" name="supplier_address" readonly>
                        </div>
                      </div>

                      <div class="col-md-3">
                        <div class="form-group">
                          <label for="supplier_gst" class="form-label">Supplier GST</label>
                          <input type="text" class="form-control supplier_gst" name="supplier_gst" readonly>
                        </div>
                      </div>

                      <div class="col-md-3">
                        <div class="form-group">
                          <label for="supplier_pan" class="form-label">Supplier PAN</label>
                          <input type="text" class="form-control supplier_pan" name="supplier_pan" readonly>
                        </div>
                      </div>

                      <div class="col-md-3">
                        <div class="form-group">
                          <label for="supplier_cont" class="form-label">Supplier Contact</label>
                          <input type="text" class="form-control supplier_cont" name="supplier_cont" readonly>
                        </div>
                      </div>

                      <div class="col-md-3">
                        <div class="form-group">
                          <label for="supplier_cp" class="form-label">Supplier Contact Person</label>
                          <input type="text" class="form-control supplier_cp" name="supplier_cp" readonly>
                        </div>
                      </div>

                      <div class="col-md-3">
                        <div class="form-group">
                          <label for="supplier_terms" class="form-label">Supplier Terms</label>
                          <input type="text" class="form-control supplier_terms" name="supplier_terms" readonly>
                        </div>
                      </div>

                    </div>

                    <div class="card-body"id="purchaseDateSection">
                      <div class="row" style="border-bottom: 3px solid rgba(0,0,0,.125);">

                        <div class="col-md-3">
                          <div class="form-group">
                            <label for="mat_pur_number" class="form-label">Purchase Number</label>
                            <input type="text" class="form-control mat_pur_number" name="mat_pur_number" id="mat_pur_number" value="<?php echo $newPOId; ?>" readonly>
                          </div>
                        </div>

                        <div class="col-md-3">
                          <div class="form-group">
                            <label for="mat_pur_date" class="form-label">Date</label><span style="color:red">*</span>
                            <input type="date" class="form-control mat_pur_date" name="mat_pur_date" id="mat_pur_date">
                          </div>
                        </div>

                        <div class="col-md-3">
                          <div class="form-group">
                            <label for="mat_pur_godown" class="form-label">Godown</label><span style="color:red">*</span>
                            <select name="mat_pur_godown" class="form-control mat_pur_godown" id="mat_pur_godown"required>
                              <option value="" selected disabled>Choose a Godown</option>
                              <?php getGodownOptions($conn); ?>
                            </select>
                          </div>
                        </div>  

                        <div class="col-md-3">
                          <div class="form-group">
                            <label for="invoice_number" class="form-label">Invoice Number</label><span style="color:red">*</span>
                            <input type="text" class="form-control invoice_number" name="invoice_number" id="invoice_number" required>
                          </div>
                        </div>

                        <div class="col-md-4">
                          <div class="form-group">
                            <label for="pur_remarks" class="form-label">Remarks</label>
                            <textarea class="form-control pur_remarks" name="pur_remarks" id="pur_remarks"></textarea>
                          </div>

                        </div>
                      </div>

                      <div class="card-body duplicatecontainers">
                        <div class="form-group purchaseRows" id="purchaseRows">
                          <div class="row align-items-center" style="border-bottom: 2px solid rgba(0,0,0,.125);">

                            <div class="col-md-3">
                              <div class="form-group">
                                <label for="mat_pur_item_matname" class="form-label">Material Name</label><span style="color:red">*</span>
                                <select name="mat_pur_item_matname[]" class="form-control mat_pur_item_matname" id="mat_pur_item_matname" required>
                                  <option value="" selected disabled>Choose a Material</option>
                                </select>
                              </div>
                            </div>

                            <div class="col-md-3">
                              <div class="form-group">
                                <label for="materialUnit" class="form-label">Material Unit</label><span style="color:red">*</span>
                                <input type="text" class="form-control materialUnit" name="materialUnit[]" id="materialUnit" readonly />
                              </div>
                            </div>

                            <div class="col-md-3">
                              <div class="form-group">
                                <label for="alternativeUnit" class="form-label">Alternative Unit</label>
                                <select class="form-control alternativeUnit" name="alternativeUnit[]" id="alternativeUnit">
                                  <option value="" selected disabled>Choose a Unit</option>
                                </select>
                              </div>
                            </div>

                            <div class="col-md-3">
                              <div class="form-group">
                                <label for="mat_pur_item_quant" class="form-label">Purchased Quantity</label><span style="color:red">*</span>
                                <input type="number" class="form-control mat_pur_item_quant" id="mat_pur_item_quant" name="mat_pur_item_quant[]" placeholder="Material Quantity" required>
                                <input type="hidden" class="form-control alternativeUnitvalue" name="alternativeUnitvalue[]" id="alternativeUnitvalue" readonly value="">
                              </div>
                            </div>

                            <div class="col-md-3">
                              <div class="form-group">
                                <label for="convertedQuantity" class="form-label">Converted Quantity</label>
                                <input type="number" class="form-control convertedQuantity" id="convertedQuantity" name="convertedQuantity[]" readonly>
                              </div>
                            </div>

                            <div class="col-md-3">
                              <div class="form-group">
                                <label for="perUnit" class="form-label">Per Unit</label><span style="color:red">*</span>
                                <input type="number" class="form-control perUnit" id="perUnit" name="perUnit[]"placeholder="Enter Per rate"required>
                              </div>
                            </div>

                            <div class="col-md-3">
                              <div class="form-group">
                                <label for="mat_pur_item_price" class="form-label">Material Price</label><span style="color:red">*</span>
                                <input type="number" class="form-control mat_pur_item_price" id="mat_pur_item_price" name="mat_pur_item_price[]" placeholder="Material Total" readonly>
                              </div>
                            </div>

                            <div class="col-md-1" style="padding-top:15px;">
                              <button type="button" class="btn btn-danger deleteCurrRow">X</button>
                            </div>

                          </div>
                        </div>
                      </div>


                      <div class="card-footer" id="duplicateButton">
                        <button type="button" class="btn btn-primary" id="duplicateButton">Duplicate Row</button>
                      </div>

                    </div>

                    <div class="card card-info card-outline" id="purchaseTotal">
                      <div class="card-header">
                        <div class="row">
                          <div class="col-md-6">
                            <h4 class="m-0">PURCHASE TOTALS</h4>
                          </div>
                        </div>
                      </div>

                      <div class="card-body">
                        <div class="row">
                          <div class="col-md-12">
                            <div class="row">
                              <!-- Total Amount -->
                              <div class="col-md-4">
                                <div class="form-group">
                                  <label>Total Amount</label>
                                  <input type="number" step="0.01" class="form-control mat_pur_totalamt" name="mat_pur_totalamt" id="mat_pur_totalamt" oninput="calculateGrandTotal();"placeholder="Material Total" readonly>
                                </div>
                              </div>

                              <div class="col-md-4">
                                <div class="form-group">
                                  <label for="fileUpload" class="form-label">Upload File</label><span style="color:red">*</span>
                                  <input type="file" class="form-control" name="fileUpload" id="fileUpload" accept=".pdf, .jpg, .png"required>
                                </div>
                              </div>

                              <!-- GST Amount -->
                              <div class="col-md-4">
                                <div class="form-group">
                                  <label>GST Amount</label>
                                  <input type="number" class="form-control mat_pur_gst_amnt" name="mat_pur_gst_amnt" id="mat_pur_gst_amnt" rows="1" placeholder="Enter Material and GST Amount" oninput="calculateGrandTotal();">
                                </div>
                              </div>

                              <div class="col-md-4">
                                <div class="form-group">
                                  <label>Other Expense</label><span style="color:red">*</span>
                                  <input type="text" class="form-control mat_pur_other_exp" name="mat_pur_other_exp" id="mat_pur_other_exp" placeholder="Enter description and expense details" />
                                </div>
                              </div>

                              <div class="col-md-4">
                                <div class="form-group">
                                  <label>Grand Total</label>
                                  <input type="number" class="form-control mat_pur_grant_total" name="mat_pur_grant_total" id="mat_pur_grant_total" placeholder="Grand Total" readonly>
                                </div>
                              </div>

                            </div>
                          </div>

                        </div>
                      </div>
                    </div>

                    <div class="card-footer">
                      <div class="row">
                        <div class="col-md-4" style="text-align:center;">
                          <button type="button" class="btn btn-success" name="completed" id="completed">Complete Purchase</button>
                        </div>

                        <div class="col-md-4" style="text-align:center;">
                          <a href="material-purchase-order-print.php" class="btn btn-info">Print Purchase Order</a>
                        </div>
                        <div class="col-md-4">
                          <button type="button" class="btn btn-danger float-sm-right" id="closeWsave">Close Without Save</button>
                        </div>
                      </div>
                    </div>
                  </form>
                </div>
              </div>
            </section>
          </div>

<!-- Include Footer File -->
<?php include_once ('../../../../include/php/footer.php') ?>

<script>
document.getElementById('completed').addEventListener('click', function(event) {
    event.preventDefault(); // Prevent default form submission

    if (validateForm()) {
        Swal.fire({
            title: 'Are you sure?',
            text: "Do you want to save the purchase details?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, save it!'
        }).then((result) => {
            if (result.isConfirmed) {
                // Disable the button to prevent multiple submissions
                document.getElementById('completed').disabled = true;

                var formData = new FormData(document.getElementById('purchaseForm')); 

                // Gather form data
                formData.append('mat_pur_supplier', document.getElementById('mat_pur_supplier').value);
                formData.append('mat_pur_number', document.getElementById('mat_pur_number').value);
                formData.append('mat_pur_date', document.getElementById('mat_pur_date').value);
                formData.append('mat_pur_po', document.getElementById('mat_pur_po').value);
                formData.append('mat_pur_godown', document.getElementById('mat_pur_godown').value);
                formData.append('pur_remarks', document.getElementById('pur_remarks').value);
                formData.append('invoice_number', document.getElementById('invoice_number').value);
                formData.append('mat_pur_totalamt', document.getElementById('mat_pur_totalamt').value);
                formData.append('mat_pur_gst_amnt', document.getElementById('mat_pur_gst_amnt').value);
                formData.append('mat_pur_other_exp', document.getElementById('mat_pur_other_exp').value);
                formData.append('fileUpload', document.getElementById('fileUpload').value);
                formData.append('mat_pur_grant_total', document.getElementById('mat_pur_grant_total').value);

                // Use actual file data instead of value
                var fileUpload = document.getElementById('fileUpload');
                if (fileUpload.files.length > 0) {
                    formData.append('fileUpload', fileUpload.files[0]);
                }
  
                // Gather material details
                document.querySelectorAll('.mat_pur_item_matname').forEach(function(el, index) {
                    formData.append('mat_pur_item_matname[]', el.value);
                });
                document.querySelectorAll('.alternativeUnit').forEach(function(el, index) {
                    formData.append('alternativeUnit[]', el.value);
                });

                document.querySelectorAll('.mat_pur_item_quant').forEach(function(el, index) {
                    formData.append('mat_pur_item_quant[]', el.value);
                });
                document.querySelectorAll('.convertedQuantity').forEach(function(el, index) {
                    formData.append('convertedQuantity[]', el.value);
                });

                document.querySelectorAll('.perUnit').forEach(function(el, index) {
                    formData.append('perUnit[]', el.value);
                });
                
                document.querySelectorAll('.mat_pur_item_price').forEach(function(el, index) {
                    formData.append('mat_pur_item_price[]', el.value);
                });

                // Send the data via fetch
                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    document.getElementById('completed').disabled = false; // Re-enable the button
                    if (data.includes('Data inserted successfully!')) {
                        Swal.fire({
                            title: 'Saved!',
                            text: 'Your purchase details have been saved.',
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            location.reload(); // Refresh the page
                        });
                    } else {
                        Swal.fire(
                            'Error!',
                            'There was an issue saving your purchase details.',
                            'error'
                        );
                    }
                })
                .catch(error => {
                    document.getElementById('completed').disabled = false; // Re-enable the button
                    Swal.fire(
                        'Error!',
                        'There was an issue saving your purchase details.',
                        'error'
                    );
                });
            }
        });
    } else {
        Swal.fire({
            title: 'Error!',
            text: 'Please fill all the required fields.',
            icon: 'error',
            confirmButtonText: 'OK'
        });
    }
});

function validateForm() {
    let valid = true;
    let requiredFields = document.querySelectorAll('.form-control[required]');

    requiredFields.forEach(function(field) {
        if (!field.value) {
            valid = false;
        }
    });

    return valid;
}
</script>

<?php

// Query to fetch existing PO from the fab_po_create table
$sql = "SELECT DISTINCT mat_po_number FROM mat_pos";

$result = mysqli_query($conn, $sql);

if (!$result) {
    // If there's an error in the query, return an empty array
    echo json_encode([]);
    exit;
}

// Fetch PO and store them in an array
$purchaseOrders = [];
while ($row = mysqli_fetch_assoc($result)) {
    $purchaseOrders[] = $row['mat_po_number'];
}

// Return the PO as a JSON array
$purchaseOrdersJSON = json_encode($purchaseOrders);
?>

<script>
$(function () {
  // Initialize Select2 with data from PHP
  var purchaseOrderData = <?php echo $purchaseOrdersJSON; ?>;

  $('#mat_pur_po').select2({
    theme: 'bootstrap4',
    placeholder: 'Select PO Number',
    allowClear: true,
    minimumInputLength: 1, // Minimum length of input before triggering AJAX
    data: purchaseOrderData, // Populate with existing PO
    tags: true // Allow custom tags (new PO)
  });
});
</script>

<script>
    $(document).ready(function() {
    // Hide the poSelect div on page load
    $('#poSelect').hide();
});
    // Initialize Bootstrap Switch
    $('input[name="hasPoCheck"]').bootstrapSwitch({
        onText: 'YES',
        offText: 'NO',
        onSwitchChange: function(event, state) {
            // Show or hide the poSelect div based on the state of the checkbox
            if (state) {
                $('#poSelect').show();
            } else {
                $('#poSelect').hide();
            }
        }
    });


    $(document).ready(function(){
        // When OK button is clicked
        $('#okButton').on('click', function() {
            // Fetch data from the database based on the selected purchase order
            var selectedPo = $('#mat_pur_po').val();
            if (selectedPo) {
                // Perform AJAX request to fetch data
                $.ajax({
                    url: 'fetch_data.php', // Assuming fetch_data.php is in the same directory
                    method: 'POST',
                    data: {mat_po_item_ponum: selectedPo},
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
                alert('Please select a purchase order');
            }
        });

        // Handle Insert button click
        $(document).on('click', 'input[name="insert"]', function() {
            var selectedIds = [];
            $('input[name="checkbox[]"]:checked').each(function() {
                selectedIds.push($(this).val());
            });

            if (selectedIds.length > 0) {
                // Perform AJAX request to fetch and display selected rows
                $.ajax({
                    url: 'insert_data.php', // Assuming insert_data.php handles the selected data
                    method: 'POST',
                    data: {ids: selectedIds},
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

// Bind the save button click event
$(document).on('click', '#saveButton', function() {
    // Custom validation function to check required fields
    function validateForm() {
        let isValid = true;
        // Iterate through each required input
        $('#editForm').find('input[required],select[required]').each(function() {
            if ($(this).val() === '') {
                isValid = false;
                // Highlight the empty field
                $(this).addClass('is-invalid'); 
            } else {
                // Remove the error highlighting if the field is filled
                $(this).removeClass('is-invalid');
            }
        });
        return isValid;
    }

    // Check if the form is valid before proceeding
    if (!validateForm()) {
        Swal.fire(
            'Error!',
            'Please fill in all required fields.',
            'error'
        );
        return; // Stop the save action if validation fails
    }

    Swal.fire({
        title: 'Are you sure?',
        text: "Do you want to save the details?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, save it!'
    }).then((result) => {
        if (result.isConfirmed) {
            var formData = $('#editForm').serialize();
            // Perform AJAX request to save the edited data
            $.ajax({
                url: 'save_data.php', // Assuming save_data.php handles the saving of edited data
                method: 'POST',
                data: formData,
                success: function(response) {
                    Swal.fire(
                        'Saved!',
                        'Your data has been saved.',
                        'success'
                    ).then(() => {
                        location.reload(); // Reload the page after the alert
                    });
                },
                error: function(xhr, status, error) {
                    // Handle errors
                    Swal.fire(
                        'Error!',
                        'An error occurred while saving your data.',
                        'error'
                    );
                    console.error(xhr.responseText);
                }
            });
        }
    });
});

    });
</script>

<script>
$(document).ready(function() {
    $('#okButton').click(function() {
        var mat_pur_po = $('#mat_pur_po').val();

        if (mat_pur_po) {
            $.ajax({
                type: 'POST',
                url: 'fetch_godown.php',
                data: { mat_po_number: mat_pur_po },
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
$(document).ready(function() {
    $('#okButton').click(function() {
        var mat_pur_po = $('#mat_pur_po').val();

        if (mat_pur_po) {
            $.ajax({
                type: 'POST',
                url: 'fetch_supplier.php',
                data: { mat_po_number: mat_pur_po },
                success: function(response) {
                    var data = JSON.parse(response);
                    if (data.success) {
                        $('#supplier_name').val(data.mat_po_supplier);
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
    $(document).ready(function() {
    // Initialize Bootstrap Switch
    $("input[data-bootstrap-switch]").bootstrapSwitch();

    // Toggle PO Select and Purchase Details based on checkbox
    function toggleSections(state) {
        if (state) {
            $('#purchaseForm').hide();
        } else {
            $('#purchaseForm').show();
        }
    }

    $('#hasPoCheck').on('switchChange.bootstrapSwitch', function(event, state) {
        toggleSections(state);
    });

    function bindSupplierNameChange() {
    $('.mat_pur_supplier').off('change').on('change', function() {
        var selectedSupplier = $(this).val();
        var row = $(this).closest('.purchaseRow'); // Fixed selector

        if (selectedSupplier) {
            $.ajax({
                url: 'getSupplierDetails.php',
                type: 'POST',
                data: { supplier_name: selectedSupplier },
                dataType: 'json',
                success: function(data) {
                    row.find('.supplier_address').val(data.supplier_address);
                    row.find('.supplier_gst').val(data.supplier_gst);
                    row.find('.supplier_pan').val(data.supplier_pan);
                    row.find('.supplier_cont').val(data.supplier_cont);
                    row.find('.supplier_cp').val(data.supplier_cp);
                    row.find('.supplier_terms').val(data.supplier_terms);
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error: ' + status + error);
                }
            });
        }
    });
}

// Initial binding for the existing row
bindSupplierNameChange();

function initSelect2ForMaterial(context) {
    context.find('.mat_pur_item_matname').each(function () {
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
                        search: params.term, // search term entered in the input
                        godownName: $('#mat_pur_godown').val() // get selected godown value
                    };
                },
                processResults: function (response) {
                    var results = JSON.parse(response);
                    return {
                        results: results.map(function (item) {
                            return {
                                id: item.materialName,
                                text: `${item.materialName} (${item.material_alias})` // Display both name and all aliases
                            };
                        })
                    };
                },
                cache: true
            },
            templateResult: function (item) {
                if (item.loading) return item.text;
                return $('<div>').text(item.text); // Render text with name and aliases
            },
            templateSelection: function (item) {
                return item.text; // Display material name with aliases in the input after selection
            }
        }).on('select2:select', function (e) {
            var selectedMaterialName = e.params.data.id;
            fetchMaterialDetails(context, selectedMaterialName);
        });
    });
}


    // Fetch the materialUnit and alternativeUnit based on selected materialName
    function fetchMaterialDetails(context, materialName) {
        $.ajax({
            url: 'getFABmaterial.php', // Endpoint to get the material details
            type: 'POST',
            data: { materialName: materialName }, // Send materialName to fetch the corresponding units
            dataType: 'json',
            success: function (data) {
                console.log('Received data:', data); // Debugging line

                // Populate the materialUnit input field in the current row (context)
                context.find('.materialUnit').val(data.materialUnit);

                // Store the alternativeUnitvalue in a hidden field in the current row (context)
                context.find('.alternativeUnitvalue').val(data.alternativeUnitvalue);

                // Populate the alternativeUnit dropdown in the current row (context)
                var $alternativeUnitDropdown = context.find('.alternativeUnit');
                $alternativeUnitDropdown.empty(); // Clear previous options

                // Add the default 'Choose a value' option
                $alternativeUnitDropdown.append('<option value="" selected disabled>Choose a Unit</option>');

                // Add the materialUnit and alternativeUnitvalue as options
                if (data.materialUnit) {
                    $alternativeUnitDropdown.append('<option value="' + data.materialUnit + '">' + data.materialUnit + '</option>');
                }
                if (data.alternativeUnit) {
                    $alternativeUnitDropdown.append('<option value="' + data.alternativeUnit + '">' + data.alternativeUnit + '</option>');
                }
            },
            error: function (xhr, status, error) {
                console.error('Error:', error); // Debugging line
            }
        });
    }

     // Function to calculate the total amount
     function calculateTotalAmount() {
        let totalAmount = 0;
        $('.mat_pur_item_price').each(function() {
            let itemPrice = parseFloat($(this).val()) || 0;
            totalAmount += itemPrice;
        });
        $('#mat_pur_totalamt').val(totalAmount.toFixed(2)); // Update the total amount field
    }

    // Function to bind quantity and unit change events for each row
function bindQuantityCalculation(context) {
    const updateConvertedQuantity = () => {
        const purchasedQuantity = parseFloat(context.find('.mat_pur_item_quant').val()) || 0;
        const perUnit = parseFloat(context.find('.perUnit').val()) || 0;
        const alternativeUnitValue = parseFloat(context.find('.alternativeUnitvalue').val()) || 1;
        const materialUnit = context.find('.materialUnit').val();
        const selectedAlternativeUnit = context.find('.alternativeUnit').val();
        const convertedQuantityField = context.find('.convertedQuantity');
        const matPurItemPriceField = context.find('.mat_pur_item_price');

        // Update converted quantity
        if (selectedAlternativeUnit === materialUnit) {
            convertedQuantityField.val(purchasedQuantity.toFixed(2));
        } else {
            const convertedQuantity = purchasedQuantity / alternativeUnitValue;
            convertedQuantityField.val(convertedQuantity.toFixed(2));
        }

        // Calculate and update material price (purchasedQuantity * perUnit)
        const totalPrice = purchasedQuantity * perUnit;
        matPurItemPriceField.val(totalPrice.toFixed(2));
        calculateTotalAmount();
    };

    // Bind quantity and unit change events
    context.find('.mat_pur_item_quant, .perUnit').on('input', updateConvertedQuantity);
    context.find('.alternativeUnit').on('change', updateConvertedQuantity); // Update on alternative unit change
}

// Reset the dropdown if godown changes
$('#mat_pur_godown').change(function () {
    $('.mat_pur_item_matname').val(null).trigger('change'); // Clear selection in all rows
});

// Clone row without cloning the Select2 structure
$('#duplicateButton').click(function() {
    var original = $('.purchaseRows').first();
    var clone = original.clone(); // Clone the original row

    // Remove the Select2-generated HTML from the clone to prevent duplication
    clone.find('.select2-container').remove();

    // Restore the original input field to its initial state
    clone.find('.mat_pur_item_matname').removeAttr('data-select2-id').removeClass('select2-hidden-accessible').show();

    // Clear the cloned input fields and selections
    clone.find('input, select').val('');
    clone.find('select').val('').trigger('change'); // Reset select dropdown

    // Append the cloned element to the container
    $('.duplicatecontainers').append(clone);

    // Re-initialize Select2 on the cloned row's mat_pur_item_matname input
    initSelect2ForMaterial(clone);

    // Bind the quantity calculation to the new row
    bindQuantityCalculation(clone);

    // Add event listener to the delete button in the cloned row
    clone.find('.deleteCurrRow').click(function() {
        $(this).closest('.purchaseRows').remove();
        calculateTotalAmount();
    });
});

// Initial bindings for the first row
initSelect2ForMaterial($('.purchaseRows').first());
bindQuantityCalculation($('.purchaseRows').first());


});

</script>

<script>
// Initialize Tagify on the input element with custom pattern
const inputElement = document.getElementById('mat_pur_other_exp');
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
    var totalAmounts = parseFloat(document.getElementById('mat_pur_totalamt').value) || 0;
    var gstAmount = parseFloat(document.getElementById('mat_pur_gst_amnt').value) || 0;

    // Extract "value" part from tags and sum it
    var otherExpTags = tagify.value || [];
    var otherExpTotal = otherExpTags.length > 0 ? extractNumbersFromTags(otherExpTags).reduce((acc, num) => acc + num, 0) : 0;

    // Calculate grand total
    var grandTotal = totalAmounts + gstAmount + otherExpTotal;

    // Update grand total field
    document.getElementById('mat_pur_grant_total').value = grandTotal.toFixed(2);
}

// Function to extract numbers from the tags
function extractNumbersFromTags(tags) {
    return tags.map(tag => {
        let valuePart = tag.value.split(':')[1];  // Get the part after ':'
        return parseFloat(valuePart) || 0;  // Convert to float or return 0
    });
}

// Event listeners for manual input changes
document.getElementById('mat_pur_gst_amnt').addEventListener('input', calculateGrandTotal);
document.getElementById('mat_pur_other_exp').addEventListener('input', calculateGrandTotal);

// Tagify event listeners to detect changes in other expenses
tagify.on('change', calculateGrandTotal);
tagify.on('add', calculateGrandTotal);
tagify.on('remove', calculateGrandTotal);

// Instead of relying solely on a MutationObserver (which watches attributes),
// use a setInterval to detect changes to the value property of mat_pur_totalamt.
let lastTotalAmt = document.getElementById('mat_pur_totalamt').value;
setInterval(function() {
    let currentTotalAmt = document.getElementById('mat_pur_totalamt').value;
    if (currentTotalAmt !== lastTotalAmt) {
         lastTotalAmt = currentTotalAmt;
         calculateGrandTotal();
    }
}, 500); // Check every 500ms
</script>

</body>
</html>