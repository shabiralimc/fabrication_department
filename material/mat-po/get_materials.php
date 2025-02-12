<?php
// Include the database connection configuration
include_once("../../../../include/php/connect.php");
ini_set('session.gc_maxlifetime', 43200);

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user']) || $_SESSION['role'] !== '4') {
    echo "<script>alert('You are not authorised to view the URL - Please login using your username and password before accessing URL...'); window.location = '$app_url';</script>";
    exit();
}

if (isset($_POST['search']) && isset($_POST['godownName'])) {
    $search = $_POST['search'];
    $godownName = $_POST['godownName'];
    $response = [];

    $sql = "SELECT materialName, material_alias 
            FROM material_master_creates 
            WHERE FIND_IN_SET(?, godown) 
            AND (materialName LIKE ? OR material_alias LIKE ?)";
    
    $searchTerm = "%$search%";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $godownName, $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();

    // Use an associative array to group aliases by materialName
    $materials = [];
    while ($row = $result->fetch_assoc()) {
        $materialName = $row['materialName'];
        $aliases = explode(',', $row['material_alias']);

        if (!isset($materials[$materialName])) {
            $materials[$materialName] = ['materialName' => $materialName, 'aliases' => []];
        }

        foreach ($aliases as $alias) {
            $alias = trim($alias);
            if (!empty($alias) && !in_array($alias, $materials[$materialName]['aliases'])) {
                $materials[$materialName]['aliases'][] = $alias;
            }
        }
    }

    // Prepare the final response
    foreach ($materials as $material) {
        $response[] = [
            'materialName' => $material['materialName'],
            'material_alias' => implode(', ', $material['aliases'])
        ];
    }

    echo json_encode($response);
    $stmt->close();
}
?>
