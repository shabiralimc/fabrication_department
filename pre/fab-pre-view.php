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

// Query to retrieve user information from jobcard_main
$sql_main = "SELECT jc_number,quotation_number,bill_on,jc_date,quotation_date,peyment_terms,client,instructed_by,jc_status,proposed_rate,s_location,completion_before,jc_billable,now_remark,involvements, user FROM jobcard_main WHERE jc_number = ?";

// Prepare the statement
$stmt_main = mysqli_prepare($conn, $sql_main);

// Bind the jc_number parameter
mysqli_stmt_bind_param($stmt_main, "s", $jc_number);

// Execute the statement
mysqli_stmt_execute($stmt_main);

// Get the result
$result_main = mysqli_stmt_get_result($stmt_main);

// Query to retrieve related records from jobcard_items
$sql_items = "SELECT pre_details FROM jobcard_items_pv WHERE jc_number = ?";

// Prepare the statement
$stmt_items = mysqli_prepare($conn, $sql_items);

// Bind the jc_number parameter
mysqli_stmt_bind_param($stmt_items, "s", $jc_number);

// Execute the statement
mysqli_stmt_execute($stmt_items);

// Get the result
$result_items = mysqli_stmt_get_result($stmt_items);




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
              <?php if ($result_main) { ?>
              <div id="print">
                <div style="padding: 10px;">
                  <div style="padding: 20px;"><img style="width: 100px;" src="<?php echo $app_url; ?>/dist/img/logo-full.png" alt="Chakra Logo" class="brand-image"></div>
                  <div class="card card-primary card-outline">
                    <div class="card-body">
                      <table class="table table-bordered table-striped">
                    <thead>
                      <?php while ($user_info = mysqli_fetch_assoc($result_main)) { 
                        $username = $user_info['user']; // Store the username in a variable
                        ?>
                      <tr>
                        <?php 
                        if ($user_info['jc_status'] == "cancelled") {
                          echo '<th style="font-size:30px; font-weight: 900; text-align: center; background-color: lightgrey;" colspan="4">CANCELLED PRE-VERIFICATION</th>';
                        } else {
                          echo '<th style="font-size:30px; font-weight: 900; text-align: center; background-color: lightgrey;" colspan="4">PRE-VERIFICATION</th>';
                        }
                        ?>
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
                        <td>Involvements</td>
                        <td colspan="3"><h5>
                          <?php
                        $involvementsArray = explode(', ', $user_info['involvements']);
                        $fab_tvmChecked = in_array('fab_tvm', $involvementsArray) ? 'checked' : '';
                        $fab_ekmChecked = in_array('fab_ekm', $involvementsArray) ? 'checked' : '';
                        ?>
                        <?php 
                        if ($fab_tvmChecked == "checked") {
                          echo '<i class="fas fa-check-square"></i> Fabrication TVM';
                        }
                        if ($fab_ekmChecked == "checked") {
                          echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span><i class="fas fa-check-square"></i> Fabrication EKM</span>';
                        }
                        ?>
                        </h5></td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
                  <?php } ?>
                  <?php } else {
                    echo "User not found for JC Number: " . $jc_number;
                    } ?>
                  <?php if ($result_items) { $i=1; ?>
                  <div class="card card-primary card-outline">
                    <div class="card-body">
                      <table class="table table-bordered table-striped">
                    <thead>
                      <tr>
                        <th style="text-align:center; font-weight: 900; font-size:20px;" colspan="7">PRE-VERIFICATION DETAILS</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php while ($row = mysqli_fetch_assoc($result_items)) { ?>
                      <tr>
                        <td><?php echo nl2br($row['pre_details']); ?></td>
                      </tr>
                      <?php $i++; } ?>
                    </tbody>
                  </table>
                </div>
              </div>
                  <?php } else {
                    echo "No related records found for JC Number: " . $jc_number;
                  } 
                    ?>
                  <div class="card card-primary card-outline">
                    <div class="card-body">
                  <table style="width:50%; " class="table table-bordered">
                    <tr>
                      <td style="width:20%">Job Created By</td>
                      <td style="width:30%"><?php echo strtoupper($username); ?></td>
                    </tr>
                    <tr>
                      <td style="width:20%">JC Printed By</td>
                      <td style="width:30%"><?php echo strtoupper($_SESSION['user']); ?></td>
                    </tr>
                  </table>
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
                <div class="col-sm-3"><a class="btn btn-danger btn-block" href="fab-pre-jobcard.php"><i class="fa fa-window-close"></i> Close</a></div>
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