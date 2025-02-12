<?php
include_once('../../../include/php/connect.php');

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


$jc_number = $_GET['jc_number']; // Replace with the way you retrieve the jc_number

$currUser_branch = $_SESSION['branch'];

// Query to retrieve user information from jobcard_main
$sql_main = "SELECT jc_number,quotation_number,bill_on,jc_date,quotation_date,peyment_terms,client,instructed_by,jc_status,proposed_rate,s_location,completion_before,jc_billable,now_remark,involvements, user FROM jobcard_main WHERE jc_number = ?";
  
  $stmt_main = mysqli_prepare($conn, $sql_main);
  mysqli_stmt_bind_param($stmt_main, "s", $jc_number);
  mysqli_stmt_execute($stmt_main);
  $result_main = mysqli_stmt_get_result($stmt_main);

  $user_info = mysqli_fetch_assoc($result_main);

//Fetch Fab Main Table Data

$sql_fabmain = "SELECT * FROM fabrication_main WHERE jc_number = ? AND fam_branch = '$currUser_branch' ";
$stmt_fabmain = mysqli_prepare($conn, $sql_fabmain);
mysqli_stmt_bind_param($stmt_fabmain, "s", $jc_number);
mysqli_stmt_execute($stmt_fabmain);
$result_fabmain = mysqli_stmt_get_result($stmt_fabmain);

$row_fabmain = $result_fabmain->fetch_assoc();




$sql_fabmainsing = "SELECT fab_supervisor FROM fabrication_main WHERE jc_number = ? AND fam_branch = '$currUser_branch' ";

  $stmt_fabmainsing = mysqli_prepare($conn, $sql_fabmainsing);
  mysqli_stmt_bind_param($stmt_fabmainsing, "s", $jc_number);
  mysqli_stmt_execute($stmt_fabmainsing);
  $result_fabmainsing = mysqli_stmt_get_result($stmt_fabmainsing);

  $fab_supervisor_name = mysqli_fetch_assoc($result_fabmainsing);

?>


<!-- Include Header File -->
<?php include_once ('../../../include/php/header.php') ?>

<style>
  th, td {
  padding: 2px;
}
</style>

<!-- Include Sidebar File -->
<?php include_once ('../../../include/php/sidebar-fab.php') ?>


<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <!-- Content Header (Page header) -->
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0">VIEW JOBCARD</h1>
        </div>
        <!-- /.col -->
      </div>
      <!-- /.row -->
    </div>
    <!-- /.container-fluid -->
  </div>
  <!-- /.content-header -->
  <!-- Main content -->
  <section class="content">
    <div class="container-fluid">
      <div class="card card-info card-outline">
        <div class="card-body">
          <div class="row">
            <div class="col-md-12">
              <!-- PRINT SECTION -->
              <div id="print">
                <div style="padding: 10px;">
                  <div style="padding: 20px;"><img style="width: 100px;" src="<?php echo $app_url; ?>/dist/img/logo-full.png" alt="Chakra Logo" class="brand-image"></div>
                    <div class="card card-primary card-outline">
                      <div class="card-body">
                        <table class="table table-bordered table-striped">
                          <thead>                           
                            <tr>
                              <th style="font-size:30px; font-weight: 900; text-align: center; background-color: lightgrey;" colspan="4">FAB INVOLVEMENT DETAILS</th>
                            </tr>
                          </thead>
                          <tbody>
                            <tr>
                              <td style="width:20%">JC Number</td>
                              <td style="width:30%"><?php echo $user_info['jc_number']; ?></td>
                              <td style="width:20%">Instructed By</td>
                              <td style="width:30%"><?php echo $user_info['instructed_by']; ?></td>
                            </tr>
                            <tr>
                              <td>JC Date</td>
                              <td><?php echo date("d-m-Y", strtotime($user_info['jc_date'])); ?></td>
                              <td>Completion Before</td>
                              <td><?php echo date("d-m-Y", strtotime($user_info['completion_before'])); ?></td>
                            </tr>
                            <tr>
                              <td>Client</td>
                              <td><?php echo strtoupper($user_info['client']); ?></td>
                              <td>Location</td>
                              <td><h5><?php echo $user_info['s_location']; ?></h5></td>                        
                            </tr>
                            <tr>
                              <td>Nature Of Work/Remarks</td>
                              <td colspan="3"><?php echo str_replace("\n", "<br/>", $user_info['now_remark']); ?></td>
                            </tr>
                            <tr>
                              <td>Fab Supervisor</td>
                              <td colspan="3">
                                <h5>
                                <?php if ($result_fabmainsing->num_rows > 0) {
                                  echo $fab_supervisor_name['fab_supervisor'];
                                } else {
                                  echo "Work Not Started";
                                } ?>
                            </h5>
                              </td>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    </div>

                    <div class="card card-primary card-outline">
                      <div class="card-header">
                        <h4>Fab Work Material Involvements</h4>
                      </div>
                      <div class="card-body">
                        <?php

                        $sql_fabmat = "SELECT * FROM fab_mat_expences WHERE jc_number = ?  AND fama_branch = '$currUser_branch' ";
                        $stmt_fabmat = mysqli_prepare($conn, $sql_fabmat);
                        mysqli_stmt_bind_param($stmt_fabmat, "s", $jc_number);
                        mysqli_stmt_execute($stmt_fabmat);
                        $result_fabmat = mysqli_stmt_get_result($stmt_fabmat);

                        if ($result_fabmat->num_rows > 0) {

                          echo '
                          <table class="table table-bordered table-striped" style="width:100%; text-align:center">
                            <tr>
                              <th>Sl No</th>
                              <th>Date</th>
                              <th>Activity</th>
                              <th>Material Name</th>
                              <th>Measuring Unit</th>
                              <th>Quantity</th>
                              <th>Per Cost (₹)</th>
                              <th>Total Cost (₹)</th>
                            </tr>
                          ';

                          $sl_no = 1;

                            while ($row = $result_fabmat->fetch_assoc()) {
                                echo '<tr>';
                                echo '<td>' . $sl_no++ . '</td>';
                                echo '<td>' . date('d-m-Y', strtotime($row['product_date'])) . '</td>';
                                echo '<td>' . $row['activity'] . '</td>';
                                echo '<td>' . $row['material_name'] . '</td>';
                                echo '<td>' . $row['measuring_unit'] . '</td>';
                                echo '<td>' . $row['quantity'] . '</td>';
                                echo '<td>' . $row['per_cost'] . '</td>';
                                echo '<td>' . $row['total_cost'] . '</td>';
                                echo '</tr>';

                            }
                              echo '<tr><th colspan="7" style="text-align:right; vertical-align:middle;">Total</th><th>₹ '. $row_fabmain['material_total'] .'</th></tr>';
                              echo '</table>';
                            } else {
                                // If no rows, display a message
                                echo '<p>No Data to Available...</p>';
                            }
                        ?>
                      </div>
                    </div>

                    <div class="card card-primary card-outline">
                      <div class="card-header">
                        <h4>Fab Labour Involvements</h4>
                      </div>
                      <div class="card-body">
                        <?php

                        $sql_fablab = "SELECT * FROM fab_labour_expences WHERE jc_number = ? AND fal_branch = '$currUser_branch' ";
                        $stmt_fablab = mysqli_prepare($conn, $sql_fablab);
                        mysqli_stmt_bind_param($stmt_fablab, "s", $jc_number);
                        mysqli_stmt_execute($stmt_fablab);
                        $result_fablab = mysqli_stmt_get_result($stmt_fablab);

                        if ($result_fablab->num_rows > 0) {

                          echo '
                          <table class="table table-bordered table-striped" style="width:100%; text-align:center">
                            <tr>
                              <th>Sl No</th>
                              <th>Expence Type</th>
                              <th>Activity</th>
                              <th>Employee Name</th>
                              <th>Place</th>
                              <th>Start Date</th>
                              <th>End Date</th>
                              <th>OT Amount (₹)</th>
                              <th>Reg Amount (₹)</th>
                              <th>Total Lab Cost (₹)</th>
                            </tr>
                          ';

                          $sl_no = 1;

                            while ($row = $result_fablab->fetch_assoc()) {
                                echo '<tr>';
                                echo '<td>' . $sl_no++ . '</td>';
                                echo '<td>' . $row['expences'] . '</td>';
                                echo '<td>' . $row['type'] . '</td>';
                                echo '<td>' . $row['name'] . '</td>';
                                echo '<td>' . $row['place'] . '</td>';
                                echo '<td>' . date('d-m-Y h:i A', strtotime($row['date'])) . '</td>';
                                echo '<td>' . date('d-m-Y h:i A', strtotime($row['endtime'])) . '</td>';
                                echo '<td>' . $row['labour_cost'] . '</td>';
                                echo '<td>' . $row['regular_expences'] . '</td>';
                                echo '<td>' . $row['total_lab_cost'] . '</td>';
                                echo '</tr>';

                            }
                              echo '<tr><th colspan="9" style="text-align:right; vertical-align:middle;">Total</th><th>₹ '. $row_fabmain['labour_total'] .'</th></tr>';
                              echo '</table>';
                            } else {
                                // If no rows, display a message
                                echo '<p>No Data to Available...</p>';
                            }
                        ?>
                      </div>
                    </div>

                    <div class="card card-primary card-outline">
                      <div class="card-header">
                        <h4>Fab Transport Involvements</h4>
                      </div>
                      <div class="card-body">
                        <?php

                        $sql_fabtra = "SELECT * FROM fab_transport_expences WHERE jc_number = ? AND fat_branch = '$currUser_branch' ";
                        $stmt_fabtra = mysqli_prepare($conn, $sql_fabtra);
                        mysqli_stmt_bind_param($stmt_fabtra, "s", $jc_number);
                        mysqli_stmt_execute($stmt_fabtra);
                        $result_fabtra = mysqli_stmt_get_result($stmt_fabtra);

                        if ($result_fabtra->num_rows > 0) {

                          echo '
                          <table class="table table-bordered table-striped" style="width:100%; text-align:center">
                            <tr>
                              <th>Sl No</th>
                              <th>Date</th>
                              <th>Employee Name</th>
                              <th>Vehicle</th>
                              <th>From</th>
                              <th>To</th>
                              <th>KM</th>
                              <th>Cost (₹)</th>
                            </tr>
                          ';

                          $sl_no = 1;

                            while ($row = $result_fabtra->fetch_assoc()) {
                                echo '<tr>';
                                echo '<td>' . $sl_no++ . '</td>';
                                echo '<td>' . date('d-m-Y', strtotime($row['fab_tran_date'])) . '</td>';
                                echo '<td>' . $row['staff_name'] . '</td>';
                                echo '<td>' . $row['vehicle'] . '</td>';
                                echo '<td>' . $row['from'] . '</td>';
                                echo '<td>' . $row['to'] . '</td>';
                                echo '<td>' . $row['km'] . '</td>';
                                echo '<td>' . $row['cost'] . '</td>';
                                echo '</tr>';

                            }
                              echo '<tr><th colspan="7" style="text-align:right; vertical-align:middle;">Total</th><th>₹ '. $row_fabmain['transport_total'] .'</th></tr>';
                              echo '</table>';
                            } else {
                                // If no rows, display a message
                                echo '<p>No Data to Available...</p>';
                            }
                        ?>
                      </div>
                    </div>

                    <div class="card card-primary card-outline">
                      <div class="card-header">
                        <h4>Fab Other Involvements</h4>
                      </div>
                      <div class="card-body">
                        <?php

                        $sql_faboth = "SELECT * FROM fab_other_expences WHERE jc_number = ? AND fao_branch = '$currUser_branch' ";
                        $stmt_faboth = mysqli_prepare($conn, $sql_faboth);
                        mysqli_stmt_bind_param($stmt_faboth, "s", $jc_number);
                        mysqli_stmt_execute($stmt_faboth);
                        $result_faboth = mysqli_stmt_get_result($stmt_faboth);

                        if ($result_faboth->num_rows > 0) {

                          echo '
                          <table class="table table-bordered table-striped" style="width:100%; text-align:center">
                            <tr>
                              <th>Sl No</th>
                              <th>Date</th>
                              <th>Employee Name</th>
                              <th>Details</th>
                              <th>Remark</th>
                              <th>Cost (₹)</th>
                            </tr>
                          ';

                          $sl_no = 1;

                            while ($row = $result_faboth->fetch_assoc()) {
                                echo '<tr>';
                                echo '<td>' . $sl_no++ . '</td>';
                                echo '<td>' . date('d-m-Y', strtotime($row['fab_other_date'])) . '</td>';
                                echo '<td>' . $row['staff_names'] . '</td>';
                                echo '<td>' . $row['exp'] . '</td>';
                                echo '<td>' . $row['remark'] . '</td>';
                                echo '<td>' . $row['other_costs'] . '</td>';
                                echo '</tr>';

                            }
                              echo '<tr><th colspan="5" style="text-align:right; vertical-align:middle;">Total</th><th>₹ '. $row_fabmain['other_total'] .'</th></tr>';
                              echo '</table>';
                            } else {
                                // If no rows, display a message
                                echo '<p>No Data to Available...</p>';
                            }
                        ?>
                      </div>
                    </div>
                    

                    
                  <div class="card card-primary card-outline">
                    <div class="card-body">

                      <div class="col-md-12">
                          <table style="font-size:30px; font-weight: bold;" class="table table-bordered">
                            <tr>
                              <td style="width:20%">EXPENCES TOTAL</td>
                              <td style="width:30%"><?php echo "₹ " . $row_fabmain['grand_total']; ?></td>
                            </tr>
                          </table>
                        </div>

                        <div class="col-md-12">
                        <table class="table table-bordered">
                          <tr>
                            <td style="width:20%">Job Created By</td>
                            <td style="width:30%"><?php echo strtoupper($user_info['user']); ?></td>
                          </tr>
                          <tr>
                            <td style="width:20%">Report Printed By</td>
                            <td style="width:30%"><?php echo strtoupper($_SESSION['user']); ?></td>
                          </tr>
                        </table>
                      </div>

                  </div>
                  </div>
                  <div class="row">
                    <div class="col-md-12">
                      <p>Job card printed from software on <?php
                        date_default_timezone_set('Asia/Kolkata');
                        $currentTime = date( 'd-m-Y h:i A', time () );
                        echo $currentTime;
                        ?></p>
                    </div>
                  </div>
                </div>
              </div>
              <!-- PRINT SECTION -->
              <center style="margin-top: 50px;">
                <div class="row">
                <div class="col-sm-3"></div>
                <div class="col-sm-3"><a class="btn btn-info btn-block" href="javascript:void(0);" onclick="printPageArea('print')"><i class="fa fa-print"></i> Print</a></div>
                <div class="col-sm-3"><a class="btn btn-danger btn-block" href="fab-work-jobcard.php"><i class="fa fa-window-close"></i> Close</a></div>
                <div class="col-sm-3"></div>
                </div>
                
              </center>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<!-- Include Footer File -->
<?php include_once ('../../../include/php/footer.php') ?>

</div>
<!-- ./wrapper -->

<!-- Page Specific Script -->
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

<script type="text/javascript">
    function printPageArea(print){
    var printContent = document.getElementById(print).innerHTML;
    var originalContent = document.body.innerHTML;
    document.body.innerHTML = printContent;
    window.print();
    document.body.innerHTML = originalContent;
}
</script>


</body>
</html> 