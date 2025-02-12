<?php
if (isset($_POST['mat_pur_item_matname'], $_POST['convertedQuantity'])) {
    $mat_names = $_POST['mat_pur_item_matname'];
    $convertedQuantity = $_POST['convertedQuantity'];
    $purchase_date = $_POST['mat_pur_date']; // Get the purchase date from the form
    $current_date = date("Y-m-d"); // Get the current date

    // Fetch the most recent mat_stock_ary (if any exists)
    $stmt_fetch_stock = $conn->prepare("SELECT stk_date, mat_stock_ary FROM mat_stocks ORDER BY stk_date DESC LIMIT 1");
    $stmt_fetch_stock->execute();
    $stmt_fetch_stock->store_result();

    // Case 1: Check if database is empty
    if ($stmt_fetch_stock->num_rows == 0) {
        // If empty, create a new row with the current date
        $new_mat_stock_ary = [];

        foreach ($mat_names as $index => $mat_name) {
            $new_mat_stock_ary[] = [
                'mn' => $mat_name,
                'gd' => $mat_pur_godown,
                'q' => $convertedQuantity[$index]
            ];
        }

        // Insert new stock entry
        $new_mat_stock_ary_json = json_encode($new_mat_stock_ary);
        $stmt_insert_stock = $conn->prepare("INSERT INTO mat_stocks (stk_date, mat_stock_ary) VALUES (?, ?)");
        $stmt_insert_stock->bind_param("ss", $current_date, $new_mat_stock_ary_json);

        if ($stmt_insert_stock->execute()) {
            echo "New stock entry created for date $current_date!";
        } else {
            echo "Error: " . $stmt_insert_stock->error;
        }
        $stmt_insert_stock->close();
    } else {
        // Case 2: Database is not empty
        $stmt_fetch_stock->bind_result($latest_date, $mat_stock_ary_json);
        $stmt_fetch_stock->fetch();
        $mat_stock_ary = json_decode($mat_stock_ary_json, true);

        // Check if there's already an entry for the current date
        $stmt_check_current_date = $conn->prepare("SELECT stk_date, mat_stock_ary FROM mat_stocks WHERE stk_date = ?");
        $stmt_check_current_date->bind_param("s", $current_date);
        $stmt_check_current_date->execute();
        $stmt_check_current_date->store_result();

        if ($stmt_check_current_date->num_rows > 0) {
            // Case 2a: Entry exists for current date, update it
            $stmt_check_current_date->bind_result($existing_date, $existing_mat_stock_ary_json);
            $stmt_check_current_date->fetch();
            $existing_mat_stock_ary = json_decode($existing_mat_stock_ary_json, true);

            // Ensure unique materials and quantities
            $processed_materials = [];

            foreach ($mat_names as $index => $mat_name) {
                $quantity_to_add = $convertedQuantity[$index];

                // Check if material has already been processed
                if (!in_array($mat_name, $processed_materials)) {
                    $found = false;

                    foreach ($existing_mat_stock_ary as &$material) {
                        if ($material['mn'] == $mat_name && $material['gd'] == $mat_pur_godown) {
                            $material['q'] += $quantity_to_add; // Update quantity
                            $found = true;
                            break;
                        }
                    }

                    // If material is not found, add it to the array
                    if (!$found) {
                        $existing_mat_stock_ary[] = [
                            'mn' => $mat_name,
                            'gd' => $mat_pur_godown,
                            'q' => $quantity_to_add
                        ];
                    }

                    // Mark material as processed
                    $processed_materials[] = $mat_name;
                } else {
                    // Handle duplicate material (e.g., log a warning or prevent update)
                    echo "Duplicate material detected: $mat_name";
                }
            }

            // Update the mat_stocks table for current date
            $updated_mat_stock_ary_json = json_encode($existing_mat_stock_ary);
            $stmt_update_stock = $conn->prepare("UPDATE mat_stocks SET mat_stock_ary = ? WHERE stk_date = ?");
            $stmt_update_stock->bind_param("ss", $updated_mat_stock_ary_json, $current_date);

            if ($stmt_update_stock->execute()) {
                echo "Stock updated successfully for date $current_date!";
            } else {
                echo "Error: " . $stmt_update_stock->error;
            }

            $stmt_update_stock->close();
        } else {
            // Case 2b: No entry exists for current date, create a new one
            $new_mat_stock_ary = $mat_stock_ary; // Copy the latest stock array

            // Ensure unique materials and quantities
            $processed_materials = [];

            foreach ($mat_names as $index => $mat_name) {
                $quantity_to_add = $convertedQuantity[$index];

                // Check if material has already been processed
                if (!in_array($mat_name, $processed_materials)) {
                    $found = false;

                    foreach ($new_mat_stock_ary as &$material) {
                        if ($material['mn'] == $mat_name && $material['gd'] == $mat_pur_godown) {
                            $material['q'] += $quantity_to_add; // Update quantity
                            $found = true;
                            break;
                        }
                    }

                    // If material is not found, add it to the array
                    if (!$found) {
                        $new_mat_stock_ary[] = [
                            'mn' => $mat_name,
                            'gd' => $mat_pur_godown,
                            'q' => $quantity_to_add
                        ];
                    }

                    // Mark material as processed
                    $processed_materials[] = $mat_name;
                } else {
                    // Handle duplicate material (e.g., log a warning or prevent insertion)
                    echo "Duplicate material detected: $mat_name";
                }
            }

            // Insert new row with the current date
            $updated_mat_stock_ary_json = json_encode($new_mat_stock_ary);
            $stmt_insert_stock = $conn->prepare("INSERT INTO mat_stocks (stk_date, mat_stock_ary) VALUES (?, ?)");
            $stmt_insert_stock->bind_param("ss", $current_date, $updated_mat_stock_ary_json);

            if ($stmt_insert_stock->execute()) {
                echo "New stock entry created for date $current_date!";
            } else {
                echo "Error: " . $stmt_insert_stock->error;
            }

            $stmt_insert_stock->close();
        }

        $stmt_check_current_date->close();
    }

    $stmt_fetch_stock->close();

    // Case 3: Handle new purchases for a different date (other than the current date)
    $stmt_fetch_stock_from_date = $conn->prepare("SELECT stk_date, mat_stock_ary FROM mat_stocks WHERE stk_date >= ? ORDER BY stk_date ASC");
    $stmt_fetch_stock_from_date->bind_param("s", $purchase_date);
    $stmt_fetch_stock_from_date->execute();
    $result = $stmt_fetch_stock_from_date->get_result();

    if ($result->num_rows > 0) {
        // ... (continuation from previous code)

    $mat_stock_records = $result->fetch_all(MYSQLI_ASSOC);

    foreach ($mat_stock_records as $record) {
        $mat_stock_ary = json_decode($record['mat_stock_ary'], true);
        $stk_date = $record['stk_date'];

        // Skip updating stock for the current date, already handled
        if ($stk_date === $current_date) {
            continue;
        }

        // Ensure unique materials and quantities
        $processed_materials = [];

        foreach ($mat_names as $index => $mat_name) {
            $quantity_to_add = $convertedQuantity[$index];

            // Check if material has already been processed
            if (!in_array($mat_name, $processed_materials)) {
                $found = false;

                foreach ($mat_stock_ary as &$material) {
                    if ($material['mn'] == $mat_name && $material['gd'] == $mat_pur_godown) {
                        $material['q'] += $quantity_to_add; // Update quantity
                        $found = true;
                        break;
                    }
                }

                // If material is not found, add it to the array
                if (!$found) {
                    $mat_stock_ary[] = [
                        'mn' => $mat_name,
                        'gd' => $mat_pur_godown,
                        'q' => $quantity_to_add
                    ];
                }

                // Mark material as processed
                $processed_materials[] = $mat_name;
            } else {
                // Handle duplicate material (e.g., log a warning or prevent update)
                echo "Duplicate material detected: $mat_name";
            }
        }

        // Update stock for that date
        $updated_mat_stock_ary_json = json_encode($mat_stock_ary);
        $stmt_update_stock = $conn->prepare("UPDATE mat_stocks SET mat_stock_ary = ? WHERE stk_date = ?");
        $stmt_update_stock->bind_param("ss", $updated_mat_stock_ary_json, $stk_date);

        if ($stmt_update_stock->execute()) {
            echo "Stock updated successfully for date $stk_date!";
        } else {
            echo "Error: " . $stmt_update_stock->error;
        }

        $stmt_update_stock->close();
    }
} else {
    echo "No existing stock data found!";
}

$stmt_fetch_stock_from_date->close();
}


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

if (isset($_POST['mat_pur_item_id'])) {
    $mat_pur_item_id = $_POST['mat_pur_item_id'];

    // Convert the array of IDs into a comma-separated string
    $idList = implode(',', array_map('intval', $mat_pur_item_id));
    // Fetch data based on the selected IDs
    $sql = "SELECT mpi.*, mmc.materialUnit,  mmc.alternativeUnitValue AS masterAlternativeUnit  FROM mat_pur_item mpi LEFT JOIN material_master_creates mmc ON mpi.mat_pur_item_matname = mmc.materialName WHERE mpi.mat_pur_item_id IN ($idList)";
    $result = $conn->query($sql);
 
    if ($result->num_rows > 0) {
        // Start form and table markup
        echo "<form id='editForm' method='POST'>";
        echo '<div class="content-wrapper">';
        echo '<section class="content">';
        echo '<div class="container-fluid">';

        echo '<div class="card card-success card-outline" id="purchaseDetailsSection">';
        echo '<div class="card-header">';
        echo '<div class="row">';
        echo '<div class="col-md-3">';
        echo '<h4 class="m-0">RETURN PURCHASES</h4>';
        echo '</div>';
        echo '</div>';
        echo '</div>';

        echo '<div class="col-md-2 col-xs-6">';
        echo '<div class="form-group">';
        echo '<label>Date<span style="color:red;">*</span></label>';
        echo '<input type="date"class="form-control" name="mat_ret_date" >';
        echo '</div>';
        echo '</div>';

        echo '<div class="col-md-2 col-xs-6">';
        echo '<div class="form-group">';
        echo '<label>Remarks<span style="color:red";>*</span></label>';
        echo '<textarea style="width: 500px;"class="form-control" name="ret_remarks"required></textarea>';
        echo '</div>';
        echo '</div>';

        echo '<input type="hidden" name="mat_pur_number" id="purchase_numberInput" value="">';
        echo '<input type="hidden"class="mat_pur_godowns" name="mat_pur_godowns" id="purchase_godownInput" value="">';

        
// Output data of each row
while ($row = $result->fetch_assoc()) {
   
    $currentQuantity = $row["convertedQuantity"]; // Get the current quantity from the database
    $currentPrice = $row["mat_pur_item_price"]; // Get the current quantity from the database

    echo '<div class="card-body">';
    echo '<div class="row">';
    
    echo '<div class="col-md-2 col-xs-6">';
    echo '<div class="form-group">';
    echo '<label>Material Name</label>';
    echo '<input type="text"class="form-control" name="mat_pur_item_matname[]" value="' . htmlspecialchars($row["mat_pur_item_matname"]) . '" readonly>';
    echo '</div>';
    echo '</div>';

    echo '<div class="col-md-2 col-xs-6">';
    echo '<div class="form-group">';
    echo '<label>Material Unit</label>';
    echo '<input type="text" class="form-control" name="materialUnit[]" value="' . htmlspecialchars($row["materialUnit"]) . '" readonly>';
    echo '<input type="hidden" class="form-control" name="masterAlternativeUnit[]" value="' . htmlspecialchars($row["masterAlternativeUnit"]) . '" readonly>';
    echo '<input type="hidden" class="form-control" name="alternativeUnit[]" value="' . htmlspecialchars($row["alternativeUnit"]) . '" readonly>';
    echo '</div>';
    echo '</div>';
    
    echo '<div class="col-md-2 col-xs-6">';
    echo '<div class="form-group">';
    echo '<label>Return Quantity</label>';
    echo '<input type="number" class="form-control mat-ret-quantity" name="mat_ret_quanity[]" data-price="' . $currentPrice . '" data-index="' . $row["mat_pur_item_id"] . '">';
    echo '</div>';
    echo '</div>';
    
    echo '<div class="col-md-2 col-xs-6">';
    echo '<div class="form-group">';
    echo '<label>Return Price</label>';
    echo '<input type="number"class="form-control mat_ret_amount" name="mat_ret_amount[]">';
    echo '</div>';
    echo '</div>';

    echo '<div class="col-md-2 col-xs-6">';
    echo '<div class="form-group">';
    echo '<label>Converted Quantity</label>';
    echo '<input type="number" class="form-control converted-quantity" name="convertedQuantity[]" readonly>';
    echo '</div>';
    echo '</div>';

    echo '<div class="col-md-2 col-xs-6">';
    echo '<div class="form-group">';
    echo '<label>Current Quantity</label>';
    echo '<input type="text" class="form-control current_quantity" readonly value="' . $currentQuantity . '">'; // Display the current quantity
    echo '</div>';
    echo '</div>';

    echo '<div class="col-md-2 col-xs-6">';
    echo '<div class="form-group">';
    echo '<label>Current Amount</label>';
    echo '<input type="text" class="form-control current_amount"value="' . $currentPrice . '" readonly>';
    echo '</div>';
    echo '</div>';

    echo '<input type="hidden" name="mat_pur_item_quant[]" value="' . $row["mat_pur_item_quant"] . '">';
    echo '<input type="hidden" name="mat_pur_item_price[]" value="' . $row["mat_pur_item_price"] . '">';
    echo '<input type="hidden" name="mat_pur_item_id[]" value="' . $row["mat_pur_item_id"] . '">';
    
    echo '</div>'; // Close row div
    echo '</div>'; // Close card-body div
}

        // Add the save button at the end of the form
        echo "<center><input type='button' class='btn btn-success' name='save' value='RETURN' id='save'></center>";
       
        echo "</form>";
    } else {
        echo "No data found for the selected IDs.";
    }
} else {
    echo "Invalid request.";
}
?>

<script>
    document.querySelectorAll('.mat-ret-quantity').forEach((input, index) => {
    input.addEventListener('input', function () {
        const matRetQuantity = parseFloat(this.value) || 0;
        const materialUnit = document.getElementsByName('materialUnit[]')[index].value;
        const alternativeUnit = document.getElementsByName('alternativeUnit[]')[index].value;
        const masterAlternativeUnit = parseFloat(document.getElementsByName('masterAlternativeUnit[]')[index].value) || 1;

        const convertedQuantityInput = document.getElementsByName('convertedQuantity[]')[index];

        if (materialUnit === alternativeUnit) {
            convertedQuantityInput.value = matRetQuantity;
        } else {
            convertedQuantityInput.value = (matRetQuantity / masterAlternativeUnit).toFixed(2);
        }
    });
});

</script>

<script>
    $(document).ready(function() {
        $('input[name="mat_ret_quanity[]"]').on('input', function() {
    var row = $(this).closest('.row');
    var currentQuantityInput = row.find('input.current_quantity');
    var initialQuantityInput = row.find('input[name="mat_pur_item_quant[]"]');
    var convertedQuantityInput = row.find('input[name="convertedQuantity[]"]');

    var initialQuantity = parseFloat(initialQuantityInput.val()) || 0;
    var convertedQuantity = parseFloat(convertedQuantityInput.val()) || 0; // Use converted quantity
    var newQuantity = initialQuantity - convertedQuantity;

    currentQuantityInput.val(newQuantity.toFixed(2)); // Use toFixed(2) if you want to round to 2 decimal places
});


        $('input[name="mat_ret_amount[]"]').on('input', function() {
            var rows = $(this).closest('.row');
            var currentPriceInput = rows.find('input.current_amount');
            var initialPriceInput = rows.find('input[name="mat_pur_item_price[]"]');
            var initialPrice = parseInt(initialPriceInput.val());
            var returnedPrice = parseInt($(this).val()) || 0; // Add || 0 to default to 0 if NaN
            var newPrice = initialPrice - returnedPrice;
            currentPriceInput.val(newPrice);
        });
    });
</script>

<script>
// Function to update the value of the hidden input field with the selected PO number
    function updatePUNumber() {
        var selectedPUNumber = $('#mat_pur_number').val();
        $('#purchase_numberInput').val(selectedPUNumber);
    }

    // Call the function when the selection changes
    $('#mat_pur_number').on('change', updatePUNumber);

    // Call the function initially to set the value if there's already a selected PO number
    updatePUNumber();

    // Function to update the value of the hidden input field with the selected PO number
    function updatePUGodown() {
        var selectedPUGodown = $('.mat_pur_godowns').val();
        $('#purchase_godownInput').val(selectedPUGodown);
    }

    // Call the function when the selection changes
    $('#mat_pur_godowns').on('change', updatePUGodown);

    // Call the function initially to set the value if there's already a selected PO number
    updatePUGodown();
</script>

<script>
// Function to update the value of the hidden input field with the selected PO number
    function updateReturnDate() {
        var selectedReturnDate = $('#mat_ret_date').val();
        $('#mat_ret_dateInput').val(selectedReturnDate);
    }

    // Call the function when the selection changes
    $('#mat_ret_date').on('change', updateReturnDate);

    // Call the function initially to set the value if there's already a selected PO number
    updateReturnDate();
</script>
