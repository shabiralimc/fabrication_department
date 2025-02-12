<?php
include_once('../../../../include/php/connect.php');
ini_set('session.gc_maxlifetime', 43200);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'] ?? '';
    $materialID = $_POST['materialID'] ?? '';

    if (!$date || !$materialID) {
        echo json_encode(['success' => false, 'error' => 'Invalid parameters.']);
        exit;
    }

    // Fetch the current proposed_rate_ary
    $query = "SELECT proposed_rate_ary FROM mat_selling_price WHERE materialId = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $materialID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $proposedRates = json_decode($row['proposed_rate_ary'], true); // Decode the JSON

        // Remove the rate with the specified date
        $proposedRates = array_filter($proposedRates, function($rate) use ($date) {
            return $rate['dt'] !== $date; // Keep all rates except the one with the matching date
        });

        // Update the proposed_rate_ary in the database
        $updatedRates = json_encode(array_values($proposedRates));
        $updateQuery = "UPDATE mat_selling_price SET proposed_rate_ary = ? WHERE materialId = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param("ss", $updatedRates, $materialID);

        if ($updateStmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to update selling prices.']);
        }

        $updateStmt->close();
    } else {
        echo json_encode(['success' => false, 'error' => 'No selling prices found for this material.']);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
}
?>