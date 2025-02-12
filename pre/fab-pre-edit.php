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
$sql_main_query = "SELECT jc_number,jc_date,client,instructed_by,s_location,involvements FROM jobcard_main WHERE jc_number = ?";
$stmt_main = mysqli_prepare($conn, $sql_main_query);

if ($stmt_main) {
mysqli_stmt_bind_param($stmt_main, "s", $newUserId);
mysqli_stmt_execute($stmt_main);
$result_main = mysqli_stmt_get_result($stmt_main);

// Check if the query returned any rows
if ($result_main) {
if (mysqli_num_rows($result_main) > 0) {
$main = mysqli_fetch_assoc($result_main);
} else {
echo "No rows found for JC Number: " . $newUserId;
}
} else {
echo "Error in SQL query: " . mysqli_stmt_error($stmt_main);
}

mysqli_stmt_close($stmt_main);
}

$sql_item = mysqli_query($conn, "SELECT * FROM jobcard_items_pv WHERE jc_number='$newUserId'");
$sql = "SELECT * FROM pre_varification_total WHERE jc_number=? AND pvt_branch LIKE '%$userbranch%'";
$stmt = $conn->prepare($sql);

if ($stmt) {
$stmt->bind_param('s', $newUserId);
$stmt->execute();

// Fetch the result
$result = $stmt->get_result();
$rowses = $result->fetch_assoc();
} else {
// Handle the case where preparing the statement fails
echo "Error preparing statement: " . $conn->error;
}

// Check if $rowses is not null before accessing its elements
if ($rowses !== null) {
$current_status = $rowses['current_status'];
$pre_supervisor = $rowses['pre_supervisor'];
} else {
// Handle the case where $rowses is null (e.g., data not found)
$current_status = null;  // You can set a default value or handle it based on your logic
$pre_supervisor = null;
}

$existingLabour = []; // Initialize as an empty array

// Check if the record with the given jc_number exists in the creative_items table
$check_labour_sql = "SELECT jc_number FROM pre_varifacation_labour WHERE jc_number = ? AND pvl_branch LIKE '%$userbranch%'";
$stmt_check_labour = mysqli_prepare($conn, $check_labour_sql);

if ($stmt_check_labour) {
mysqli_stmt_bind_param($stmt_check_labour, "s", $newUserId);
mysqli_stmt_execute($stmt_check_labour);
$result_check_labour = mysqli_stmt_get_result($stmt_check_labour);

// If the record exists in creative_items, fetch and display existing data
if (mysqli_num_rows($result_check_labour) > 0) {
$isUpdateLabour = true;
$sql_labour = "SELECT id, expences,type,name, place,date,endtime,total_ot,labour_cost,regular_time,regular_expences,total_lab_cost, pvl_branch FROM pre_varifacation_labour WHERE jc_number = ? AND pvl_branch LIKE '%$userbranch%'";
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
$check_transport_sql = "SELECT jc_number FROM pre_varification_transport WHERE jc_number = ? AND pvtr_branch LIKE '%$userbranch%'";
$stmt_check_transport = mysqli_prepare($conn, $check_transport_sql);

if ($stmt_check_transport) {
mysqli_stmt_bind_param($stmt_check_transport, "s", $newUserId);
mysqli_stmt_execute($stmt_check_transport);
$result_check_transport = mysqli_stmt_get_result($stmt_check_transport);

// If the record exists in creative_items, fetch and display existing data
if (mysqli_num_rows($result_check_transport) > 0) {
$isUpdateTransport = true;
$sql_transport = "SELECT id,pre_tran_date, staff_name,vehicle,`from`, `to`, km, cost,pvtr_branch FROM pre_varification_transport WHERE jc_number = ? AND pvtr_branch LIKE '%$userbranch%'";
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
$check_other_sql = "SELECT jc_number FROM pre_varification_other WHERE jc_number = ? AND pvo_branch LIKE '%$userbranch%'";
$stmt_check_other = mysqli_prepare($conn, $check_other_sql);

if ($stmt_check_other) {
mysqli_stmt_bind_param($stmt_check_other, "s", $newUserId);
mysqli_stmt_execute($stmt_check_other);
$result_check_other = mysqli_stmt_get_result($stmt_check_other);

// If the record exists in creative_items, fetch and display existing data
if (mysqli_num_rows($result_check_other) > 0) {
$isUpdateOther = true;
$sql_other = "SELECT id,pre_other_date,staff_names,exp,remark, other_costs, pvo_branch FROM pre_varification_other WHERE jc_number = ? AND pvo_branch LIKE '%$userbranch%'";
$stmt_other = mysqli_prepare($conn, $sql_other);
if ($stmt_other) {
mysqli_stmt_bind_param($stmt_other, "s", $newUserId);
mysqli_stmt_execute($stmt_other);
$result_other = mysqli_stmt_get_result($stmt_other);


// // Loop through the results if needed
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

if (isset($_POST["save"])) {    
// Labour Form
if (isset($_POST['expences'], $_POST['type'], $_POST['name'], $_POST['place'], $_POST['date'], $_POST['endtime'], $_POST['total_ot'], $_POST['labour_cost'], $_POST['regular_time'], $_POST['regular_expences'], $_POST['total_lab_cost'], $_POST['pvl_branch'])) {
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
    $pvl_branchs = $_POST['pvl_branch'];
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
        $pvl_branch = isset($pvl_branchs[$i]) ? $pvl_branchs[$i] : '';
        $labourId = isset($labourIds[$i]) ? $labourIds[$i] : ''; // Get the labour_id for this row

        // Check if this row should be updated or inserted
        if (!empty($labourId)) {
            // Update
            $sql = "UPDATE pre_varifacation_labour SET expences=?, `type`=?, `name`=?, place=?, `date`=?, endtime=?, total_ot=?, labour_cost=?,regular_time=?,regular_expences=?,total_lab_cost=?,pvl_branch=? WHERE id=?";
        } else {
            // Insert
            if ($expence === "labour" || $expence === "rework" || $expence === "rectification") {
                // Insert all inputs when $expence is not "Bata"
                $sql = "INSERT INTO pre_varifacation_labour (jc_number,expences, `type`, `name`, place, `date`, endtime, total_ot, labour_cost, regular_time, regular_expences, total_lab_cost,pvl_branch) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)";
            } else if ($expence === "bata") {
                // Insert without data, endtime, and total_ot when $expence is "Bata"
                $sql = "INSERT INTO pre_varifacation_labour (jc_number,expences, `type`, `name`, place, `date`, total_lab_cost,pvl_branch) VALUES (?,?,?,?,?,?,?,?)";
            }
        }

        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            // Error handling if the preparation of SQL statement fails
            die('Error preparing SQL statement: ' . $conn->error);
        }

        if (!empty($labourId)) {
            $stmt->bind_param("sssssssdsddsi", $expence, $type, $name, $place, $date, $endtime, $total_ot, $cost,$regular_time,$regular_expences,$total_lab_cost,$pvl_branch, $labourId);
            } else {
            if ($expence === "labour" || $expence === "rework" || $expence === "rectification") {
                $stmt->bind_param("ssssssssdsdds", $newUserId, $expence, $type, $name, $place, $date, $endtime, $total_ot,$cost,$regular_time,$regular_expences,$total_lab_cost,$pvl_branch);
            } else if ($expence === "bata") {
                $stmt->bind_param("ssssssds", $newUserId, $expence, $type, $name, $place, $date, $total_lab_cost,$pvl_branch);
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

// Transport Form
if (isset($_POST['pre_tran_date'],$_POST['staff_name'],$_POST['vehicle'], $_POST['from'], $_POST['to'], $_POST['km'], $_POST['cost'], $_POST['pvtr_branch'])) {
$pre_tran_dates = $_POST['pre_tran_date'];
$staff_names = $_POST['staff_name'];
$vehicles = $_POST['vehicle'];
$froms = $_POST['from'];
$tos = $_POST['to'];
$kms = $_POST['km'];
$costss = $_POST['cost'];
$pvtr_branchs = $_POST['pvtr_branch'];
$transportIds = $_POST['transport_id']; // Add item_id field to identify existing rows

// Update the existing record in the creative_items table
$update_transport_sql = "UPDATE pre_varification_transport SET pre_tran_date=?,staff_name=?,vehicle=?,`from`=?, `to`=?,km=?,cost=?,pvtr_branch=? WHERE id=?";
$stmt_transport1 = $conn->prepare($update_transport_sql);
$stmt_transport1->bind_param("sssssddsi",$pre_tran_date, $staff_name,$vehicle, $from, $to,$km,$costs,$pvtr_branch,$transportId);

// Insert a new record into the creative_items table
$insert_transport_sql = "INSERT INTO pre_varification_transport (jc_number,pre_tran_date,staff_name,vehicle,`from`, `to`,km,cost,pvtr_branch) VALUES (?,?,?,?,?,?,?,?,?)";
$stmt_transport2 = $conn->prepare($insert_transport_sql);
$stmt_transport2->bind_param("ssssssdds", $newUserId,$pre_tran_date,$staff_name,$vehicle, $from, $to,$km,$costs,$pvtr_branch);

// Loop through the arrays and insert/update each row separately
for ($i = 0; $i < count($staff_names); $i++) {
$pre_tran_date = isset($pre_tran_dates[$i]) ? $pre_tran_dates[$i] : null;
$staff_name = isset($staff_names[$i]) ? $staff_names[$i] : null;
$vehicle = isset($vehicles[$i]) ? $vehicles[$i] : null;
$from = isset($froms[$i]) ?$froms[$i] : null;
$to = isset($tos[$i]) ?$tos[$i] : null;
$km = isset($kms[$i]) ?$kms[$i] : null;
$costs = isset($costss[$i]) ?$costss[$i] : null;
$pvtr_branch = isset($pvtr_branchs[$i]) ?$pvtr_branchs[$i] : null;
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
if (isset($_POST['pre_other_date'],$_POST['staff_names'], $_POST['exp'], $_POST['remark'], $_POST['other_costs'], $_POST['pvo_branch'])) {
$pre_other_dates = $_POST['pre_other_date'];
$staff_names = $_POST['staff_names'];
$exps = $_POST['exp'];
$remarks = $_POST['remark'];
$other_costs = $_POST['other_costs'];
$pvo_branchs = $_POST['pvo_branch'];
$otherIds = $_POST['other_id']; // Add item_id field to identify existing rows

// Loop through the arrays and insert/update each row separately
for ($i = 0; $i < count($staff_names); $i++) {
$pre_other_date = isset($pre_other_dates[$i]) ? $pre_other_dates[$i] : null;
$staff_name = isset($staff_names[$i]) ? $staff_names[$i] : null;
$exp = isset($exps[$i]) ? $exps[$i] : null;
$remark = isset($remarks[$i]) ? $remarks[$i] : null;
$other_cost = isset($other_costs[$i]) ? $other_costs[$i] : null;
$pvo_branch = isset($pvo_branchs[$i]) ? $pvo_branchs[$i] : null;
$otherId = isset($otherIds[$i]) ? $otherIds[$i] : null;

// Check if this row should be updated or inserted
if (!empty($otherId)) {
// Update
$update_other_sql = "UPDATE pre_varification_other SET pre_other_date=?, staff_names=?, exp=?, remark=?, other_costs=?, pvo_branch=? WHERE id=?";
$stmt_other1 = $conn->prepare($update_other_sql);
$stmt_other1->bind_param("ssssdsi",$pre_other_date, $staff_name, $exp, $remark, $other_cost, $pvo_branch, $otherId);
$stmt_other1->execute();
$stmt_other1->close();
} else {
// Insert
$insert_other_sql = "INSERT INTO pre_varification_other (jc_number,pre_other_date, staff_names, exp, remark, other_costs, pvo_branch) VALUES (?,?,?,?,?,?,?)";
$stmt_other2 = $conn->prepare($insert_other_sql);
$stmt_other2->bind_param("sssssds", $newUserId,$pre_other_date, $staff_name, $exp, $remark, $other_cost,$pvo_branch);
$stmt_other2->execute();
$stmt_other2->close();

}
}
}

// Retrieve form data
$total_labour_cost = isset($_POST['total_labour_cost']) ? $_POST['total_labour_cost'] : null;
$total_transport_cost = isset($_POST['total_transport_cost']) ? $_POST['total_transport_cost'] : null;
$total_other_cost = isset($_POST['total_other_cost']) ? $_POST['total_other_cost'] : null;
$total_expences = isset($_POST['total_amount']) ? $_POST['total_amount'] : null;
$current_status = isset($_POST['current_status']) ? $_POST['current_status'] : null;
$pre_supervisor = isset($_POST['pre_supervisor']) ? $_POST['pre_supervisor'] : null;
$total_id = $_POST['id'];
$pvt_branch = $_POST['pvt_branch'];

// Check if the record already exists in the pre_varification_total table
$checkQuery = "SELECT * FROM pre_varification_total WHERE jc_number = '$newUserId' AND pvt_branch LIKE '%$userbranch%'";
$checkResult = mysqli_query($conn, $checkQuery);

if (mysqli_num_rows($checkResult) > 0) {
    // Update the existing record
    $updateQuery = "UPDATE pre_varification_total SET total_labour_cost='$total_labour_cost', total_transport_cost='$total_transport_cost', total_other_cost='$total_other_cost', total_amount = '$total_expences', current_status = '$current_status', pre_supervisor = '$pre_supervisor', pvt_branch = '$pvl_branch' WHERE id = '$total_id'";
    if (mysqli_query($conn, $updateQuery)) {
        echo "Record updated successfully";
    } else {
        echo "Error updating record: " . mysqli_error($conn);
    }
} else {
    date_default_timezone_set('Asia/Kolkata'); // Set timezone to IST
    $insert_date = date("Y-m-d");
    // Insert a new record
    $insertQuery = "INSERT INTO pre_varification_total (jc_number, pv_job_startdate, total_labour_cost, total_transport_cost, total_other_cost, total_amount, current_status, pre_supervisor, pvt_branch) VALUES ('$newUserId', '$insert_date', '$total_labour_cost', '$total_transport_cost', '$total_other_cost', '$total_expences', '$current_status', '$pre_supervisor', '$pvt_branch')";
    if (mysqli_query($conn, $insertQuery)) {
        echo "Record inserted successfully";
    } else {
        echo "Error inserting record: " . mysqli_error($conn);
    }
}

// Display a success message after all sections are processed
echo "<script>alert('All data updated successfully'); window.location = 'fab-pre-edit.php?jc_number=$newUserId';</script>";
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
          <h1 class="m-0">PRE-VERIFICATION INVOLVEMENT</h1>
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

          <form action=""method="POST"id="modalTable">

            <div class="card">
              <div class="card-body">
                <div class="row">
                  <div class="col-md-12">

                    <div class="card card-primary card-outline">
                      <div class="card-header">
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#exampleModal">Labour Expenses</button>
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#exampleModals">Transportation Expenses</button>
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#exampleModalses">Other Expenses</button>
                      </div>

                      <div class="card-body">
                        <div class="row">
                          <div class="col-md-4">

                            <div class="form-group">
                              <label for="jcnumber" class="form-label">JC Number</label>
                              <input type="text" class="form-control"id="jc_number"name="jc_number" value="<?php echo $main['jc_number']; ?>" readonly>
                            </div>

                            <div class="form-group">
                              <label for="jcdate" class="form-label">JC Date</label>
                              <input type="text" class="form-control"id="jc_date"name="jc_date"value="<?php echo $main['jc_date']; ?>" readonly>
                            </div>

                            <div class="form-group">
                              <label for="client" class="form-label">Client Name</label>
                              <input type="text"class="form-control"id="client"name="client" value="<?php echo $main['client']; ?>"readonly>
                            </div>

                            <div class="form-group">
                              <label for="instructed" class="form-label">Instructed By</label>
                              <input type="text"class="form-control"id="instructed"name="instructed"value="<?php echo $main['instructed_by']; ?>"readonly>
                            </div>

                            <div class="form-group">
                              <label for="location" class="form-label">Location</label>
                              <input type="text"class="form-control"id="location"name="location"value="<?php echo $main['s_location']; ?>"readonly>
                            </div>

                            <div class="form-group">
                              <label for="involvments" class="form-label">Involvments</label><br>
                              <input type="text"class="form-control"id="involvment"name="involvment"value="<?php echo $main['involvements']; ?>"readonly>
                            </div>

                          </div>

                          <div class="col-sm-8">

                            <table class="table table-striped table-bordered">

                              <thead>
                                <tr>
                                  <th>PV Details</th>
                                </tr>
                              </thead>

                              <tbody>

                                <?php
                                while ($row = mysqli_fetch_assoc($sql_item)) {
                                ?>
                                <tr>
                                  <td><?php echo $row['pre_details']; ?></td>
                                </tr>

                                <?php
                                }
                                ?>
                              </tbody>
                            </table> 
                          </div>
                        </div>
                      </div>
                    </div>

<!-- MODAL FOR LABOUR -->

                    <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true"data-backdrop="static" data-keyboard="false">
                      <div class="modal-dialog modal-xl" role="document">
                        <div class="modal-content">
                          <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">PV Labour Expences</h5>
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
                 echo '<td><input class="form-control" type="text" name="place[]" sid="place' . $rowCounted . '" value="' . $row_labour['place'] . '" readonly></td>';
                 if ($expences == "bata") {
                 echo '<td><input name="date[]" class="form-control" id="date' . $rowCounted . '" value="' . $row_labour['date'] . '" readonly></td>';
                 echo '<td><input type="hidden" name="endtime[]" class="form-control" id="endtime' . $rowCounted . '" value="' . $row_labour['endtime'] . '" readonly></td>';
                 echo '<td><input type="hidden" name="total_ot[]" class="form-control" id="total_ot' . $rowCounted . '" value="' . $row_labour['total_ot'] . '" readonly><input type="hidden" name="labour_cost[]" class="form-control costss" id="labour_cost' . $rowCounted . '" value="' . $row_labour['labour_cost'] . '" readonly ></td>'; 
                 echo '<td><input type="hidden" name="regular_time[]" class="form-control regular_time" id="regular_time' . $rowCounted . '" value="' . $row_labour['regular_time'] . '" readonly><input type="hidden" name="regular_expences[]" class="form-control regular_expences" id="regular_expences' . $rowCounted . '" value="' . $row_labour['regular_expences'] . '" readonly></td>';
                 echo '<td><input type="number" name="total_lab_cost[]" class="form-control total_lab_cost" id="total_lab_cost' . $rowCounted . '" value="' . $row_labour['total_lab_cost'] . '" readonly><input type="hidden" name="labour_id[]" id="labour_id'. $rowCounted .'" value="' . $row_labour['id'] . '"><input type="hidden" name="pvl_branch[]" id="pvl_branch'. $rowCounted .'" value="' . $row_labour['pvl_branch'] . '"></td>';
                 } else {
                 echo '<td><input type="text" name="date[]" class="form-control" id="date' . $rowCounted . '" value="' . $row_labour['date'] . '" readonly></td>';
                 echo '<td><input type="text" name="endtime[]" class="form-control" id="endtime' . $rowCounted . '" value="' . $row_labour['endtime'] . '" readonly></td>';
                 echo '<td><input hidden type="text" name="total_ot[]" class="form-control total_ots" id="total_ot' . $rowCounted . '" value="' . $row_labour['total_ot'] . '" readonly><input type="number" name="labour_cost[]" class="form-control costss" id="labour_cost' . $rowCounted . '" value="' . $row_labour['labour_cost'] . '" readonly step="0.01"></td>';
                 echo '<td><input hidden type="text" name="regular_time[]" class="form-control regular_time" id="regular_time' . $rowCounted . '" value="' . $row_labour['regular_time'] . '" readonly><input type="number" name="regular_expences[]" class="form-control regular_expences" id="regular_expences' . $rowCounted . '" value="' . $row_labour['regular_expences'] . '" readonly></td>';
                 echo '<td><input name="total_lab_cost[]" class="form-control total_lab_cost" id="total_lab_cost' . $rowCounted . '" value="' . $row_labour['total_lab_cost'] . '" readonly><input type="hidden" name="labour_id[]" id="labour_id'. $rowCounted .'" value="' . $row_labour['id'] . '"><input type="hidden" name="pvl_branch[]" id="pvl_branch'. $rowCounted .'" value="' . $row_labour['pvl_branch'] . '"></td>';
                 }
                 echo '</tr>';
                 }
                 ?>
                              </tbody>

                              <tfoot>
                                <tr>
                                  <th colspan="10" style="text-align: right; vertical-align: middle;">Total Labour Cost</th>
                                  <th><input type="number" id="total_labour_cost" name="total_labour_cost" value="<?php echo isset($rowses['total_labour_cost']) ? $rowses['total_labour_cost'] : ''; ?>" class="form-control" readonly></th>
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

<!-- MODAL FOR TRANSPORT -->

                    <div class="modal fade" id="exampleModals" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true"data-backdrop="static" data-keyboard="false">
                      <div class="modal-dialog modal-xl" role="document">
                        <div class="modal-content">
                          
                          <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">PV Transportation Expenses</h5>
                          </div>

                          <div class="modal-body">
                            <table id="Transportationtable" class="table table-striped table-bordered">
                              
                              <thead>
                                <tr>
                                  <th style="width: 3%;"></th>
                                  <th style="width: 10%;">Sl.No</th>
                                  <th style="width: 15%;">Date</th>
                                  <th style="width: 15%;">Staff Name</th>
                                  <th style="width: 15%;">Vehicle</th>
                                  <th style="width: 10%;">From</th>
                                  <th style="width: 10%;">To</th>
                                  <th style="width: 10%;">KM</th>
                                  <th style="width: 10%;">Cost</th>
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

                                echo '<tr id="row' . $rowCounts . '">';
                                echo '<td><input type="checkbox" name="deleted[]"class="delete-checkedbox"></td>';
                                echo '<td><input type="number" class = "form-control" value="' . $rowCounts . '" readonly></td>';
                                echo '<td><input type="text" name="pre_tran_date[]" class = "form-control" id="pre_tran_date' . $rowCounts . '" value="' . $row_transport['pre_tran_date'] . '"readonly></td>';
                                echo '<td><select name="staff_name[]" id="staff_name' . $rowCounts . '" class = "form-control">' . getStaffOption($conn, $row_transport['staff_name']) . '</select> </td>';
                                echo '<td><input type="text" name="vehicle[]" class = "form-control" id="vehicle' . $rowCounts . '" value="' . $row_transport['vehicle'] . '" ></td>';

                                echo '<td><input type="text" name="from[]" class = "form-control" id="from' . $rowCounts . '" value="' . $row_transport['from'] . '" ></td>';
                                echo '<td><input type="text" name="to[]" class = "form-control" id="to' . $rowCounts . '" value="' . $row_transport['to'] . '"></td>';
                                echo '<td><input type="number" name="km[]" class = "form-control" id="km' . $rowCounts . '" value="' . $row_transport['km'] . '" step="0.01" ></td>';
                                echo '<td><input type="number" name="cost[]" class="form-control cost" id="cost' . $rowCounts . '" value="' . $row_transport['cost'] . '" readonly step="0.01"><input type="hidden" name="transport_id[]" id="transport_id' . $rowCounts . '"value="'.$row_transport['id'].'"><input type="hidden" name="pvtr_branch[]" id="pvtr_branch' . $rowCounts . '"value="'.$row_transport['pvtr_branch'].'"></td>';

                                echo '</tr>';
                                }
                                ?>
                              </tbody>

                              <tfoot>
                                <tr>
                                  <th colspan="8" style="text-align:right; vertical-align:middle;">Total  Transport Cost</th>
                                  <th><input type="number" id="total_transport_cost" name="total_transport_cost" value="<?php echo isset($rowses['total_transport_cost']) ? $rowses['total_transport_cost'] : ''; ?>" class="form-control" readonly></th>
                                </tr>
                              </tfoot>
                            </table>
                            <input type="button" name="addmore" id="addmore" value="Add Row" class="btn btn-info">
                            <input type="button" name="deleted" id="deleted" value="Delete Row" class="btn btn-danger">
                          </div>

                          <div class="modal-footer">
                            <button class="btn btn-primary" id="okButtons">CLOSE</button>
                          </div>

                        </div>
                      </div>
                    </div>

<!-- MODAL FOR OTHER -->

                    <div class="modal fade" id="exampleModalses" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"aria-hidden="true"data-backdrop="static" data-keyboard="false">
                      <div class="modal-dialog modal-xl" role="document">
                        <div class="modal-content">

                          <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Add Pre Verifications Other Expences</h5>
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
                                function getStaffOptionses($conn, $selectedStafff)
                                {
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

                                echo '<tr>';
                                echo '<td><input type="checkbox" name="deletes[]"class="deletes-checkbox"></td>';
                                echo '<td><input type="number" class = "form-control" value="' . $rowCount . '" readonly></td>';
                                echo '<td><input type="text" name="pre_other_date[]" class = "form-control" id="pre_other_date' . $rowCount . '" value="' . $row_other['pre_other_date'] . '"readonly></td>';

                                echo '<td><select name="staff_names[]" id="staff_names" class = "form-control">' . getStaffOptionses($conn, $row_other['staff_names']) . '</select> </td>';
                                echo '<td><select type="text" name="exp[]" class = "form-control" id="exp' . $rowCount . '">';
                                echo '<option value="food_exp"' . ($row_other['exp'] == 'food_exp' ? ' selected' : '') . '>Food Exp</option>';
                                echo '<option value="travel_exp"' . ($row_other['exp'] == 'travel_exp' ? ' selected' : '') . '>Travel Exp</option>';
                                echo '<option value="other_exp"' . ($row_other['exp'] == 'other_exp' ? ' selected' : '') . '>Other Exp</option>';
                                echo '</select></td>';
                                echo '<td><input type="text" name="remark[]" class = "form-control" id="remark' . $rowCount . '" value="' . $row_other['remark'] . '"></td>';
                                echo '<td><input type="number" name="other_costs[]" class="form-control costs" id="other_costs' . $rowCount . '" value="' . $row_other['other_costs'] . '"step="0.01"><input type="hidden" name="other_id[]"id="other_id'.$rowCount.'"value="'.$row_other['id'].'"><input type="hidden" name="pvo_branch[]"id="pvo_branch'.$rowCount.'"value="'.$row_other['pvo_branch'].'"></td>';

                                echo '</tr>';

                                }
                                ?>
                              </tbody>

                              <tfoot>
                                <tr>
                                  <th colspan="6" style="text-align: right; vertical-align:middle;">Total  Other Cost</th>
                                  <th><input type="number" id="total_other_cost" name="total_other_cost" value="<?php echo isset($rowses['total_other_cost']) ? $rowses['total_other_cost'] : ''; ?>" class="form-control" readonly></th>
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

                    <div class="card card-primary card-outline">
                      <div class="card-body">
                        <table id="datatable" class="table table-striped table-bordered">
                          <thead>
                            <tr>
                              <th>Total Expences </th>
                              <th>Current Status</th>
                              <th>Supervisor</th>
                            </tr>
                          </thead>
                          
                          <tbody>
                            <tr>
                              <td><input class="form-control" type="number" id="total_amount" name="total_amount" value="<?php echo isset($rowses['total_amount']) ? $rowses['total_amount'] : ''; ?>" class="form-control" readonly></td>
                              <td>
                                <select class="form-control" name="current_status" id="current_status"required>
                                  <option value="" selected disabled>Choose an option</option>
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
$query_pre_supervisor = "SELECT pre_supervisor FROM pre_varification_total WHERE jc_number = '$specific_jc_number' AND pvt_branch LIKE '%$userbranch%'";

// Execute the queries
$result_staffs = mysqli_query($conn, $query_staffs);
$result_pre_supervisor = mysqli_query($conn, $query_pre_supervisor);


// Check if the queries were successful
if ($result_staffs) {
    // Start the select element
    echo '<td>';
    echo '<select name="pre_supervisor" id="pre_supervisor" class="form-control" required>';

    // Add the default option
    echo '<option value="" selected disabled>--Choose Supervisor--</option>';

    // Loop through the result set and generate options
    while ($row = mysqli_fetch_assoc($result_staffs)) {
        // Output an option for each staff name
        echo '<option value="' . $row['staff_name'] . '"';

        // Check if the pre_supervisor value is fetched and matches the current staff name
        if ($result_pre_supervisor) {
            $row_pre_supervisor = mysqli_fetch_assoc($result_pre_supervisor);
            if ($row_pre_supervisor && $row['staff_name'] == $row_pre_supervisor['pre_supervisor']) {
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
                              <input type="hidden" name="pvt_branch"id="pvt_branch"value="<?php echo $userbranch; ?>">
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    </div>

                  </div>
                </div>
              </div>
              <div class="card-footer">
                <button type="submit"class="btn btn-success"name="save"id="save">Save Involvement</button>
              </div>

            </div>
          </form>
        </div>
      </div>
    </div>
  </section>
</div>



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
cell2.innerHTML ='<select name="expences[]"class="form-control expences" id="expences' +rowCounted +'"required><option selected disabled value="">Choose Expences </option><option value="labour">Labour</option><option value="bata">Bata</option><option value="rework">Rework</option><option value="rectification">Rectification</option></select>';
cell3.innerHTML ='<select type="text"name="type[]"class="form-control type"id="type' +rowCounted +'"required><option value="fabrication">Fabrication</option><option value="painting">Painting</option><option value="electrical">Electrical</option><option value="vinyl pasting">Vinrl Pasting</option><option value="labour">Labour</option> <option value="erection">Erection</option><option value="pre-verification">Pre-Verification</option></select>';
cell4.innerHTML ='<select type="text"name="name[]"class="form-control names"id="name' +rowCounted +'"required></select>';
cell5.innerHTML ='<input type="text"name="place[]"class="form-control place"id="place' +rowCounted +'"required>';
cell6.innerHTML ='<input type="text"name="date[]" class="form-control dates" id="date' +rowCounted +'">';
cell7.innerHTML ='<input type="text"name="endtime[]"class="form-control endtimes" id="endtime' +rowCounted +'">';
cell8.innerHTML ='<input hidden type="text"name="total_ot[]"class="form-control total_ots" id="total_ot' +rowCounted +'"readonly><input type="number"name="labour_cost[]" class="form-control costss" id="labour_cost' +rowCounted +'"readonly>';
cell9.innerHTML ='<input hidden type="text"name="regular_time[]"class="form-control regular_times" id="regular_time' +rowCounted +'"readonly><input type="number"name="regular_expences[]"class="form-control regular_expences" id="regular_expences' +rowCounted +'" readonly>';
cell10.innerHTML ='<input type="number"name="total_lab_cost[]"class="form-control total_lab_costs" id="total_lab_cost' +rowCounted +'" readonly><input type="hidden" name="labour_id[]" id="labour_id' +rowCounted +'" value=""><input type="hidden" name="pvl_branch[]" id="pvl_branch' +rowCounted +'" value="<?php echo $userbranch ?>">';

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

            dateStartTimeInputs.value = "";
            dateEndTimeInputs.value = "";
            totalOTInputs.value = "";
            costInputs.value = "";
            regularTimes.value = "";
            regularExpencess.value = "";
            totalLabCosts.value = "";

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

});

document.getElementById("deleterows").addEventListener("click", function () {
            var selectedId = [];
            var checkboxes = document.querySelectorAll(".deleterows-checkboxes:checked");

            checkboxes.forEach(function (checkbox) {
                // Get the closest row (tr) and remove it from the table
                var row = checkbox.closest("tr");
                row.remove();

                // Get the hidden input field with item_id and add its value to the selectedIds array
                var itemIdInput = row.querySelector("input[name='labour_id[]']");
                if (itemIdInput) {
                    selectedId.push(itemIdInput.value);
                }
            });

            // Store selected IDs temporarily
            localStorage.setItem("selectedId", JSON.stringify(selectedId));
        });

        document.getElementById("save").addEventListener("click", function () {
            var selectedId = JSON.parse(localStorage.getItem("selectedId"));

            // Send the selected IDs to your server using AJAX
            if (selectedId && selectedId.length > 0) {
                var xhr = new XMLHttpRequest();
                xhr.open("POST", "", true); // Assuming delete.php is your server-side script
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.onreadystatechange = function () {
                    if (xhr.readyState == 4 && xhr.status == 200) {
                        
                    }
                };
                xhr.send("selectedId=" + JSON.stringify(selectedId));
                
                // Clear selected IDs after sending to server
                localStorage.removeItem("selectedId");
            }
        });
    });
    </script>
    <?php
include_once('../../../include/php/connect.php');
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["selectedId"])) {
    $selectedId = json_decode($_POST["selectedId"]);

    // Delete records from the database
    foreach ($selectedId as $id) {
        $delete_items_sql = "DELETE FROM pre_varifacation_labour WHERE id=?";
        $stmt_delete_items = $conn->prepare($delete_items_sql);
        $stmt_delete_items->bind_param("i", $id);
        $stmt_delete_items->execute();
    }
}
?>


<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
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
newRow.id = 'row' + rowCounts;

cell0.innerHTML = '<input type="checkbox" name="deleted[]" class="delete-checkedbox">';
cell1.innerHTML = '<input type="number" class="form-control" value="' + rowCounts + '" readonly>';
cell2.innerHTML = '<input type="date" name="pre_tran_date[]" class="form-control pre_tran_date" id="pre_tran_date' + rowCounts + '" required>';
cell3.innerHTML = '<select type="text" name="staff_name[]" class="form-control staff_name" id="staff_name' + rowCounts + '"required></select>';
cell4.innerHTML = '<select name="vehicle[]"class="form-control vehicle" id="vehicle' + rowCounts + '" required><option selected disabled value="">--Choose Vehicle--</option><option value="KL01 AW 0738">KL01 AW 0738</option><option value="KL01 BF 8159">KL01 BF 8159</option><option value="KL 01 CY 2262">KL 01 CY 2262</option><option value="KL01 DA 8030">KL01 DA 8030</option><option value="KL01 AR 6420">KL01 AR 6420</option><option value="KL01 BU 1911">KL01 BU 1911</option><option value="KL01 BK 3664">KL01 BK 3664</option></select>';
cell5.innerHTML = '<input type="text" name="from[]"class="form-control from"id="from' + rowCounts + '"required>';
cell6.innerHTML = '<input type="text" name="to[]"class="form-control to"id="to' + rowCounts + '"required >';
cell7.innerHTML = '<input type="number" name="km[]" class="form-control km" id="km' + rowCounts + '" step="0.01" required>';
cell8.innerHTML = '<input type="number" name="cost[]"class="form-control cost"id="cost' + rowCounts + '" readonly step="0.01"required><input type="hidden" name="transport_id[]" id="transport_id' + rowCounts + '" value=""><input type="hidden" name="pvtr_branch[]" id="pvtr_branch' + rowCounts + '" value="<?php echo $userbranch; ?>">';


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
            var checkboxes = document.querySelectorAll(".delete-checkedbox:checked");

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
        $delete_items_sql = "DELETE FROM pre_varification_transport WHERE id=?";
        $stmt_delete_items = $conn->prepare($delete_items_sql);
        $stmt_delete_items->bind_param("i", $id);
        $stmt_delete_items->execute();
    }
}
?>


<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
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
newRow.id = 'rows' + rowCount;

// Set the content of the cells
cell0.innerHTML = '<input type="checkbox" name="deletes[]" class="deletes-checkbox">';
cell1.innerHTML = '<input type="number" class = "form-control" value="' + rowCount + '" readonly>';
cell2.innerHTML = '<input type="date" name="pre_other_date[]" class="form-control pre_other_date" id="pre_other_date' + rowCount + '"required>';
cell3.innerHTML = '<select name="staff_names[]" id="staff_names' + rowCount + '"class="form-control staff_names"required></select>';
cell4.innerHTML = '<select type="text" name="exp[]" class="form-control exp" id="exp' + rowCount + '"required><option value="food_exp">Food Exp</option><option value="travel_exp">Travel Exp</option> <option value="other_exp">Other Exp</option></select>';
cell5.innerHTML = '<input type="text" name="remark[]" class="form-control remark" id="remark' + rowCount + '"required>';
cell6.innerHTML = '<input type="number" name="other_costs[]"class="form-control other_costs"id="other_costs' + rowCount + '"step="0.01"required><input type="hidden" name="other_id[]"id="other_id'+rowCount+'"><input type="hidden" name="pvo_branch[]"id="pvo_branch'+rowCount+'" value="<?php echo $userbranch; ?>">';

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
                var itemIdInputs = row.querySelector("input[name='other_id[]']");
                if (itemIdInputs) {
                    selectedIdes.push(itemIdInputs.value);
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
        $delete_items_sql = "DELETE FROM pre_varification_other WHERE id=?";
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
    var requiredFields = document.querySelectorAll('.expences[required], .type[required], .name[required], .place[required], .dates[required], .endtime[required], .pre_tran_date[required], .staff_name[required], .vehicle[required], .from[required], .to[required], .km[required], .cost[required], .pre_other_date[required], .staff_names[required], .exp[required], .remark[required], .other_costs[required]');
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
    $('#total_labour_cost').val(totalLabourCost); // Update the total labour cost input field
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
    $('#total_transport_cost').val(totalTransportCost); // Update the total transport cost input field
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
    $('#total_other_cost').val(totalOtherCost); // Update the total other cost input field
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

// Function to calculate and update the total expense
function updateTotalExpense() {
    var totalLabourCost = parseFloat($('#total_labour_cost').val()) || 0;
    var totalTransportCost = parseFloat($('#total_transport_cost').val()) || 0;
    var totalOtherCost = parseFloat($('#total_other_cost').val()) || 0;

    var totalExpense = totalLabourCost + totalTransportCost + totalOtherCost;
    $('#total_amount').val(totalExpense.toFixed(2)); // Update the total expense input field
}
</script>

<!-- Include Footer File -->
<?php include_once ('../../../include/php/footer.php') ?>

</body>
</html> 