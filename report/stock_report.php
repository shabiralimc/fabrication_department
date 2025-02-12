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

// Fetch godowns from master_godowns table
$query = "SELECT DISTINCT godownName FROM master_godown";
$result = $conn->query($query);

// Initialize an array to hold the godown names
$godowns = [];

// Fetch all godown names
while ($row = $result->fetch_assoc()) {
    $godowns[] = $row['godownName'];
}

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
            <h3 class="m-0">STOCK REPORTS</h3>
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
                <form id="dateForm">
                    <div class="input-group">
                        <input type="date" id="selectedDate" class="form-control form-control-lg" required>
                        <div class="input-group-append">
                            <button type="submit"id="search-button"class="btn btn-lg btn-default">
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
                    <th colspan="<?php echo count($godowns) * 5; ?>" style="background-color:#479e8d;">Godown</th>
                </tr>
                <tr>
                    <?php foreach ($godowns as $godown): ?>
                        <th colspan="5" style="background-color:#88f6e0;"><?php echo htmlspecialchars($godown); ?></th>
                    <?php endforeach; ?>
                </tr>
                <tr style="background-color: #92cec2;">
                    <?php for ($i = 0; $i < count($godowns); $i++): ?>
                        <th>Opening Stock</th>
                        <th>Total Purchase</th>
                        <th>Total Consumption</th>
                        <th>Total Return</th>
                        <th>Current Stock</th>
                    <?php endfor; ?>
                </tr>
            </thead>
            <tbody id="reportBody">
                <!-- Data will be populated here -->
            </tbody>
        </table>
    </div>
</div>

  <!-- <div class="card card-info card-outline">
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
  </div> -->
</div>
</section>
</div>

<script>
    
    document.getElementById('dateForm').addEventListener('submit', function(event) {
    event.preventDefault();
    const selectedDate = document.getElementById('selectedDate').value;

    fetch(`getStockData.php?date=${selectedDate}`)
        .then(response => response.json())
        .then(data => {
            const reportBody = document.getElementById('reportBody');
            reportBody.innerHTML = ''; // Clear previous data

            // Group data by material name
            const groupedData = {};
            data.forEach(item => {
                if (!groupedData[item.mn]) {
                    groupedData[item.mn] = { mn: item.mn, godowns: {} };
                }

                groupedData[item.mn].godowns[item.gd] = {
                    openingStock: item.openingStock || 0, 
                    fromOs: item.from_os || '',
                    pq: item.totalPurchase || 0,
                    co: item.totalConsumption || 0,
                    pr: item.totalReturn || 0,
                    currentStock: item.currentStock || 0
                };
            });

            // Populate the table
            for (const material in groupedData) {
                const row = document.createElement('tr');
                row.innerHTML = `<td>${groupedData[material].mn}</td>`;

                for (const godown of <?php echo json_encode($godowns); ?>) {
                    const godownData = groupedData[material].godowns[godown] || {
                        openingStock: 0,
                        fromOs: '',
                        pq: 0,
                        co: 0,
                        pr: 0,
                        currentStock: 0
                    };

                    row.innerHTML += `
                        <td>${godownData.openingStock}<br>From(${godownData.fromOs})</td>
                        <td>${godownData.pq}</td>
                        <td>${godownData.co}</td>
                        <td>${godownData.pr}</td>
                        <td>${godownData.currentStock}</td>
                    `;
                }

                reportBody.appendChild(row);
            }
        })
        .catch(error => console.error('Error fetching data:', error));
});

</script>

<!-- Include Footer File -->
<?php include_once ('../../../include/php/footer.php') ?>

<script>
$(document).ready(function() {
    $('#date-wise-report').DataTable();
});
</script>


</body>
</html>
