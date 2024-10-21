<?php
include('../db.php');

if (isset($_GET['id'])) {
    $product_id = $_GET['id'];
    $stmt = $conn->prepare("SELECT price FROM products WHERE id=?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();

    if ($product) {
        echo $product['price'];
    } else {
        echo "0";
    }
}
?>
