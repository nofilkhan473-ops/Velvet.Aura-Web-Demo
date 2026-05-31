<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$query = isset($_GET['q']) ? sanitize($_GET['q']) : '';

if (strlen($query) < 2) {
    echo json_encode(['results' => []]);
    exit();
}

$sql = "SELECT id, name, price, image FROM products 
        WHERE (name LIKE '%$query%' OR description LIKE '%$query%') 
        AND in_stock = 1 
        LIMIT 10";
$result = mysqli_query($conn, $sql);
$products = mysqli_fetch_all($result, MYSQLI_ASSOC);

echo json_encode(['results' => $products]);
?>
