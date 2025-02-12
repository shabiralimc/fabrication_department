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

// Calculate the remaining time
$sessionStart = $_SESSION['session_start'];
$sessionLifetime = $_SESSION['session_lifetime'];
$currentTime = time();
$remainingTime = ($sessionStart + $sessionLifetime) - $currentTime;

// Check if 'search' and 'godownName' are passed via POST
if (isset($_POST['search']) && isset($_POST['godownName'])) {
    $search = $_POST['search'];
    $godownName = $_POST['godownName'];
    $materials = [];

    $sql = "SELECT materialName, material_alias 
            FROM material_master_creates 
            WHERE FIND_IN_SET(?, godown) 
            AND (material_alias LIKE ? OR materialName LIKE ?)";
    
    $searchTerm = "%$search%";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $godownName, $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $materialName = $row['materialName'];
        $aliases = explode(',', $row['material_alias']);
        if (!isset($materials[$materialName])) {
            $materials[$materialName] = [];
        }
        foreach ($aliases as $alias) {
            $alias = trim($alias);
            if (!empty($alias) && !in_array($alias, $materials[$materialName])) {
                $materials[$materialName][] = $alias;
            }
        }
    }

    $response = [];
    foreach ($materials as $name => $aliases) {
        $response[] = [
            'materialName' => $name,
            'material_alias' => implode(', ', $aliases) // Combine all aliases
        ];
    }

    echo json_encode($response);
    $stmt->close();
}

?>
