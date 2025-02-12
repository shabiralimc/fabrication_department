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
          <h1 class="m-0">MASTERS DASHBOARD</h1>
        </div>
      </div>
      <!-- /.row -->
    </div>
    <!-- /.container-fluid -->
  </div>
  <!-- /.content-header -->
  <!-- Main content -->
  <section class="content">
    <div class="container-fluid">
      <!-- Main row -->
      <div class="row">
        <section class="col-12">

          <div class="card card-primary card-outline">
            <div class="card-body">
              <div class="row">
                <div class="col-md-3">
                  <a href="brand-master/master-brand.php" type="button" class="btn btn-block bg-gradient-primary" style="padding: 3rem .75rem;">BRAND</a>
                </div>
                <div class="col-md-3">
                  <a href="category-master/master-category.php" type="button" class="btn btn-block bg-gradient-primary" style="padding: 3rem .75rem;">CATEGORY</a>
                </div>
                <div class="col-md-3">
                  <a href="godown-master/master-godowns.php" type="button" class="btn btn-block bg-gradient-primary" style="padding: 3rem .75rem;">GODOWN</a>
                </div>
                <div class="col-md-3">
                  <a href="material-master/master-material.php" type="button" class="btn btn-block bg-gradient-primary" style="padding: 3rem .75rem;">MATERIAL NAME</a>
                </div>
              </div>
              <div class="row" style="margin-top: 25px;">
                <div class="col-md-3">
                  <a href="material-unit/master-unit.php" type="button" class="btn btn-block bg-gradient-primary" style="padding: 3rem .75rem;">UNIT</a>
                </div>
                <div class="col-md-3">
                  <a href="supplier-master/supplier-master.php" type="button" class="btn btn-block bg-gradient-primary" style="padding: 3rem .75rem;">SUPPLIER</a>
                </div>
              </div>
            </div>
          </div>

        </section>
      </div>
      <!-- /.row (main row) -->
    </div>
    <!-- /.container-fluid -->
  </section>
  <!-- /.content -->
</div>
  
<!-- Include Footer File -->
<?php include_once ('../../../include/php/footer.php') ?>


</div>
<!-- ./wrapper -->



</body>
</html>