<?php
// Include Database Connection
include_once "../../include/php/connect.php";

// Start the session (make sure you have this at the beginning of your PHP script)

session_start(); 
error_reporting(E_ALL);
ini_set("display_errors", 1);

if (!isset($_SESSION['user']) || $_SESSION['role'] !== '4') {
    echo "<script>alert('You are not authorised to view the URL - Please login using your username and password before accessing URL...'); window.location = '$app_url';</script>";
    exit();
}

// Calculate the remaining time
$sessionStart = $_SESSION['session_start'];
$sessionLifetime = $_SESSION['session_lifetime'];
$currentTime = time();
$remainingTime = ($sessionStart + $sessionLifetime) - $currentTime;


// Check if the 'user' session variable is set
if (isset($_SESSION["username"])) {
    $name = $_SESSION["username"]; // Get the value of 'user' from the session
} else {
    echo "User is not logged in."; // Print a message if the session variable is not set
}
?>


<!-- Include Header File -->
<?php include_once ('../../include/php/header.php') ?>

<!-- Include Sidebar File -->
<?php include_once ('../../include/php/sidebar-fab.php') ?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <!-- Content Header (Page header) -->
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0">DASHBOARD</h1>
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

<!-- Your Main Content Goes Here -->

        </section>
      </div>
      <!-- /.row (main row) -->
    </div>
    <!-- /.container-fluid -->
  </section>
  <!-- /.content -->
</div>
  
<!-- Include Footer File -->
<?php include_once ('../../include/php/footer.php') ?>


</div>
<!-- ./wrapper -->



</body>
</html>
