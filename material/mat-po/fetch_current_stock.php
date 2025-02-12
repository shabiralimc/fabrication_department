<?php
// Include the database connection configuration
include_once("../../../../include/php/connect.php");
ini_set('session.gc_maxlifetime', 43200);

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (isset($_POST['mat_po_date']) && isset($_POST['mat_po_godown']) && isset($_POST['mat_po_item_name'])) {
    $matPoDate = $_POST['mat_po_date'];
    $matPoGodown = $_POST['mat_po_godown'];
    $matPoItemName = $_POST['mat_po_item_name'];

    // Fetch opening stock from material_master_creates
    $sql = "SELECT openingStock FROM material_master_creates WHERE materialName = ? AND godown = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $matPoItemName, $matPoGodown);
    $stmt->execute();
    $stmt->bind_result($openingStock);
    $stmt->fetch();
    $stmt->close();

    // Fetch stock data from mat_stocks
    $sql = "SELECT SUM(pq) AS total_pq, SUM(pr) AS total_pr, SUM(co) AS total_co 
            FROM mat_stocks 
            WHERE stk_date <= ? AND godown = ? AND materialName = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $matPoDate, $matPoGodown, $matPoItemName);
    $stmt->execute();
    $stmt->bind_result($totalPq, $totalPr, $totalCo);
    $stmt->fetch();
    $stmt->close();

    // Calculate current stock
    $currentStock = ($openingStock + ($totalPq ?? 0) - ($totalPr ?? 0) - ($totalCo ?? 0));

    // Return the current stock as JSON
    echo json_encode(['currentStock' => $currentStock]);
} else {
    echo json_encode(['currentStock' => 0]);
}
?>