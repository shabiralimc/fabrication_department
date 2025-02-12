<?php
// Include the database connection file
include_once("../../../../include/php/connect.php");

// Initialize $rows variable
$rows = array();

// Check if category_id is provided in the request
if (isset($_GET['category_id'])) {
    $categoryId = $_GET['category_id'];

    // Prepare and execute SQL query to fetch category details based on category_id
    $sql = "SELECT * FROM master_category WHERE category_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $categoryId);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if any rows are returned
    if ($result->num_rows > 0) {
        // Fetch the category details
        $rows = $result->fetch_assoc();
        
        // Return the category details as JSON
        header('Content-Type: application/json');
        echo json_encode($rows);
    } else {
        // No category found with the given category_id
        echo json_encode(array('error' => 'Category not found'));
    }
} else {
    // No category_id provided in the request
    echo json_encode(array('error' => 'Category ID not provided'));
}

// Close the database connection
$stmt->close();
?>
