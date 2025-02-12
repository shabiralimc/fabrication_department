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


?>


<!-- Include Header File -->
<?php include_once ('../../../include/php/header.php') ?>

<!-- Include Sidebar File -->
<?php include_once ('../../../include/php/sidebar-fab.php') ?>


  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h3 class="m-0">MANAGE MATERIALS</h3>
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


<!-- --------------------------------------------------------------------------------------
  -------------------------- YOUR BODY CONTENT START HERE ---------------------------------
  ------------------------------------------------------------------------------------- -->

  <div class="card card-info card-outline">
    <div class="card-header">
      <div class="row">
                <div class="col-md-8 offset-md-2">
                    <form>
                        <div class="input-group">
                            <input type="date" class="form-control form-control-lg" placeholder="Type your keywords here">
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-lg btn-default">
                                    <i class="fa fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
    </div>
    <div class="card-body">
      <table id="date-wise-report" class="table table-bordered table-striped" style="vertical-align: middle; text-align: center;">
        <thead>
          <tr>
            <th rowspan="3" style="vertical-align: middle; text-align: center; background-color: #92cec2;">Material Name</th>
            <th colspan="10" style="background-color:#479e8d;">Godown</th>
          </tr>
          <tr>
            <th colspan="5" style="background-color:#88f6e0;">Trivandrum</th>
            <th colspan="5" style="background-color:#88f6e0;">Ernakulam</th>
          </tr>
          <tr style="background-color: #92cec2;">
            <th>Opening Stock</th>
            <th>Total Purchase</th>
            <th>Total Consumption</th>
            <th>Total Return</th>
            <th>Current Stock</th>
            <th>Opening Stock</th>
            <th>Total Purchase</th>
            <th>Total Consumption</th>
            <th>Total Return</th>
            <th>Current Stock</th>
          </tr>
        </thead>
        <tbody>
        <tr>
          <td>Pipe 2" Round Gindal</td>
          <td>0<p style="font-size: 12px;">As on 12-12-2024</p></td>
          <td>1</td>
          <td>0</td>
          <td>1</td>
          <td>0</td>
          <td>0<p style="font-size: 12px;">As on 12-12-2024</p></td>
          <td>5</td>
          <td>1</td>
          <td>1</td>
          <td>3</td>
        </tr>
        <tr>
          <td>Pipe 2" Round Gindal</td>
          <td>0<p style="font-size: 12px;">As on 12-12-2024</p></td>
          <td>1</td>
          <td>0</td>
          <td>1</td>
          <td>0</td>
          <td>0<p style="font-size: 12px;">As on 12-12-2024</p></td>
          <td>5</td>
          <td>1</td>
          <td>1</td>
          <td>3</td>
        </tr>
        <tr>
          <td>Pipe 2" Round Gindal</td>
          <td>0<p style="font-size: 12px;">As on 12-12-2024</p></td>
          <td>1</td>
          <td>0</td>
          <td>1</td>
          <td>0</td>
          <td>0<p style="font-size: 12px;">As on 12-12-2024</p></td>
          <td>5</td>
          <td>1</td>
          <td>1</td>
          <td>3</td>
        </tr>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card card-info card-outline">
    <div class="card-header">
      <div class="row">
                <div class="col-md-8 offset-md-2">
                    <form>
                        <div class="input-group">
                            <input type="text" class="form-control form-control-lg" placeholder="Type your keywords here">
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-lg btn-default">
                                    <i class="fa fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
    </div>
    <div class="card-body">
    <p>HERE DATE BASED REPORT RESULT SHOWS</p>
    </div>
  </div>
</div>
</section>
</div>

<!-- Include Footer File -->
<?php include_once ('../../../include/php/footer.php') ?>

<script>
$(document).ready(function() {
    $('#date-wise-report').DataTable();
});
</script>


</body>
</html>
