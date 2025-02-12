<?php
include_once('../../../include/php/connect.php');

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["staffName"])) {
    $staffName = $_POST["staffName"];

    // Fetch the salary from the database
    $salary = fetchSalaryFromDatabase($staffName);

    // Return the salary data as JSON
    echo json_encode(["success" => true, "salary" => $salary]);
} else {
    // Return an error response if the request method is not POST or staffName is not set
    echo json_encode(["success" => false, "error" => "Invalid request"]);
}

function fetchSalaryFromDatabase($staffName) {
    global $conn;

    // Prepare and execute SQL query to fetch the salary based on staff name
    $sql = "SELECT salary FROM staffs_masters WHERE staff_name = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $staffName);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if a row was found
    if ($result->num_rows > 0) {
        // Fetch salary from the first row
        $row = $result->fetch_assoc();
        return $row['salary'];
    } else {
        // If no row found, return 0 or handle the situation accordingly
        return 0;
    }
}
?>
