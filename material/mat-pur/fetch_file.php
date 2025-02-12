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

// Check if ID is set
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Fetch the file path from the database based on the ID
    $stmt = $conn->prepare("SELECT file_path FROM mat_purs WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Assuming the file_path is stored relative to the uploaded_files folder
        $filePath = 'uploaded_files/' . $row['file_path']; // Adjust the path accordingly

        // Check if the file exists
        if (file_exists($filePath)) {
            // Get the file extension
            $fileExt = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            $fileName = $row['file_path']; // Get the filename from the database

            // Return both the file extension and filename as a JSON response
            echo json_encode([
                'fileExt' => $fileExt,
                'fileName' => $fileName
            ]);
        } else {
            echo json_encode([
                'error' => "file_not_found"
            ]);
        }
    } else {
        echo json_encode([
            'error' => "no_file_associated"
        ]);
    }

    $stmt->close();
} else {
    echo json_encode([
        'error' => "no_id_provided"
    ]);
}
?>
