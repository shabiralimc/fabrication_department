<?php
include_once('../../../../include/php/connect.php');
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
<?php include_once ('../../../../include/php/header.php') ?>

<!-- Include Sidebar File -->
<?php include_once ('../../../../include/php/sidebar-fab.php') ?>

  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h3 class="m-0">SELLING PRICE</h3>
          </div>
        </div>
        <!-- /.row -->
      </div>
      <!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->


<!-- --------------------------------------------------------------------------------------
  -------------------------- YOUR BODY CONTENT START HERE ---------------------------------
  ------------------------------------------------------------------------------------- -->

  <!-- Main content -->
  <section class="content">
    <div class="container-fluid">
      <!-- Small boxes (Stat box) -->
      <div class="row">
        <?php
    // Get the 'id' parameter from the URL
        if (isset($_GET['id'])) {
          $Id = $_GET['id'];

        // Query the database to fetch material details
          $sql = "SELECT materialID, materialName, materialUnit FROM material_master_creates WHERE id = ?";
          $stmt = $conn->prepare($sql);
          if (!$stmt) {
            die("Error preparing statement: " . $conn->error);
          }
          $stmt->bind_param("i", $Id);
          $stmt->execute();
          $result = $stmt->get_result();

        // Display the material details if found
          if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            ?>
            <div class="col-lg-3 col-6">
              <!-- small box -->
              <div class="small-box bg-info">
                <div class="inner">
                  <h3><?php echo $row['materialID']; ?></h3>

                  <p>MATERIAL ID</p>
                </div>
              </div>
            </div>
            <div class="col-lg-3 col-6">
              <!-- small box -->
              <div class="small-box bg-success">
                <div class="inner">
                  <h3><?php echo $row['materialName']; ?></h3>

                  <p>MATERIAL NAME</p>
                </div>
              </div>
            </div>
            <div class="col-lg-3 col-6">
              <!-- small box -->
              <div class="small-box bg-warning">
                <div class="inner">
                  <h3><?php echo $row['materialUnit']; ?></h3>

                  <p>MATERIAL UNIT</p>
                </div>
              </div>
            </div>
          </div>


          <!-- Main row -->
          <div class="row">
            <!-- Left col -->
            <section class="col-lg-3 connectedSortable">
              <!-- Custom tabs (Charts with tabs)-->
              <div class="card">
                <div class="card-header">
                  <h3 class="card-title">
                    NEW SELLING PRICE
                  </h3>

                </div><!-- /.card-header -->

                <form method="POST" id="updateForm">
                  <div class="card-body">
                    <div class="form-group">
                      <label for="proposedRate" style="font-weight:bold;">Proposed Rate</label><span style="color:red;">*</span>
                      <input type="number" step="0.001" class="form-control" name="proposedRate" id="proposedRate" required>
                      <input type="hidden" class="form-control" id="hiddenMaterialID" name="materialID" value="<?php echo $row['materialID']; ?>">
                      <input type="hidden" class="form-control" id="hiddenMaterialName" name="materialName" value="<?php echo $row['materialName']; ?>">
                    </div>
                    <div class="form-group">
                      <label for="applicableFrom" style="font-weight:bold;">Applicable From</label><span style="color:red;">*</span>
                      <input type="date" class="form-control" name="applicableFrom" id="applicableFrom" required>
                    </div>
                  </div>
                  <div class="card-footer">
                    <input type="button" class="btn btn-success" value="Update" id="updateButton">                  
                  </div>
                </form>
              </div>
              <!-- /.card -->
            </section>
            <!-- /.Left col -->


            <!-- right col (We are only adding the ID to make the widgets sortable)-->
            <section class="col-lg-8 connectedSortable">

              <!-- Map card -->
              <div class="card bg-gradient-primary">
                <div class="card-header border-0">
                  <h3 class="card-title">
                    PRICE COMPARISON
                  </h3>
                </div>
                <div class="card-body">

                  <div class="row">
                    <div class="col-lg-6">
                      <table class="table table-bordered">
                        <thead>
                          <tr>
                            <th>Recent Purchase Prices</th>
                          </tr>
                          <?php
                        // Fetch recent 3 purchase prices
                          $purchaseQuery = "SELECT perUnit FROM mat_pur_item WHERE mat_pur_item_matname = ? ORDER BY mat_pur_date DESC LIMIT 3";
                          $purchaseStmt = $conn->prepare($purchaseQuery);
                          $purchaseStmt->bind_param("s", $row['materialName']);
                          $purchaseStmt->execute();
                          $purchaseResult = $purchaseStmt->get_result();

                          if ($purchaseResult->num_rows > 0) {
                            while ($purchaseRow = $purchaseResult->fetch_assoc()) {
                              echo "<tr><td>₹ " . $purchaseRow['perUnit'] . "</tr></td>";
                            }
                          } else {
                            echo "<tr><td>No recent purchase prices found.</tr></td>";
                          }
                          ?>
                        </thead>
                      </table>
                    </div>
                    <div class="col-lg-5">
  <table class="table table-bordered">
    <thead>
      <tr>
        <th>Recent Selling Prices</th>
      </tr>
    </thead>
    <tbody>
      <?php
      // Fetch recent selling prices based on materialID
      $materialID = $row['materialID']; // Assuming you have this from the previous code
      $sellingQuery = "SELECT proposed_rate_ary FROM mat_selling_price WHERE materialId = ?";
      $sellingStmt = $conn->prepare($sellingQuery);
      $sellingStmt->bind_param("s", $materialID);
      $sellingStmt->execute();
      $sellingResult = $sellingStmt->get_result();

      if ($sellingResult->num_rows > 0) {
        $sellingRow = $sellingResult->fetch_assoc();
        $proposedRates = json_decode($sellingRow['proposed_rate_ary'], true); // Decode the JSON

        // Sort the rates by applicable date in descending order
        usort($proposedRates, function($a, $b) {
          return strtotime($b['dt']) - strtotime($a['dt']);
        });

        // Display the last five proposed rates
        $recentRates = array_slice($proposedRates, 0, 5); // Get the most recent 5 rates
        foreach ($recentRates as $rate) {
          echo "<tr><td>₹ " . htmlspecialchars($rate['sp']) . " (Applicable from: " . htmlspecialchars($rate['dt']) . ") 
          <span style='cursor:pointer; color:red;' onclick='deleteSellingPrice(\"" . htmlspecialchars($rate['dt']) . "\", \"$materialID\")'>&times;</span></td></tr>";
        }
      } else {
        echo "<tr><td>No recent selling prices found.</td></tr>";
      }
      ?>
    </tbody>
  </table>
</div>

              </div>
            </div>
            <!-- /.card-body-->

          </div>
          <!-- /.card -->


        </section>
        <!-- right col -->
      </div>
      <!-- /.row (main row) -->
    </div><!-- /.container-fluid -->
  </section>
  <!-- /.content -->






  <?php
} else {
  echo "Material not found!";
}
} else {
  echo "No material ID provided!";
}
?>
</div>




    <!-- jQuery -->
<script src="https://work.chakracom.net/plugins/jquery/jquery.min.js"></script>
<!-- jQuery UI 1.11.4 -->
<script src="https://work.chakracom.net/plugins/jquery-ui/jquery-ui.min.js"></script>
<!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
<script>
  $.widget.bridge('uibutton', $.ui.button)
</script>
<!-- Bootstrap 4 -->
<script src="https://work.chakracom.net/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>


<!-- overlayScrollbars -->
<script src="https://work.chakracom.net/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
<!-- AdminLTE App -->
<script src="https://work.chakracom.net/dist/js/adminlte.js"></script>

<!-- DataTables  & Plugins -->
<script src="https://work.chakracom.net/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="https://work.chakracom.net/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="https://work.chakracom.net/plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="https://work.chakracom.net/plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
<script src="https://work.chakracom.net/plugins/datatables-buttons/js/dataTables.buttons.min.js"></script>
<script src="https://work.chakracom.net/plugins/datatables-buttons/js/buttons.bootstrap4.min.js"></script>
<script src="https://work.chakracom.net/plugins/jszip/jszip.min.js"></script>
<script src="https://work.chakracom.net/plugins/pdfmake/pdfmake.min.js"></script>
<script src="https://work.chakracom.net/plugins/pdfmake/vfs_fonts.js"></script>
<script src="https://work.chakracom.net/plugins/datatables-buttons/js/buttons.html5.min.js"></script>
<script src="https://work.chakracom.net/plugins/datatables-buttons/js/buttons.print.min.js"></script>
<script src="https://work.chakracom.net/plugins/datatables-buttons/js/buttons.colVis.min.js"></script>
<!-- Ekko Lightbox -->
<script src="https://work.chakracom.net/plugins/ekko-lightbox/ekko-lightbox.min.js"></script>
<!-- Toastr -->
<script src="https://work.chakracom.net/plugins/toastr/toastr.min.js"></script>

<!-- Select2 -->
<script src="https://work.chakracom.net/plugins/select2/js/select2.full.min.js"></script>

<!-- bs-custom-file-input -->
<script src="https://work.chakracom.net/plugins/bs-custom-file-input/bs-custom-file-input.min.js"></script>

<!-- Bootstrap Switch -->
<script src="https://work.chakracom.net/plugins/bootstrap-switch/js/bootstrap-switch.min.js"></script>

<!-- AdminLTE dashboard demo (This is only for demo purposes) -->
<script src="https://work.chakracom.net/dist/js/pages/dashboard.js"></script>

<!-- date-range-picker -->
<script src="https://work.chakracom.net/plugins/daterangepicker/daterangepicker.js"></script>

<!-- Flatpickr Date -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<!-- ALERT MESSAGES -->

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-tagsinput/0.8.0/bootstrap-tagsinput.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/@yaireo/tagify"></script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.8/dist/sweetalert2.all.min.js"></script>
<script>

function deleteSellingPrice(date, materialID) {
    if (confirm("Are you sure you want to delete this selling price?")) {
        fetch('deleteSellingPrice.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `date=${encodeURIComponent(date)}&materialID=${encodeURIComponent(materialID)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reload the page to reflect changes
                location.reload();
            } else {
                alert("Error deleting selling price: " + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert("An error occurred while deleting the selling price.");
        });
    }
}
    function confirmUpdate() {
        // Get the values of each field
        var proposedRate = $('#proposedRate').val();
        var applicableFrom = $('#applicableFrom').val();
        var materialID = $('#hiddenMaterialID').val(); // Get material ID from hidden input
        var materialName = $('#hiddenMaterialName').val();

        // Client-side validation to check if all fields are filled
        if (!proposedRate || !applicableFrom || !materialID) {
            Swal.fire("Error!", "All fields are required.", "error");
            return; // Stop further execution if any field is empty
        }

        // Proceed with confirmation if all fields are filled
        Swal.fire({
            title: "Are you sure?",
            text: "Do you want to save this proposed rate?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, save it!",
            cancelButtonText: "Cancel"
        }).then((result) => {
            if (result.isConfirmed) {
                // Submit the form data via AJAX
                $.ajax({
                    url: 'insert_data.php', // PHP script to process the form
                    type: 'POST',
                    data: {
                        materialID: materialID,
                        materialName: materialName,
                        proposedRate: proposedRate,
                        applicableFrom: applicableFrom
                    },
                    success: function(response) {
                        console.log("AJAX Success Response:", response); // Log the response
                        try {
                            let res = JSON.parse(response); // Attempt to parse the response as JSON
                            if (res.success) {
                                Swal.fire("Success!", res.message, "success").then(() => {
                                    location.reload(); // Refresh the page
                                });
                            } else {
                                Swal.fire("Error!", res.message, "error");
                            }
                        } catch (error) {
                            console.error("JSON Parse Error:", error); // Log any JSON parse errors
                            // Handle non-JSON response
                            Swal.fire("Success!", response, "success").then(() => {
                                location.reload(); // Refresh the page
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error:", error, "Status:", status, "Response:", xhr.responseText);
                        Swal.fire("Error!", "Failed to save the proposed rate. Please try again later.", "error");
                    }
                });
            }
        });
    }

    // Attach event listener to the button when the DOM is ready
    $(document).ready(function() {
        $('#updateButton').on('click', confirmUpdate);
    });
</script>

</body>
</html>