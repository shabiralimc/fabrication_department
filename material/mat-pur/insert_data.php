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

if (isset($_POST['ids'])) {
  $ids = $_POST['ids'];

    // Convert the array of IDs into a comma-separated string
  $idList = implode(',', array_map('intval', $ids));
    // Fetch data based on the selected IDs
  $sql = "SELECT mat_po_item.id, mat_po_item.mat_po_item_name, mat_po_item.mat_po_item_quan,material_master_creates.materialUnit,material_master_creates.alternativeUnitvalue,material_master_creates.alternativeUnit 
  FROM mat_po_item
  JOIN material_master_creates ON mat_po_item.mat_po_item_name = material_master_creates.materialName
  WHERE mat_po_item.id IN ($idList)";
  $result = $conn->query($sql);

  if ($result->num_rows > 0) {
    ?>

    <form id="editForm" method="POST" enctype="multipart/form-data">
      <div class="card-body duplicatecontainers">
        <div class="form-group purchaseRow" id="purchaseRow">
          <div class="row" style="border-bottom: 2px solid rgba(0,0,0,.125);">
            <input type="hidden" name="supplier_name" id="supplier_nameInput" value="">
            <input type="hidden" name="mat_pur_numbers" id="purchase_numberInput" value="">
            <input type="hidden" name="mat_pur_po" id="poNumberSelectedInput" value="">
            <input type="hidden" name="mat_pur_godowns" id="godownInput" value="">

            <div class="col-md-3">
              <div class="form-group">
                <label for="mat_pur_date" class="form-label">Date</label><span style="color:red">*</span>
                <input type="date" class="form-control mat_pur_date" name="mat_pur_date" id="mat_pur_date" required>
              </div>
            </div>

            <div class="col-md-3">
              <div class="form-group">
                <label for="invoice_number" class="form-label">Invoice Number</label><span style="color:red">*</span>
                <input type="text" class="form-control invoice_number" name="invoice_number" id="invoice_number" required>
              </div>
            </div>

            <div class="col-md-6">
              <div class="form-group">
                <label for="pur_remark" class="form-label">Remarks</label>
                <textarea class="form-control pur_remark" name="pur_remark" id="pur_remark"></textarea>
              </div>
            </div>
          </div>

        <?php
        // Output data of each row
        while ($row = $result->fetch_assoc()) {
          $purchaseQuery = "SELECT SUM(mat_pur_item.mat_pur_item_quant) as total_purchased 
          FROM mat_pur_item 
          WHERE mat_pur_item.mat_pur_item_matname = '{$row["mat_po_item_name"]}'";
          $purchaseResult = $conn->query($purchaseQuery);
          $resultData = $purchaseResult->fetch_assoc();
          $total_purchased = $resultData['total_purchased'] ?? 0;

          ?>
          <div class="card-body" style="border-bottom: 2px solid rgba(0,0,0,.125);">
            <div class="row">
              <div class="col-md-4">
                <div class="form-group">
                  <label>Material Name</label>
                  <input type="text" class="form-control mat_pur_item_matname" id="mat_pur_item_matname" name="mat_pur_item_matname[]" value="<?= htmlspecialchars($row["mat_po_item_name"]) ?>" readonly>
                </div>
              </div>

              <div class="col-md-3">
                <div class="form-group">
                  <label>Primary Unit</label>
                  <input type="text" name="materialUnit[]"id="materialUnit" class="form-control materialUnit" value="<?= htmlspecialchars($row["materialUnit"]) ?>" readonly>
                </div>
              </div>

              <div class="col-md-3">
                <div class="form-group">
                  <label>Alternative Unit</label><span style="color:red;">*</span>
                  <select class="form-control alternativeUnit" name="alternativeUnit[]"id="alternativeUnit" required>
                    <option value="" selected disabled>Choose a Unit</option>
                    <option value="<?= htmlspecialchars($row["materialUnit"]) ?>"><?= htmlspecialchars($row["materialUnit"]) ?></option>
                    <?php if (!empty($row["alternativeUnit"])) { ?>
                      <option value="<?= htmlspecialchars($row["alternativeUnit"]) ?>"><?= htmlspecialchars($row["alternativeUnit"]) ?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>

              <div class="col-md-2">
                <div class="form-group">
                  <label>Purchased Quantity<span style="color:red;">*</span></label>
                  <input type="number" class="form-control mat_pur_item_quant" name="mat_pur_item_quant[]" id="mat_pur_item_quant" required data-max-quantity="<?= $remaining_quantity ?>" oninput="validateQuantity(this)">
                  <input type="hidden" class="form-control alternativeUnitvalue"id="alternativeUnitvalue" name="alternativeUnitvalue[]" value="<?= htmlspecialchars($row["alternativeUnitvalue"]) ?>">
                  <input type="hidden" class="form-control alternativeUnit"id="alternativeUnit" name="alternativeUnit[]" value="<?= htmlspecialchars($row["alternativeUnit"]) ?>" readonly>
                </div>
              </div>

              <div class="col-md-2">
                <div class="form-group">
                  <label>Converted Quantity</label>
                  <input type="number" class="form-control convertedQuantity" name="convertedQuantity[]"id="convertedQuantity" readonly>
                </div>
              </div>

              <div class="col-md-3">
                <div class="form-group">
                  <label>Per Unit<span style="color:red;">*</span></label>
                  <input type="number" class="form-control perUnit" name="perUnit[]"id="perUnit" placeholder="Enter per rate" required>
                </div>
              </div>

              <div class="col-md-3">
                <div class="form-group">
                  <label>Purchasing Price</label>
                  <input type="number" class="form-control mat_pur_item_price" placeholder="Enter Your Price"id="mat_pur_item_price" name="mat_pur_item_price[]" readonly>
                  <input type="hidden" name="ids[]"id="i" value="<?= $row["id"] ?>">
                </div>
              </div>
            </div>
          </div>
          <?php
        }
        ?>
        <div class="card card-info card-outline" id="purchaseTotal" style="margin-top: 20px;">
          <div class="card-header">
            <div class="row">
              <div class="col-md-6">
                <h4 class="m-0">PURCHASE TOTALS</h4>
              </div>
            </div>
          </div>

          <div class="card-body">
            <div class="row">
              <div class="col-md-4">
                <div class="form-group">
                  <label>Total Amount</label>
                  <input type="number" step="0.01" class="form-control mat_pur_totalamt" name="mat_pur_totalamt" id="mat_pur_totalamt" placeholder="Material Total" readonly>
                </div>
              </div>

              <div class="col-md-4">
                <div class="form-group">
                  <label>Upload file</label><span style="color:red;">*</span>
                  <input type="file" class="form-control fileUpload" name="fileUpload" id="fileUpload"accept=".jpg,.jpeg,.png,.pdf"required>
                </div>
              </div>

              <div class="col-md-4">
                <div class="form-group">
                  <label>GST Amount</label>
                  <input type="number" step="0.01" class="form-control mat_pur_gst_amnt" name="mat_pur_gst_amnt" id="mat_pur_gst_amnt" placeholder="GST Amount">
                </div>
              </div>

              <div class="col-md-4">
                <div class="form-group">
                  <label>Other Expense</label>
                  <input type="text" class="form-control mat_pur_other_exp" name="mat_pur_other_exp" id="mat_pur_other_exp" placeholder="Enter description and expense details">
                </div>
              </div>

              <div class="col-md-4">
                <div class="form-group">
                  <label>Grand Total</label>
                  <input type="number" step="0.01" class="form-control mat_pur_grant_total" name="mat_pur_grant_total" id="mat_pur_grant_total" placeholder="Grand Total" readonly>
                </div>
              </div>
            </div>
          </div>
        </div>

        <center><input type="button" class="btn btn-success" name="saveButton" value="Complete" id="saveButton"></center>
      </form>

      <?php
    } else {
      echo "No results found for the selected IDs.";
    }
  }
  ?>


  <script>
    $(document).ready(function() {

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
            // Disable the save button to prevent multiple submissions
          $('#saveButton').prop('disabled', true);

          var formData = new FormData(document.getElementById('editForm'));

            // Add individual input fields to formData if needed
          formData.append('supplier_name', $('#supplier_name').val());
          formData.append('mat_pur_numbers', $('#mat_pur_numbers').val());
          formData.append('mat_pur_date', $('#mat_pur_date').val());
          formData.append('mat_pur_po', $('#mat_pur_po').val());
          formData.append('mat_pur_godowns', $('#mat_pur_godowns').val());
          formData.append('pur_remark', $('#pur_remark').val());
          formData.append('invoice_number', $('#invoice_number').val());
          formData.append('mat_pur_totalamt', $('#mat_pur_totalamt').val());
          formData.append('mat_pur_gst_amnt', $('#mat_pur_gst_amnt').val());
          formData.append('mat_pur_other_exp', $('#mat_pur_other_exp').val());
          formData.append('mat_pur_grant_total', $('#mat_pur_grant_total').val());

            // Handle file upload
          var fileUpload = document.getElementById('fileUpload');
          if (fileUpload.files.length > 0) {
            formData.append('fileUpload', fileUpload.files[0]);
          }

            // AJAX request to save the form data
          $.ajax({
                url: 'save_data.php', // The script handling data saving
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    $('#saveButton').prop('disabled', false); // Re-enable the button
                    Swal.fire(
                      'Saved!',
                      'Your data has been saved successfully.',
                      'success'
                      ).then(() => {
                        location.reload(); // Reload the page after success
                      });
                    },
                    error: function(xhr, status, error) {
                    $('#saveButton').prop('disabled', false); // Re-enable the button
                    Swal.fire(
                      'Error!',
                      'There was an error saving your data.',
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
    function validateQuantity(input) {
    // Get the maximum allowed quantity from the data attribute
      const maxQuantity = parseFloat(input.getAttribute('data-max-quantity'));
    // Get the entered quantity
      const enteredQuantity = parseFloat(input.value);

    // Check if the entered quantity exceeds the maximum allowed quantity
      if (enteredQuantity > maxQuantity) {
        alert(`Entered quantity (${enteredQuantity}) exceeds the maximum allowed quantity (${maxQuantity}).`);
        input.value = maxQuantity; // Optionally reset the input to the max allowed quantity
      }
    }
  </script>

  <script>
    $(document).ready(function() {
    // Function to calculate the total amount
      function calculateTotalAmount() {
        var totalAmount = 0;
        // Iterate over each materialTotal input field
        $('.mat_pur_item_price').each(function() {
            // Parse the value and add it to the totalAmount
          var materialTotal = parseFloat($(this).val()) || 0;
          totalAmount += materialTotal;
        });
        // Update the totalAmount input field with the calculated total
        $('#mat_pur_totalamt').val(totalAmount.toFixed(2));
      }

    // Bind the input event to the materialTotal fields
      $(document).on('input', '.mat_pur_item_price', function() {
        // Call the calculateTotalAmount function when the input event is triggered
        calculateTotalAmount();
      });

    // Initial calculation when the page loads
      calculateTotalAmount();
    });
  </script>

  <script>
    $(document).ready(function() {

    // Function to bind the supplier_name change event
      function bindSupplierNameChange() {
        $('.supplier_name').off('change').on('change', function() {
          var selectedSupplier = $(this).val();
          var row = $(this).closest('.purchaseRow');
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
        });
      }

    // Initial binding for the existing row
      bindSupplierNameChange();

     // Function to bind the materialName change event
      function bindMaterialNameChange() {
        $('.mat_pur_item_matname').off('change').on('change', function() {
          var selectedMaterial = $(this).val();
          var row = $(this).closest('.purchaseRows');
          $.ajax({
            url: 'getFABmaterial.php',
            type: 'POST',
            data: { materialName: encodeURIComponent(selectedMaterial) }, // Encode the material name
            dataType: 'json',
            success: function(data) {
                console.log('Received data:', data); // Debugging line
                row.find('.alternativeUnit').val(data.alternativeUnit);
                row.find('.materialUnit').val(data.materialUnit);
                row.find('.alternativeUnitvalue').val(data.alternativeUnitvalue);

              },
              error: function(xhr, status, error) {
                console.error('Error:', error); // Debugging line
              }
            });
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

 // Function to handle the calculation of converted quantity and item price
      function bindQuantityCalculation() {
            // Listen for input changes on both quantity and perUnit fields
        $('.mat_pur_item_quant, .perUnit,.alternativeUnit').on('input', function() {
                // Get the current row's values for purchased quantity, alternative unit, and per unit price
          const currentRow = $(this).closest('.row');
          const purchasedQuantity = parseFloat(currentRow.find('.mat_pur_item_quant').val()) || 0;
          const alternativeUnitValue = parseFloat(currentRow.find('.alternativeUnitvalue').val()) || 1;
          const perUnit = parseFloat(currentRow.find('.perUnit').val()) || 0;
          const convertedQuantityField = currentRow.find('.convertedQuantity');
                const materialUnit = currentRow.find('.materialUnit').val(); // Material Unit
                const selectedAlternativeUnit = currentRow.find('.alternativeUnit option:selected').val(); // Selected Alternative Unit
                const itemPriceField = currentRow.find('.mat_pur_item_price'); // Item price field

                let convertedQuantity;

                // Check if materialUnit and selectedAlternativeUnit are the same
                if (materialUnit === selectedAlternativeUnit) {
                    // If the units are the same, set convertedQuantity to purchasedQuantity
                  convertedQuantity = purchasedQuantity;
                } else {
                    // If the units are different, calculate based on division
                  convertedQuantity = purchasedQuantity / alternativeUnitValue;
                }

                // Update the converted quantity field
                convertedQuantityField.val(convertedQuantity.toFixed(2)); // Limit to 2 decimal places

                // Calculate and update the item price (quantity * perUnit)
                const itemPrice = purchasedQuantity * perUnit;
                itemPriceField.val(itemPrice.toFixed(2)); // Limit to 2 decimal places

                // Recalculate the total amount
                calculateTotalAmount();
              });
      }

        // Call the function initially to bind the calculation functionality
      bindQuantityCalculation();


    });

  </script>

  <script>
    // Function to update the value of the hidden input field with the selected PO number
    function updateSelectedPurchaseNumber() {
      var selectedPurchaseNumber = $('#mat_pur_po').val();
      $('#poNumberSelectedInput').val(selectedPurchaseNumber);
    }

    // Call the function when the selection changes
    $('#mat_pur_po').on('change', updateSelectedPurchaseNumber);

    // Call the function initially to set the value if there's already a selected PO number
    updateSelectedPurchaseNumber();

  </script>

  <script>
    // Function to update the value of the hidden input field with the selected godown value
    function updateGodown() {
      var godown = $('.mat_pur_godowns').val();
      $('#godownInput').val(godown);
    }

    // Call the function when the selection changes
    $('.mat_pur_godowns').on('change', updateGodown);

    // Call the function initially to set the value if there's already a selected godown value
    updateGodown();
  </script>

  <script>
    // Function to update the value of the hidden input field with the selected PO number
    function updateSelectedPoNumber() {
      var selectedPoNumber = $('#mat_pur_numbers').val();
      $('#purchase_numberInput').val(selectedPoNumber);
    }

    // Call the function when the selection changes
    $('#mat_pur_numbers').on('change', updateSelectedPoNumber);

    // Call the function initially to set the value if there's already a selected PO number
    updateSelectedPoNumber();
  </script>

  <script>
    // Function to update the value of the hidden input field with the selected godown value
    function updateSupplier() {
      var supplier = $('.supplier_name').val();
      $('#supplier_nameInput').val(supplier);
    }

    // Call the function when the selection changes
    $('.supplier_name').on('change', updateSupplier);

    // Call the function initially to set the value if there's already a selected godown value
    updateSupplier();
  </script>
  <script src="https://cdn.jsdelivr.net/npm/@yaireo/tagify"></script>

  <script>
// Initialize Tagify on the input element
    const otherExp = document.getElementById('mat_pur_other_exp');
    const tags = new Tagify(otherExp, {
    whitelist: [], // Optionally predefine tags
    enforceWhitelist: false, // Allow any tag
    dropdown: {
        enabled: 0, // Disable the dropdown for this example
      },
      tag: {
        backgroundColor: '#007bff', // Blue background color
        color: 'white', // White text color
        borderRadius: '3px',
        padding: '5px 10px',
        border: '1px solid #007bff' // Blue border color
      }
    });

// Add event listeners
    tags.on('add', function (e) {
      calculateGrandTotal();
    });

    tags.on('remove', function (e) {
      calculateGrandTotal();
    });

// Calculate grand total
    function calculateGrandTotal() {
    // Get the total amount from the input field
      var totalAmounts = parseFloat(document.getElementById('mat_pur_totalamt').value) || 0;

    // Get the GST amount from the input field
      var gstAmount = parseFloat(document.getElementById('mat_pur_gst_amnt').value) || 0;

    // Extract the numbers from the "Other Expense" tags and sum them
      var otherExpTags = tags.value;
      var otherExpTotal = otherExpTags.length > 0 ? extractNumbersFromTags(otherExpTags).reduce((acc, num) => acc + num, 0) : 0;

    // Calculate the grand total
      var grandTotal = totalAmounts + gstAmount + otherExpTotal;

    // Update the grand total input field
      document.getElementById('mat_pur_grant_total').value = grandTotal.toFixed(2);
    }

// Function to extract numbers from the tags
    function extractNumbersFromTags(tags) {
      let numbers = tags.map(tag => {
        let number = tag.value.match(/-?\d+(\.\d+)?/g); // Extract numbers from the tag
        return number ? parseFloat(number[0]) : 0; // Convert the first match to a number, or default to 0
      });
      return numbers;
    }


  </script>