<?php
include_once('../../../include/php/connect.php');
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

if (isset($_GET['jc_number'])) {
    $newUserId = $_GET['jc_number'];
    $_SESSION['new'] = $newUserId;
    $userbranch = $_SESSION['branch'];

// Use prepared statements to fetch data based on jc_number for jobcard_main
$sql_main = "SELECT * FROM jobcard_main WHERE jc_number = ?";
$stmt_main= mysqli_prepare($conn, $sql_main);

if ($stmt_main) {
mysqli_stmt_bind_param($stmt_main, "s", $newUserId);
mysqli_stmt_execute($stmt_main);
$result_main = mysqli_stmt_get_result($stmt_main);
$row_main = mysqli_fetch_assoc($result_main);
mysqli_stmt_close($stmt_main);
}
$sql_item = mysqli_query($conn, "SELECT * FROM jobcard_items WHERE jc_number='$newUserId'");

$sql = "SELECT * FROM fabrication_main WHERE jc_number=? AND fam_branch LIKE '%$userbranch%'";
$stmt = $conn->prepare($sql);

if ($stmt) {
$stmt->bind_param('s', $newUserId);
$stmt->execute();

// Fetch the result
$result = $stmt->get_result();
$rowses = $result->fetch_assoc();
$stmt->close();
} else {

// Handle the case where preparing the statement fails
echo "Error preparing statement: " . $conn->error;
}

// Check if $rowses is not null before accessing its elements
if ($rowses !== null) {
$current_status = $rowses['fab_status'];
$fab_supervisor = $rowses['fab_supervisor'];
} else {
// Handle the case where $rowses is null (e.g., data not found)
$current_status = null;  // You can set a default value or handle it based on your logic
$fab_supervisor = null;
}

$existingProduct = []; // Initialize as an empty array

// Check if the record with the given jc_number exists in the fab_material_expences  table
$check_product_sql = "SELECT jc_number FROM fab_mat_expences WHERE jc_number = ? AND fama_branch LIKE '%$userbranch%'";
$stmt_check_product = mysqli_prepare($conn, $check_product_sql);

if ($stmt_check_product) {
mysqli_stmt_bind_param($stmt_check_product, "s", $newUserId);
mysqli_stmt_execute($stmt_check_product);
$result_check_product = mysqli_stmt_get_result($stmt_check_product);

if (mysqli_num_rows($result_check_product) > 0) {
$isUpdateProduct = true;

$sql_product = "SELECT * FROM fab_mat_expences WHERE jc_number = ? AND fama_branch LIKE '%$userbranch%'";

$stmt_product = mysqli_prepare($conn, $sql_product);

if ($stmt_product) {
mysqli_stmt_bind_param($stmt_product, "s", $newUserId);
mysqli_stmt_execute($stmt_product);
$result_product = mysqli_stmt_get_result($stmt_product);

// Loop through the results if needed
while ($row_product = mysqli_fetch_assoc($result_product)) {
// Store data for each row in the array
$existingProduct[] = $row_product; // Uncomment this line to store data
}
// Close the $stmt_items after the loop
mysqli_stmt_close($stmt_product);
} else {
$isUpdateProduct = false;
}

mysqli_stmt_close($stmt_check_product);
} else {
$isUpdateProduct = false;
}
}

$existingLabour = []; // Initialize as an empty array

// Check if the record with the given jc_number exists in the creative_items table
$check_labour_sql = "SELECT jc_number FROM fab_labour_expences WHERE jc_number = ? AND fal_branch LIKE '%$userbranch%'";
$stmt_check_labour = mysqli_prepare($conn, $check_labour_sql);

if ($stmt_check_labour) {
mysqli_stmt_bind_param($stmt_check_labour, "s", $newUserId);
mysqli_stmt_execute($stmt_check_labour);
$result_check_labour = mysqli_stmt_get_result($stmt_check_labour);

// If the record exists in creative_items, fetch and display existing data
if (mysqli_num_rows($result_check_labour) > 0) {
$isUpdateLabour = true;
$sql_labour = "SELECT id, expences,type,name, place,date,endtime,total_ot,labour_cost,regular_time,regular_expences,total_lab_cost, fal_branch FROM fab_labour_expences WHERE jc_number = ?";
$stmt_labour = mysqli_prepare($conn, $sql_labour);

if ($stmt_labour) {
mysqli_stmt_bind_param($stmt_labour, "s", $newUserId);
mysqli_stmt_execute($stmt_labour);
$result_labour = mysqli_stmt_get_result($stmt_labour);

// // Loop through the results if needed
while ($row_labour = mysqli_fetch_assoc($result_labour)) {

// Store data for each row in the array
$existingLabour[] = $row_labour; // Uncomment this line to store data
}

// Close the $stmt_items after the loop
mysqli_stmt_close($stmt_labour);
}
} else {
$isUpdateLabour = false;
}

mysqli_stmt_close($stmt_check_labour);
} else {
$isUpdateLabour = false;
}
$existingTransport = []; // Initialize as an empty array

// Check if the record with the given jc_number exists in the creative_items table
$check_transport_sql = "SELECT jc_number FROM fab_transport_expences WHERE jc_number = ? AND fat_branch LIKE '%$userbranch%'";
$stmt_check_transport = mysqli_prepare($conn, $check_transport_sql);

if ($stmt_check_transport) {
mysqli_stmt_bind_param($stmt_check_transport, "s", $newUserId);
mysqli_stmt_execute($stmt_check_transport);
$result_check_transport = mysqli_stmt_get_result($stmt_check_transport);

// If the record exists in creative_items, fetch and display existing data
if (mysqli_num_rows($result_check_transport) > 0) {
$isUpdateTransport = true;
$sql_transport = "SELECT id,fab_tran_date, staff_name,vehicle,`from`, `to`, km, cost, fat_branch FROM fab_transport_expences WHERE jc_number = ?";
$stmt_transport = mysqli_prepare($conn, $sql_transport);

if ($stmt_transport) {
mysqli_stmt_bind_param($stmt_transport, "s", $newUserId);
mysqli_stmt_execute($stmt_transport);
$result_transport = mysqli_stmt_get_result($stmt_transport);


// // Loop through the results if needed
while ($row_transport = mysqli_fetch_assoc($result_transport)) {
// Store data for each row in the array
$existingTransport[] = $row_transport; // Uncomment this line to store data
}

// Close the $stmt_items after the loop
mysqli_stmt_close($stmt_transport);
}
} else {
$isUpdateTransport = false;
}

mysqli_stmt_close($stmt_check_transport);
} else {
$isUpdateTransport = false;
}
$existingOther = []; // Initialize as an empty array

// Check if the record with the given jc_number exists in the creative_items table
$check_other_sql = "SELECT jc_number FROM fab_other_expences WHERE jc_number = ? AND fao_branch LIKE '%$userbranch%'";
$stmt_check_other = mysqli_prepare($conn, $check_other_sql);

if ($stmt_check_other) {
mysqli_stmt_bind_param($stmt_check_other, "s", $newUserId);
mysqli_stmt_execute($stmt_check_other);
$result_check_other = mysqli_stmt_get_result($stmt_check_other);

// If the record exists in creative_items, fetch and display existing data
if (mysqli_num_rows($result_check_other) > 0) {
$isUpdateOther = true;
$sql_other = "SELECT id,fab_other_date, staff_names,exp,remark, other_costs, fao_branch FROM fab_other_expences WHERE jc_number = ?";
$stmt_other = mysqli_prepare($conn, $sql_other);
if ($stmt_other) {
mysqli_stmt_bind_param($stmt_other, "s", $newUserId);
mysqli_stmt_execute($stmt_other);
$result_other = mysqli_stmt_get_result($stmt_other);

// Loop through the results if needed
while ($row_other = mysqli_fetch_assoc($result_other)) {

// Store data for each row in the array
$existingOther[] = $row_other; // Uncomment this line to store data
}

// Close the $stmt_items after the loop
mysqli_stmt_close($stmt_other);
}
} else {
$isUpdateOther = false;
}

mysqli_stmt_close($stmt_check_other);
} else {
$isUpdateOther = false;
}


}

/// Add the form submission handling logic
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if there are any new rows to insert
    if (isset($_POST['product_date'], $_POST['activity'], $_POST['material_name'], $_POST['measuring_unit'], $_POST['quantity'], $_POST['per_cost'], $_POST['total_cost'], $_POST['fama_branch'], $_POST['product_id'])) {
        // Prepare the statement for inserting new rows
        $insert_product_sql = "INSERT INTO fab_mat_expences (jc_number, product_date, activity, material_name, measuring_unit, quantity, per_cost, total_cost, fama_branch) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_product = $conn->prepare($insert_product_sql);
        $stmt_product->bind_param("sssssddds", $newUserId, $date, $activity, $material_name, $measuring_unit, $quantity, $per_cost, $total_cost, $fama_branch);

        // Retrieve data for new rows and insert them one by one
        $datess = $_POST['product_date'];
        $activities = $_POST['activity'];
        $material_names = $_POST['material_name'];
        $measuring_units = $_POST['measuring_unit'];
        $quantities = $_POST['quantity'];
        $per_costs = $_POST['per_cost'];
        $total_costs = $_POST['total_cost'];
        $product_ids = $_POST['product_id']; // Add product_id field to identify existing rows
        $fama_branchs = $_POST['fama_branch'];

        for ($i = 0; $i < count($datess); $i++) {
            // Check if the product_id is empty, indicating a new row
            if (empty($product_ids[$i])) {
                $date = $datess[$i];
                $activity = $activities[$i];
                $material_name = $material_names[$i];
                $measuring_unit = $measuring_units[$i];
                $quantity = $quantities[$i];
                $per_cost = $per_costs[$i];
                $total_cost = $total_costs[$i];
                $fama_branch = $fama_branchs[$i];
                // Execute the prepared statement to insert the data
                $stmt_product->execute();
            }
        }

        // Close the statement after insertion
        $stmt_product->close();
    }



// Labour Form
if (isset($_POST['expences'], $_POST['type'], $_POST['name'], $_POST['place'], $_POST['date'], $_POST['endtime'], $_POST['total_ot'], $_POST['labour_cost'],$_POST['regular_time'],$_POST['regular_expences'],$_POST['total_lab_cost'],$_POST['fal_branch'])) {
    $expences = $_POST['expences'];
    $types = $_POST['type'];
    $names = $_POST['name'];
    $places = $_POST['place'];
    $dates = $_POST['date'];
    $endtimes = $_POST['endtime'];
    $total_ots = $_POST['total_ot'];
    $costs = $_POST['labour_cost'];
    $regular_times = $_POST['regular_time'];
    $regular_expencess = $_POST['regular_expences'];
    $total_lab_costs = $_POST['total_lab_cost'];
    $fal_branchs = $_POST['fal_branch'];
    $labourIds = $_POST['labour_id']; // Add item_id field to identify existing rows

    // Loop through the arrays and insert/update each row separately
    for ($i = 0; $i < count($expences); $i++) {
    $expence = isset($expences[$i]) ? $expences[$i] : '';
    $type = isset($types[$i]) ? $types[$i] : '';
    $name = isset($names[$i]) ? $names[$i] : '';
    $place = isset($places[$i]) ? $places[$i] : '';
    $date = isset($dates[$i]) ? $dates[$i] : '';
    $endtime = isset($endtimes[$i]) ? $endtimes[$i] : '';
    $total_ot = isset($total_ots[$i]) ? $total_ots[$i] : '';
    $cost = isset($costs[$i]) ? $costs[$i] : '';
    $regular_time = isset($regular_times[$i]) ? $regular_times[$i] : '';
    $regular_expences = isset($regular_expencess[$i]) ? $regular_expencess[$i] : '';
    $total_lab_cost = isset($total_lab_costs[$i]) ? $total_lab_costs[$i] : '';
    $fal_branch = isset($fal_branchs[$i]) ? $fal_branchs[$i] : '';
    $labourId = isset($labourIds[$i]) ? $labourIds[$i] : ''; // Get the labour_id for this row
    
    // Check if this row should be updated or inserted
    if (!empty($labourId)) {
    // Update
    $sql = "UPDATE fab_labour_expences SET expences=?, `type`=?, `name`=?, place=?, `date`=?, endtime=?, total_ot=?, labour_cost=?,regular_time=?,regular_expences=?,total_lab_cost=?, fal_branch=? WHERE id=?";
    } else {
    // Insert
    if ($expence === "labour" || $expence === "rework" || $expence === "rectification") {
        // Insert all inputs when $expence is not "Bata"
        $sql = "INSERT INTO fab_labour_expences (jc_number, expences, `type`, `name`, place, `date`, endtime, total_ot, labour_cost,regular_time,regular_expences,total_lab_cost, fal_branch) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)";
    } else if ($expence === "bata") {
        // Insert without data, endtime, and total_ot when $expence is "Bata"
        $sql = "INSERT INTO fab_labour_expences (jc_number, expences, `type`, `name`, place, `date`, total_lab_cost,fal_branch) VALUES (?,?,?,?,?,?,?,?)";
    }
    }
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        // Error handling if the preparation of SQL statement fails
        die('Error preparing SQL statement: ' . $conn->error);
    }

    if (!empty($labourId)) {
        $stmt->bind_param("sssssssdsddis", $expence, $type, $name, $place, $date, $endtime, $total_ot, $cost,$regular_time,$regular_expences,$total_lab_cost, $labourId, $fal_branch);
        } else {
        if ($expence === "labour" || $expence === "rework" || $expence === "rectification") {
            $stmt->bind_param("ssssssssdsdds", $newUserId, $expence, $type, $name, $place, $date, $endtime, $total_ot,$cost,$regular_time,$regular_expences,$total_lab_cost,$fal_branch);
        } else if ($expence === "bata") {
            $stmt->bind_param("ssssssds", $newUserId, $expence, $type, $name, $place, $date, $total_lab_cost,$fal_branch);
        }
    }
    $stmt->execute();

    if ($stmt->error) {
        // Error handling if the execution of the SQL statement fails
        die('Error executing SQL statement: ' . $stmt->error);
    }

    $stmt->close();
}
}
    
    if (isset($_POST['fab_tran_date'],$_POST['staff_name'],$_POST['vehicle'], $_POST['from'], $_POST['to'], $_POST['km'], $_POST['cost'],$_POST['fat_branch'])) {
        $fab_tran_dates =$_POST['fab_tran_date'];
        $staff_names = $_POST['staff_name'];
        $vehicles = $_POST['vehicle'];
        $froms = $_POST['from'];
        $tos = $_POST['to'];
        $kms = $_POST['km'];
        $costss = $_POST['cost'];
        $fat_branchs = $_POST['fat_branch'];
        $transportIds = $_POST['transport_id']; // Add item_id field to identify existing rows
        
        // Update the existing record in the creative_items table
        $update_transport_sql = "UPDATE fab_transport_expences SET fab_tran_date=?,staff_name=?, vehicle=?, `from`=?, `to`=?, km=?, cost=?, fat_branch=? WHERE id=?";
        $stmt_transport1 = $conn->prepare($update_transport_sql);
        $stmt_transport1->bind_param("sssssddsi",$fab_tran_date, $staff_name, $vehicle, $from, $to, $km, $costs, $fat_branch, $transportId);
        
        // Insert a new record into the creative_items table
        $insert_transport_sql = "INSERT INTO fab_transport_expences (jc_number,fab_tran_date,vehicle, staff_name, `from`, `to`, km, cost, fat_branch) VALUES (?,?,?,?,?,?,?,?,?)";
        $stmt_transport2 = $conn->prepare($insert_transport_sql);
        $stmt_transport2->bind_param("ssssssdds", $newUserId,$fab_tran_date, $vehicle, $staff_name, $from, $to, $km, $costs,$fat_branch);
        
        // Loop through the arrays and insert/update each row separately
        for ($i = 0; $i < count($staff_names); $i++) {
            $fab_tran_date = isset($fab_tran_dates[$i]) ? $fab_tran_dates[$i] : null;
            $staff_name = isset($staff_names[$i]) ? $staff_names[$i] : null;
            $vehicle = isset($vehicles[$i]) ? $vehicles[$i] : null;
            $from = isset($froms[$i]) ? $froms[$i] : null;
            $to = isset($tos[$i]) ? $tos[$i] : null;
            $km = isset($kms[$i]) ? $kms[$i] : null;
            $costs = isset($costss[$i]) ? $costss[$i] : null;
            $fat_branch = isset($fat_branchs[$i]) ? $fat_branchs[$i] : null;
            $transportId = isset($transportIds[$i]) ? $transportIds[$i] : null;
            
            // Check if this row should be updated or inserted
            if (!empty($transportId)) {
                // Update
                $stmt_transport1->execute();
                // echo "Transportation Data updated successfully.";
            } else {
                // Insert
                $stmt_transport2->execute();
                // echo "Transportation Data inserted successfully.";
            }
        }    
        
        // Close the statements after the loop
        $stmt_transport1->close();
        $stmt_transport2->close();
    }
    
    
    // Other Expenses Form
    if (isset($_POST['fab_other_date'],$_POST['staff_names'], $_POST['exp'], $_POST['remark'], $_POST['other_costs'], $_POST['fao_branch'])) {
        // Assuming $conn is your database connection object
        $fab_other_dates = $_POST['fab_other_date'];
        $staff_names = $_POST['staff_names'];
        $exps = $_POST['exp'];
        $remarks = $_POST['remark'];
        $other_costs = $_POST['other_costs'];
        $fao_branchs = $_POST['fao_branch'];
        $otherIds = $_POST['other_id']; // Add item_id field to identify existing rows
        
        // Loop through the arrays and insert/update each row separately
        for ($i = 0; $i < count($staff_names); $i++) {
            $fab_other_date = isset($fab_other_dates[$i]) ? $fab_other_dates[$i] : null;
            $staff_name = isset($staff_names[$i]) ? $staff_names[$i] : null;
            $exp = isset($exps[$i]) ? $exps[$i] : null;
            $remark = isset($remarks[$i]) ? $remarks[$i] : null;
            $other_cost = isset($other_costs[$i]) ? $other_costs[$i] : null;
            $fao_branch = isset($fao_branchs[$i]) ? $fao_branchs[$i] : null;
            $otherId = isset($otherIds[$i]) ? $otherIds[$i] : null;
            
            // Check if this row should be updated or inserted
            if (!empty($otherId)) {
                // Update
                $update_other_sql = "UPDATE fab_other_expences SET fab_other_date=?,staff_names=?, exp=?, remark=?, other_costs=?, fao_branch=? WHERE id=?";
                $stmt_other1 = $conn->prepare($update_other_sql);
                if ($stmt_other1) {
                    $stmt_other1->bind_param("ssssdsi",$fab_other_date, $staff_name, $exp, $remark, $other_cost, $fao_branch, $otherId);
                    $stmt_other1->execute();
                    $stmt_other1->close();
                } else {
                    echo "Error preparing update statement: " . mysqli_error($conn);
                }
            } else {
                // Insert
                $insert_other_sql = "INSERT INTO fab_other_expences (jc_number,fab_other_date, staff_names, exp, remark, other_costs, fao_branch) VALUES (?,?,?,?,?,?,?)";
                $stmt_other2 = $conn->prepare($insert_other_sql);
                if ($stmt_other2) {
                    $stmt_other2->bind_param("sssssds", $newUserId,$fab_other_date, $staff_name, $exp, $remark, $other_cost,$fao_branch);
                    $stmt_other2->execute();
                    $stmt_other2->close();
                } else {
                    echo "Error preparing insert statement: " . mysqli_error($conn);
                }
            }
        }
    }
    
// Retrieve form data
$labour_total = isset($_POST['labour_total']) ? $_POST['labour_total'] : null;
$transport_total = isset($_POST['transport_total']) ? $_POST['transport_total'] : null;
$other_total = isset($_POST['other_total']) ? $_POST['other_total'] : null;
$material_total = isset($_POST['material_total']) ? $_POST['material_total'] : null; // Retrieve material_total
$current_status = isset($_POST['fab_status']) ? $_POST['fab_status'] : null;
$grand_total = isset($_POST['grand_total']) ? $_POST['grand_total'] : null;
$fab_supervisor = isset($_POST['fab_supervisor']) ? $_POST['fab_supervisor'] : null;
$total_id = $_POST['id'];
$fam_branch = isset($_POST['fam_branch']) ? $_POST['fam_branch'] : null;

// Check if the record already exists in the fabrication_main table
$checkQuery = "SELECT * FROM fabrication_main WHERE jc_number = '$newUserId' AND fam_branch LIKE '%$userbranch%'";
$checkResult = mysqli_query($conn, $checkQuery);    

if (mysqli_num_rows($checkResult) > 0) {
    // Update the existing record
    $updateQuery = "UPDATE fabrication_main SET labour_total='$labour_total', transport_total='$transport_total', other_total='$other_total', material_total='$material_total', fab_status='$current_status', grand_total='$grand_total', fab_supervisor='$fab_supervisor', fam_branch = '$fam_branch' WHERE id = '$total_id'";
    mysqli_query($conn, $updateQuery);
    // echo "Record updated successfully";
} else {
    date_default_timezone_set('Asia/Kolkata'); // Set timezone to IST
    $insert_fab_date = date("Y-m-d");
    // Insert a new record
    $insertQuery = "INSERT INTO fabrication_main (jc_number, fab_work_startdate, labour_total, transport_total, other_total, material_total, fab_status, grand_total, fab_supervisor, fam_branch) VALUES ('$newUserId', '$insert_fab_date', '$labour_total', '$transport_total', '$other_total', '$material_total', '$current_status', '$grand_total', '$fab_supervisor', '$fam_branch')";
    mysqli_query($conn, $insertQuery);
    // echo "Record inserted successfully";
}


// Display a success message after all sections are processed
echo "<script>alert('All data updated successfully'); window.location = 'fab-work-edit.php?jc_number=$newUserId';</script>";
}
?>

<!-- Include Header File -->
<?php include_once ('../../../include/php/header.php') ?>

<!-- Include Sidebar File -->
<?php include_once ('../../../include/php/sidebar-fab.php') ?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <!-- Content Header (Page header) -->
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0">FAB WORK INVOLVEMENT</h1>
        </div>
      </div><!-- /.row -->
    </div>
    <!-- /.container-fluid -->
  </div>
  <!-- /.content-header -->

  <!-- Main content -->
  <section class="content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-md-12">

          <form action=""method="POST"id="newForm">

            <div class="card card-info card-outline">
              
              <div class="card-body">
                <div class="row">

                  <div class="col-sm-4">
                    <div class="small-box bg-default">
                      <div class="inner">
                        <h3><?php echo $row_main['jc_number'];?></h3>
                        <p>JC Number</p>
                      </div>
                      <div class="icon">
                        <i class="ion ion-ios-paper"></i>
                      </div>
                    </div>
                  </div>
                  <div class="col-sm-4">
                    <div class="small-box bg-default">
                      <div class="inner">
                        <h3><?php echo date('d-m-Y', strtotime($row_main['jc_date']));?></h3>
                        <p>JC Date</p>
                      </div>
                      <div class="icon">
                        <i class="ion ion-ios-paper"></i>
                      </div>
                    </div>
                  </div>
                  <div class="col-sm-4">
                    <div class="small-box bg-default">
                      <div class="inner">
                        <h3><?php echo $row_main['client'];?></h3>
                        <p>Client</p>
                      </div>
                      <div class="icon">
                        <i class="ion ion-ios-paper"></i>
                      </div>
                    </div>
                  </div>

                </div>
              </div>

              <div class="card-footer">
                <h3>List of Jobs</h3>
                <table border="3"class="table table-striped table-bordered">

                  <thead>
                    <tr>
                      <th>Description</th>
                      <th>Width</th>
                      <th>Height</th>
                      <th>Unit</th>
                      <th>Quantity</th>
                      <th>Amount</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    while ($row = mysqli_fetch_assoc($sql_item)) {
                    ?>
                    <tr>
                      <td><?php echo $row['s_description']; ?></td>
                      <td><?php echo $row['width']; ?></td>
                      <td><?php echo $row['height']; ?></td>
                      <td><?php echo $row['unit']; ?></td>
                      <td><?php echo $row['qty']; ?></td>
                      <td><?php echo $row['amount']; ?></td>
                    </tr>
                    <?php } ?>
                  </tbody>
                </table>  
              </div>

            </div> 

            <div class="card card-info card-outline">

              <div class="card-header">
                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#exampleModal">Labour Expenses</button>
                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#exampleModals">Transportation Expenses</button>
                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#exampleModalses">Other Expenses</button>
              </div>

              <div class="card-body">
                <table id="datatable" class="table table-striped table-bordered">
                  
                  <thead>
                    <tr>
                      <th></th>
                      <th>Date</th>
                      <th>Activity</th>
                      <th>Materials</th>
                      <th>Measuring Unit</th>
                      <th>Quantity</th>
                      <th>Per Cost</th>
                      <th>Total Cost</th>
                      </tr>
                  </thead>
                  <tbody>
                    <?php
                    $rowCountable = 0; // Reset the counter before each row
                    // Loop through existing data to pre-fill the form fields
                    foreach ($existingProduct as $row_product) {
                    $rowCountable++;
                    echo '<tr id="mat_row' . $rowCountable . '">';
                    echo '<td><input type="checkbox" name="deleterow[]" class="deleterow-checkboxs"></td>';
                    
                    echo '<td><input type="text" class="form-control product_date" name="product_date[]" id="product_date' . $rowCountable . '" value="' . $row_product['product_date'] . '"readonly></td>';
                    echo '<td><input type="text"class="form-control activity"name="activity[]" id="activity' . $rowCountable . '" value="' . $row_product['activity'] . '"readonly></td>';
                    echo '<td><input type="text"class="form-control material_name" name="material_name[]" id="material_name' . $rowCountable . '" value="' . $row_product['material_name'] . '"readonly></td>';
                    echo '<td><input type="text"class="form-control measuring_unit" name="measuring_unit[]" id="measuring_unit' . $rowCountable . '" value="' . $row_product['measuring_unit'] . '"readonly></td>';
                    echo '<td><input type="number"class="form-control quantity" name="quantity[]" step="0.01" id="quantity' . $rowCountable . '" value="' . $row_product['quantity'] . '"readonly></td>';
                    echo '<td><input type="number"class="form-control per_cost" name="per_cost[]" id="per_cost' . $rowCountable . '" value="' . $row_product['per_cost'] . '"readonly></td>';
                    echo '<td><input type="number" class="form-control total_cost"name="total_cost[]" id="total_cost' . $rowCountable . '" value="' . $row_product['total_cost'] . '"readonly><input type="hidden" name="product_id[]" id="product_id' . $rowCountable . '" value="' . $row_product['id'] . '"><input type="hidden" name="fama_branch[]" id="fama_branch' . $rowCountable . '" value="' . $row_product['fama_branch'] . '"></td>';
                    echo '</tr>';
                    } 
                    ?>
                  </tbody>
                </table>

                <div class="row">
                  <div class="col-md-6">
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label" for="material_total">Total Material Cost</label>
                      <input type="number"class="form-control"id="material_total"name="material_total" value="<?php echo isset($material_total) ? $material_total : ''; ?>"readonly >
                    </div>
                  </div>
                </div>

              </div>
              <div class="card-footer">
                <button type="button"name="addrow"id="addrow"class="btn btn-primary">Add Material</button>
                <button type="button"name="deleterow"id="deleterow"class="btn btn-danger">Delete</button>
              </div>

            </div>

            <div class="card card-info card-outline">
              <div class="card-body">

                <table border="5" class="table table-striped table-bordered">
                  <thead>
                    <tr>
                      <th>Grand Total Expences </th>
                      <th>Current Status</th>
                      <th>Supervisor</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td><input type="number"class="form-control" id="grand_total" name="grand_total" value="<?php echo isset($rowses['grand_total']) ? $rowses['grand_total'] : ''; ?>" class="form-control" readonly></td>
                      <td>
                        <select class="form-control status-curr" name="fab_status" id="fab_status"required>
                          <option value="" selected disabled>Choose an Option</option>
                          <option value="wip" <?php if ($current_status == 'wip') echo 'selected'; ?>>Work In Progress</option>
                          <option value="completed" <?php if ($current_status == 'completed') echo 'selected'; ?>>Completed</option>
                        </select>
                      </td>
<?php

// Loggedin User Branch
$userbranch = $_SESSION['branch'];

// Query to select staff names from the database
$query_staffs = "SELECT * FROM staffs_masters WHERE designation ='Supervisor' AND branch LIKE '%$userbranch%' AND status LIKE 'active' ORDER BY staff_name ASC";

// Query to fetch the saved pre_supervisor value based on a specific jc_number
$specific_jc_number = "$newUserId"; // Replace with the actual jc_number
$query_pre_supervisor = "SELECT fab_supervisor FROM fabrication_main WHERE jc_number = '$specific_jc_number' AND fam_branch LIKE '%$userbranch%'";

// Execute the queries
$result_staffs = mysqli_query($conn, $query_staffs);
$result_pre_supervisor = mysqli_query($conn, $query_pre_supervisor);


// Check if the queries were successful
if ($result_staffs) {
    // Start the select element
    echo '<td>';
    echo '<select name="fab_supervisor" id="fab_supervisor" class="form-control select-supervisor" required>';

    // Add the default option
    echo '<option value="" selected disabled>--Choose Supervisor--</option>';

    // Loop through the result set and generate options
    while ($row = mysqli_fetch_assoc($result_staffs)) {
        // Output an option for each staff name
        echo '<option value="' . $row['staff_name'] . '"';

        // Check if the pre_supervisor value is fetched and matches the current staff name
        if ($result_pre_supervisor) {
            $row_pre_supervisor = mysqli_fetch_assoc($result_pre_supervisor);
            if ($row_pre_supervisor && $row['staff_name'] == $row_pre_supervisor['fab_supervisor']) {
                echo ' selected'; // Mark this option as selected
            }
            mysqli_data_seek($result_pre_supervisor, 0); // Reset pointer for next iteration
        }

        echo '>' . $row['staff_name'] . '</option>';
    }

    // Close the select element
    echo '</select>';
    echo '</td>';
} else {
    // If any query fails, handle the error
    echo "Error: " . mysqli_error($conn);
}
?>
                      <input type="hidden" name="id"id="id"value="<?php echo $rowses['id']; ?>">
                      <input type="hidden" name="fam_branch"id="fam_branch"value="<?php echo $userbranch; ?>">
                    </tr>
                  </tbody>
                </table>

              </div>


              <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true"data-backdrop="static" data-keyboard="false">
                <div class="modal-dialog modal-xl" role="document">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="exampleModalLabel">Add Fabrication Labour Expences</h5>
                    </div>
                    <div class="modal-body">
                      <table id="labourtable" class="table table-striped table-bordered">
                        <thead>
                          <tr>
                            <th style="width: 3%;"></th>
                            <th style="width: 7%;">Sl.No</th>
                            <th style="width: 8%;">Expences</th>
                            <th style="width: 8%;">Type</th>
                            <th style="width: 10%;">Staff Name</th>
                            <th style="width: 7%;">Place</th>
                            <th style="width: 12%;">Start Date</th>
                            <th style="width: 12%;">End Date</th>
                            <th style="width: 11%;">OT Exp.</th>
                            <th style="width: 11%;">Reg. Exp.</th>
                            <th style="width: 11%;">Total Exp.</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php
                          function getStaffOptions($conn, $selectedStaffs) {
                          $option = '';
                          $querys = "SELECT staff_name FROM staffs_masters";
                          $results = mysqli_query($conn, $querys);
                          while ($rows = mysqli_fetch_assoc($results)) {
                          $isSelecteds = ($rows['staff_name'] == $selectedStaffs) ? 'selected' : '';
                          $option .= '<option value="' . $rows['staff_name'] . '" ' . $isSelecteds . '>' . $rows['staff_name'] . '</option>';
                          }
                          return $option;
                          }
                          $rowCounted = 0;
                          // Loop through existing data to pre-fill the form fields
                          foreach ($existingLabour as $row_labour) {
                          $rowCounted++;

                          echo '<tr  id="modal1_row' . $rowCounted . '">';
                          echo '<td><input type="checkbox" name="deleterows[]" class="deleterows-checkboxes"></td>';
                          echo '<td><input type="number" class="form-control" value="' . $rowCounted . '" readonly></td>';
                          $expences = $row_labour['expences'];
                          echo '<td><input type="text"name="expences[]"class="form-control expences"id="expences '.$rowCounted.'"value="'.$row_labour['expences'].'"readonly></td>';
                          echo '<td><input class="form-control" type="text"name="type[]"id="type'. $rowCounted . '"value="' . $row_labour['type'] . '" readonly></td>';
                          echo '<td><input type="text"name="name[]"class="form-control names"id="name' . $rowCounted . '" value="' . $row_labour['name'] . '" readonly></td>';
                          echo '<td><input class="form-control" type="text" name="place[]" id="place' . $rowCounted . '" value="' . $row_labour['place'] . '" readonly></td>';
                          if ($expences == "bata") {
                          echo '<td><input class="form-control" name="date[]" id="date' . $rowCounted . '" value="' . $row_labour['date'] . '" readonly></td>';
                          echo '<td><input type="hidden" class="form-control" name="endtime[]" id="endtime' . $rowCounted . '" value="' . $row_labour['endtime'] . '" readonly></td>';
                          echo '<td><input class="form-control" type="hidden" name="total_ot[]" id="total_ot' . $rowCounted . '" value="' . $row_labour['total_ot'] . '" readonly><input type="hidden" name="labour_cost[]" class="form-control costss" id="labour_cost' . $rowCounted . '" value="' . $row_labour['labour_cost'] . '" readonly ></td>';   
                          echo '<td><input type="hidden" name="regular_time[]" class="form-control regular_time" id="regular_time' . $rowCounted . '" value="' . $row_labour['regular_time'] . '" readonly><input type="hidden" name="regular_expences[]" class="form-control regular_expences" id="regular_expences' . $rowCounted . '" value="' . $row_labour['regular_expences'] . '" readonly></td>';
                          echo '<td><input type="number" name="total_lab_cost[]" class="form-control total_lab_cost" id="total_lab_cost' . $rowCounted . '" value="' . $row_labour['total_lab_cost'] . '" readonly><input type="hidden" name="labour_id[]" id="labour_id'. $rowCounted .'" value="' . $row_labour['id'] . '"><input type="hidden" name="fal_branch[]" id="fal_branch'. $rowCounted .'" value="' . $row_labour['fal_branch'] . '"></td>';
                          } else {
                          echo '<td><input type="text" name="date[]" class="form-control dates" id="date' . $rowCounted . '" value="' . $row_labour['date'] . '" readonly></td>';
                          echo '<td><input type="text" name="endtime[]" class="form-control endtimes" id="endtime' . $rowCounted . '" value="' . $row_labour['endtime'] . '" readonly></td>';
                          echo '<td><input hidden type="text" name="total_ot[]" class="form-control total_ots" id="total_ot' . $rowCounted . '" value="' . $row_labour['total_ot'] . '" readonly><input type="number" name="labour_cost[]" class="form-control costss" id="labour_cost' . $rowCounted . '" value="' . $row_labour['labour_cost'] . '" readonly step="0.01"></td>';
                          echo '<td><input hidden type="text" name="regular_time[]" class="form-control regular_time" id="regular_time' . $rowCounted . '" value="' . $row_labour['regular_time'] . '" readonly><input type="number" name="regular_expences[]" class="form-control regular_expences" id="regular_expences' . $rowCounted . '" value="' . $row_labour['regular_expences'] . '" readonly></td>';
                          echo '<td><input name="total_lab_cost[]" class="form-control total_lab_cost" id="total_lab_cost' . $rowCounted . '" value="' . $row_labour['total_lab_cost'] . '" readonly><input type="hidden" name="labour_id[]" id="labour_id'. $rowCounted .'" value="' . $row_labour['id'] . '"><input type="hidden" name="fal_branch[]" id="fal_branch'. $rowCounted .'" value="' . $row_labour['fal_branch'] . '"></td>';
                          }
                          echo '</tr>';
                          }
                          ?>
                        </tbody>
                        <tfoot>
                          <tr>
                            <th colspan="10" style="text-align: right; vertical-align: middle;">Total Labour Cost</th>
                            <th><input type="number" id="labour_total" name="labour_total" value="<?php echo isset($rowses['labour_total']) ? $rowses['labour_total'] : ''; ?>" class="form-control" readonly></th>
                          </tr>
                        </tfoot>
                      </table>
                      <input type="button"name="addrows"id="addrows"value="Add Row"class="btn btn-info">
                      <input type="button"name="deleterows"id="deleterows"value="Delete Row"class="btn btn-danger">
                    </div>

                    <div class="modal-footer"> 
                      <button class="btn btn-primary" id="okButtonss">CLOSE</button>
                    </div>
                  </div>
                </div>
              </div>


              <div class="modal fade" id="exampleModals" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true"data-backdrop="static" data-keyboard="false">
                <div class="modal-dialog modal-xl" role="document">
                  <div class="modal-content">

                    <div class="modal-header">
                      <h5 class="modal-title" id="exampleModalLabel">Add Fabrication Transportation Expences</h5>
                    </div>

                    <div class="modal-body">
                      <table id="Transportationtable" class="table table-striped table-bordered">
                        <thead>
                          <tr>
                            <th></th>
                            <th>Sl.No</th>
                            <th>Date</th>
                            <th>Staff Name</th> 
                            <th>Vehicle</th>
                            <th>From</th>
                            <th>To</th>
                            <th>KM</th>
                            <th>Cost</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php
                          function getStaffOption($conn, $selectedStaff) {
                          $option = '';
                          $querys = "SELECT staff_name FROM staffs_masters";
                          $results = mysqli_query($conn, $querys);
                          while ($rows = mysqli_fetch_assoc($results)) {
                          $isSelected = ($rows['staff_name'] == $selectedStaff) ? 'selected' : '';
                          $option .= '<option value="' . $rows['staff_name'] . '" ' . $isSelected . '>' . $rows['staff_name'] . '</option>';
                          }
                          return $option;
                          }
                          $rowCounts = 0;
                          // Loop through existing data to pre-fill the form fields
                          foreach ($existingTransport as $row_transport) {
                          $rowCounts++;
                          echo '<tr  id="modal2_rows' . $rowCounts . '">';
                          echo '<td><input type="checkbox" name="deleted[]"class="deleted-checkedbox"></td>';
                          echo '<td><input type="number" class="form-control" value="' . $rowCounts . '" readonly></td>';
                          echo '<td><input type="text" class="form-control" name="fab_tran_date[]" id="fab_tran_date' . $rowCounts . '" value="' . $row_transport['fab_tran_date'] . '"readonly ></td>';

                          echo '<td><input type="text" name="staff_name[]" class="form-control" id="staff_name' . $rowCounts . '"value="' . $row_transport['staff_name'] . '"readonly> </td>';
                          echo '<td><input type="text" name="vehicle[]" class="form-control" id="vehicle' . $rowCounts . '" value="' . $row_transport['vehicle'] . '"readonly></td>';
                          echo '<td><input type="text" name="from[]" class="form-control" id="from' . $rowCounts . '" value="' . $row_transport['from'] . '"readonly ></td>';
                          echo '<td><input type="text" name="to[]" class="form-control" id="to' . $rowCounts . '" value="' . $row_transport['to'] . '"readonly></td>';
                          echo '<td><input type="number" name="km[]" class="form-control" id="km' . $rowCounts . '" value="' . $row_transport['km'] . '" step="0.01" readonly></td>';
                          echo '<td><input type="number" name="cost[]" class="form-control cost" id="cost' . $rowCounts . '" value="' . $row_transport['cost'] . '" readonly step="0.01"><input type="hidden" name="transport_id[]" id="transport_id' . $rowCounts . '"value="'.$row_transport['id'].'"></td><input type="hidden" name="fat_branch[]" id="fat_branch' . $rowCounts . '"value="'.$row_transport['fat_branch'].'"></td>';
                          echo '</tr>';
                          }
                          ?>
                        </tbody>
                        <tfoot>
                          <tr>
                            <th colspan="8" style="text-align:right; vertical-align:middle;">Total  Transport Cost</th>
                            <th><input type="number" id="transport_total" name="transport_total" value="<?php echo isset($rowses['transport_total']) ? $rowses['transport_total'] : ''; ?>" class="form-control" readonly></th>
                          </tr>
                        </tfoot>

                      </table>
                      <input type="button" name="addmore" id="addmore" value="Add Row" class="btn btn-info">
                      <input type="button" name="deleted" id="deleted" value="Delete Row" class="btn btn-danger">

                      <div class="modal-footer">
                        <button class="btn btn-primary" id="okButtons">CLOSE</button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>


              <div class="modal fade" id="exampleModalses" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"aria-hidden="true"data-backdrop="static" data-keyboard="false">
                <div class="modal-dialog modal-xl" role="document">
                  <div class="modal-content">
                    
                    <div class="modal-header">
                      <h5 class="modal-title" id="exampleModalLabel">Add Fabrication Other Expences</h5>
                    </div>

                    <div class="modal-body">
                      <table id="Othertable" class="table table-striped table-bordered">
                        <thead>
                          <tr>
                            <th></th>
                            <th>Sl.No</th>
                            <th>Date</th>
                            <th>Staff Name</th>
                            <th>Expences</th>
                            <th>Remarks</th>
                            <th>Cost</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php
                          function getStaffOptionses($conn, $selectedStafff){
                          $option = '';
                          $querys = "SELECT staff_name FROM staffs_masters";
                          $results = mysqli_query($conn, $querys);
                          while ($rows = mysqli_fetch_assoc($results)) {
                          $isSelected = ($rows['staff_name'] == $selectedStafff) ? 'selected' : '';
                          $option .= '<option value="' . $rows['staff_name'] . '" ' . $isSelected . '>' . $rows['staff_name'] . '</option>';
                          }
                          return $option;
                          }
                          $rowCount = 0;
                          // Loop through existing data to pre-fill the form fields
                          foreach ($existingOther as $row_other) {
                          $rowCount++;
                          echo '<tr  id="modal3_rowses' . $rowCount . '">';
                          echo '<td><input type="checkbox" name="deletes[]"class="deletes-checkbox"></td>';
                          echo '<td><input type="number" class="form-control" value="' . $rowCount . '" readonly></td>';
                          echo '<td><input type="text" class="form-control" name="fab_other_date[]" id="fab_other_date' . $rowCount . '" value="' . $row_other['fab_other_date'] . '"readonly> </td>';
                          echo '<td><input type="text" class="form-control" name="staff_names[]" id="staff_names' . $rowCount . '" value="' . $row_other['staff_names'] . '"readonly> </td>';
                          echo '<td><input type="text" class="form-control" name="exp[]" id="exp' . $rowCount . '" value="' . $row_other['exp'] . '"> </td>';
                          echo '<td><input type="text" name="remark[]" class="form-control" id="remark' . $rowCount . '" value="' . $row_other['remark'] . '"readonly></td>';
                          echo '<td><input type="number" name="other_costs[]" class="form-control costs" id="other_costs' . $rowCount . '" value="' . $row_other['other_costs'] . '"step="0.01"readonly><input type="hidden" name="other_id[]"id="other_id'.$rowCount.'"value="'.$row_other['id'].'"><input type="hidden" name="fao_branch[]"id="fao_branch'.$rowCount.'"value="'.$row_other['fao_branch'].'"></td>';
                          echo '</tr>';
                          }
                          ?>
                        </tbody>
                        <tfoot>
                          <tr>
                            <th colspan="6" style="text-align:right; vertical-align:middle;">Total  Other Cost</th>
                            <th><input type="number" id="other_total" name="other_total" value="<?php echo isset($rowses['other_total']) ? $rowses['other_total'] : ''; ?>" class="form-control" readonly></th>
                          </tr>
                        </tfoot>
                      </table>
                      <input type="button" name="added" id="added" value="Add Row" class="btn btn-info">
                      <input type="button" name="deletes" id="deletes" value="Delete Row" class="btn btn-danger">
                    </div>

                    <div class="modal-footer">
                      <button class="btn btn-primary" id="okButton">CLOSE</button>
                    </div>
                  </div>
                </div>
              </div>

              <div class="card-footer">
                <button type="submit"class="btn btn-success"name="save"id="save">SAVE INVOLVEMENT</button>
                <a class="btn btn-danger float-right" href="fab-work-jobcard.php">Close (without save)</a>
              </div>

            </div>
          </form>
        </div>
      </div>
    </div>
  </section>
</div>


<script>
  // Get the button element
  const saveButton = document.getElementById('save');

  // Add event listener for click event
  saveButton.addEventListener('click', function() {

    var requiredFields = document.querySelectorAll('.product_date[required], .activity[required], .material_name[required], .quantity[required], .per_cost[required], .status-curr[required], .select-supervisor[required]');
    var allFieldsFilled = true;

    requiredFields.forEach(function(field) {
        if (!field.value.trim()) {
            // alert('Field with name: ' + field.name + ' is not filled.'); // Display alert message
            allFieldsFilled = false;
        }
    });

    if (!allFieldsFilled) {
        alert('Please fill in all required fields.');
    }

    return allFieldsFilled;

    // Disable the button
    saveButton.disabled = true;
    // Optional: You might want to change the text or add a loading spinner to indicate that the action is being processed
    saveButton.innerHTML = 'Saving...';
    document.getElementById('newForm').submit();
  });
</script>


<script>
document.addEventListener("DOMContentLoaded", function () {
    var rowCountable = <?php echo $rowCountable; ?>;
    var newlyAddedRows = []; // Array to store newly added rows

    document.getElementById("addrow").addEventListener("click", function () {

    var table = document.getElementById("datatable");
    var newRow = table.insertRow(-1);
    rowCountable++;

     // Set a custom attribute to mark the row as newly added
     newRow.setAttribute("data-new-row", "true");
        newlyAddedRows.push(newRow); // Push newly added row to the array


     // Set a custom attribute to mark the row as newly added
     newRow.setAttribute("data-new-row", "true");

    var cell0 = newRow.insertCell(0);
    var cell1 = newRow.insertCell(1);
    var cell2 = newRow.insertCell(2);
    var cell3 = newRow.insertCell(3);
    var cell4 = newRow.insertCell(4);
    var cell5 = newRow.insertCell(5);
    var cell6 = newRow.insertCell(6);
    var cell7 = newRow.insertCell(7);

    // Set a unique ID for the row
    newRow.id = 'mat_row' + rowCountable;

    cell0.innerHTML = '<input type="checkbox" name="deleterow[]" id="deleterow' + rowCountable + '" class="deleterow-checkboxs">';
    cell1.innerHTML = '<input type="date" class="form-control product_date" name="product_date[]" id="product_date' + rowCountable + '" required>';
    cell2.innerHTML = '<select class="form-control activity" name="activity[]" id="activity' + rowCountable + '" required><option selected value="fabrication">Fabrication</option></select>';
    cell3.innerHTML = '<select class="form-control material_name select2bs4" name="material_name[]" id="material_name' + rowCountable + '" required><option value="" selected disabled>Choose Material</option></select>';
    cell4.innerHTML = '<input type="text" class="form-control measuring_unit" name="measuring_unit[]" id="measuring_unit' + rowCountable + '" readonly>';
    cell5.innerHTML = '<input type="number" class="form-control quantity" step="0.01" name="quantity[]" id="quantity' + rowCountable + '" required >';
    cell6.innerHTML = '<input type="number" class="form-control per_cost"  step="0.01" name="per_cost[]" id="per_cost' + rowCountable + '">';
    cell7.innerHTML = '<input type="number" class="form-control total_cost" name="total_cost[]" id="total_cost' + rowCountable + '" readonly><input type="hidden" name="product_id[]" id="product_id' + rowCountable + '" value=""><input type="hidden" name="fama_branch[]" id="fama_branch' + rowCountable + '" value="<?php echo $userbranch; ?>">';
    

    var productDropdown = cell3.querySelector("select");

    // Populate the dropdown with materials fetched from PHP
    fetchMaterials(productDropdown);

    // Add event listener to handle material dropdown change
    productDropdown.addEventListener("change", function (event) {
        var target = event.target; // Get the dropdown that triggered the event
        var parentRow = target.closest('tr'); // Find the parent row of the dropdown
        var rowNumber = parentRow.rowIndex; // Get the index of the parent row
        copyProductValuesToNewRow(productDropdown, newRow, rowNumber); // Pass the correct row index
    });
    var activityDropdown = cell2.querySelector("select");
     fetchActivities(activityDropdown);
});


    function fetchMaterials(productDropdown) {
        var xhr = new XMLHttpRequest();
        xhr.open("GET", "fetch_materials_name.php", true);
        xhr.setRequestHeader("Content-Type", "application/json");
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4 && xhr.status === 200) {
                var materials = JSON.parse(xhr.responseText);
                for (var i = 0; i < materials.length; i++) {
                    var option = new Option(materials[i], materials[i]);
                    productDropdown.add(option);
                }
                productDropdown.dispatchEvent(new Event("change")); // Trigger change event after adding options
            }
        };
        xhr.send();
    }

    // function fetchActivities(activityDropdown) {
    //     var xhr = new XMLHttpRequest();
    //     xhr.open("GET", "fetch_activity.php", true); // Specify the correct URL for your PHP script here
    //     xhr.onreadystatechange = function () {
    //         if (xhr.readyState === 4 && xhr.status === 200) {
    //             var activityNames = JSON.parse(xhr.responseText);
    //             for (var i = 0; i < activityNames.length; i++) {
    //                 var option = new Option(activityNames[i], activityNames[i]);
    //                 activityDropdown.add(option);
    //             }
    //             activityDropdown.dispatchEvent(new Event("change")); // Trigger change event after adding options
    //         }
    //     };
    //     xhr.send();
    // }

    // Call fetchActivities function to populate the dropdown when the page loads
    window.onload = function() {
        var activityDropdown = document.getElementById("activityDropdown");
        fetchActivities(activityDropdown);
    };

    function copyProductValuesToNewRow(productDropdown, newRow, rowNumber) {
        var selectedMaterial = productDropdown.value;

        var xhr = new XMLHttpRequest();
        xhr.open("POST", "fetch_material_details.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            // Populate the measuring_unit and per_cost fields for the current row
                            newRow.querySelector('#measuring_unit' + rowNumber).value = response.measuring_unit;
                            newRow.querySelector('#per_cost' + rowNumber).value = response.per_cost;
                        } else {
                            console.error('Error:', response.message);
                        }
                    } catch (e) {
                        console.error('Error parsing JSON:', xhr.responseText);
                    }
                } else {
                    console.error('Error:', xhr.status, xhr.statusText);
                }
            }
        };

        // Send the material_name parameter
        xhr.send("material_name=" + encodeURIComponent(selectedMaterial));
    }

  // Add event listener to handle quantity change
document.addEventListener('input', function (event) {
    var target = event.target;
    if (target && target.classList.contains('quantity')) {
        var parentRow = target.closest('tr');
        var perCostInput = parentRow.querySelector("input[name='per_cost[]']");
        var totalCostInput = parentRow.querySelector("input[name='total_cost[]']");
        var totalCost = parseFloat(target.value) * parseFloat(perCostInput.value);
        totalCostInput.value = isNaN(totalCost) ? '' : totalCost.toFixed(2);

    }
});

document.getElementById("save").addEventListener("click", function () {
    updateStock(); // Call the updateStock function when the Save button is clicked
});

function updateStock() {
    var formData = new FormData();

    // Loop through newly added rows and append their data to formData
    newlyAddedRows.forEach(function (row) {
        var materialName = row.querySelector(".material_name").value;
        var quantity = row.querySelector(".quantity").value;
        formData.append('material_name[]', materialName); // Append the material names of newly added rows
        formData.append('quantity[]', quantity); // Append the quantities of newly added rows
    });

    var xhr = new XMLHttpRequest();
    xhr.open("POST", "update_stock.php", true);
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                console.log(xhr.responseText); // Display the response message (optional)
            } else {
                console.error('Error:', xhr.status, xhr.statusText);
            }
        }
    };

    xhr.send(formData);
}


    // Add event listener to the "Delete" button
    document.getElementById("deleterow").addEventListener("click", function () {

        var selectedId = [];
        var deletedRowsData = [];
        var checkboxes = document.querySelectorAll(".deleterow-checkboxs:checked");

        checkboxes.forEach(function (checkbox) {
            // Get the closest row (tr) and remove it from the table
            var row = checkbox.closest("tr");
            row.remove();

            // Get the hidden input field with item_id and add its value to the selectedIds array
            var itemIdInput = row.querySelector("input[name='product_id[]']");
            if (itemIdInput) {
                selectedId.push(itemIdInput.value);
            }

            // Store data of deleted rows for updating mat_current_stock
            var rowData = {
                materialName: row.querySelector("input[name='material_name[]']").value,
                quantity: parseFloat(row.querySelector("input[name='quantity[]']").value)
            };
            deletedRowsData.push(rowData);

        });

        // Store selected IDs temporarily
        localStorage.setItem("selectedId", JSON.stringify(selectedId));
        document.getElementById("save").addEventListener("click", function () {
            var selectedId = JSON.parse(localStorage.getItem("selectedId"));

        // Send the selected IDs to your server using AJAX
        if (selectedId && selectedId.length > 0) {
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function () {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    // Handle the response if needed
                }
            };
            xhr.send("selectedId=" + JSON.stringify(selectedId));

            // Clear selected IDs after sending to server
            localStorage.removeItem("selectedId");
        }

        // Update mat_current_stock in fab_mat_inventory table for deleted rows only
        updatedStocks(deletedRowsData); // Pass deletedRowsData to updateStock function
    });

    function updatedStocks(deletedRowsData) {
        deletedRowsData.forEach(function (rowData) {
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "update_mat_current_stock.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function () {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    console.log(xhr.responseText); // Log the response for debugging
                }
            };
            xhr.send("materialName=" + encodeURIComponent(rowData.materialName) + "&quantity=" + rowData.quantity);
        });
    }
});

});
</script>

<?php
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["selectedId"])) {
    $selectedId = json_decode($_POST["selectedId"]);

    // Delete records from the database
    foreach ($selectedId as $id) {
        $delete_items_sql = "DELETE FROM fab_mat_expences WHERE id=?";
        $stmt_delete_items = $conn->prepare($delete_items_sql);
        $stmt_delete_items->bind_param("i", $id);
        $stmt_delete_items->execute();
    }
}
?>


<script>
document.addEventListener("DOMContentLoaded", function () {
// Counter to keep track of the row number
var rowCounted = <?php echo $rowCounted; ?>;

// Initialize Flatpickr for existing and newly added "date" and "endtime" inputs
const dateInputs = document.querySelectorAll('input.dates, input.endtimes');

dateInputs.forEach(function (input) {
// const today = new Date();
// const yesterday = new Date(today.getTime() - 24 * 60 * 60 * 1000);
// const dayBeforeYesterday = new Date(yesterday.getTime() - 24 * 60 * 60 * 1000);

// const enabledDates = [
// today.toISOString().slice(0, 10), // Format as YYYY-MM-DD
// yesterday.toISOString().slice(0, 10),
// dayBeforeYesterday.toISOString().slice(0, 10)
// ];

flatpickr(input, {
enableTime: true,
noCalendar: false,
dateFormat: "Y-m-d H:i",
time_24hr: false
// enable: enabledDates
});
});


document.getElementById("addrows").addEventListener("click", function () {
var table = document.getElementById("labourtable");
var tbody = table.getElementsByTagName("tbody")[0];
var newRow = tbody.insertRow(-1);

rowCounted++;

var cell0 = newRow.insertCell(0);
var cell1 = newRow.insertCell(1);
var cell2 = newRow.insertCell(2);
var cell3 = newRow.insertCell(3);
var cell4 = newRow.insertCell(4);
var cell5 = newRow.insertCell(5);
var cell6 = newRow.insertCell(6);
var cell7 = newRow.insertCell(7);
var cell8 = newRow.insertCell(8);
var cell9 = newRow.insertCell(9);
var cell10 = newRow.insertCell(10);

cell0.innerHTML ='<input type="checkbox" name="deleterows[]" class="deleterows-checkboxes">';
cell1.innerHTML ='<input type="number" class="form-control" value="' +rowCounted +'" readonly>';
cell2.innerHTML ='<select name="expences[]"class="form-control expences" id="expences' +rowCounted +'"required><option selected disabled value="">Choose Expences</option><option value="labour">Labour</option><option value="bata">Bata</option><option value="rework">Rework</option><option value="rectification">Rectification</option></select>';
cell3.innerHTML ='<select type="text"name="type[]"class="form-control type"id="type' +rowCounted +'"required><option value="fabrication">Fabrication</option><option value="painting">Painting</option><option value="electrical">Electrical</option><option value="vinyl pasting">Vinrl Pasting</option><option value="labour">Labour</option><option value="erection">Erection</option><option value="erection">Erection</option><option value="pre-verification">Pre-Verification</option></select>';
cell4.innerHTML ='<select type="text"name="name[]"class="form-control names"id="name' +rowCounted +'"required></select>';
cell5.innerHTML ='<input type="text"name="place[]"class="form-control place"id="place' +rowCounted +'"required>';
cell6.innerHTML ='<input type="text"name="date[]" class="form-control dates" id="date' +rowCounted +'">';
cell7.innerHTML ='<input type="text"name="endtime[]"class="form-control endtimes" id="endtime' +rowCounted +'">';
cell8.innerHTML ='<input hidden type="text"name="total_ot[]"class="form-control total_ots" id="total_ot' +rowCounted +'"readonly><input type="number"name="labour_cost[]" class="form-control costss" id="labour_cost' +rowCounted +'"readonly>';
cell9.innerHTML ='<input hidden type="text"name="regular_time[]"class="form-control regular_times" id="regular_time' +rowCounted +'"readonly><input type="number"name="regular_expences[]"class="form-control regular_expences" id="regular_expences' +rowCounted +'" readonly>';
cell10.innerHTML ='<input type="number"name="total_lab_cost[]"class="form-control total_lab_costs" id="total_lab_cost' +rowCounted +'"><input type="hidden" name="labour_id[]" id="labour_id' +rowCounted +'" value=""><input type="hidden" name="fal_branch[]" id="fal_branch' +rowCounted +'" value="<?php echo $userbranch; ?>">';

// Update the IDs for the new row's date, endtime, total_ot, and labour_cost inputs
var dateInput = newRow.querySelector('.dates');
var endTimeInput = newRow.querySelector('.endtimes');
var totalOTInput = newRow.querySelector('.total_ots');
var costInput = newRow.querySelector('.costss');
var regularTime = newRow.querySelector('.regular_times');
var regularExpences = newRow.querySelector('.regular_expences');
var totalLabCost = newRow.querySelector('.total_lab_costs');


dateInput.id = 'date' + rowCounted;
endTimeInput.id = 'endtime' + rowCounted;
totalOTInput.id = 'total_ot' + rowCounted;
costInput.id = 'labour_cost' + rowCounted;
regularTime.id = 'regular_time' + rowCounted;
regularExpences.id = 'regular_expences' + rowCounted;
totalLabCost.id = 'total_lab_cost' + rowCounted;
// Populate the staff name dropdown in the first cell of the newly added row
var staffNameDropdown = cell4.querySelector("select");

var xhr = new XMLHttpRequest();
xhr.open("POST", "staff_option.php", true);
xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
xhr.onreadystatechange = function () {
if (xhr.readyState === 4 && xhr.status === 200) {
var staffs = JSON.parse(xhr.responseText);

for (var i = 0; i < staffs.length; i++) {
var option = new Option(staffs[i].name, staffs[i].name);
staffNameDropdown.add(option);
}

staffNameDropdown.dispatchEvent(new Event("change"));
}
};
xhr.send();


document.getElementById("labourtable").addEventListener("change", function (event) {
if (event.target.name === 'expences[]') {
var rows = event.target.closest('tr');
var dateStartTimeInputs = rows.querySelector('input.dates');
var dateEndTimeInputs = rows.querySelector('input.endtimes');
var totalOTInputs = rows.querySelector('input.total_ots');
var costInputs = rows.querySelector('input.costss');
var regularTimes = rows.querySelector('input.regular_times');
var regularExpencess = rows.querySelector('input.regular_expences');
var totalLabCosts = rows.querySelector('input.total_lab_costs');


if (event.target.value === "bata") {
dateStartTimeInputs.style.display = 'block';
dateEndTimeInputs.style.display = 'none';
totalOTInputs.style.display = 'none';
costInputs.style.display = 'none';
regularTimes.style.display = 'none';
regularExpencess.style.display = 'none';
totalLabCosts.style.display = 'block';

dateStartTimeInputs.value = "";  // Clear the value for 'date'
dateEndTimeInputs.value = "";    // Clear the value for 'endtime'
totalOTInputs.value = "";        // Clear the value for 'total_ot'
costInputs.value = "";        // Clear the value for 'total_ot'
regularTimes.value = "";        // Clear the value for 'total_ot'
regularExpencess.value = "";        // Clear the value for 'total_ot'

totalLabCosts.value = "350";
totalLabCosts.readOnly = false;
} else if (event.target.value === "labour" || event.target.value === "rework" || event.target.value === "rectification") {
dateStartTimeInputs.style.display = 'block';
dateEndTimeInputs.style.display = 'block';
totalOTInputs.style.display = 'block';
costInputs.style.display = 'block';
regularTimes.style.display = 'block';
regularExpencess.style.display = 'block';

totalOTInputs.value = "";
costInputs.value = "";
regularTimes.value = "";
regularExpencess.value = "";

totalLabCosts.value = "";
totalLabCosts.readOnly = true;
}
}
});


// Initialize Flatpickr for existing and newly added "date" and "endtime" inputs
const dateInputs = document.querySelectorAll('input.dates, input.endtimes');

dateInputs.forEach(function (input) {
// const today = new Date();
// const yesterday = new Date(today.getTime() - 24 * 60 * 60 * 1000);
// const dayBeforeYesterday = new Date(yesterday.getTime() - 24 * 60 * 60 * 1000);

// const enabledDates = [
// today.toISOString().slice(0, 10), // Format as YYYY-MM-DD
// yesterday.toISOString().slice(0, 10),
// dayBeforeYesterday.toISOString().slice(0, 10)
// ];

flatpickr(input, {
enableTime: true,
noCalendar: false,
dateFormat: "Y-m-d H:i",
time_24hr: false
// enable: enabledDates
});
});


document.getElementById("labourtable").addEventListener("input", function (event) {
    if (event.target.classList.contains('dates') || event.target.classList.contains('endtimes')) {
        var rows = event.target.closest('tr');
        var dateInput = rows.querySelector('.dates');
        var endTimeInput = rows.querySelector('.endtimes');
        var totalOTInput = rows.querySelector('.total_ots');
        var costInput = rows.querySelector('.costss');
        var regularTimeInput = rows.querySelector('.regular_times');
        var regularExpensesInput = rows.querySelector('.regular_expences');
        var totalLabCostInput = rows.querySelector('.total_lab_costs');

        if (dateInput && endTimeInput && totalOTInput && costInput && regularTimeInput && regularExpensesInput && totalLabCostInput) {
            var startTimesStr = dateInput.value;
            var endTimesStr = endTimeInput.value;

            var startTimes = new Date(startTimesStr);
            var endTimes = new Date(endTimesStr);

            // Define workday start and end (9:30 AM and 5:30 PM)
            var expectedStartTimes = new Date(startTimes);
            expectedStartTimes.setHours(9, 30, 0, 0); // 9:30 AM
            var expectedEndTimes = new Date(startTimes);
            expectedEndTimes.setHours(18, 0, 0, 0); // 6:00 PM

            var extraMorning = 0;
            var extraEvening = 0;

            // ---------------- Morning Overtime Logic ----------------
            if (startTimes < expectedStartTimes) {
                if (endTimes >= expectedStartTimes) {
                    // Calculate overtime from start time to 9:30 AM
                    extraMorning = expectedStartTimes - startTimes;
                } else {
                    // Calculate overtime from start time to end time
                    extraMorning = endTimes - startTimes;
                }
            }

            // ---------------- Evening Overtime Logic ----------------
            if (endTimes > expectedEndTimes) {
                if (startTimes >= expectedEndTimes) {
                    // Calculate overtime from start time to end time after 5:30 PM
                    extraEvening = endTimes - startTimes;
                } else {
                    // Calculate overtime from 5:30 PM to end time
                    extraEvening = endTimes - expectedEndTimes;
                }
            }

            // ---------------- Total Overtime Calculation ----------------
            var totalExtraTime = extraMorning + extraEvening;

            var totalExtraHours = Math.floor(totalExtraTime / (1000 * 60 * 60)); // Convert to hours
            var totalExtraMinutes = Math.floor((totalExtraTime % (1000 * 60 * 60)) / (1000 * 60)); // Convert remainder to minutes

            // Ensure positive values for overtime hours and minutes
            totalExtraHours = Math.max(totalExtraHours, 0);
            totalExtraMinutes = Math.max(totalExtraMinutes, 0);

            // If totalExtraMinutes exceed 60, convert them to hours
            if (totalExtraMinutes >= 60) {
                var additionalHours = Math.floor(totalExtraMinutes / 60);
                totalExtraHours += additionalHours;
                totalExtraMinutes = totalExtraMinutes % 60;
            }

            // Display the total extra time in the Total OT input
            totalOTInput.value = (isNaN(totalExtraHours) ? 0 : totalExtraHours) + "h " + (isNaN(totalExtraMinutes) ? 0 : totalExtraMinutes) + "m";

            // ---------------- Cost Calculation ----------------
            var costs = (totalExtraHours * 50) + (totalExtraMinutes * 0.83);
            costInput.value = isNaN(costs) ? 0 : costs.toFixed(0); // Round to 0 decimal places
            costInput.readOnly = true;

            // ---------------- Regular Time Calculation ----------------
            var regularTimeHours = 0;
            var regularTimeMinutes = 0;

            // Regular time should only be calculated within the regular working hours (9:30 AM - 5:30 PM)
            if (startTimes >= expectedStartTimes && endTimes <= expectedEndTimes) {
                var duration = endTimes - startTimes;
                regularTimeHours = Math.floor(duration / (1000 * 60 * 60));
                regularTimeMinutes = Math.floor((duration % (1000 * 60 * 60)) / (1000 * 60));
            } else if (startTimes < expectedStartTimes && endTimes > expectedEndTimes) {
                // Full workday, capped at 8 hours 30 minutes (8.5 hours)
                regularTimeHours = 8;
                regularTimeMinutes = 30;
            } else if (startTimes < expectedStartTimes && endTimes >= expectedStartTimes && endTimes <= expectedEndTimes) {
                var duration = endTimes - expectedStartTimes;
                regularTimeHours = Math.floor(duration / (1000 * 60 * 60));
                regularTimeMinutes = Math.floor((duration % (1000 * 60 * 60)) / (1000 * 60));
            } else if (startTimes >= expectedStartTimes && endTimes > expectedEndTimes) {
                var duration = expectedEndTimes - startTimes;
                regularTimeHours = Math.floor(duration / (1000 * 60 * 60));
                regularTimeMinutes = Math.floor((duration % (1000 * 60 * 60)) / (1000 * 60));
            }

            // Ensure positive values for regular hours and minutes (no negative regular time)
            regularTimeHours = Math.max(regularTimeHours, 0);
            regularTimeMinutes = Math.max(regularTimeMinutes, 0);

            // Display regular time
            regularTimeInput.value = regularTimeHours + "h " + regularTimeMinutes + "m";

            // ---------------- Fetch Salary and Calculate Costs ----------------
            var staffName = rows.querySelector('.names').value;
            fetchSalaryFromDatabase(staffName, function(salary) {
                var regularTimeDecimal = regularTimeHours + regularTimeMinutes / 60;
                var regularExpenses = (salary / 30 / 8.5) * regularTimeDecimal; // Assuming 8.5-hour workday

                // Ensure regularExpenses is positive and only calculated for valid time ranges
                regularExpenses = Math.max(regularExpenses, 0);

                // Display regular expenses
                regularExpensesInput.value = regularExpenses.toFixed(0);

                // ---------------- Calculate Total Lab Cost ----------------
                var totalCost = parseFloat(costInput.value);
                var totalExpenses = parseFloat(regularExpensesInput.value);
                var totalLabCost = totalCost + totalExpenses;

                // Display total lab cost
                totalLabCostInput.value = isNaN(totalLabCost) ? 0 : totalLabCost.toFixed(2);
            });
        }
    }
});

});
function fetchSalaryFromDatabase(staffName, callback) {
        // Perform an AJAX request to your server-side script
        var xhr = new XMLHttpRequest();
    xhr.open("POST", "fetch_salary.php", true); // Replace "fetch_salary.php" with your actual server-side script
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function () {
        if (xhr.readyState == 4 && xhr.status == 200) {
            var response = JSON.parse(xhr.responseText);
            // Handle the response from the server
            if (response.success) {
                // Process the salary data
                var salary = response.salary;
                callback(salary); // Pass the salary to the callback function
            } else {
                console.error("Error fetching salary:", response.error);
            }
        }
    };
    xhr.send("staffName=" + encodeURIComponent(staffName));
}

document.getElementById("deleterows").addEventListener("click", function () {
            var selectedIded = [];
            var checkboxes = document.querySelectorAll(".deleterows-checkboxes:checked");

            checkboxes.forEach(function (checkbox) {
                // Get the closest row (tr) and remove it from the table
                var row = checkbox.closest("tr");
                row.remove();

                // Get the hidden input field with item_id and add its value to the selectedIds array
                var itemIdInput = row.querySelector("input[name='labour_id[]']");
                if (itemIdInput) {
                    selectedIded.push(itemIdInput.value);
                }
            });

            // Store selected IDs temporarily
            localStorage.setItem("selectedIded", JSON.stringify(selectedIded));
        });

        document.getElementById("save").addEventListener("click", function () {
            var selectedIded = JSON.parse(localStorage.getItem("selectedIded"));

            // Send the selected IDs to your server using AJAX
            if (selectedIded && selectedIded.length > 0) {
                var xhr = new XMLHttpRequest();
                xhr.open("POST", "", true); // Assuming delete.php is your server-side script
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.onreadystatechange = function () {
                    if (xhr.readyState == 4 && xhr.status == 200) {
                        
                    }
                };
                xhr.send("selectedIded=" + JSON.stringify(selectedIded));
                
                // Clear selected IDs after sending to server
                localStorage.removeItem("selectedIded");
            }
        });
    
});
    </script>
   <?php
include_once('../../../include/php/connect.php');

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["selectedIded"])) {
    $selectedIded = json_decode($_POST["selectedIded"]);

    // Delete records from the database
    foreach ($selectedIded as $id) {
        $delete_items_sql = "DELETE FROM fab_labour_expences WHERE id=?";
        $stmt_delete_items = $conn->prepare($delete_items_sql);
        $stmt_delete_items->bind_param("i", $id);
        $stmt_delete_items->execute();
    }
    
    // Close the prepared statement
    $stmt_delete_items->close();
    
    // Return a success message (optional)
    echo "Rows deleted successfully.";
    exit; // Stop further execution
}
?>


<script>
document.addEventListener("DOMContentLoaded", function () {
// Counter to keep track of the row number
var rowCounts = <?php echo $rowCounts; ?>;

$("#Transportationtable").on("keyup change","input[name='km[]']", function () {
var kmInput = $(this);
var kmValue = parseFloat(kmInput.val());
var costInput = kmInput.closest('tr').find("input[name='cost[]']");
var cost = kmValue * 15; // Assuming a fixed cost rate of 15
costInput.val(isNaN(cost) ? '' : cost.toFixed(2));
});

$("#addmore").on("click", function () {
var table = document.getElementById("Transportationtable");
var tbody = table.getElementsByTagName("tbody")[0];
var newRow = tbody.insertRow(-1);
rowCounts++;

var cell0 = newRow.insertCell(0);
var cell1 = newRow.insertCell(1);
var cell2 = newRow.insertCell(2);
var cell3 = newRow.insertCell(3);
var cell4 = newRow.insertCell(4);
var cell5 = newRow.insertCell(5);
var cell6 = newRow.insertCell(6);
var cell7 = newRow.insertCell(7);
var cell8 = newRow.insertCell(8);

// Set a unique ID for the row
newRow.id = 'modal2_rows' + rowCounts;

cell0.innerHTML = '<input type="checkbox" name="deleted[]" class="deleted-checkedbox" style="width: 30px;">';
cell1.innerHTML = '<input type="number" class="form-control" value="' + rowCounts + '" readonly>';
cell2.innerHTML = '<input type="date" name="fab_tran_date[]" class="form-control fab_tran_date" id="fab_tran_date' + rowCounts + '"required>';
cell3.innerHTML = '<select  name="staff_name[]"class="form-control staff_name" id="staff_name' + rowCounts + '"required></select>';
cell4.innerHTML = '<select  name="vehicle[]"  id="vehicle' + rowCounts + '"class="form-control vehicle"required><option selected disabled value="">--Choose Vehicle--</option><option value="KL01 AW 0738">KL01 AW 0738</option><option value="KL01 BF 8159">KL01 BF 8159</option><option value="KL 01 CY 2262">KL 01 CY 2262</option><option value="KL01 DA 8030">KL01 DA 8030</option><option value="KL01 AR 6420">KL01 AR 6420</option><option value="KL01 BU 1911">KL01 BU 1911</option><option value="KL01 BK 3664">KL01 BK 3664</option></select>';
cell5.innerHTML = '<input type="text" name="from[]" class="form-control from" id="from' + rowCounts + '"required>';
cell6.innerHTML = '<input type="text" name="to[]" class="form-control to" id="to' + rowCounts + '" required>';
cell7.innerHTML = '<input type="number" name="km[]" class="form-control km" id="km' + rowCounts + '" step="0.01" required>';
cell8.innerHTML = '<input type="number" name="cost[]" class="form-control cost" id="cost' + rowCounts + '" readonly step="0.01"><input type="hidden" name="transport_id[]" id="transport_id' + rowCounts + '" value=""><input type="hidden" name="fat_branch[]" id="fat_branch' + rowCounts + '" value="<?php echo $userbranch; ?>">';


var staffNameDropdown = cell3.querySelector("select");

var xhr = new XMLHttpRequest();
xhr.open("POST", "staff_option.php", true);
xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
xhr.onreadystatechange = function () {
if (xhr.readyState === 4 && xhr.status === 200) {
var staffs = JSON.parse(xhr.responseText);

for (var i = 0; i < staffs.length; i++) {
var option = new Option(staffs[i].name, staffs[i].name);
staffNameDropdown.add(option);
}

staffNameDropdown.dispatchEvent(new Event("change"));
}
};
xhr.send();
});
$("#Transportationtable input[name='km[]']").on('keyup change', function () {
var kmInput = $(this);
var kmValue = parseFloat(kmInput.val());
var cost = kmValue * 15;
var costPerKm = cost / kmValue;
var costInput = kmInput.closest('tr').find("input[name='cost[]']");
costInput.val(cost.toFixed(2));
});

document.getElementById("deleted").addEventListener("click", function () {
            var selectedIds= [];
            var checkboxes = document.querySelectorAll(".deleted-checkedbox:checked");

            checkboxes.forEach(function (checkbox) {
                // Get the closest row (tr) and remove it from the table
                var row = checkbox.closest("tr");
                row.remove();

                // Get the hidden input field with item_id and add its value to the selectedIds array
                var itemIdInput = row.querySelector("input[name='transport_id[]']");
                if (itemIdInput) {
                    selectedIds.push(itemIdInput.value);
                }
            });

            // Store selected IDs temporarily
            localStorage.setItem("selectedIds", JSON.stringify(selectedIds));
        });

        document.getElementById("save").addEventListener("click", function () {
            var selectedIds = JSON.parse(localStorage.getItem("selectedIds"));

            // Send the selected IDs to your server using AJAX
            if (selectedIds && selectedIds.length > 0) {
                var xhr = new XMLHttpRequest();
                xhr.open("POST", "", true); // Assuming delete.php is your server-side script
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.onreadystatechange = function () {
                    if (xhr.readyState == 4 && xhr.status == 200) {
                        
                    }
                };
                xhr.send("selectedIds=" + JSON.stringify(selectedIds));
                
                // Clear selected IDs after sending to server
                localStorage.removeItem("selectedIds");
            }
        });
});
</script>
<?php
include_once('../../../include/php/connect.php');
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["selectedIds"])) {
    $selectedIds = json_decode($_POST["selectedIds"]);

    // Delete records from the database
    foreach ($selectedIds as $id) {
        $delete_items_sql = "DELETE FROM fab_transport_expences WHERE id=?";
        $stmt_delete_items = $conn->prepare($delete_items_sql);
        $stmt_delete_items->bind_param("i", $id);
        $stmt_delete_items->execute();
    }
}
?>

<script>
document.addEventListener("DOMContentLoaded", function () {
// Counter to keep track of the row number
var rowCount = <?php echo $rowCount; ?>;
document.getElementById("added").addEventListener("click", function () {
var table = document.getElementById("Othertable");
var tbody = table.getElementsByTagName("tbody")[0]; // Get the first tbody element
var newRow = tbody.insertRow(-1); // Append a new row to the tbody element

// Increment the row counter
rowCount++;

// Insert cells for each column in the table
var cell0 = newRow.insertCell(0);
var cell1 = newRow.insertCell(1);
var cell2 = newRow.insertCell(2);
var cell3 = newRow.insertCell(3);
var cell4 = newRow.insertCell(4);
var cell5 = newRow.insertCell(5);
var cell6 = newRow.insertCell(6);

// Set a unique ID for the row
newRow.id = 'modal3_rowses' + rowCount;

// Set the content of the cells
cell0.innerHTML = '<input type="checkbox" name="deletes[]" class="deletes-checkbox" style="width: 30px;">';
cell1.innerHTML = '<input type="number" class="form-control" value="' + rowCount + '" readonly>';
cell2.innerHTML = '<input type="date" name="fab_other_date[]" class="form-control fab_other_date" id="fab_other_date' + rowCount + '"required>';
cell3.innerHTML = '<select name="staff_names[]" id="staff_names' + rowCount + '" class="form-control staff_names"required></select>';
cell4.innerHTML = '<select type="text" name="exp[]" class="form-control exp" id="exp' + rowCount + '"required><option value="food_exp">Food Exp</option><option value="travel_exp">Travel Exp</option> <option value="other_exp">Other Exp</option></select>';
cell5.innerHTML = '<input type="text" name="remark[]" class="form-control remark" id="remark' + rowCount + '"required>';
cell6.innerHTML = '<input type="number" name="other_costs[]" class="form-control other_costs" id="other_costs' + rowCount + '"step="0.01"required><input type="hidden" name="other_id[]"id="other_id'+rowCount+'"><input type="hidden" name="fao_branch[]"id="fao_branch'+rowCount+'" value="<?php echo $userbranch; ?>">';

// Populate the staff name dropdown in the first cell of the newly added row
var staffNameDropdown = cell3.querySelector("select");

var xhr = new XMLHttpRequest();
xhr.open("POST", "staff_option.php", true);
xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
xhr.onreadystatechange = function () {
if (xhr.readyState === 4 && xhr.status === 200) {
// Parse the JSON response to get the staff options
var staffs = JSON.parse(xhr.responseText);

// Add the staff options to the dropdown
for (var i = 0; i < staffs.length; i++) {
    var option = new Option(staffs[i].name, staffs[i].name);
    staffNameDropdown.add(option);
}

// Trigger the change event to update other inputs based on the selected material type
staffNameDropdown.dispatchEvent(new Event("change"));
}
};
xhr.send(); // Send the XHR request

});

document.getElementById("deletes").addEventListener("click", function () {
            var selectedIdes = [];
            var checkboxes = document.querySelectorAll(".deletes-checkbox:checked");

            checkboxes.forEach(function (checkbox) {
                // Get the closest row (tr) and remove it from the table
                var row = checkbox.closest("tr");
                row.remove();

                // Get the hidden input field with item_id and add its value to the selectedIds array
                var itemIdInput = row.querySelector("input[name='other_id[]']");
                if (itemIdInput) {
                    selectedIdes.push(itemIdInput.value);
                }
            });

            // Store selected IDs temporarily
            localStorage.setItem("selectedIdes", JSON.stringify(selectedIdes));
        });

        document.getElementById("save").addEventListener("click", function () {
            var selectedIdes = JSON.parse(localStorage.getItem("selectedIdes"));

            // Send the selected IDs to your server using AJAX
            if (selectedIdes && selectedIdes.length > 0) {
                var xhr = new XMLHttpRequest();
                xhr.open("POST", "", true); // Assuming delete.php is your server-side script
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.onreadystatechange = function () {
                    if (xhr.readyState == 4 && xhr.status == 200) {
                        
                    }
                };
                xhr.send("selectedIdes=" + JSON.stringify(selectedIdes));
                
                // Clear selected IDs after sending to server
                localStorage.removeItem("selectedIdes");
            }
        });

});
</script>
<?php
include_once('../../../include/php/connect.php');
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["selectedIdes"])) {
    $selectedIdes = json_decode($_POST["selectedIdes"]);

    // Delete records from the database
    foreach ($selectedIdes as $id) {
        $delete_items_sql = "DELETE FROM fab_other_expences WHERE id=?";
        $stmt_delete_items = $conn->prepare($delete_items_sql);
        $stmt_delete_items->bind_param("i", $id);
        $stmt_delete_items->execute();
    }
}
?>

<script>
// Function to handle button clicks for okButtonss
document.getElementById('okButtonss').addEventListener('click', function(event) {
    event.preventDefault();

    var allFieldsFilled = checkRequiredFields();

    if (allFieldsFilled) {
        displayTotalLabourCost();
    }
});

// Function to handle button clicks for okButtons
document.getElementById('okButtons').addEventListener('click', function(event) {
    event.preventDefault();

    var allFieldsFilled = checkRequiredFields();

    if (allFieldsFilled) {
        displayTotalTransportCost();
    }
});

// Function to handle button clicks for okButton
document.getElementById('okButton').addEventListener('click', function(event) {
    event.preventDefault();

    var allFieldsFilled = checkRequiredFields();

    if (allFieldsFilled) {
        displayTotalOtherCost();
    }
});


// Function to check if all required fields are filled
function checkRequiredFields() {
    var requiredFields = document.querySelectorAll('.expences[required], .type[required], .name[required], .place[required], .dates[required], .endtime[required], .fab_tran_date[required], .staff_name[required], .vehicle[required], .from[required], .to[required], .km[required], .cost[required], .fab_other_date[required], .staff_names[required], .exp[required], .remark[required], .other_costs[required]');
    var allFieldsFilled = true;

    requiredFields.forEach(function(field) {
        if (!field.value.trim()) {
            // alert('Field with name: ' + field.name + ' is not filled.'); // Display alert message
            allFieldsFilled = false;
        }
    });

    if (!allFieldsFilled) {
        alert('Please fill in all required fields.');
    }

    return allFieldsFilled;
}

// Function to display the total labour cost
function displayTotalLabourCost() {
    var totalLabourCost = calculateTotalLabourCost();
    $('#labour_total').val(totalLabourCost); // Update the total labour cost input field
    updateTotalExpense(); // Calculate and update the total expense
    alert('Total Labour Cost: ' + totalLabourCost.toFixed(2));
    $('#exampleModal').modal('hide'); // Hide the modal
}

// Function to calculate the total labour cost
function calculateTotalLabourCost() {
    var totalLabourCost = 0;

    $('input[name^="total_lab_cost"]').each(function() {
        var value = parseFloat($(this).val()) || 0;
        totalLabourCost += value;
    });

    return totalLabourCost;
}

// Function to display the total transport cost
function displayTotalTransportCost() {
    var totalTransportCost = calculateTotalTransportCost();
    $('#transport_total').val(totalTransportCost); // Update the total transport cost input field
    updateTotalExpense(); // Calculate and update the total expense
    alert('Total Transport Cost: ' + totalTransportCost.toFixed(2));
    $('#exampleModals').modal('hide'); // Hide the modal
}

// Function to calculate the total transport cost
function calculateTotalTransportCost() {
    var totalTransportCost = 0;

    $('input[name^="cost"]').each(function() {
        var value = parseFloat($(this).val()) || 0;
        totalTransportCost += value;
    });

    return totalTransportCost;
}

// Function to display the total other cost
function displayTotalOtherCost() {
    var totalOtherCost = calculateTotalOtherCost();
    $('#other_total').val(totalOtherCost); // Update the total other cost input field
    updateTotalExpense(); // Calculate and update the total expense
    alert('Total Other Cost: ' + totalOtherCost.toFixed(2));
    $('#exampleModalses').modal('hide'); // Hide the modal
}

// Function to calculate the total other cost
function calculateTotalOtherCost() {
    var totalOtherCost = 0;

    $('input[name^="other_costs"]').each(function() {
        var value = parseFloat($(this).val()) || 0;
        totalOtherCost += value;
    });

    return totalOtherCost;
}

// Function to calculate and update the total material cost and total expense
function calculateAndUpdateTotalMaterialCostAndExpense() {
    var totalMaterialCost = calculateTotalMaterialCost();
    $('#material_total').val(totalMaterialCost); // Update the total material cost input field

    updateTotalExpense(); // Calculate and update the total expense
}

// Function to calculate the total material cost
function calculateTotalMaterialCost() {
    var totalMaterialCost = 0;

    // Loop through each row and sum up the total_cost values
    $('input[name^="total_cost"]').each(function() {
        var value = parseFloat($(this).val()) || 0;
        totalMaterialCost += value;
    });

    return totalMaterialCost;
}

// Function to update the total expense
function updateTotalExpense() {
    var totalLabourCost = parseFloat($('#labour_total').val()) || 0;
    var totalTransportCost = parseFloat($('#transport_total').val()) || 0;
    var totalOtherCost = parseFloat($('#other_total').val()) || 0;
    var totalMaterialCost = parseFloat($('#material_total').val()) || 0;

    var totalExpense = totalLabourCost + totalTransportCost + totalOtherCost + totalMaterialCost;
    $('#grand_total').val(totalExpense.toFixed(2)); // Update the total expense input field
}

$(document).ready(function() {
    // Event listener for quantity change
    $(document).on('input', '.quantity', function() {
        calculateAndUpdateTotalMaterialCostAndExpense();
    });
});

// Event listener for adding a new row
$('#addrow').on('click', function() {
    // After adding a new row, recalculate the total material cost and update the total expense
    calculateAndUpdateTotalMaterialCostAndExpense();
});

// Event listener for deleting a row
$('#deleterow').on('click', function() {
    // After deleting a row, recalculate the total material cost and update the total expense
    calculateAndUpdateTotalMaterialCostAndExpense();
});

// Trigger calculation and update on document ready
$(document).ready(function() {
    calculateAndUpdateTotalMaterialCostAndExpense();
});

</script>

<script>
  $(function () {
    //Initialize Select2 Elements
    $('.select2').select2()

    //Initialize Select2 Elements
    $('.select2bs4').select2({
      theme: 'bootstrap4'
    })
  })

</script>

<!-- Include Footer File -->
<?php include_once ('../../../include/php/footer.php') ?>

</body>
</html> 