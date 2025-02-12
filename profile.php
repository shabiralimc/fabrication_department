<?php

include_once('../../include/php/connect.php');

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


// Retrieve the username from the session
if (isset($_SESSION['id'])) {
    $id = $_SESSION['id'];

    // Use prepared statements to fetch data based on id for user
    $sql_user = "SELECT id, user, username,designation,rolename,contact, email, user_image FROM user WHERE id = ?";
    $stmt_user = mysqli_prepare($conn, $sql_user);

    if ($stmt_user) {
        mysqli_stmt_bind_param($stmt_user, "i", $id);
        mysqli_stmt_execute($stmt_user);
        $result_user = mysqli_stmt_get_result($stmt_user);
        $row_user = mysqli_fetch_assoc($result_user);
        mysqli_stmt_close($stmt_user);
    }

}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["ok"])) {
    if (isset($_POST['user'],$_POST['designation'], $_POST['contact'], $_POST['email'])) {
        $user = $_POST['user'];
        $designation = $_POST['designation'];
        $contact = $_POST['contact'];
        $email = $_POST['email'];

        if ($_FILES['user_image']['error'] === UPLOAD_ERR_OK) {
            // Handle image upload
            $upload_directory = '../../uploads/profile/';
            $file_name = basename($_FILES['user_image']['name']); // Get the uploaded file name
            $target_file = $upload_directory . $file_name;
            $uploadOk = 1;
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
            // Check if the file is an image
            if (getimagesize($_FILES['user_image']['tmp_name'])) {
                // Check file size and file format
                if ($_FILES['user_image']['size'] > 500000) {
                    echo "<script>alert('Sorry, your file is too large.'); window.location = 'profile.php';</script>";
                    $uploadOk = 0;
                }
        
                // Allow only certain file formats (you can adjust this as needed)
                if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg") {
                    echo "<script>alert('Sorry, only JPG, JPEG, and PNG files are allowed.'); window.location = 'profile.php';</script>";
                    $uploadOk = 0;
                }
        
                if ($uploadOk == 1) {
                    if (move_uploaded_file($_FILES['user_image']['tmp_name'], $target_file)) {
                        // Image uploaded successfully, update the file name in the database
                        $sql_update_user = "UPDATE user SET user=?, designation=?, contact=?, email=?, user_image=? WHERE id=?";
                        $stmt_main = $conn->prepare($sql_update_user);
                        $stmt_main->bind_param("sssssi", $user,  $designation,$contact, $email, $file_name, $id);
        
                        if ($stmt_main->execute()) {
                            echo "<script>alert('User details and image data updated successfully.'); window.location = 'profile.php';</script>";
                        } else {
                            echo "Error: " . $stmt_main->error;
                        }
                    } else {
                        echo "<script>alert('Sorry, there was an error uploading your file.'); window.location = 'profile.php';</script>";
                    }
                }
            } else {
                echo "<script>alert('File is not an image.'); window.location = 'profile.php';</script>";
            }
        }
        
        else {
            // No new image file uploaded, update user details without changing the image
            $sql_update_user = "UPDATE user SET user=?, designation=?, contact=?, email=? WHERE id=?";
            $stmt_main = $conn->prepare($sql_update_user);
            $stmt_main->bind_param("ssssi", $user, $designation, $contact, $email, $id);

            if ($stmt_main->execute()) {
                echo "<script>alert('User details updated successfully.'); window.location = 'profile.php';</script>";
            } else {
                echo "Error: " . $stmt_main->error;
            }
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["changePassword"])) {
    // Retrieve the username from the session
if (isset($_SESSION['id'])) {
    $id = $_SESSION['id'];

    $oldPassword = $_POST['oldPassword'];
    $newPassword = $_POST['newPassword'];
    $confirmPassword = $_POST['confirmPassword'];

    // Check if the old password is correct (you'll need to replace 'your_password_column' with the actual column name in your database)
    $checkQuery = "SELECT s_password FROM user WHERE id = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($hashedPassword);
    $stmt->fetch();
    $stmt->close();
}
    if (password_verify($oldPassword, $hashedPassword)) {
        // Old password is correct
        if ($newPassword === $confirmPassword) {
            // Hash the new password
            $hashedNewPassword = password_hash($newPassword, PASSWORD_BCRYPT);

            // Update the user's password in the database
            $updateQuery = "UPDATE user SET s_password = ? WHERE id = ?";
            $stmt = $conn->prepare($updateQuery);
            $stmt->bind_param("si", $hashedNewPassword, $id);

            if ($stmt->execute()) {
                echo "<script>alert('Password changed successfully! Your logged out automatically after pressing the OK button'); window.location = '../../include/php/logout.php';</script>";
            } else {
                echo "<script>alert('Error updating the password:$stmt->error'); window.location = 'profile.php';</script>";
            }
        } else {
            echo "<script>alert('New passwords do not match'); window.location = 'profile.php';</script>";
        }
    } else {
        echo "<script>alert('Old password is incorrect.'); window.location = 'profile.php';</script>";
    }
}

?>

<!-- Include Header File -->
<?php include_once ('../../include/php/header.php') ?>

<!-- Include Sidebar File -->
<?php if ($curDirName == 'marketing') {
    echo include_once ('../../include/php/sidebar-marketing.php');
}elseif ($curDirName == 'creative') {
    echo include_once ('../../include/php/sidebar-creative.php');
}elseif ($curDirName == 'fabrication') {
    echo include_once ('../../include/php/sidebar-fab.php');
}  ?>


<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <!-- Content Header (Page header) -->
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
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

      <form action="" method="post" enctype="multipart/form-data">

      <!-- Main row -->
      <div class="row">

        <div class="col-md-3">
          <!-- Profile Image -->
          <div class="card card-primary card-outline">
            <div class="card-body box-profile">
              <div class="text-center">
                <?php
                  if (!empty($row_user['user_image'])) {
                     echo '<img class="profile-user-img img-fluid img-circle" src="../../uploads/profile/' . $row_user['user_image'] . '" width="150" height="150" />';
                  } else {
                      echo '<img class="profile-user-img img-fluid img-circle" src="../../uploads/profile/male-placeholder-image.jpeg"/>';
                  }
                ?>
                </div>
                  <h3 class="profile-username text-center"><?php echo $row_user['user']; ?></h3>
                  <p class="text-muted text-center"><?php echo $row_user['designation']; ?></p>
                  <div class="dropdown-divider"></div>
                  <div class="form-group">
                    <label for="user_image">Upload New Profile Image</label>
                    <input type="file" name="user_image">
                  </div>
                  
                </div>
                <!-- /.card-body -->
              </div>
              <!-- /.card -->
            </div>

            <div class="col-md-6">
          <div class="card card-primary card-outline">
              <div class="card-header">
                <h3 class="m-0">Manage Profile</h3>
              </div>
              <div class="card-body">

                <div class="form-group">
                  <label for="user" class="form-label">Name</label>
                  <input type="text" name="user" class="form-control" value="<?php echo $row_user['user']; ?>">
                </div>

                <div class="form-group">
                  <label for="username" class="form-label">User Name</label>
                  <input type="text" name="username" class="form-control" value="<?php echo $row_user['username']; ?>" readonly>
                </div>

                <div class="form-group">
                  <label for="designation" class="form-label">Designation</label>
                  <input type="text" name="designation" class="form-control" value="<?php echo $row_user['designation']; ?>">
                </div>

                <div class="form-group">
                  <label class="dummy">Password</label>
                  <div class="input-group">
                    <input type="password" name="dummy" class="form-control" value="...." readonly>
                    <span class="input-group-append">
                      <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#modal-default">Change Password</button>
                    </span>
                  </div>
                </div>

                <div class="form-group">
                  <label for="contact" class="form-label">Contact</label>
                  <input type="text" name="contact" class="form-control" value="<?php echo $row_user['contact']; ?>">
                </div>

                <div class="form-group">
                  <label for="email" class="form-label">Email</label>
                  <input type="text" name="email" class="form-control" value="<?php echo $row_user['email']; ?>">
                </div>

              </div>
              <div class="card-footer">
                <input type="submit" class="btn btn-primary" name="ok" value="Save Updates">
                <a href="index.php" class="btn btn-info">Close Without Save</a>
              </div>





            </div>
          </div>
        </div>
      </form>
    </div>
  </section>
</div>
</center>

<div class="modal fade" id="modal-default">
        <div class="modal-dialog">
          <div class="modal-content">

            <form action="" method="POST">


            <div class="modal-header">
              <h4 class="modal-title">CHANGE PASSWORD</h4>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              


        
        <div class="input-group">
            <input type="password" name="oldPassword" class="form-control" id="oldPassword" aria-describedby="toggleOldPassword" placeholder="Enter Old Password*" required>
            <span class="input-group-append">
              <button type="button" id="toggleOldPassword" class="btn btn-info" onclick="togglePasswordVisibility('oldPassword', 'toggleOldPassword')">Show</button>
            </span>
        </div>
        <br>
        <div class="dropdown-divider"></div>
        <br>
        <div class="input-group">
            <input type="password" name="newPassword" class="form-control" required id="newPassword" aria-describedby="toggleNewPassword" placeholder="Enter New Password*" required>
            <span class="input-group-append">
              <button type="button" id="toggleNewPassword" class="btn btn-info" onclick="togglePasswordVisibility('newPassword', 'toggleNewPassword')">Show</button>
            </span>
        </div>
        <br>
        <div class="dropdown-divider"></div>
        <br>
        <div class="input-group">
            <input type="password" name="confirmPassword" class="form-control" required id="confirmPassword" aria-describedby="toggleConfirmPassword" placeholder="Enter Confirm New Password*" required>
            <span class="input-group-append">
              <button type="button" id="toggleConfirmPassword" class="btn btn-info" onclick="togglePasswordVisibility('confirmPassword', 'toggleConfirmPassword')">Show</button>
            </span>
        </div>
        <br>
        <div>**Store your new password securely for your next login</div>


            </div>

            <div class="modal-footer justify-content-between">
              <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
              <input type="submit" name="changePassword" class="btn btn-success" value="Change Password">
            </div>
            </form>
          </div>
          <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
      </div>
      <!-- /.modal -->




<!-- Include Footer File -->
<?php include_once ('../../include/php/footer.php') ?>


<script>
function togglePasswordVisibility(inputFieldId, buttonId) {
    const passwordInput = document.getElementById(inputFieldId);
    const toggleButton = document.getElementById(buttonId);

    if (passwordInput.type === "password") {
        passwordInput.type = "text";
        toggleButton.innerText = "hide";
    } else {
        passwordInput.type = "password";
        toggleButton.innerText = "show";
    }
}
</script>

</body>
</html>
