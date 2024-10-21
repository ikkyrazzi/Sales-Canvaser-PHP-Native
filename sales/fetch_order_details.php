<?php
include('../db.php'); // Include your database connection

if (isset($_GET['order_id'])) {
    $order_id = intval($_GET['order_id']);
    $query = "
        SELECT 
            orders.order_number, 
            orders.order_date, 
            orders.status, 
            orders.total_amount, 
            customers.customer_name
        FROM 
            orders 
        JOIN 
            customers ON orders.customer_id = customers.id
        WHERE 
            orders.id = $order_id
    ";
    $result = $conn->query($query);

    if ($result && $result->num_rows > 0) {
        $order = $result->fetch_assoc();
        echo "<h5>Order Number: " . htmlspecialchars($order['order_number']) . "</h5>";
        echo "<p>Customer Name: " . htmlspecialchars($order['customer_name']) . "</p>";
        echo "<p>Order Date: " . htmlspecialchars($order['order_date']) . "</p>";
        echo "<p>Status: " . htmlspecialchars($order['status']) . "</p>";
        echo "<p>Total Amount: Rp " . number_format($order['total_amount'], 2, ',', '.') . "</p>";
    } else {
        echo "<p>Order details not found.</p>";
    }
} else {
    echo "<p>Invalid order ID.</p>";
}
?>
