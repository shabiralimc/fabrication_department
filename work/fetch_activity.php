
<?php
include_once('../../../include/php/connect.php');

// Query to fetch activity names from the database
$query = "SELECT * FROM activity_master ORDER BY activity_name ASC";
$result = $conn->query($query);

// Initialize an empty array to store activity names
$activityNames = array();

// Check if the query was successful
if ($result->num_rows > 0) {
    // Fetch each row from the result set
    while ($row = $result->fetch_assoc()) {
        // Push activity data to the array
        $activityNames[] = $row['activity_name'];
    }
}

// Output activity names as JSON
echo json_encode($activityNames);
?>