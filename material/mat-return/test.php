

<?php
include_once("../../../../include/php/connect.php");

    // Fetch data based on the selected IDs
    $sql = "SELECT mpi.*, mmc.materialUnit,  mmc.alternativeUnitValue AS masterAlternativeUnit  FROM mat_pur_item mpi LEFT JOIN material_master_creates mmc ON mpi.mat_pur_item_matname = mmc.materialName";
    $result = $conn->query($sql);
 
    if ($result->num_rows > 0) {
while ($row = $result->fetch_assoc()) {

    echo '<input type="hidden"  class="form-control current_amount" value="' . htmlspecialchars($row["perUnit"]) . '">';


echo '<div class="col-md-2 col-xs-6">';
echo '<div class="form-group">';
echo '<label>Return Quantity</label>';
echo '<input type="number"  class="form-control mat-ret-quantity" name="mat_ret_quantity[]">';
echo '</div>';
echo '</div>';

echo '<div class="col-md-2 col-xs-6">';
echo '<div class="form-group">';
echo '<label>Return Price</label>';
echo '<input type="number"  class="form-control mat_ret_amount" name="mat_ret_amount[]"readonly>';
echo '</div>';
echo '</div>';
}
    }

?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Loop through each mat-ret-quantity input field and calculate the return price
    const matRetQuantities = document.querySelectorAll('.mat-ret-quantity'); // Return quantity inputs
    const currentAmounts = document.querySelectorAll('.current_amount'); // Per unit price inputs
    const matRetAmounts = document.querySelectorAll('.mat_ret_amount'); // Return price fields

    matRetQuantities.forEach((input, index) => {
        input.addEventListener('input', function () {
            // Get the return quantity entered by the user
            const returnQuantity = parseFloat(this.value) || 0;

            // Get the corresponding perUnit price (using the same index)
            const perUnitPrice = parseFloat(currentAmounts[index]?.value) || 0;

            // Debugging log for validation
            console.log(`Row ${index}: Return Quantity = ${returnQuantity}, Per Unit Price = ${perUnitPrice}`);

            let returnPrice = 0;

            // Perform the calculation only if returnQuantity > 0
            if (returnQuantity > 0) {
                returnPrice = perUnitPrice / returnQuantity; // Multiply per unit price with return quantity
            } else {
                returnPrice = 0; // Avoid division by zero
            }

            // Debugging log for calculated return price
            console.log(`Row ${index}: Calculated Return Price = ${returnPrice}`);

            // Update the corresponding mat_ret_amount input field
            if (matRetAmounts[index]) {
                matRetAmounts[index].value = returnPrice.toFixed(2);
            } else {
                console.error(`No mat_ret_amount input found for index: ${index}`);
            }

            
        });
    });

    // Initialize the total return amount calculation
    updateTotalReturnAmount();
});

// Function to calculate and update the total return amount
function updateTotalReturnAmount() {
    let totalReturnAmount = 0;

    // Sum all return amounts
    document.querySelectorAll('.mat_ret_amount').forEach(input => {
        totalReturnAmount += parseFloat(input.value) || 0;
    });

    // Update the total return amount field
    const totalReturnAmountField = document.getElementById('totalReturnAmount');
    if (totalReturnAmountField) {
        totalReturnAmountField.value = totalReturnAmount.toFixed(2);
    } else {
        console.error("Total return amount field (#totalReturnAmount) not found.");
    }

    // Update the grand total amount
    updateTotalAmount();
}

// Function to update the grand total amount including other expenses and GST
function updateTotalAmount() {
    const totalReturnAmount = parseFloat(document.getElementById('totalReturnAmount')?.value) || 0;
    const otherExpenses = parseFloat(document.getElementById('mat_pur_other_exp')?.value) || 0;
    const gstAmount = parseFloat(document.getElementById('gst_amount')?.value) || 0;

    const totalAmount = totalReturnAmount + otherExpenses + gstAmount;

    const totalAmountField = document.getElementById('totalAmount');
    if (totalAmountField) {
        totalAmountField.value = totalAmount.toFixed(2);
    } else {
        console.error("Total amount field (#totalAmount) not found.");
    }
}

</script>
