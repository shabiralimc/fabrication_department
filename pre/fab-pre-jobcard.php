<?php

// Include the database connection configuration
include_once("../../../include/php/connect.php");
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


$userbranch = $_SESSION['branch'];


// NEW/PENDING JOBS SQL QUERY
$sqlnew = "SELECT jm.jc_number, jm.jc_date,jm.client,jm.involvements, jm.pre_varification, jm.completion_before, jm.jc_status, jm.csr, pvt.current_status
FROM jobcard_main jm
LEFT JOIN pre_varification_total pvt ON jm.jc_number = pvt.jc_number AND pvt.pvt_branch LIKE '%$userbranch%'
WHERE jm.involvements LIKE '%$userbranch%' AND jm.pre_varification = 'pre_varification' AND (SELECT COUNT(*) FROM pre_varification_total WHERE jc_number = jm.jc_number) = 0 AND jm.jc_status != 'cancelled' ";

$resultnew = mysqli_query($conn, $sqlnew);

// WIP JOBS SQL QUERY
$sqlwip = "SELECT jm.jc_number, jm.jc_date,jm.client,jm.involvements, jm.pre_varification, jm.jc_status, jm.completion_before, jm.csr, pvt.current_status
FROM jobcard_main jm
LEFT JOIN pre_varification_total pvt ON jm.jc_number = pvt.jc_number AND pvt.pvt_branch LIKE '%$userbranch%'
WHERE jm.involvements LIKE '%$userbranch%' AND jm.pre_varification = 'pre_varification' AND pvt.current_status = 'wip' AND jm.jc_status != 'cancelled'";

$resultwip = mysqli_query($conn, $sqlwip);

// COMPLETED JOBS SQL QUERY
$sqlcom = "SELECT jm.jc_number, jm.jc_date,jm.client,jm.involvements, jm.pre_varification, jm.jc_status, jm.completion_before, jm.csr, pvt.current_status
FROM jobcard_main jm
LEFT JOIN pre_varification_total pvt ON jm.jc_number = pvt.jc_number AND pvt.pvt_branch LIKE '%$userbranch%'
WHERE jm.involvements LIKE '%$userbranch%' AND jm.pre_varification = 'pre_varification' AND pvt.current_status = 'completed' AND jm.jc_status != 'cancelled'";

$resultcom = mysqli_query($conn, $sqlcom);

// ALL JOBS SQL QUERY
$sqlall = "SELECT jm.jc_number, jm.jc_date,jm.client,jm.involvements, jm.pre_varification, jm.jc_status, jm.completion_before, jm.csr, pvt.current_status
FROM jobcard_main jm
LEFT JOIN pre_varification_total pvt ON jm.jc_number = pvt.jc_number AND pvt.pvt_branch LIKE '%$userbranch%'
WHERE jm.involvements LIKE '%$userbranch%' AND jm.pre_varification = 'pre_varification' AND jm.jc_status != 'cancelled'";

$resultall = mysqli_query($conn, $sqlall);

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
      </div><!-- /.row -->
    </div>
    <!-- /.container-fluid -->
  </div>
  <!-- /.content-header -->

  <!-- Main content -->
  <section class="content">
    <div class="container-fluid">

      <!-- TAB LAYOUT STARTS -->
      <div class="row">
        <div class="col-md-12">
          <div class="card card-primary card-tabs">
            <div class="card-header p-0 pt-1">
              <ul class="nav nav-tabs" id="custom-tabs-one-tab" role="tablist">
                <li class="nav-item">
                  <a class="nav-link active" id="custom-tabs-one-newjob-tab" data-toggle="pill" href="#custom-tabs-one-newjob" role="tab" aria-controls="custom-tabs-one-newjob" aria-selected="true">NEW/PENDING JOBS</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" id="custom-tabs-one-wip-tab" data-toggle="pill" href="#custom-tabs-one-wip" role="tab" aria-controls="custom-tabs-one-wip" aria-selected="false">WIP JOBS</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" id="custom-tabs-one-completed-tab" data-toggle="pill" href="#custom-tabs-one-completed" role="tab" aria-controls="custom-tabs-one-completed" aria-selected="false">COMPLETED JOBS</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" id="custom-tabs-one-alljobs-tab" data-toggle="pill" href="#custom-tabs-one-alljobs" role="tab" aria-controls="custom-tabs-one-alljobs" aria-selected="false">ALL JOBS</a>
                </li>
              </ul>
            </div>
            <div class="card-body">
              <div class="tab-content" id="custom-tabs-one-tabContent">
                
                <div class="tab-pane fade show active" id="custom-tabs-one-newjob" role="tabpanel" aria-labelledby="custom-tabs-one-newjob-tab">
                  
                  <table id="fabricationTablenew" class="table table-bordered table-striped">
                    <thead>
                      <tr style="text-align: center;">
                        <th>Sl.No</th>
                        <th>JC Number</th>
                        <th>Date</th>
                        <th>Completion Date</th>
                        <th>Client</th>
                        <th>CSR</th>
                        <th>View</th>
                        <th>Edit</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      $sino=0;
                      while ($row = mysqli_fetch_assoc($resultnew)) {
                        $sino++;
                        ?>
                      <tr style="text-align: center;">
                        <td style="width: 5%"><?php echo $sino;?></td>
                        <td style="width: 10%"><?php echo $row['jc_number'] ?></td>
                        <td style="width: 10%"><?php echo date("d-m-Y", strtotime($row['jc_date'])) ?></td>
                        <td style="width: 10%"><?php echo date("d-m-Y", strtotime($row['completion_before'])) ?></td>
                        <td style="width: 10%"><?php echo $row['client'] ?></td>
                        <td style="width: 20%"><?php echo $row['csr']?></td>
                        <td style="width: 10%"><a href="fab-pre-view.php?jc_number=<?php echo $row['jc_number'];?>"class="btn btn-warning"><i class="fas fa-search"></i> View PV</a></td>
                        <td style="width: 10%"><a href="fab-pre-edit.php?jc_number=<?php echo $row['jc_number'];?>"class="btn btn-primary"><i class='fas fa-edit'></i> Edit PV</a></td>

                        <?php
                      }
                      ?>
                      </tr>
                    </tbody>
                  </table>

                </div>

                <div class="tab-pane fade" id="custom-tabs-one-wip" role="tabpanel" aria-labelledby="custom-tabs-one-wip-tab">
                  
                  <table id="fabricationTablewip" class="table table-bordered table-striped" style="width:100%;">
                    <thead>
                      <tr style="text-align: center;">
                        <th>Sl.No</th>
                        <th>JC Number</th>
                        <th>Date</th>
                        <th>Completion Date</th>
                        <th>Client</th>
                        <th>CSR</th>
                        <th>View</th>
                        <th>Edit</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      $sino=0;
                      while ($row = mysqli_fetch_assoc($resultwip)) {
                        $sino++;
                        ?>
                      <tr style="text-align: center;">
                        <td style="width: 5%"><?php echo $sino;?></td>
                        <td style="width: 10%"><?php echo $row['jc_number'] ?></td>
                        <td style="width: 10%"><?php echo date("d-m-Y", strtotime($row['jc_date'])) ?></td>
                        <td style="width: 10%"><?php echo date("d-m-Y", strtotime($row['completion_before'])) ?></td>
                        <td style="width: 10%"><?php echo $row['client'] ?></td>
                        <td style="width: 20%"><?php echo $row['csr']?></td>
                        <td style="width: 10%"><a href="fab-pre-view.php?jc_number=<?php echo $row['jc_number'];?>"class="btn btn-warning"><i class="fas fa-search"></i> View PV</a></td>
                        <td style="width: 10%"><a href="fab-pre-edit.php?jc_number=<?php echo $row['jc_number'];?>"class="btn btn-primary"><i class='fas fa-edit'></i> Edit PV</a></td>

                        <?php
                      }
                      ?>
                      </tr>
                    </tbody>
                  </table>

                </div>

                <div class="tab-pane fade" id="custom-tabs-one-completed" role="tabpanel" aria-labelledby="custom-tabs-one-completed-tab">
                  
                  <table id="fabricationTablecom" class="table table-bordered table-striped" style="width:100%;">
                    <thead>
                      <tr style="text-align: center;">
                        <th>Sl.No</th>
                        <th>JC Number</th>
                        <th>Date</th>
                        <th>Completion Date</th>
                        <th>Client</th>
                        <th>CSR</th>
                        <th>View</th>
                        <th>Edit</th>
                        <th>Report</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      $sino=0;
                      while ($row = mysqli_fetch_assoc($resultcom)) {
                        $sino++;
                        ?>
                      <tr style="text-align: center;">
                        <td style="width: 5%"><?php echo $sino;?></td>
                        <td style="width: 10%"><?php echo $row['jc_number'] ?></td>
                        <td style="width: 10%"><?php echo date("d-m-Y", strtotime($row['jc_date'])) ?></td>
                        <td style="width: 10%"><?php echo date("d-m-Y", strtotime($row['completion_before'])) ?></td>
                        <td style="width: 10%"><?php echo $row['client'] ?></td>
                        <td style="width: 20%"><?php echo $row['csr']?></td>
                        <td style="width: 10%"><a href="fab-pre-view.php?jc_number=<?php echo $row['jc_number'];?>"class="btn btn-warning"><i class="fas fa-search"></i> View PV</a></td>
                        <?php if ($row['current_status'] == "completed" && $row > 0) {
                          echo '<td style="width: 10%"><a href="#"class="btn btn-primary disabled"><i class="fas fa-edit"></i> Edit PV</a></td>';
                        } 
                        ?>

                        <?php if ($row['current_status'] == "completed") {
                          echo '<td style="width: 10%"><a href="fab-pre-report.php?jc_number='.$row['jc_number'].'"class="btn btn-warning"><i class="fas fa-print"></i> Report</a></td>';
                        } else {
                          echo '<td style="width: 10%"><button class="btn btn-warning disabled"><i class="fas fa-print"></i> Report</a></button></td>';
                        }
                        ?>


                        <?php
                      }
                      ?>
                      </tr>
                    </tbody>
                  </table>

                </div>

                <div class="tab-pane fade" id="custom-tabs-one-alljobs" role="tabpanel" aria-labelledby="custom-tabs-one-alljobs-tab">
                  
                  <table id="fabricationTableall" class="table table-bordered table-striped" style="width:100%;">
                    <thead>
                      <tr style="text-align: center;">
                        <th>Sl.No</th>
                        <th>JC Number</th>
                        <th>Date</th>
                        <th>Completion Date</th>
                        <th>Client</th>
                        <th>CSR</th>
                        <th>View</th>
                        <th>Edit</th>
                        <th>Report</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      $sino=0;
                      while ($row = mysqli_fetch_assoc($resultall)) {
                        $sino++;
                        ?>
                      <tr style="text-align: center;">
                        <td style="width: 5%"><?php echo $sino;?></td>
                        <td style="width: 10%"><?php echo $row['jc_number'] ?></td>
                        <td style="width: 10%"><?php echo date("d-m-Y", strtotime($row['jc_date'])) ?></td>
                        <td style="width: 10%"><?php echo date("d-m-Y", strtotime($row['completion_before'])) ?></td>
                        <td style="width: 10%"><?php echo $row['client'] ?></td>
                        <td style="width: 20%"><?php echo $row['csr']?></td>
                        <td style="width: 10%"><a href="fab-pre-view.php?jc_number=<?php echo $row['jc_number'];?>"class="btn btn-warning"><i class="fas fa-search"></i> View PV</a></td>
                        <?php if ($row['current_status'] == "completed" && $row > 0) {
                          echo '<td style="width: 10%"><a href="#"class="btn btn-primary disabled"><i class="fas fa-edit"></i> Completed Job</a></td>';
                        } else {
                          echo '<td style="width: 10%"><a href="fab-pre-edit.php?jc_number='.$row['jc_number'].'"class="btn btn-primary"><i class="fas fa-edit"></i> Edit PV</a></td>';
                        }
                        ?>

                        <?php if ($row['current_status'] == "completed" || $row['current_status'] == "wip") {
                          echo '<td style="width: 10%"><a href="fab-pre-report.php?jc_number='.$row['jc_number'].'"class="btn btn-warning"><i class="fas fa-print"></i> Report</a></td>';
                        } else {
                          echo '<td style="width: 10%"><button class="btn btn-warning disabled"><i class="fas fa-print"></i> Report</a></button></td>';
                        }
                        ?>

                        <?php
                      }
                      ?>
                      </tr>
                    </tbody>
                  </table>

                </div>

              </div>
            </div>
            <!-- /.card -->
          </div>


        </div>
      </div>
      <!-- TAB LAYOUT ENDS -->

    </div>
  </section>
</div>

<!-- Include Footer File -->
<?php include_once ('../../../include/php/footer.php') ?>

<script>
$(document).ready(function() {
    $('#fabricationTablenew').DataTable({
      "responsive": true,
      "columnDefs": [ {
        "targets": [0, 5, 6],
        "orderable": false
      } ],
      "order": [[ 0, "desc" ]]
    });
});

$(document).ready(function() {
    $('#fabricationTablewip').DataTable({
      "responsive": true,
      "columnDefs": [ {
        "targets": [0, 5, 6],
        "orderable": false
      } ],
      "order": [[ 0, "desc" ]]
    });
});

$(document).ready(function() {
    $('#fabricationTablecom').DataTable({
      "responsive": true,
      "columnDefs": [ {
        "targets": [0, 5, 6],
        "orderable": false
      } ],
      "order": [[ 0, "desc" ]]
    });
});

$(document).ready(function() {
    $('#fabricationTableall').DataTable({
      "responsive": true,
      "columnDefs": [ {
        "targets": [0, 5, 6],
        "orderable": false
      } ],
      "order": [[ 0, "desc" ]]
    });
});
</script>

</body>
</html> 
      