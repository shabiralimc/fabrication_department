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

function generateUserId($conn) {
    // Query the database to get the current maximum return number
    $query = "SELECT MAX(returnNumber) AS max_ret_number FROM mat_pur_return";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $maxId = $row['max_ret_number'];

        // If no return number exists, start from 'RN-NUM-0001'
        if ($maxId === null) {
            $nextId = 'RN-NUM-0001';
        } else {
            // Extract the numeric part and increment it
            preg_match('/(\d+)$/', $maxId, $matches); // Match digits at the end of the return number
            $lastNumber = (int)$matches[0];  // Get the last number

            // Increment and pad with zeros to make it 4 digits
            $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);

            // Generate the next return number with prefix
            $nextId = 'RN-NUM-' . $nextNumber;
        }
        return $nextId;
    } else {
        // In case no data exists, start from 'RN-NUM-0001'
        return 'RN-NUM-0001';
    }
}

// Call the function to get the new user ID
$newReturnId = generateUserId($conn);

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
        echo '<label>Return Number</label>';
        echo '<input type="text" class="form-control" name="returnNumber" value="' . $newReturnId . '" readonly>';
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

        $index = 0;
       // Output data of each row
       while ($row = $result->fetch_assoc()) {
   
    $currentQuantity = $row["convertedQuantity"]; // Get the current quantity from the database

    echo '<div class="card-body">';
    echo '<div class="row">'; // Add row wrapper

    echo '<div class="col-md-2 col-xs-6">';
    echo '<div class="form-group">';
    echo '<label>Material Name</label>';
    echo '<input type="text"class="form-control" name="mat_pur_item_matname[]"id="mat_pur_item_matname' . $index . '"  value="' . htmlspecialchars($row["mat_pur_item_matname"]) . '" readonly>';
    echo '</div>';
    echo '</div>';

    echo '<div class="col-md-2 col-xs-6">';
    echo '<div class="form-group">';
    echo '<label>Material Unit</label>';
    echo '<input type="text"class="form-control" name="materialUnit[]"id="materialUnit' . $index . '" value="' . htmlspecialchars($row["materialUnit"]) . '" readonly>';
    echo '</div>';
    echo '</div>';

    echo '<div class="col-md-2 col-xs-6">';
    echo '<div class="form-group">';
    echo '<label>Alternative Unit</label>';
    echo '<input type="text"class="form-control alternativeUnit" name="alternativeUnit[]"id="alternativeUnit' . $index . '"  value="' . htmlspecialchars($row["alternativeUnit"]) . '" readonly>';
    echo '<input type="hidden"class="form-control master-alternative-unit" name="masterAlternativeUnit[]" value="' . htmlspecialchars($row["masterAlternativeUnit"]) . '"id="masterAlternativeUnit' . $index . '"  readonly>';
    echo '</div>';
    echo '</div>';

    echo '<input type="hidden"class="form-control current_amount"id="current_amount_' . $index . '" value="' . htmlspecialchars($row["perUnit"]) . '">';

    echo '<div class="col-md-2 col-xs-6">';
    echo '<div class="form-group">';
    echo '<label>Return Quantity</label>';
    echo '<input type="number" class="form-control mat-ret-quantity"name="mat_ret_quanity[]" id="mat_ret_quantity_' . $index . '" ">';
    echo '</div>';
    echo '</div>';

    echo '<div class="col-md-2 col-xs-6">';
    echo '<div class="form-group">';
    echo '<label>Return Price</label>';
    echo '<input type="number" class="form-control mat_ret_amount"id="mat_ret_amount_' . $index . '"  name="mat_ret_amount[]"readonly>';
    echo '</div>';
    echo '</div>';

    echo '<div class="col-md-2 col-xs-6">';
    echo '<div class="form-group">';
    echo '<label>Stock Quantity</lable>';
    echo '<input type="number"class="form-control converted-quantity" name="convertedQuantity[]" readonly>';
    echo '</div>';
    echo '</div>';

    echo '<div class="col-md-2 col-xs-6">';
    echo '<div class="form-group">';
    echo '<input type="hidden" class="form-control current_quantity" readonly value="' . htmlspecialchars($currentQuantity) . '">'; // Display the current quantity
    echo '</div>';
    echo '</div>';

    echo '<input type="hidden"name="mat_pur_item_quant[]" value="' . htmlspecialchars($row["mat_pur_item_quant"]) . '">';
    echo '<input type="hidden"name="mat_pur_item_price[]" value="' . htmlspecialchars($row["mat_pur_item_price"]) . '">';
    echo '<input type="hidden" name="mat_pur_item_id[]" value="' . htmlspecialchars($row["mat_pur_item_id"]) . '">';

    echo '</div>'; // Close row div
    }

    // New Total Amount Section
    echo '<div class="card-body">';
    echo '<div class="row">';

    // Total Return Amount
    echo '<div class="col-md-2 col-xs-6">';
    echo '<div class="form-group">';
    echo '<label>Total Return Amount</label>';
    echo '<input type="number" class="form-control" name="total_return_amount" id="totalReturnAmount" readonly>';
    echo '</div>';
    echo '</div>';

    // Other Expenses
    echo '<div class="col-md-6 col-xs-6">';
    echo '<div class="form-group">';
    echo '<label>Other Expenses</label>';
    echo '<input type="text" class="form-control mat_pur_other_exp" name="mat_pur_other_exp" id="mat_pur_other_exp" placeholder="Enter description and expense details"style="width:200px" />';
    echo '</div>';
    echo '</div>';

    // GST Amount
    echo '<div class="col-md-2 col-xs-6">';
    echo '<div class="form-group">';
    echo '<label>GST Amount</label>';
    echo '<input type="number" class="form-control" name="gst_amount" id="gst_amount">';
    echo '</div>';
    echo '</div>';

    // Total Amount
    echo '<div class="col-md-2 col-xs-6">';
    echo '<div class="form-group">';
    echo '<label>Total Amount</label>';
    echo '<input type="number" class="form-control" name="total_amount" id="totalAmount" readonly>';
    echo '</div>';
    echo '</div>';

    echo '</div>'; // Close row div
    echo '</div>'; // Close card-body div

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
    document.querySelectorAll('.mat-ret-quantity').forEach((quantityInput, index) => {
        const alternativeUnitInput = document.querySelectorAll('.alternativeUnit')[index];
        const convertedQuantityInput = document.querySelectorAll('.converted-quantity')[index];
        const masterAlternativeUnitInput = document.querySelectorAll('.master-alternative-unit')[index];
        const materialUnitInput = document.querySelectorAll('input[name="materialUnit[]"]')[index];

        function updateConvertedQuantity() {
            const matRetQuantity = parseFloat(quantityInput.value) || 0;
            const alternativeUnit = alternativeUnitInput.value.trim();
            const materialUnit = materialUnitInput.value.trim();
            const masterAlternativeUnit = parseFloat(masterAlternativeUnitInput.value) || 1;

            if (materialUnit === alternativeUnit) {
                convertedQuantityInput.value = matRetQuantity;
            } else {
                convertedQuantityInput.value = (matRetQuantity / masterAlternativeUnit).toFixed(2);
            }
        }

        // Add event listeners for user interaction
        quantityInput.addEventListener('input', updateConvertedQuantity);
        alternativeUnitInput.addEventListener('change', updateConvertedQuantity);
    });
</script>

<script>

document.querySelectorAll('.mat-ret-quantity').forEach((quantityInput, index) => {
    const currentAmountInput = document.querySelectorAll('.current_amount')[index]; // Per unit price input
    const returnAmountInput = document.querySelectorAll('.mat_ret_amount')[index]; // Return price field

    // Event listener for quantity input change
    quantityInput.addEventListener('input', function () {
        const returnQuantity = parseFloat(this.value) || 0; // Return quantity entered by user
        const perUnitPrice = parseFloat(currentAmountInput.value) || 0; // Per unit price from database

        // Calculate the return price for the row
        const returnPrice =  perUnitPrice * returnQuantity ;

        // Update the return amount field
        returnAmountInput.value = returnPrice.toFixed(2);

        // Update total return amount after every row update
        updateTotalReturnAmount();
    });
});

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

// Event listeners to calculate total when tags are added/removed
tagify.on('add', function () {
    updateTotalAmount();
});
tagify.on('remove', function () {
    updateTotalAmount();
});

// Function to calculate and update the total return amount
function updateTotalReturnAmount() {
    let totalReturnAmount = 0;

    // Iterate over all return amount inputs and sum their values
    document.querySelectorAll('.mat_ret_amount').forEach(input => {
        totalReturnAmount += parseFloat(input.value) || 0;
    });

    // Update the total return amount field
    const totalReturnAmountField = document.getElementById('totalReturnAmount');
    if (totalReturnAmountField) {
        totalReturnAmountField.value = totalReturnAmount.toFixed(2);
    }

    // Update the grand total amount
    updateTotalAmount();
}

// Function to calculate and update the total return amount
function updateTotalReturnAmount() {
    let totalReturnAmount = 0;

    // Iterate over all return amount inputs and sum their values
    document.querySelectorAll('.mat_ret_amount').forEach(input => {
        totalReturnAmount += parseFloat(input.value) || 0;
    });

    // Update the total return amount field
    const totalReturnAmountField = document.getElementById('totalReturnAmount');
    if (totalReturnAmountField) {
        totalReturnAmountField.value = totalReturnAmount.toFixed(2);
    }

    // Update the grand total amount
    updateTotalAmount();
}

// Function to update the grand total amount including other expenses and GST
function updateTotalAmount() {
    const totalReturnAmount = parseFloat(document.getElementById('totalReturnAmount')?.value) || 0;
    const gstAmount = parseFloat(document.getElementById('gst_amount')?.value) || 0;

    // Extract "value" part from tags and sum it
    const otherExpTags = tagify.value;
    const otherExpTotal = otherExpTags.length > 0 ? extractNumbersFromTags(otherExpTags).reduce((acc, num) => acc + num, 0) : 0;

    // Calculate the grand total
    const totalAmount = totalReturnAmount + otherExpTotal + gstAmount;

    // Update the total amount field
    const totalAmountField = document.getElementById('totalAmount');
    if (totalAmountField) {
        totalAmountField.value = totalAmount.toFixed(2);
    }
}

// Function to extract numbers from the tags
function extractNumbersFromTags(tags) {
    return tags.map(tag => {
        const valuePart = tag.value.split(':')[1]; // Get the part after ':'
        return parseFloat(valuePart) || 0; // Convert to float or return 0
    });
}

// Add event listeners for GST to update total amount
document.getElementById('gst_amount')?.addEventListener('input', updateTotalAmount);

// Recalculate the total when a tag is added or removed
tagify.on('add', updateTotalAmount);
tagify.on('remove', updateTotalAmount);

// Initialize calculations on page load
updateTotalReturnAmount();


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
