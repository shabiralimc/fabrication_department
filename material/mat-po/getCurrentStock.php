<?php
include_once("../../../../include/php/connect.php");

$response = ['current_stock' => 0];

if (isset($_POST['materialName'], $_POST['godown'], $_POST['poDate'])) {
    $materialName = $_POST['materialName'];
    $godown = $_POST['godown'];
    $poDate = $_POST['poDate'];

    // 1. Fetch Opening Stock from material_master_creates
    $openingStock = 0;
    $query = "SELECT openingstock_ary FROM material_master_creates WHERE materialName = ?";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("s", $materialName);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $openingStockArray = json_decode($row['openingstock_ary'], true);
            // Filter entries by godown and fos <= poDate
            $filteredEntries = array_filter($openingStockArray, function($entry) use ($godown, $poDate) {
                return ($entry['gd'] === $godown && strtotime($entry['fos']) <= strtotime($poDate));
            });
            if (!empty($filteredEntries)) {
                // Sort by fos descending to get the latest entry
                usort($filteredEntries, function($a, $b) {
                    return strtotime($b['fos']) - strtotime($a['fos']);
                });
                $openingStock = (float)$filteredEntries[0]['ops'];
            }
        }
        $stmt->close();
    } else {
        error_log("Error in prepare() for opening stock: " . $conn->error);
    }

    // 2. Fetch PQ, PR, CO from mat_stocks
    $totalPq = 0;
    $totalPr = 0;
    $totalCo = 0;

    $query = "SELECT mat_stock_ary FROM mat_stocks WHERE stk_date <= ?";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("s", $poDate);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $matStockArray = json_decode($row['mat_stock_ary'], true);
            foreach ($matStockArray as $entry) {
                if ($entry['mn'] === $materialName && $entry['gd'] === $godown) {
                    $totalPq += (float)$entry['pq'];
                    $totalPr += (float)$entry['pr'];
                    $totalCo += (float)$entry['co'];
                }
            }
        }
        $stmt->close();
    } else {
        error_log("Error in prepare() for mat_stocks: " . $conn->error);
    }

    // 3. Calculate Current Stock
    $currentStock = $openingStock + $totalPq - $totalPr - $totalCo;
    $response['current_stock'] = $currentStock;
}

echo json_encode($response);
?>