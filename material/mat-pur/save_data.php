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

// Check if the form has been submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $errors = [];

  // Gather POST data (using prepared statements for security)
  $supplier_name = htmlspecialchars(trim($_POST['supplier_name'] ?? ''));
  $mat_pur_number = htmlspecialchars(trim($_POST['mat_pur_numbers'] ?? ''));
  $purchase_date = htmlspecialchars(trim($_POST['mat_pur_date'] ?? ''));
  $mat_pur_po = htmlspecialchars(trim($_POST['mat_pur_po'] ?? ''));
  $mat_pur_godowns = htmlspecialchars(trim($_POST['mat_pur_godowns'] ?? ''));
  $pur_remark = htmlspecialchars(trim($_POST['pur_remark'] ?? ''));
  $invoice_number = htmlspecialchars(trim($_POST['invoice_number'] ?? ''));
  $fileUpload = $_FILES['fileUpload'];

  $mat_pur_totalamt = $_POST['mat_pur_totalamt'] ?? '';
  $mat_pur_gst_amnt = $_POST['mat_pur_gst_amnt'] ?? 0;
  $mat_pur_other_exp = $_POST['mat_pur_other_exp'] ?? '';
  $mat_pur_grant_total = $_POST['mat_pur_grant_total'] ?? '';

  $mat_names = $_POST['mat_pur_item_matname'] ?? [];
  $alternativeUnits = $_POST['alternativeUnit'] ?? [];
  $purchase_quantities = $_POST['mat_pur_item_quant'] ?? [];
  $convertedQuantity = $_POST['convertedQuantity'] ?? [];
  $perUnit = $_POST['perUnit'] ?? [];
  $purchase_prices = $_POST['mat_pur_item_price'] ?? [];

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

  if (isset($fileUpload) && $fileUpload['error'] === 0) {
    $filePath = $fileUpload['name']; // Get the file name
    $fileTmpPath = $fileUpload['tmp_name']; // Get the temporary file path
    $uploadFileDir = 'uploaded_files/';
    $filePaths = basename($filePath); // Store only the file name in the database

    // Ensure the directory exists
    if (!is_dir($uploadFileDir)) {
        mkdir($uploadFileDir, 0755, true);
    }

    // Move the uploaded file
    if (!move_uploaded_file($fileTmpPath, $uploadFileDir . $filePaths)) {
        echo "Error moving the uploaded file.";
        exit;
    }
}


  // Start transaction
  $conn->begin_transaction();

  try {

    // Insert into mat_purs table (including file_path if file was uploaded)
    $insert_mat_pur_sql = "INSERT INTO mat_purs (mat_pur_supplier, mat_pur_number, mat_pur_date, mat_pur_po, mat_pur_godown, pur_remarks, invoice_number, mat_pur_totalamt, mat_pur_gst_amnt, mat_pur_other_exp, mat_pur_grant_total, file_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt_mat_pur = $conn->prepare($insert_mat_pur_sql);
    if (!$stmt_mat_pur) {
      throw new Exception("Prepare statement failed: " . $conn->error);
        }

        // Bind parameters, and pass NULL if no file was uploaded
    $stmt_mat_pur->bind_param("sssssssddsds",$supplier_name,$mat_pur_number,$purchase_date,$mat_pur_po,$mat_pur_godowns,$pur_remark,$invoice_number,$mat_pur_totalamt,$mat_pur_gst_amnt,$mat_pur_other_exp_string,$mat_pur_grant_total,$filePaths);
  
      if (!$stmt_mat_pur->execute()) {
        throw new Exception("Error inserting into mat_purs: " . $stmt_mat_pur->error);
      }
  
      $stmt_mat_pur->close(); // Close the statement
  
      // Insert into mat_pur_items table (using prepared statement)
      $insert_mat_pur_items_sql = "INSERT INTO mat_pur_item (mat_pur_po, mat_pur_number,mat_pur_date, mat_pur_item_matname, alternativeUnit, mat_pur_item_quant, convertedQuantity, perUnit, mat_pur_item_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
      $stmt_mat_pur_items = $conn->prepare($insert_mat_pur_items_sql);
      if (!$stmt_mat_pur_items) {
        throw new Exception("Prepare statement failed: " . $conn->error);
      }
  
      // Loop through each material in the form submission
      foreach ($mat_names as $index => $mat_name) {
        $alternative_unit = $alternativeUnits[$index];
        $quantity = $purchase_quantities[$index];
        $converted_qty = $convertedQuantity[$index];
        $unit_price = $perUnit[$index];
        $price = $purchase_prices[$index];
  
        $stmt_mat_pur_items->bind_param("sssssdddd", $mat_pur_po, $mat_pur_number,$purchase_date, $mat_name, $alternative_unit, $quantity, $converted_qty, $unit_price, $price);
  
        if (!$stmt_mat_pur_items->execute()) {
          throw new Exception("Error inserting into mat_pur_item: " . $stmt_mat_pur_items->error);
        }
      }
  
      // Commit the transaction
      $conn->commit();
  
      // Success message
      echo '<div class="alert alert-success">Purchase details have been successfully saved, including the file!</div>';
  
    } catch (Exception $e) {
      // Rollback the transaction in case of error
      $conn->rollback();
      echo '<div class="alert alert-danger">Transaction failed: ' . htmlspecialchars($e->getMessage()) . '</div>';
    } finally {
      // Close the prepared statement
    //   $stmt_mat_pur_items->close();
    }
  
    if (isset($_POST['mat_pur_item_matname'], $_POST['convertedQuantity'])) {

      $mat_names = $_POST['mat_pur_item_matname'];
      $convertedQuantity = $_POST['convertedQuantity'];
      $purchase_date = $_POST['mat_pur_date']; // Purchase date from form
      
      // Make sure to retrieve the godown value from your form as well.
      // For example:
      $mat_pur_godowns = $_POST['mat_pur_godowns']; // Adjust the key as per your form
  
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
              // Convert the quantity to float and keep its decimal portion
              $quantity_to_add = (float)$convertedQuantity[$index];
  
              // Check if material has already been processed
              if (!in_array($mat_name, $processed_materials)) {
                  $found = false;
  
                  foreach ($existing_mat_stock_ary as &$material) {
                      if ($material['mn'] === $mat_name && $material['gd'] === $mat_pur_godowns) {
                          $material['pq'] += $quantity_to_add;
                          $found = true;
                          break;
                      }
                  }
  
                  // If the material is new, append it to the array
                  if (!$found) {
                      $existing_mat_stock_ary[] = [
                          'mn' => $mat_name,
                          'gd' => $mat_pur_godowns,
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
              $quantity_to_add = (float)$convertedQuantity[$index];
  
              // Avoid duplicate entries in the new array
              $already_exists = false;
              foreach ($new_mat_stock_ary as $material) {
                  if ($material['mn'] === $mat_name && $material['gd'] === $mat_pur_godowns) {
                      $already_exists = true;
                      break;
                  }
              }
  
              if (!$already_exists) {
                  $new_mat_stock_ary[] = [
                      'mn' => $mat_name,
                      'gd' => $mat_pur_godowns,
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
