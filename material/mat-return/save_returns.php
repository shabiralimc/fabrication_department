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

    $mat_ret_date = $_POST['mat_ret_date'] ?? '';
    $ret_remarks = $_POST['ret_remarks'] ?? '';
    $mat_pur_number = $_POST['mat_pur_number'] ?? '';
    $mat_pur_godown = $_POST['mat_pur_godowns'] ?? '';
    $total_return_amount = $_POST['total_return_amount'] ?? '';
    $mat_pur_other_exp = $_POST['mat_pur_other_exp'] ?? '';
    $gst_amount = $_POST['gst_amount'] ?? '';
    $total_amount = $_POST['total_amount'] ?? '';
    $mat_pur_item_matname = $_POST['mat_pur_item_matname'] ?? [];
    $convertedQuantity = $_POST['convertedQuantity'] ?? [];
    $mat_pur_item_id = $_POST['mat_pur_item_id'] ?? [];
    $mat_ret_quanity = $_POST['mat_ret_quanity'] ?? [];
    $mat_ret_amount = $_POST['mat_ret_amount'] ?? [];
    $newReturnId = $_POST['returnNumber'] ?? '';

    // Convert mat_pur_other_exp from JSON array to string
    $mat_pur_other_exp_array = json_decode($mat_pur_other_exp, true);
    $mat_pur_other_exp_string = '';
    if ($mat_pur_other_exp_array) {
        $other_exp_parts = [];
        foreach ($mat_pur_other_exp_array as $exp) {
            $other_exp_parts[] = $exp['value']; // Use the 'value' part of each tag
        }
        $mat_pur_other_exp_string = implode(',', $other_exp_parts);
    }

    // Validate required fields
    if (empty($mat_ret_date)) $errors[] = 'Return Date is required';
    if (empty($ret_remarks)) $errors[] = 'Remarks are required';
    if (empty($mat_pur_number)) $errors[] = 'Purchase Number is required';
    if (empty($mat_pur_godown)) $errors[] = 'Godown is required';
    if (empty($convertedQuantity)) $errors[] = 'Converted Quantity is required';
    if (empty($mat_pur_item_id)) $errors[] = 'Purchase ID is required';
    if (empty($mat_ret_quanity)) $errors[] = 'Return Quantity is required';
    if (empty($mat_ret_amount)) $errors[] = 'Amount is required';

    if (!empty($errors)) {
        echo '<div class="alert alert-danger"><ul>';
        foreach ($errors as $error) {
            echo '<li>' . htmlspecialchars($error) . '</li>';
        }
        echo '</ul></div>';
    } else {
        // Prepare SQL for inserting into mat_pur_return table
        $insert_mat_pur_sql = "INSERT INTO mat_pur_return (mat_ret_date, ret_remarks, mat_pur_number, mat_pur_item_id, mat_ret_quanity, mat_ret_amount, mat_pur_godown, total_return_amount, mat_pur_other_exp, gst_amount, total_amount, returnNumber) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_mat_pur = $conn->prepare($insert_mat_pur_sql);

        if ($stmt_mat_pur === false) {
            die('Prepare failed: ' . htmlspecialchars($conn->error));
        }

        // Loop through the arrays and process each row
        for ($i = 0; $i < count($mat_pur_item_id); $i++) {
            $itemId = $mat_pur_item_id[$i];
            $quantity = $mat_ret_quanity[$i];       // This is the actual return quantity
            $amount = $mat_ret_amount[$i];
            $material_name = $mat_pur_item_matname[$i];
            $convertedQty = $convertedQuantity[$i];

            // Bind the correct variables.
            // Note: We now bind $quantity (the actual return quantity) rather than $convertedQty
            $stmt_mat_pur->bind_param(
                "sssiddsdsdds",
                $mat_ret_date,           // s
                $ret_remarks,            // s
                $mat_pur_number,         // s
                $itemId,                 // i
                $quantity,               // d (corrected: use $quantity instead of $convertedQty)
                $amount,                 // d
                $mat_pur_godown,         // s
                $total_return_amount,    // d
                $mat_pur_other_exp_string, // s
                $gst_amount,             // d
                $total_amount,           // d
                $newReturnId             // s
            );
            if (!$stmt_mat_pur->execute()) {
                echo "Error: " . $stmt_mat_pur->error;
                continue;
            }

            // Check and update mat_stock_ary in mat_stocks table
            $stmt_check_stock = $conn->prepare("SELECT mat_stock_ary FROM mat_stocks WHERE stk_date = ?");
            $stmt_check_stock->bind_param("s", $mat_ret_date);
            $stmt_check_stock->execute();
            $result_check_stock = $stmt_check_stock->get_result();

            if ($result_check_stock->num_rows > 0) {
                // Date exists, update 'pr' in mat_stock_ary
                $row_stock = $result_check_stock->fetch_assoc();
                $mat_stock_ary = json_decode($row_stock['mat_stock_ary'], true);

                $found = false;
                foreach ($mat_stock_ary as &$material) {
                    if ($material['mn'] == $material_name && $material['gd'] == $mat_pur_godown) {
                        // Fetch current 'pr' and add the new quantity
                        $material['pr'] = isset($material['pr']) ? $material['pr'] + $convertedQty : $convertedQty;
                        $found = true;
                        break;
                    }
                }

                if ($found) {
                    // Update the stock table
                    $updated_mat_stock_ary_json = json_encode($mat_stock_ary);
                    $stmt_update_stock = $conn->prepare("UPDATE mat_stocks SET mat_stock_ary = ? WHERE stk_date = ?");
                    $stmt_update_stock->bind_param("ss", $updated_mat_stock_ary_json, $mat_ret_date);
                    if (!$stmt_update_stock->execute()) {
                        echo "Error updating mat_stocks: " . $stmt_update_stock->error;
                    }
                    $stmt_update_stock->close();
                } else {
                    echo "<script>alert('No matching material found in stock for date $mat_ret_date!');</script>";
                }
            } else {
                // Date doesn't exist, insert new row into mat_stocks table
                $new_mat_stock_ary = json_encode([
                    [
                        'mn' => $material_name,
                        'gd' => $mat_pur_godown,
                        'pr' => $convertedQty,
                        'pq' => 0,
                        'co' => 0
                    ]
                ]);

                $stmt_insert_stock = $conn->prepare("INSERT INTO mat_stocks (stk_date, mat_stock_ary) VALUES (?, ?)");
                $stmt_insert_stock->bind_param("ss", $mat_ret_date, $new_mat_stock_ary);
                if (!$stmt_insert_stock->execute()) {
                    echo "Error inserting new mat_stocks entry: " . $stmt_insert_stock->error;
                }
                $stmt_insert_stock->close();
            }

            $stmt_check_stock->close();
        }

        $stmt_mat_pur->close();
        echo '<div class="alert alert-success">Data successfully inserted, and quantities updated.</div>';
    }
}
?>
