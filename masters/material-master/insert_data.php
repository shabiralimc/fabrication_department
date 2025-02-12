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


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the data from the POST request
    $materialID = $_POST['materialID'];
    $materialName = $_POST['materialName'];
    $proposedRate = $_POST['proposedRate'];
    $applicableFrom = $_POST['applicableFrom'];

    // Validate required fields
    if (!empty($materialID) && !empty($proposedRate) && !empty($applicableFrom)) {
        // Prepare the SQL query to check for existing records
        $checkSql = "SELECT proposed_rate_ary FROM mat_selling_price WHERE materialId = ?";
        $checkStmt = $conn->prepare($checkSql);
        if (!$checkStmt) {
            die("Error preparing check statement: " . $conn->error);
        }
        $checkStmt->bind_param("s", $materialID);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        // Prepare the data array
        $dataArray = array(
            "dt" => $applicableFrom,
            "sp" => $proposedRate
        );

        if ($checkResult->num_rows > 0) {
            // If a record exists, update the existing record
            $row = $checkResult->fetch_assoc();
            $existingData = json_decode($row['proposed_rate_ary'], true);

            // Check if the applicable date already exists
            $dateExists = false;
            foreach ($existingData as &$existingEntry) {
                if ($existingEntry['dt'] === $applicableFrom) {
                    $existingEntry['sp'] = $proposedRate; // Update the existing rate
                    $dateExists = true;
                    break;
                }
            }

            // If the date does not exist, add a new entry
            if (!$dateExists) {
                $existingData[] = $dataArray;
            }

            // Update the existing record
            $updatedJson = json_encode($existingData);
            $updateSql = "UPDATE mat_selling_price SET proposed_rate_ary = ? WHERE materialId = ?";
            $updateStmt = $conn->prepare($updateSql);
            if (!$updateStmt) {
                die("Error preparing update statement: " . $conn->error);
            }
            $updateStmt->bind_param("ss", $updatedJson, $materialID);
            if ($updateStmt->execute()) {
                echo json_encode(array('success' => true, 'message' => 'Proposed rate updated successfully.'));
            } else {
                echo json_encode(array('success' => false, 'message' => 'Failed to update the proposed rate. Error: ' . $conn->error));
            }
        } else {
            // If no record exists, create a new one
            $newJson = json_encode(array($dataArray));
            $insertSql = "INSERT INTO mat_selling_price (materialId,materialName, proposed_rate_ary) VALUES (?, ?, ?)";
            $insertStmt = $conn->prepare($insertSql);
            if (!$insertStmt) {
                die("Error preparing insert statement: " . $conn->error);
            }
            $insertStmt->bind_param("sss", $materialID,$materialName, $newJson);
            if ($insertStmt->execute()) {
                echo json_encode(array('success' => true, 'message' => 'Proposed rate saved successfully.'));
            } else {
                echo json_encode(array('success' => false, 'message' => 'Failed to save the proposed rate. Error: ' . $conn->error));
            }
        }
    } else {
        echo json_encode(array('success' => false, 'message' => 'All fields are required.'));
    }
} else {
    echo json_encode(array('success' => false, 'message' => 'Invalid request method.'));
}
?>  