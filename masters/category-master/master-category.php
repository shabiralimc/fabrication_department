<?php
// Include the database connection configuration
include_once("../../../../include/php/connect.php");
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


if ($_SERVER["REQUEST_METHOD"] == "POST") {

if (isset($_POST['categoryName'], $_POST['categoryDesc'])) {
        // Handle create supplier form submission
        // Process the form data
        $categoryName = $_POST['categoryName'];
        $categoryDesc = $_POST['categoryDesc'];

        // Prepare and execute the insert query
        $sql = "INSERT INTO master_category (category_name, category_description)
                VALUES ('$categoryName', '$categoryDesc')";

        if ($conn->query($sql) === TRUE) {
            echo "New record created successfully";
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
        exit; // Add this to prevent the rest of the page from executing
    }
}
?>

<?php

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize input
    $category_id  = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    $category_Name = isset($_POST['category-Name']) ? $_POST['category-Name'] : '';
    $category_Desc = isset($_POST['category-Desc']) ? $_POST['category-Desc'] : '';
    
    // Prepare and execute the update query using prepared statements
    $sql_update = "UPDATE master_category SET category_name=?, category_description=? WHERE category_id=?";
    $stmt_main = $conn->prepare($sql_update);
    $stmt_main->bind_param("ssi", $category_Name, $category_Desc,$category_id);

    if ($stmt_main->execute()) {
        echo "Record updated successfully"; // Output success message
    } else {
        echo "Error updating record: " . $stmt_main->error; // Output error message
    }
    
    // Close the prepared statement
    $stmt_main->close();
    exit; // Stop further execution
}
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
            <h3 class="m-0">MANAGE CATEGORY</h3>
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
    <div class="card-body">
      <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#category-create">
        Create Category
      </button>
    </div>
    <div class="card-body">
      <table class="table table-bordered table-striped" id="table-category">
        <thead>
          <tr>
            <th style="text-align: center;">Category ID</th>
            <th style="text-align: center;">Category Name</th>
            <th style="text-align: center;">Description</th>
            <th style="text-align: center;">Edit</th>
            <th style="text-align: center;">Delete</th>
          </tr>
        </thead>
        <tbody>
        <?php
                $sql_fetch = mysqli_query($conn, "SELECT * FROM master_category");

                while ($row = mysqli_fetch_assoc($sql_fetch)) {
                ?>
          <tr>
            <td style="text-align: center;"><?php echo $row['category_id']; ?></td>
            <td style="text-align: center;"><?php echo $row['category_name']; ?></td>
            <td style="text-align: center;"><?php echo $row['category_description']; ?></td>
            <td style="text-align: center; width:20%;"><button type="button" class="btn btn-xs btn-primary btn-block btn-edit" data-category-id="<?php echo $row['category_id']; ?>">Edit</button></td>
            <td style="text-align: center; width:20%;"><button type="button" class="btn btn-xs btn-danger btn-block btn-delete" data-categories-id="<?php echo $row['category_id']; ?>">Delete</button></td>
          </tr>
          <?php
                }
                ?>
        </tbody>
      </table>

    </div>
  </div>

      </div>
      
    </section>
<!-- --------------------------------------------------------------------------------------
  -------------------------- YOUR BODY CONTENT ENDS HERE ----------------------------------
  ------------------------------------------------------------------------------------- -->

<!-- MODAL FOR CREATE CATERGORY -->

<div class="modal fade show" id="category-create" aria-modal="true" role="dialog" style="display: none;">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">Create Category</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">×</span>
        </button>
      </div>
      <div class="modal-body">

      <div class="form-group">
          <label for="categoryName">Category Name<span class="text-danger">*</span></label>
          <select name="categoryName" class="form-control" id="categoryName" required>
            <option value="">Select Category</option>
          </select>
          <div id="validationFeedback" class="text-danger"></div>
        </div>

        <div class="form-group">
          <label for="categoryDesc">Description</label>
          <input type="text" name="categoryDesc" class="form-control" id="categoryDesc">
        </div>

      </div>
      <div class="modal-footer justify-content-between">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary" id="createNewCategory">Create</button>
      </div>
    </div>
    <!-- /.modal-content -->
  </div>
  <!-- /.modal-dialog -->
</div>
<!-- MODAL FOR EDIT CATEGORY -->

<div class="modal fade show" id="category-edit" aria-modal="true" role="dialog" style="display: none;">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">Edit Category</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">×</span>
        </button>
      </div>
      <div class="modal-body">
      <input type="hidden" name="category_id" class="form-control" id="category_id">

        <div class="form-group">
          <label for="category-Name">Category Name</label>
          <input type="text" name="category-Name" class="form-control" id="category-Name">
          <div id="validationFeedbacks" class="text-danger"></div>
        </div>

        <div class="form-group">
          <label for="category-Desc">Description</label>
          <input type="text" name="category-Desc" class="form-control" id="category-Desc">
        </div>

      </div>
      <div class="modal-footer justify-content-between">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="createEditCategory">Save Edits</button>
      </div>
    </div>
    <!-- /.modal-content -->
  </div>
  <!-- /.modal-dialog -->
</div>
</div>
<!-- Include Footer File -->
<?php include_once ('../../../../include/php/footer.php') ?>

<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function () {
  document.getElementById('createNewCategory').addEventListener('click', function () {
    // Get input values
    var categoryName = document.getElementById('categoryName').value;
    var categoryDesc = document.getElementById('categoryDesc').value;

     // Check if the required fields are not empty
     if (categoryName === "" ) {
            Swal.fire({
                title: "Required Fields Empty",
                text: "Please fill the Category Name.",
                icon: "warning",
                confirmButtonColor: "#3085d6",
                confirmButtonText: "OK"
            });
            return; // Stop further execution if validation fails
        }

    // Define a regex to match allowed characters (letters, numbers, and spaces)
    var regex = /^[a-zA-Z0-9\s-\/]*$/;

    // Validate categoryName for special characters
    if (!regex.test(categoryName)) {
      Swal.fire({
        title: "Invalid Input",
        text: "Special characters are not allowed in Category Name.",
        icon: "error"
      });
      return; // Stop execution if validation fails
    }

    Swal.fire({
      title: "Are you sure?",
      text: "You won't be able to revert this!",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#3085d6",
      cancelButtonColor: "#d33",
      confirmButtonText: "Yes, Create!"
    }).then((result) => {
      if (result.isConfirmed) {
        // Send AJAX request to insert_supplier.php
        var xhr = new XMLHttpRequest();
        xhr.open("POST", " ", true); // Update the URL to point to the correct PHP file
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function () {
          if (xhr.readyState === 4 && xhr.status === 200) {
            Swal.fire({
              title: "Created!",
              text: "New Category Created.",
              icon: "success"
            }).then(() => {
              location.reload(); // Reload the page
            });
          }
        };
        xhr.send("categoryName=" + encodeURIComponent(categoryName) +
                 "&categoryDesc=" + encodeURIComponent(categoryDesc));
      }
    });
  });
});
</script>


<?php

// Query to fetch existing category names from the master_supplier table
$sql = "SELECT DISTINCT category_name FROM master_category";

$result = mysqli_query($conn, $sql);

if (!$result) {
    // If there's an error in the query, return an empty array
    echo json_encode([]);
    exit;
}

// Fetch category names and store them in an array
$categoryNames = [];
while ($row = mysqli_fetch_assoc($result)) {
    $categoryNames[] = $row['category_name'];
}

// Close the database connection
mysqli_close($conn);

// Return the category names as a JSON array
$categoryNamesJSON = json_encode($categoryNames);
?>

<script>
$(document).ready(function() {
    var $categoryNameSelect = $('#categoryName');
    var $validationFeedback = $('#validationFeedback');
    var regex = /^[a-zA-Z0-9\s-\/]*$/; // Allow only letters, numbers, and spaces

    // Initialize Select2
    var categoryNamesData = <?php echo $categoryNamesJSON; ?>;
    $categoryNameSelect.select2({
        theme: 'bootstrap4',
        placeholder: 'Select or type Category Name',
        allowClear: true,
        tags: true,
        data: categoryNamesData.map(function(name) {
            return { id: name, text: name };
        })
    });

    // Live validation on input
    $categoryNameSelect.on('select2:open', function() {
        var $searchField = $($('.select2-search__field')[0]);

        $searchField.on('input', function() {
            var value = $searchField.val();

            if (regex.test(value)) {
                $validationFeedback.text('');
                $categoryNameSelect.removeClass('is-invalid');
            } else {
                $validationFeedback.text('Special characters are not allowed. Only letters and numbers.');
                $categoryNameSelect.addClass('is-invalid');
            }
        });
    });
});
</script>


<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function () {
  document.getElementById('createEditCategory').addEventListener('click', function () {
    // Get input values
    var category_id = document.getElementById('category_id').value;
    var category_Name = document.getElementById('category-Name').value;
    var category_Desc = document.getElementById('category-Desc').value;

    // Define a regex to match allowed characters (letters, numbers, and spaces)
    var regex = /^[a-zA-Z0-9\s-\/]*$/;

    // Validate category_Name for special characters
    if (!regex.test(category_Name)) {
      Swal.fire({
        title: "Invalid Input",
        text: "Special characters are not allowed in Category Name.",
        icon: "error"
      });
      return; // Stop execution if validation fails
    }

    Swal.fire({
      title: "Are you sure?",
      text: "You won't be able to revert this!",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#3085d6",
      cancelButtonColor: "#d33",
      confirmButtonText: "Yes, Save!"
    }).then((result) => {
      if (result.isConfirmed) {
        // Send AJAX request to update the category details
        var xhr = new XMLHttpRequest();
        xhr.open("POST", " ", true); // Update the URL to point to the correct PHP file
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function () {
          if (xhr.readyState === 4 && xhr.status === 200) {
            Swal.fire({
              title: "Saved!",
              text: "Your edits were saved successfully.",
              icon: "success"
            }).then(() => { 
              location.reload(); // Reload the page
            });
          }
        };

        // Construct the data to be sent in the request
        xhr.send("category_id=" + encodeURIComponent(category_id) +
                 "&category-Name=" + encodeURIComponent(category_Name) +
                 "&category-Desc=" + encodeURIComponent(category_Desc));
      }
    });
  });
});
</script>


<script>
document.addEventListener('DOMContentLoaded', function() {
    var categoryInput = document.getElementById('category-Name');
    var validationFeedbacks = document.getElementById('validationFeedbacks');
    
    categoryInput.addEventListener('input', function() {
        var value = categoryInput.value;
        var regex = /^[a-zA-Z0-9\s-\/]*$/; // Allow only letters, numbers, and spaces

        if (regex.test(value)) {
            validationFeedbacks.textContent = '';
            categoryInput.classList.remove('is-invalid');
        } else {
            validationFeedbacks.textContent = 'Special characters are not allowed.Only allowed letters and Numbers';
            categoryInput.classList.add('is-invalid');
        }
    });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get all elements with the class 'btn-edit'
    var editButtons = document.querySelectorAll('.btn-edit');

    // Add click event listener to each edit button
    editButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            var categoryId = this.getAttribute('data-category-id');

            // Set the supplier_id value in the URL
            var url = new URL(window.location.href);
            url.searchParams.set('category_id', categoryId);
            window.history.pushState({}, '', url);

            // Set the supplier_id value in the modal input field
            document.getElementById('category_id').value = categoryId;
            
            // Open the modal using Bootstrap's modal function
            $('#category-edit').modal('show');
        });
    });
});
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
    var editButtons = document.querySelectorAll('.btn-edit');
    editButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            var categoryId = button.getAttribute('data-category-id');
            // Fetch category details using AJAX
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'fetch_category_details.php?category_id=' + categoryId, true);
            xhr.onload = function () {
                if (xhr.status === 200) {
                    var data = JSON.parse(xhr.responseText);
                    // Check if data is valid
                    if (data && !data.error) {
                        // Populate modal fields with category details
                        document.getElementById('category_id').value = categoryId;
                        document.getElementById('category-Name').value = data.category_name;
                        document.getElementById('category-Desc').value = data.category_description;
                        // Open the modal using Bootstrap's modal function
                        $('#category-edit').modal('show');
                    } else {
                        console.error('Error: ' + (data ? data.error : 'Invalid response'));
                    }
                } else {
                    console.error('Error fetching category details: ' + xhr.statusText);
                }
            };
            xhr.onerror = function () {
                console.error('Error fetching category details.');
            };
            xhr.send();
        });
    });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var deleteButtons = document.querySelectorAll('.btn-delete');

    deleteButtons.forEach(function (button) {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            var categoryId = this.getAttribute('data-categories-id');
            var row = this.closest('tr'); // Get the parent <tr> element

            Swal.fire({
                title: "Are you sure?",
                text: "You won't be able to revert this!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#3085d6",
                confirmButtonText: "Yes, Delete!"
            }).then((results) => {
                if (results.isConfirmed) {
                    // Send AJAX request to delete the supplier
                    var xhr = new XMLHttpRequest();
                    xhr.open("POST", "delete_category.php", true); // Specify your PHP file name or endpoint here
                    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                    xhr.onreadystatechange = function () {
                        if (xhr.readyState === 4) {
                            if (xhr.status === 200) {
                                try {
                                    var response = JSON.parse(xhr.responseText);
                                    if (response.status === "success") {
                                        // Remove the entire row from the table
                                        row.remove();
                                        // Display Swal.fire for successful deletion
                                        Swal.fire({
                                            title: "Deleted!",
                                            text: "The selected supplier has been deleted.",
                                            icon: "success"
                                        }).then((result) => {
                                                if (result.isConfirmed) {
                                                    // Reload the page after the alert is confirmed
                                                    location.reload();
                                                }
                                            });
                                    } else {
                                        // Display Swal.fire for deletion error
                                        Swal.fire({
                                            title: "Error!",
                                            text: "Failed to delete the supplier.",
                                            icon: "error"
                                        });
                                    }
                                } catch (error) {
                                    console.error("Error parsing JSON response:", error);
                                }
                            } else {
                                // Display Swal.fire for network error
                                Swal.fire({
                                    title: "Error!",
                                    text: "Failed to delete the supplier due to a network error.",
                                    icon: "error"
                                });
                            }
                        }
                    };
                    xhr.send("delete_category=true&category_id=" + encodeURIComponent(categoryId));
                }
            });
        });
    });
});
</script>

<script>
  $(document).ready(function() {
    $('#table-category').DataTable({
        'responsive': true,
        'lengthMenu': [[50, 100, 500, -1], [50, 100, 500, 'All']],
        dom: 'Bfrtip',
        buttons: [
            'pageLength',
            {
                extend: 'spacer',
                style: 'bar',
                text: 'Export files:'
            },
            {
                extend: 'copyHtml5',
                filename: 'Category Master Data Export',
                title: 'Category Master Data Export',
                exportOptions: {
                    columns: ':visible'
                }
            },
            {
                extend: 'excelHtml5',
                filename: 'Category Master Data Export',
                title: 'Category Master Data Export',
                exportOptions: {
                    columns: ':visible'
                }
            },
            {
                extend: 'pdfHtml5',
                filename: 'Category Master Data Export',
                title: 'Category Master Data Export',
                exportOptions: {
                    columns: ':visible'
                },
                customize: function(doc) {
                    // Check if content[1] exists and is an object with a table property
                    if (doc.content[1] && typeof doc.content[1] === 'object' && doc.content[1].table) {
                        // Set widths of each column to '*' for auto width
                        doc.content[1].table.widths = Array(doc.content[1].table.body[0].length + 1).join('*').split('');
                        // Set alignment of all cells to center
                        doc.content[1].table.body.forEach(function(row) {
                            row.forEach(function(cell) {
                                cell.alignment = 'center';
                            });
                        });

                        // Set table width to 100%
                        doc.content[1].table.width = '100%';
                    } else {
                        console.error('Content structure does not match expected format.');
                        // Log the content structure for debugging
                        console.log(doc.content);
                    }
                }
            }
        ]
    });
  });
</script>



<script>
$(function () {
  // Initialize Select2 with data from PHP
  var categoryNamesData = <?php echo $categoryNamesJSON; ?>;

  $('#categoryName').select2({
    theme: 'bootstrap4',
    placeholder: 'Select or type category name',
    allowClear: true,
    minimumInputLength: 1, // Minimum length of input before triggering AJAX
    data: categoryNamesData, // Populate with existing categories
    tags: true // Allow custom tags (new categories)
  });
});
</script>

</body>
</html>