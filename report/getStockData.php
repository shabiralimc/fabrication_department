<?php
header('Content-Type: application/json');
include_once('../../../include/php/connect.php');

ini_set('session.gc_maxlifetime', 43200);
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user']) || $_SESSION['role'] !== '4') {
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

if (!isset($_GET['date'])) {
    echo json_encode(['error' => 'Date parameter is missing']);
    exit();
}

$selectedDate = $_GET['date'];
$response = [];
$materialData = [];

// Step 1: Fetch all materials and their `openingstock_ary`
$materialsQuery = "SELECT materialName, openingstock_ary FROM material_master_creates";
$materialsResult = $conn->query($materialsQuery);
$materials = [];

while ($row = $materialsResult->fetch_assoc()) {
    $materialName = $row['materialName'];

    // Ensure JSON is valid and decode it properly
    $openingStockAry = json_decode($row['openingstock_ary'], true);
    if (!is_array($openingStockAry)) {
        $openingStockAry = []; // Default to empty array to prevent errors
    }

    $materials[$materialName] = [
        'openingStockAry' => $openingStockAry
    ];
}

// Step 2: Fetch stock transactions until `selectedDate`
$stockQuery = "SELECT stk_date, mat_stock_ary FROM mat_stocks WHERE stk_date <= ?";
$stockStmt = $conn->prepare($stockQuery);
$stockStmt->bind_param("s", $selectedDate);
$stockStmt->execute();
$stockResult = $stockStmt->get_result();

while ($stockRow = $stockResult->fetch_assoc()) {
    $stkDate = $stockRow['stk_date'];
    $matStockAry = json_decode($stockRow['mat_stock_ary'], true);
    
    if (!is_array($matStockAry)) {
        continue; // Skip invalid JSON data
    }

    foreach ($matStockAry as $stock) {
        $materialName = $stock['mn'];
        $godown = $stock['gd'] ?? '';
        $purchaseQty = $stock['pq'] ?? 0;
        $consumption = $stock['co'] ?? 0;
        $returnQty = $stock['pr'] ?? 0;

        // Check if material exists in `$materials`
        if (!isset($materials[$materialName])) {
            continue; // Skip if material is not found in `$materials`
        }

        // Get opening stock (`ops`) and `from_os` (`fos`) from `openingstock_ary`
        $openingStockOps = 0;
        $fromOsFos = '';
        foreach ($materials[$materialName]['openingStockAry'] as $stockItem) {
            if ($stockItem['gd'] === $godown) {
                $openingStockOps = $stockItem['ops'] ?? 0;
                $fromOsFos = $stockItem['fos'] ?? '';
                break;
            }
        }

        if (!isset($materialData[$materialName][$godown])) {
            $materialData[$materialName][$godown] = [
                'openingStock' => $openingStockOps, // ops from `openingstock_ary`
                'from_os' => $fromOsFos,
                'totalPurchase' => 0,
                'totalConsumption' => 0,
                'totalReturn' => 0
            ];
        }

        // Aggregate data
        $materialData[$materialName][$godown]['totalPurchase'] += $purchaseQty;
        $materialData[$materialName][$godown]['totalConsumption'] += $consumption;
        $materialData[$materialName][$godown]['totalReturn'] += $returnQty;
    }
}

// Step 3: Prepare JSON response
foreach ($materialData as $materialName => $godownData) {
    foreach ($godownData as $godown => $data) {
        $currentStock = $data['openingStock'] + $data['totalPurchase'] - $data['totalConsumption'] - $data['totalReturn'];
        
        $response[] = [
            'mn' => $materialName,
            'gd' => $godown,
            'openingStock' => $data['openingStock'],  // ops from `openingstock_ary`
            'from_os' => $data['from_os'],  // fos from `openingstock_ary`
            'totalPurchase' => $data['totalPurchase'],
            'totalConsumption' => $data['totalConsumption'],
            'totalReturn' => $data['totalReturn'],
            'currentStock' => round($currentStock, 2)
        ];
    }
}

echo json_encode($response);
?>
