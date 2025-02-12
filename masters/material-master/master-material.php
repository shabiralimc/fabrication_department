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

$sql = "SELECT * FROM material_master_creates";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Fetch all rows
$row_sql = mysqli_fetch_all($result, MYSQLI_ASSOC);


function generateFABId($conn) {
  // Query the database to get the current maximum material ID for FAB materials
  $query = "SELECT MAX(materialID) AS max_material_id FROM material_master_creates WHERE materialID LIKE 'FMAT-%'";
  $result = mysqli_query($conn, $query);

  if ($result && mysqli_num_rows($result) > 0) {
      $row = mysqli_fetch_assoc($result);
      $maxId = $row['max_material_id'];

      // Determine the starting point
      $endNumber = '0001';

      // If no job cards exist yet, start from the specified value
      if ($maxId === null) {
          return 'FMAT-' . $endNumber;
      }

      // Extract the numeric part and increment
      $numericPart = (int)substr($maxId, 5) + 1;

      // Generate the next job card number by using the incremented numeric part, zero-padded to 4 digits
      $nextId = 'FMAT-' . str_pad($numericPart, 4, '0', STR_PAD_LEFT);

      return $nextId;
  } else {
      // Handle errors or initial case when no job cards exist
      return 'FMAT-0001';
  }
}

function getUnitOptions($conn) {
    // Query to fetch unit names from the master_unit table
    $query = "SELECT DISTINCT unit_name FROM master_unit";
    $result = mysqli_query($conn, $query);

    // Check if the query was successful
    if ($result && mysqli_num_rows($result) > 0) {
        // Loop through the result set and generate the options
        while ($row = mysqli_fetch_assoc($result)) {
            echo '<option value="' . htmlspecialchars($row['unit_name']) . '">' . htmlspecialchars($row['unit_name']) . '</option>';
        }
    } else {
        // Handle case when no data is returned or query fails
        echo '<option value="">No units available</option>';
    }
}

function getCategoryOptions($conn) {
  // Query to fetch unit names from the master_unit table
  $query = "SELECT DISTINCT category_name FROM master_category";
  $result = mysqli_query($conn, $query);

  // Check if the query was successful
  if ($result && mysqli_num_rows($result) > 0) {
      // Loop through the result set and generate the options
      while ($row = mysqli_fetch_assoc($result)) {
          echo '<option value="' . htmlspecialchars($row['category_name']) . '">' . htmlspecialchars($row['category_name']) . '</option>';
      }
  } else {
      // Handle case when no data is returned or query fails
      echo '<option value="">No Category available</option>';
  }
}

function getBrandOptions($conn) {
  // Query to fetch unit names from the master_unit table
  $query = "SELECT DISTINCT brand_name FROM master_brand";
  $result = mysqli_query($conn, $query);

  // Check if the query was successful
  if ($result && mysqli_num_rows($result) > 0) {
      // Loop through the result set and generate the options
      while ($row = mysqli_fetch_assoc($result)) {
          echo '<option value="' . htmlspecialchars($row['brand_name']) . '">' . htmlspecialchars($row['brand_name']) . '</option>';
      }
  } else {
      // Handle case when no data is returned or query fails
      echo '<option value="">No Brand available</option>';
  }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  if (isset($_POST['materialID'], $_POST['materialName'], $_POST['material_alias'], $_POST['materialUnit'], $_POST['alternativeUnit'], $_POST['alternativeUnitvalue'], $_POST['materialCategory'], $_POST['materialBrand'], $_POST['negativeStock'], $_POST['warrantyYearFAB'], $_POST['godown'], $_POST['openingstock_ary'], $_POST['department'])) {

      // Initialize variables from POST
      $materialID            = $_POST['materialID'];
      $materialName          = $_POST['materialName'];
      $materialAlias         = $_POST['material_alias'];
      $materialUnit          = $_POST['materialUnit'];
      $alternativeUnit       = $_POST['alternativeUnit'];
      $alternativeUnitvalue  = (isset($_POST['alternativeUnitvalue']) && $_POST['alternativeUnitvalue'] !== "") ? (float)$_POST['alternativeUnitvalue'] : 0;
      $materialCategory      = $_POST['materialCategory'];
      $materialBrand         = $_POST['materialBrand'];
      $negativeStockCheckbox = isset($_POST['negativeStockCheckboxFAB']) ? 1 : 0;
      $negativeStock         = (isset($_POST['negativeStock']) && $_POST['negativeStock'] !== "") ? (float)$_POST['negativeStock'] : 0;
      $warrantyCheckbox      = isset($_POST['warrantyCheckboxFAB']) ? 1 : 0;
      $warrantyYearFAB       = (isset($_POST['warrantyYearFAB']) && $_POST['warrantyYearFAB'] !== "") ? (float)$_POST['warrantyYearFAB'] : 0;
      $godown                = $_POST['godown']; // e.g., "G01,G02,G03"
      $department            = $_POST['department'];

      // Convert godown list into a string
      $godownArray = explode(',', $godown);
      $godownString = implode(',', $godownArray); // Convert array to string

      // Decode JSON string safely for opening stock array
      $openingStockAry = json_decode($_POST['openingstock_ary'], true);
      if (!is_array($openingStockAry)) {
          error_log("Error decoding openingstock_ary JSON");
          die(json_encode(["status" => "error", "message" => "Invalid JSON format for openingstock_ary"]));
      }

      // Check if materialName already exists
      $stmt_check = $conn->prepare("SELECT openingstock_ary FROM material_master_creates WHERE materialName = ?");
      $stmt_check->bind_param("s", $materialName);
      $stmt_check->execute();
      $result_check = $stmt_check->get_result();

      if ($result_check->num_rows > 0) {
          // Update existing record
          $row = $result_check->fetch_assoc();
          $existingOpeningstockAry = json_decode($row['openingstock_ary'], true);
          $mergedOpeningstockAry = array_merge($existingOpeningstockAry, $openingStockAry);
          $mergedOpeningstockAryJson = json_encode($mergedOpeningstockAry);

          $stmt_update = $conn->prepare("UPDATE material_master_creates SET openingstock_ary = ? WHERE materialName = ?");
          $stmt_update->bind_param("ss", $mergedOpeningstockAryJson, $materialName);
          $stmt_update->execute();
          $stmt_update->close();
      } else {
          // Insert new record
          $openingstock_ary_json = json_encode($openingStockAry);

          $stmt_insert = $conn->prepare("INSERT INTO material_master_creates (materialID, materialName, material_alias, materialUnit, alternativeUnit, alternativeUnitvalue, materialCategory, materialBrand, godown, negativeStockCheckbox, negativeStock, warrantyCheckbox, warrantyYear, openingstock_ary, department) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

          if (!$stmt_insert) {
              error_log("Prepare failed: " . $conn->error);
          }

          $stmt_insert->bind_param("sssssdsssididss", 
              $materialID, 
              $materialName, 
              $materialAlias, 
              $materialUnit, 
              $alternativeUnit, 
              $alternativeUnitvalue, 
              $materialCategory, 
              $materialBrand, 
              $godownString, // String instead of array
              $negativeStockCheckbox, 
              $negativeStock,
              $warrantyCheckbox, 
              $warrantyYearFAB, 
              $openingstock_ary_json, 
              $department
          );

          if (!$stmt_insert->execute()) {
              error_log("Execute failed: " . $stmt_insert->error);
              die(json_encode(["status" => "error", "message" => "Database insert error."]));
          }

          $stmt_insert->close();
      }

      $stmt_check->close();

      echo json_encode(["status" => "success", "message" => "Material saved successfully."]);

      // Handle mat_stock table update
$currentDate = date('Y-m-d');

// Check if there is an entry for the current date
$stmt2 = $conn->prepare("SELECT mat_stock_ary FROM mat_stocks WHERE stk_date = ?");
$stmt2->bind_param("s", $currentDate);
$stmt2->execute();
$stmt2->store_result();

if ($stmt2->num_rows > 0) {
    // Entry exists for the current date
    $stmt2->bind_result($existingMatStockAry);
    $stmt2->fetch();
    $existingMatStockAry = json_decode($existingMatStockAry, true);

    // Flag to check if material-godown combination exists
    $materialGodownFound = false;

    foreach ($godownArray as $godownCode) {
        $materialFound = false;

        // Check if the material-godown combination already exists
        foreach ($existingMatStockAry as &$material) {
            if ($material['mn'] == $materialName && $material['gd'] == $godownCode) {
                // Material-godown combination found, no need to add it again
                $materialFound = true;
                break;
            }
        }

        // If not found, add it with quantity 0
        if (!$materialFound) {
            $existingMatStockAry[] = ["mn" => $materialName, "gd" => $godownCode, "pq" => 0, "pr" => 0, "co" => 0];
            $materialGodownFound = true; // Set flag to indicate a new entry was added
        }
    }

    // Update the existing stock array in the database only if changes were made
    if ($materialGodownFound) {
        $newMatStockAry = json_encode($existingMatStockAry);
        $stmt3 = $conn->prepare("UPDATE mat_stocks SET mat_stock_ary = ? WHERE stk_date = ?");
        $stmt3->bind_param("ss", $newMatStockAry, $currentDate);
        
        if ($stmt3->execute()) {
            echo "Stock updated successfully for date $currentDate!";
        } else {
            echo "Error updating stock: " . $stmt3->error;
        }
        $stmt3->close();
    }
} else {
    // No entry exists for the current date, create a new row
    $newMatStockAry = [];
    
    // Add each godown with quantity 0 for the new stock entry
    foreach ($godownArray as $godownCode) {
        $newMatStockAry[] = ["mn" => $materialName, "gd" => $godownCode, "pq" => 0, "pr" => 0, "co" => 0];
    }

    // Insert new stock entry into database
    $newMatStockAryJson = json_encode($newMatStockAry);
    $stmtInsert = $conn->prepare("INSERT INTO mat_stocks (stk_date, mat_stock_ary) VALUES (?, ?)");
    $stmtInsert->bind_param("ss", $currentDate, $newMatStockAryJson);
    
    if ($stmtInsert->execute()) {
        echo "New stock entry created for date $currentDate!";
    } else {
        echo "Error inserting new stock: " . $stmtInsert->error;
    }
    
    $stmtInsert->close();
}

$stmt2->close();
  
}
  }

$newFABId = generateFABId($conn);
// $newCPSId = generateCPSId($conn);

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
            <h3 class="m-0">MANAGE MATERIALS</h3>
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
    <div class="card-header">
      <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#material-fabcreate">
        Create FAB Material
      </button>
      <!-- <button type="button" class="btn btn-info" data-toggle="modal" data-target="#material-cpscreate">
        Create CPS Material
      </button> -->
    </div>
    <div class="card-body">
      <table class="table table-bordered table-striped" id="table-material">
        <thead>
          <tr>
            <th>Material ID</th>
            <th>Material Name</th>
            <th>Unit</th>
            <th>Item Category</th>
            <th>Brand</th>
            <th>Godown</th>
            <th>Negative Stock</th>
            <th>OpeningStock Array</th>
            <th>Selling Price</th>
            <th>Edit</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($row_sql as $row): ?>
        
          <tr>
            <td style="text-align: center;"><?php echo $row['materialID']; ?> </td>
            <td style="text-align: center;"><?php echo $row['materialName']; ?></td>
            <td style="text-align: center;"><?php echo $row['materialUnit']; ?></td>
            <td style="text-align: center;"><?php echo $row['materialCategory']; ?></td>
            <td style="text-align: center;"><?php echo $row['materialBrand']; ?></td>
            <td style="text-align: center;"><?php echo $row['godown']; ?></td>
            <td style="text-align: center;"><?php echo $row['negativeStock']; ?></td>
            <td style="text-align: center;">
    <?php 
        $openingStockData = json_decode($row['openingstock_ary'], true);
        if (!empty($openingStockData)) {
            foreach ($openingStockData as $stock) {
                echo "Godown: " . htmlspecialchars($stock['gd']) . "<br>";
                echo "Reorder Level: " . htmlspecialchars($stock['rol']) . "<br>";
                echo "Opening Stock: " . htmlspecialchars($stock['ops']) . "<br>";
                echo "Opn.Unit: " . htmlspecialchars($stock['ops_value']) . "<br>";
                echo "From: " . (!empty($stock['fos']) ? htmlspecialchars($stock['fos']) : "N/A") . "<br>";
            }
        } else {
            echo "No Stock Data Available";
        }
    ?>
</td>

            <td><a href="proposed-rate.php?id=<?php echo $row['id']; ?>" class="btn btn-success btn-xs btn-block">Selling Price</a></td>
            <td style="text-align: center; width:20%;">
  <button type="button" class="btn btn-xs btn-primary btn-block btn-edit"
    data-material-id="<?php echo $row['id']; ?>">Edit
  </button>
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

<!-- -------------------- FAB MATERIAL CREATE MODAL --------------------- -->

<div class="modal fade show" id="material-fabcreate" aria-modal="true" role="dialog" style="display: none;">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">Create FAB Material</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">×</span>
        </button>
      </div>
      <div class="modal-body">

        <div class="form-group">
          <label for="materialID">Material ID<span class="text-danger">*</span></label>
          <input type="text" name="materialID" class="form-control" id="materialID" value="<?php echo $newFABId; ?>" required readonly>
        </div>

        <div class="form-group">
          <label for="materialName">Material Name<span class="text-danger">*</span></label>
          <input type="text" name="materialName" class="form-control" id="materialName" required>
          <div id="validationFeedback" class="text-danger"></div>
        </div> 

        <div class="form-group">
    <label for="material_alias">Material Alias</label><br>
    <input type="text" class="form-control" id="material_alias" name="material_alias" data-role="tagsinput" />
       </div>

        <div class="form-group">
          <label for="materialUnit">Material Unit<span class="text-danger">*</span></label>
          <select name="materialUnit" class="form-control" id="materialUnit" required>
            <option value="" selected disabled>Choose a Unit</option>
            <?php getUnitOptions($conn); ?>
          </select>
        </div>

          <div class="form-group row">
          <div class="col-md-6">
          <label for="alternativeUnit">Alternative Unit<span class="text-danger">*</span></label>
          <select name="alternativeUnit" class="form-control" id="alternativeUnit" required>
          <option value="" selected disabled>Choose a Unit</option>
          <?php getUnitOptions($conn); ?>
          </select>
          </div>
          <div class="col-md-6">
          <label for="alternativeUnitvalue">Unit Value<span class="text-danger">*</span></label>
          <input type="number"step="0.01"name="alternativeUnitvalue" class="form-control" id="alternativeUnitvalue"placeholder="Enter value" required>
          </div>
          </div>


        <div class="form-group">
          <label for="materialCategory">Material Category<span class="text-danger">*</span></label>
          <select name="materialCategory" class="form-control" id="materialCategory" required>
            <option value="" selected disabled>Choose a Category</option>
            <?php getCategoryOptions($conn); ?>
          </select>
        </div>

        <div class="form-group">
          <label for="materialBrand">Material Brand<span class="text-danger">*</span></label>
          <select name="materialBrand" class="form-control" id="materialBrand" required>
            <option value="" selected disabled>Choose a Brand</option>
            <?php getBrandOptions($conn); ?>
          </select>
        </div> 

        <label for="godowns">Godown</label>
        <div class="row" id="godowns-container"></div>

        <div class="negativeStockCheckboxFABrow">
    <div class="col-md-6">
        <div class="form-group">
            <label for="negativeStockCheckboxFAB">Material Has Negative Stock</label>
            <input type="checkbox" name="negativeStockCheckboxFAB" id="negativeStockCheckboxFAB" data-bootstrap-switch>
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group" style="display: none;" id="numberOfWarrantyFAB">
            <label for="negativeStock">Negative Stock</label>
            <input type="number" name="negativeStock" class="form-control" id="negativeStock">
        </div>
    </div>
</div>

<div class="warrantyCheckboxFABrow">
    <div class="col-md-6">
        <div class="form-group">
            <label for="warrantyCheckboxFAB">Material Has Warranty</label><br>
            <input type="checkbox" name="warrantyCheckboxFAB" id="warrantyCheckboxFAB" data-bootstrap-switch>
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group" style="display: none;" id="yearsOfWarrantyFAB">
            <label for="warrantyYearFAB">Enter Years of Warranty</label><br>
            <input type="number" name="warrantyYearFAB" class="form-control" id="warrantyYearFAB">
        </div>
    </div>
</div>

        <input type="hidden" name="department" class="form-control" id="department" value="fabrication">

      </div>
      <div class="modal-footer justify-content-between">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="createNewFABmaterial">Create Material</button>
      </div>
    </div>
  </div>
</div>


<div class="modal fade show" id="material-alledit" aria-modal="true" role="dialog" style="display: none;">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">Edit Material</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">×</span>
        </button>
      </div>
      
      <div class="modal-body">
        
        <input type="hidden" name="id" class="form-control" id="id">

        <div class="row">
          
          <div class="col-md-6">
            
            <div class="form-group">
              <label for="material-ID">Material ID</label>
              <input type="text" name="material-ID" class="form-control" id="material-ID" readonly>
            </div>

          </div>

          <div class="col-md-6">
            
            <div class="form-group">
              <label for="material-Name">Material Name</label>
              <input type="text" name="material-Name" class="form-control" id="material-Name">
            </div>

          </div>

          <div class="col-md-12">
            
            <div class="form-group">
              <div class="form-group">
                <label for="material-alias">Material Alias</label>
                <textarea name="material-alias" class="form-control" id="material-alias"></textarea>
              </div>
            </div>

          </div>

          <div class="col-md-6">
            
            <div class="form-group">
              <label for="material-Unit">Material Unit</label>
              <select name="material-Unit" class="form-control" id="material-Unit">
                <?php getUnitOptions($conn); ?>
              </select>
            </div>
            <div class="form-group">
          <label for="material-Category">Material Category</label>
          <select name="material-Category" class="form-control" id="material-Category">
            <?php getCategoryOptions($conn); ?>
          </select>
        </div>

          </div>

          <div class="col-md-6">
            
            <div class="form-group">
              <label for="alternative-Unit">Alternative Unit<span class="text-danger">*</span></label>
              <select name="alternative-Unit" class="form-control" id="alternative-Unit" required>
                <option value="" selected disabled>Choose a Unit</option>
                <?php getUnitOptions($conn); ?>
              </select>
            </div>
            <div class="form-group">
              <label for="alternativeUnit-value">Unit Value<span class="text-danger">*</span></label>
              <input type="number" name="alternativeUnit-value" class="form-control" id="alternativeUnit-value"placeholder="Enter value" required>
            </div>

          </div>

          <div class="col-md-6">

            <div class="form-group">
              <label for="material-Brand">Material Brand</label>
              <select name="material-Brand" class="form-control" id="material-Brand">
                <?php getBrandOptions($conn); ?>
              </select>
            </div>

          </div>

          <div class="col-md-6">

            <label for="godownCheckbox">Godown</label><br>
            <div id="godowns-populatated-area" class="rows"></div>

          </div>

          <div class="col-md-6">

            <div class="editrows">
              <div class="col-md-12">
                <div class="form-group">
                  <label for="negativeStockCheckboxAll">Material Has Negative Stock</label><br>
                  <input type="checkbox" name="negativeStockCheckboxAll" id="negativeStockCheckboxAll" data-bootstrap-switch>
                </div>
              </div>

              <div class="col-md-12">
                <div class="form-group" style="display: none;" id="numberOfWarrantyAll">
                  <label for="negative-Stock">Negative Stock</label>
                  <input type="number" name="negative-Stock" class="form-control" id="negative-Stock">
                </div>
              </div>
            </div>

          </div>

          <div class="col-md-6">

            <div class="editrow">
              <div class="col-md-12">
                <div class="form-group">
                  <label for="warrantyCheckboxAll">Material Has Warranty</label><br>
                  <input type="checkbox" name="warrantyCheckboxAll" id="warrantyCheckboxAll" data-bootstrap-switch>
                </div>
              </div>

              <div class="col-md-12">
                <div class="form-group" style="display: none;" id="yearsOfWarrantyAll">
                  <label for="warrantyYearAll">Enter Years of Warranty</label><br>
                  <input type="number" name="warrantyYearAll" class="form-control" id="warrantyYearAll">
                </div>
              </div>
            </div>

          </div>

        </div>

      </div>
      <div class="modal-footer justify-content-between">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary"id="updateMaterial">Update</button>
      </div>
    </div>
  </div>
</div>


<!-- Include Footer File -->
<?php include_once ('../../../../include/php/footer.php') ?>

<script>
document.getElementById('material_alias').addEventListener('keydown', function(event) {
    if (event.key === 'Enter' || event.key === ',') {
        event.preventDefault();
        addTag(this);
    }
});

function addTag(inputElement) {
    let value = inputElement.value.trim();
    if (value === '') return;

    // Get the current values from the input
    let currentValues = inputElement.value.split(',').map(item => item.trim()).filter(item => item !== '');

    // Add the new value if it's not already in the array
    if (!currentValues.includes(value)) {
        currentValues.push(value);
    }

    // Update the input with the new comma-separated values
    inputElement.value = currentValues.join(', ');

    // Clear the input for the next entry
    inputElement.value = '';
}
</script>


<script>
document.addEventListener('DOMContentLoaded', function() {
    // Ensure the DOM is fully loaded before initializing Bootstrap Switch
    if (document.getElementById('warrantyCheckboxFAB')) {
        $('#warrantyCheckboxFAB').bootstrapSwitch({
            onText: 'Yes',
            offText: 'No',
            onSwitchChange: function(event, state) {
                // Show or hide the yearsOfWarrantyFAB div based on the state of the checkbox
                if (state) {
                    document.getElementById('yearsOfWarrantyFAB').style.display = 'block';
                } else {
                    document.getElementById('yearsOfWarrantyFAB').style.display = 'none';
                }
            }
        });
    }

    if (document.getElementById('negativeStockCheckboxFAB')) {
        $('#negativeStockCheckboxFAB').bootstrapSwitch({
            onText: 'Yes',
            offText: 'No',
            onSwitchChange: function(event, state) {
                // Show or hide the numberOfWarrantyFAB div based on the state of the checkbox
                if (state) {
                    document.getElementById('numberOfWarrantyFAB').style.display = 'block';
                } else {
                    document.getElementById('numberOfWarrantyFAB').style.display = 'none';
                }
            }
        });
    }
});
</script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const createButton = document.getElementById("createNewFABmaterial");

    if (createButton) {
        createButton.addEventListener("click", function () {
            // Collect form data
            const materialID = document.getElementById("materialID").value.trim();
            const materialName = document.getElementById("materialName").value.trim();
            const materialAlias = document.getElementById("material_alias").value.trim();
            const materialUnit = document.getElementById("materialUnit").value.trim();
            const alternativeUnit = document.getElementById("alternativeUnit").value.trim();
            const alternativeUnitvalue = document.getElementById("alternativeUnitvalue").value.trim();
            const materialCategory = document.getElementById("materialCategory").value.trim();
            const materialBrand = document.getElementById("materialBrand").value.trim();
            const negativeStockCheckbox = document.getElementById("negativeStockCheckboxFAB").checked;
            const negativeStock = negativeStockCheckbox ? document.getElementById("negativeStock").value.trim() : "";
            const warrantyCheckbox = document.getElementById("warrantyCheckboxFAB").checked;
            const warrantyYearFAB = warrantyCheckbox ? document.getElementById("warrantyYearFAB").value.trim() : "";
            const department = document.getElementById("department").value.trim();

            // Collect selected godowns
            const godowns = [];
            document.querySelectorAll('input[name^="godownCreateCheckbox"]:checked').forEach(function (checkbox) {
                godowns.push(checkbox.id.replace("godownCreateCheckbox", ""));
            });

            // Prepare opening stock data
            const openingstock_ary = godowns.map((gd) => {
                const reorderInput = document.getElementById(`reorderInput${gd}`);
                const openingStockInput = document.getElementById(`openingStockInput${gd}`);
                const openingStockValue = document.getElementById(`openingStockValue${gd}`);
                const fromOSInput = document.getElementById(`fromOSInput${gd}`);

                return {
                    gd: gd,
                    rol: reorderInput ? parseFloat(reorderInput.value.trim()) || 0 : 0,
                    ops: openingStockInput ? parseFloat(openingStockInput.value.trim()) || 0 : 0,
                    ops_value: openingStockValue ? parseFloat(openingStockValue.value.trim()) || 0 : 0,
                    fos: fromOSInput ? fromOSInput.value.trim() : null
                };
            });

            Swal.fire({
                title: "Are you sure?",
                text: "You won't be able to revert this!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, Create!",
            }).then((result) => {
                if (result.isConfirmed) {
                    // Prepare POST data
                    const dataParams = [
                        `materialID=${encodeURIComponent(materialID)}`,
                        `materialName=${encodeURIComponent(materialName)}`,
                        `material_alias=${encodeURIComponent(materialAlias)}`,
                        `materialUnit=${encodeURIComponent(materialUnit)}`,
                        `alternativeUnit=${encodeURIComponent(alternativeUnit)}`,
                        `alternativeUnitvalue=${encodeURIComponent(alternativeUnitvalue)}`,
                        `materialCategory=${encodeURIComponent(materialCategory)}`,
                        `materialBrand=${encodeURIComponent(materialBrand)}`,
                        `godown=${encodeURIComponent(godowns.join(","))}`,
                        `negativeStock=${encodeURIComponent(negativeStock)}`,
                        `warrantyYearFAB=${encodeURIComponent(warrantyYearFAB)}`,
                        `openingstock_ary=${encodeURIComponent(JSON.stringify(openingstock_ary))}`,
                        `department=${encodeURIComponent(department)}`
                    ];

                    // Add checkboxes to data if checked
                    if (document.getElementById('negativeStockCheckboxFAB').checked) {
                          dataParams.push('negativeStockCheckboxFAB=on');
                      }
                      if (document.getElementById('warrantyCheckboxFAB').checked) {
                          dataParams.push('warrantyCheckboxFAB=on');
                      }


                    const data = dataParams.join('&');

                    // AJAX request
                    const xhr = new XMLHttpRequest();
                    xhr.open("POST", "", true);
                    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

                    xhr.onreadystatechange = function () {
                        if (xhr.readyState === 4) {
                            if (xhr.status === 200) {
                                Swal.fire({
                                    title: "Created!",
                                    text: "New Material Created.",
                                    icon: "success",
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    title: "Error!",
                                    text: "An error occurred while saving the material.",
                                    icon: "error",
                                });
                            }
                        }
                    };

                    xhr.send(data);
                }
            });
        });
    }

    fetch("get_godowns.php")
    .then((response) => response.json())
    .then((data) => {
        const godownsContainer = document.getElementById("godowns-container");

        data.forEach((godown) => {  
            const colDiv = document.createElement("div");
            colDiv.className = "col-md-12 godown-switches-container";

            const formGroupDiv = document.createElement("div");
            formGroupDiv.className = "form-group";

            const label = document.createElement("label");
            label.setAttribute("for", `godownCreateCheckbox${godown}`);
            label.textContent = godown.toUpperCase();

            const checkbox = document.createElement("input");
            checkbox.type = "checkbox";
            checkbox.name = `godownCreateCheckbox${godown}`;
            checkbox.id = `godownCreateCheckbox${godown}`;
            checkbox.setAttribute("data-bootstrap-switch", "");

            const inputContainer = document.createElement("div");
            inputContainer.className = "godown-inputs-container row mt-2";
            inputContainer.style.display = "none";

            inputContainer.innerHTML = `
                <div class="form-group col-md-4">
                    <label for="reorderInput${godown}">Reorder Level</label>
                    <input type="number" class="form-control" name="reorderLevel" id="reorderInput${godown}" placeholder="Reorder Level">
                </div>
                <div class="form-group col-md-6 d-flex align-items-center">
                  <div class="mr-2">
                    <label for="openingStockInput${godown}" class="mb-0">Opening Stock</label>
                    <input type="number" class="form-control" name="openingStock" id="openingStockInput${godown}" placeholder="Opening Stock" style="width: 120px;">
                  </div>
                  <div>
                    <label for="openingStockValue${godown}" class="mb-0">Opn.UnitPrice</label>
                    <input type="number" class="form-control" name="openingStockUnitPrice" id="openingStockValue${godown}" placeholder="Unit Price" style="width: 120px;">
                  </div>
                </div>

                <div class="form-group col-md-4">
                    <label for="fromOSInput${godown}">From</label>
                    <input type="date" class="form-control" name="from_os" id="fromOSInput${godown}">
                </div>
            `;

            formGroupDiv.appendChild(label);
            formGroupDiv.appendChild(checkbox);
            colDiv.appendChild(formGroupDiv);
            colDiv.appendChild(inputContainer);
            godownsContainer.appendChild(colDiv);
        });

        // Initialize Bootstrap Switch for godown checkboxes only
        $("#godowns-container [data-bootstrap-switch]").bootstrapSwitch();

        // Bind event only to godown switches
        $("#godowns-container [data-bootstrap-switch]").on("switchChange.bootstrapSwitch", function (event, state) {
            const container = this.closest(".godown-switches-container");
            if (container) {
                const inputContainer = container.querySelector(".godown-inputs-container");
                if (inputContainer) {
                    inputContainer.style.display = state ? "block" : "none";
                }
            }
        });
    })
    .catch((error) => console.error("Error fetching godowns:", error));

});

</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var materialNameInput = document.getElementById('materialName');
    var validationFeedback = document.getElementById('validationFeedback');
    
    materialNameInput.addEventListener('input', function() {
        var value = materialNameInput.value;
        var regex = /^[a-zA-Z0-9\s-\/]*$/; // Allow only letters, numbers, and spaces

        if (regex.test(value)) {
            validationFeedback.textContent = '';
            materialNameInput.classList.remove('is-invalid');
        } else {
            validationFeedback.textContent = 'Special characters are not allowed.Only allowed letters and Numbers';
            materialNameInput.classList.add('is-invalid');
        }
    });
});
</script>

<script type="text/javascript">
  $(document).ready(function() {
  // Initialize Bootstrap Switch for warranty checkbox
  $('#warrantyCheckboxAll').bootstrapSwitch({
    onText: 'Yes',
    offText: 'No',
    onSwitchChange: function(event, state) {
      $('#yearsOfWarrantyAll').toggle(state);
    }
  });

  // Initialize Bootstrap Switch for negative stock checkbox
  $('#negativeStockCheckboxAll').bootstrapSwitch({
    onText: 'Yes',
    offText: 'No',
    onSwitchChange: function(event, state) {
      $('#numberOfWarrantyAll').toggle(state);
    }
  });

  // Tagify initialization for material alias input
  var input = $('#material-alias')[0];
    var tagify = new Tagify(input, {
      delimiters: ",",
      maxTags: Infinity,
      dropdown: {
        enabled: 0
      }
    });

  // Fetch material details for editing
  $('.btn-edit').on('click', function() {
    var materialId = $(this).data('material-id'); // Fetch material ID

    // Fetch material details using AJAX
    $.ajax({
      url: 'fetch_material_details.php',
      type: 'GET',
      data: { id: materialId },
      success: function(response) {
        try {
          var data = JSON.parse(response);
          if (data && !data.error) {
            // Populate modal fields
            $('#id').val(data.id);
            $('#material-ID').val(data.materialID);
            $('#material-Name').val(data.materialName);
            $('#material-Unit').val(data.materialUnit);
            $('#alternative-Unit').val(data.alternativeUnit);
            $('#alternativeUnit-value').val(data.alternativeUnitvalue);
            $('#material-Category').val(data.materialCategory);
            $('#material-Brand').val(data.materialBrand);
            $('#negative-Stock').val(data.negativeStock);
            $('#warrantyYearAll').val(data.warrantyYear);
          
            // Handle material aliases using Tagify
            tagify.removeAllTags(); // Clear existing tags
            if (data.materialAlias) {
              tagify.addTags(data.materialAlias.split(',')); // Add aliases
            }

            // Set the state of the checkboxes and re-initialize Bootstrap Switch
            $('#warrantyCheckboxAll').bootstrapSwitch('state', data.warrantyCheckbox == 1, true);
            $('#negativeStockCheckboxAll').bootstrapSwitch('state', data.negativeStockCheckbox == 1, true);

            // Display warranty year input if checkbox is checked
            $('#yearsOfWarrantyAll').toggle(data.warrantyCheckbox == 1);
            $('#numberOfWarrantyAll').toggle(data.negativeStockCheckbox == 1);

            // Open modal
            $('#material-alledit').modal('show');

            // Fetch and populate godowns
            fetchGodowns(materialId);
          } else {
            console.error('Error fetching material details: ' + (data ? data.error : 'Invalid response'));
          }
        } catch (e) {
          console.error("Error parsing JSON response:", e);
        }
      },
      error: function(xhr) {
        console.error('Error fetching material details: ' + xhr.statusText);
      }
    });

    function fetchGodowns(materialId) {
  // Fetch all godowns first
  $.ajax({
    url: 'fetch_all_godowns.php',
    type: 'GET',
    success: function(allGodownsResponse) {
      var allGodownsData = JSON.parse(allGodownsResponse);
      if (!allGodownsData.success || !Array.isArray(allGodownsData.godowns)) {
        console.error('Error fetching all godowns:', allGodownsData.message);
        return;
      }

      const godownsContainer = $('#godowns-populatated-area');
      godownsContainer.empty(); // Clear previous entries

      // Create a map to hold all godowns and their check state
      const godownElements = new Map();

      // Loop through all godowns and create checkboxes (unchecked initially)
      allGodownsData.godowns.forEach(godown => {
        const trimmedGodown = godown.trim();

      // Generate IDs using trimmed godown names
      const checkboxId = `godownUpdateCheckbox${trimmedGodown.replace(/\s+/g, '_')}`;
      const inputContainerId = `inputContainer${trimmedGodown.replace(/\s+/g, '_')}`;
      const reorderId = `reorderInput${trimmedGodown.replace(/\s+/g, '_')}`;
      const openingStockId = `openingStockInput${trimmedGodown.replace(/\s+/g, '_')}`;
      const openingStockValueId = `openingStockValue${trimmedGodown.replace(/\s+/g, '_')}`;
      const fromOSId = `fromOSInput${trimmedGodown.replace(/\s+/g, '_')}`;

        let colDiv = document.createElement('div');
        colDiv.className = 'col-md-12 godown-switch-container';

        let formGroupDiv = document.createElement('div');
        formGroupDiv.className = 'form-group';

        let label = document.createElement('label');
        label.setAttribute('for', checkboxId);
        label.textContent = godown.toUpperCase();

        let checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.name = "godownUpdateCheckbox[]";
        checkbox.id = checkboxId;
        checkbox.value = godown;
        checkbox.setAttribute('data-bootstrap-switch', '');

        let inputContainer = document.createElement('div');
        inputContainer.className = 'godown-input-container row mt-2';
        inputContainer.id = inputContainerId;
        inputContainer.style.display = "none"; // Hide initially

        inputContainer.innerHTML = `
            <div class="form-group col-md-4">
                <label for="${reorderId}">Reorder Level</label>
                <input type="number" class="form-control reorder-input" name="reorder-Level[${godown}]" id="${reorderId}">
            </div>
            <div class="form-group col-md-4">
                <label for="${openingStockId}">Opening Stock</label>
                <input type="number" class="form-control opening-stock-input" name="opening-Stock[${godown}]" id="${openingStockId}">

                <label for="${openingStockValueId}">Opn.UnitPrice</label>
                <input type="number" class="form-control opening-stock-inputValue" name="opening-StockValue[${godown}]" id="${openingStockValueId}">
            </div>
            <div class="form-group col-md-4">
                <label for="${fromOSId}">From</label>
                <input type="date" class="form-control from-os-input" name="from-os[${godown}]" id="${fromOSId}">
            </div>
        `;

        formGroupDiv.appendChild(label);
        formGroupDiv.appendChild(checkbox);
        colDiv.appendChild(formGroupDiv);
        colDiv.appendChild(inputContainer);

        godownsContainer.append(colDiv);

        // Store reference to elements for later use
        godownElements.set(godown, { checkbox, inputContainer, reorderId, openingStockId,openingStockValueId, fromOSId });

        // Initialize Bootstrap Switch
        $(checkbox).bootstrapSwitch({
          onText: 'ON',
          offText: 'OFF',
          state: false
        });

        // Toggle input visibility when checkbox is changed
        $(checkbox).on('switchChange.bootstrapSwitch', function(event, state) {
          document.getElementById(inputContainerId).style.display = state ? "block" : "none";
        });
      });

      // Now fetch checked godowns and update the UI
      fetchCheckedGodowns(materialId, godownElements); // Ensure this is called
    },
    error: function(xhr) {
      console.error('Error fetching all godowns: ' + xhr.statusText);
    }
  });
}

function fetchCheckedGodowns(materialId, godownElements) {
  $.ajax({
    url: 'get_material_godowns.php',
    type: 'GET',
    data: { id: materialId },
    success: function(response) {
      var data = JSON.parse(response);
      if (!data.success) {
        console.error('Error fetching assigned godowns:', data.message);
        return;
      }

      // Extract opening stock array from response
      let openingStockArray = data.openingstock_ary || [];

      // Convert opening stock array into a map for quick lookup
      const stockDataMap = new Map();
      openingStockArray.forEach(item => {
        // Trim whitespace and normalize godown names
        const godownKey = item.gd.trim();
        stockDataMap.set(godownKey, { 
          reorderLevel: item.rol || '',
          openingStock: item.ops || '', 
          openingStockValue: item.ops_value || '', 
          fromOS: item.fos || '' 
        });
      });

      // Loop through assigned godowns and update inputs
      data.godowns.forEach(godown => {
        // Trim whitespace from godown names for consistency
        const trimmedGodown = godown.trim();

        if (godownElements.has(trimmedGodown)) {
          const { 
            checkbox, 
            inputContainer, 
            reorderId, 
            openingStockId,
            openingStockValueId, 
            fromOSId 
          } = godownElements.get(trimmedGodown);

          // Get data from the map (use trimmed key)
          const godownData = stockDataMap.get(trimmedGodown) || { 
            reorderLevel: '', 
            openingStock: '', 
            openingStockValue: '',
            fromOS: '' 
          };

          // Check the checkbox
          $(checkbox).bootstrapSwitch('state', true, true);

          // Show input fields
          inputContainer.style.display = "block";

          // Populate input fields
          const reorderInput = document.getElementById(reorderId);
          const openingStockInput = document.getElementById(openingStockId);
          const openingStockInputValue = document.getElementById(openingStockValueId);
          const fromOSInput = document.getElementById(fromOSId);

          if (reorderInput) reorderInput.value = godownData.reorderLevel;
          if (openingStockInput) openingStockInput.value = godownData.openingStock;
          if (openingStockInputValue) openingStockInputValue.value = godownData.openingStockValue;
          if (fromOSInput) fromOSInput.value = godownData.fromOS;

        } else {
          console.warn(`Godown "${trimmedGodown}" not found in godownElements.`);
        }
      });
    },
    error: function(xhr) {
      console.error('Error fetching assigned godowns: ' + xhr.statusText);
    }
  });
}
  });

  $('#updateMaterial').on('click', function () {
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
      // Arrays to track godowns
      const openingStockArray = [];
      const checkedGodowns = [];  // Only checked godowns
      const allGodowns = [];  // Track all godowns (checked + unchecked)

      // Loop through all godown checkboxes
      $('input[name^="godownUpdateCheckbox"]').each(function () {
        const godown = $(this).val();
        allGodowns.push(godown); // Track all godowns

        if ($(this).is(':checked')) {
          // If checked, include in checkedGodowns and collect input values
          checkedGodowns.push(godown);

          openingStockArray.push({
            gd: godown,
            rol: $(`#reorderInput${godown.replace(/\s+/g, '_')}`).val() || 0,
            ops: $(`#openingStockInput${godown.replace(/\s+/g, '_')}`).val() || 0,
            ops_value: $(`#openingStockValue${godown.replace(/\s+/g, '_')}`).val() || 0,
            fos: $(`#fromOSInput${godown.replace(/\s+/g, '_')}`).val() || null
          });
        }
      });

      // Prepare form data
      const formData = {
        id: $('#id').val(),
        'material-ID': $('#material-ID').val(),
        'material-Name': $('#material-Name').val(),
        'material-Unit': $('#material-Unit').val(),
        'material-alias': tagify.value.map(tag => tag.value).join(','),
        'alternative-Unit': $('#alternative-Unit').val(),
        'alternativeUnit-value': $('#alternativeUnit-value').val(),
        'material-Category': $('#material-Category').val(),
        'material-Brand': $('#material-Brand').val(),
        'godownUpdateCheckbox': checkedGodowns.join(','), // Only send checked godowns
        'openingstock_ary': JSON.stringify(openingStockArray), // Send as JSON string
        'negativeStockCheckboxAll': $('#negativeStockCheckboxAll').bootstrapSwitch('state') ? 1 : 0,
        'negative-Stock': $('#negative-Stock').val(),
        'warrantyCheckboxAll': $('#warrantyCheckboxAll').bootstrapSwitch('state') ? 1 : 0,
        'warrantyYearAll': $('#warrantyYearAll').val()
      };

      if (!formData.id || !formData['material-ID'] || !formData['material-Name']) {
        Swal.fire("Error", "Please fill out all required fields.", "error");
        return;
      }

      $.ajax({
        url: "update.php",
        type: "POST",
        dataType: 'json',
        data: formData,
        success: function (response) {
          if (response.success) {
            Swal.fire({
              title: "Updated!",
              text: "Material has been updated.",
              icon: "success",
              confirmButtonText: "OK"
            }).then(() => {
              location.reload();
            });
          } else {
            Swal.fire("Error!", response.message, "error");
          }
        },
        error: function (xhr) {
          let errorMessage = "Failed to process request";
          try {
            const response = JSON.parse(xhr.responseText);
            errorMessage = response.message || errorMessage;
          } catch (e) {
            errorMessage = xhr.statusText;
          }
          Swal.fire("Error!", errorMessage, "error");
        }
      });
    }
  });
});

});

</script>
<script>
  $(document).ready(function() {
    $('#table-material').DataTable({
        'responsive': true,
        'lengthMenu': [[10,50, 100, 500, -1], [10, 50, 100, 500, 'All']],
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
                filename: 'Material Master Data Export',
                title: 'Material Master Data Export',
                exportOptions: {
                    columns: ':visible'
                }
            },
            {
                extend: 'excelHtml5',
                filename: 'Material Master Data Export',
                title: 'Material Master Data Export',
                exportOptions: {
                    columns: ':visible'
                }
            },
            {
                extend: 'pdfHtml5',
                filename: 'Material Master Data Export',
                title: 'Material Master Data Export',
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
