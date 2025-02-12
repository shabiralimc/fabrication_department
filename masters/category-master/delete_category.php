<?php
include_once("../../../../include/php/connect.php");

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete_category"])) {
    $category_id = $_POST["category_id"];

    // Prepare and execute the delete query
    $sql_delete = "DELETE FROM master_category WHERE category_id=?";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("i", $category_id);

    if ($stmt_delete->execute()) {
        // If deletion is successful, return success status as JSON
        echo json_encode(array("status" => "success"));
    } else {
        // If deletion fails, return error status with message as JSON
        echo json_encode(array("status" => "error", "message" => $stmt_delete->error));
    }
    exit; // Terminate the script
}
?>