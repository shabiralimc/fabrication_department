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

if (isset($_POST['mat_pur_number'])) {
    $mat_pur_number = $_POST['mat_pur_number'];

    $stmt = $conn->prepare("SELECT mat_pur_godown FROM mat_purs WHERE mat_pur_number = ?");
    $stmt->bind_param("s", $mat_pur_number);

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $response = array(
                'success' => true,
                'mat_pur_godowns' => $row['mat_pur_godown']
            );
        } else {
            $response = array(
                'success' => false,
                'message' => 'No godown found for the selected PO number.'
            );
        }
        $stmt->close();
    } else {
        $response = array(
            'success' => false,
            'message' => 'Database query failed.'
        );
    }
} else {
    $response = array(
        'success' => false,
        'message' => 'Invalid request.'
    );
}

echo json_encode($response);
?>
