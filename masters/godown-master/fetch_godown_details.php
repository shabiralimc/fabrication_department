<?php
include_once("../../../../include/php/connect.php");

// Check if godown_id is provided in the request
if (isset($_GET['godown_id'])) {
    $godownId = intval($_GET['godown_id']);

    // Prepare and execute SQL query to fetch godown details based on godown_id
    $sql = "SELECT * FROM master_godown WHERE godown_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $godownId);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if any rows are returned
    if ($result->num_rows > 0) {
        // Fetch the godown details
        $row = $result->fetch_assoc();
        
        // Return the godown details as JSON
        header('Content-Type: application/json');
        echo json_encode($row);
    } else {
        // No godown found with the given godown_id
        echo json_encode(array('error' => 'Godown not found'));
    }

    // Close the statement and connection
    $stmt->close();
} else {
    // No godown_id provided in the request
    echo json_encode(array('error' => 'Godown ID not provided'));
}
?>
