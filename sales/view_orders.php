<?php
// Start session and ensure user is logged in as a sales representative
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'sales') {
    header("Location: login.php");
    exit();
}

// Connect to the database
include('../db.php');

// Handle creating a new order
if (isset($_POST['create_order'])) {
    $customer_id = $_POST['customer_id'];
    $order_number = uniqid('ORD_'); // Generate a unique order number
    $status = 'pending'; // Default status

    // Validate input
    if (is_numeric($customer_id)) {
        // Insert order into orders table
        $stmt = $conn->prepare("INSERT INTO orders (order_number, user_id, customer_id, total_amount, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("siiis", $order_number, $_SESSION['user_id'], $customer_id, $_POST['total_amount'], $status);

        if ($stmt->execute()) {
            $order_id = $conn->insert_id; // Get the last inserted order ID

            // Insert order items
            $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)");
            foreach ($_POST['products'] as $product) {
                $product_id = $product['product_id'];
                $quantity = $product['quantity'];

                // Validate product_id and quantity
                if (is_numeric($product_id) && is_numeric($quantity)) {
                    // Fetch product price
                    $product_query = "SELECT price FROM products WHERE id=?";
                    $product_stmt = $conn->prepare($product_query);
                    $product_stmt->bind_param("i", $product_id);
                    $product_stmt->execute();
                    $product_result = $product_stmt->get_result();
                    $product_data = $product_result->fetch_assoc();

                    $unit_price = isset($product_data['price']) ? floatval($product_data['price']) : 0;
                    $total_price = $unit_price * intval($quantity);

                    $stmt->bind_param("iiidd", $order_id, $product_id, $quantity, $unit_price, $total_price);
                    $stmt->execute();
                }
            }

            // Update total_sales in sales_report
            $total_amount = $_POST['total_amount'];
            $stmt = $conn->prepare("INSERT INTO sales_report (order_id, sales_id, total_sales) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE total_sales = total_sales + ?");
            $stmt->bind_param("iidd", $order_id, $_SESSION['user_id'], $total_amount, $total_amount);
            $stmt->execute();

            $message = "Order created successfully.";
        } else {
            $message = "Error creating order: " . $conn->error;
        }
    } else {
        $message = "Invalid customer ID.";
    }
}

// Handle updating or canceling an order
if (isset($_POST['update_order'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];

    // Validate order_id and status
    if (is_numeric($order_id) && in_array($status, ['pending', 'completed', 'canceled'])) {
        $stmt = $conn->prepare("UPDATE orders SET status=? WHERE id=?");
        $stmt->bind_param("si", $status, $order_id);

        if ($stmt->execute()) {
            $message = "Order updated successfully.";
        } else {
            $message = "Error updating order: " . $conn->error;
        }
    } else {
        $message = "Invalid order status or ID.";
    }
}

// Fetch all orders
$orders_query = "SELECT orders.id, orders.order_number, customers.customer_name, orders.order_date, orders.status, orders.total_amount
                 FROM orders
                 JOIN customers ON orders.customer_id = customers.id
                 WHERE orders.user_id = ?
                 ORDER BY orders.order_date DESC";
$orders_stmt = $conn->prepare($orders_query);
$orders_stmt->bind_param("i", $_SESSION['user_id']);
$orders_stmt->execute();
$orders_result = $orders_stmt->get_result();

$orders = [];
if ($orders_result && $orders_result->num_rows > 0) {
    while ($order_row = $orders_result->fetch_assoc()) {
        $orders[] = $order_row;
    }
}

// Fetch order details if modal is triggered
$order_details = null;
if (isset($_GET['details']) && is_numeric($_GET['details'])) {
    $order_id = $_GET['details'];
    $details_query = "SELECT orders.*, customers.customer_name, order_items.*, products.product_name
                      FROM orders
                      JOIN customers ON orders.customer_id = customers.id
                      JOIN order_items ON orders.id = order_items.order_id
                      JOIN products ON order_items.product_id = products.id
                      WHERE orders.id=?";
    $details_stmt = $conn->prepare($details_query);
    $details_stmt->bind_param("i", $order_id);
    $details_stmt->execute();
    $details_result = $details_stmt->get_result();

    if ($details_result && $details_result->num_rows > 0) {
        $order_details = $details_result->fetch_all(MYSQLI_ASSOC);
    }
}

// Include header and sidebar
include('../includes/sales/header.php');
include('../includes/sales/sidebar.php');
?>

<!-- Content -->
<div class="container-fluid">
    <!-- Page Heading -->
    <h1 class="h3 mb-4 text-gray-800">Manage Orders</h1>

    <?php if (isset($message)): ?>
        <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <!-- Create Order Button -->
    <button class="btn btn-primary mb-3" data-toggle="modal" data-target="#createOrderModal">Create New Order</button>

    <!-- Create Order Modal -->
    <div class="modal fade" id="createOrderModal" tabindex="-1" role="dialog" aria-labelledby="createOrderModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createOrderModalLabel">Create New Order</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="view_orders.php" id="createOrderForm">
                        <div class="form-group">
                            <label for="customer_id">Customer</label>
                            <select class="form-control" id="customer_id" name="customer_id" required>
                                <?php
                                // Fetch customers for the dropdown
                                $customer_query = "SELECT id, customer_name FROM customers";
                                $customer_result = $conn->query($customer_query);
                                while ($customer = $customer_result->fetch_assoc()): ?>
                                    <option value="<?php echo htmlspecialchars($customer['id']); ?>"><?php echo htmlspecialchars($customer['customer_name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="products">Products</label>
                            <div id="product-list">
                                <!-- Repeatable Product Selection -->
                                <div class="form-group product-row">
                                    <select class="form-control product-select" name="products[0][product_id]" required>
                                        <?php
                                        // Fetch products for the dropdown
                                        $product_query = "SELECT id, product_name, price FROM products";
                                        $product_result = $conn->query($product_query);
                                        while ($product = $product_result->fetch_assoc()): ?>
                                            <option value="<?php echo htmlspecialchars($product['id']); ?>" data-price="<?php echo htmlspecialchars($product['price']); ?>">
                                                <?php echo htmlspecialchars($product['product_name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                    <input type="number" class="form-control mt-2 quantity-input" name="products[0][quantity]" placeholder="Quantity" required>
                                </div>
                            </div>
                            <button type="button" class="btn btn-secondary mt-3" id="add-product-btn">Add Another Product</button>
                        </div>
                        <div class="form-group">
                            <label for="total_amount">Total Amount</label>
                            <input type="number" class="form-control" id="total_amount" name="total_amount" readonly>
                        </div>
                        <button type="submit" class="btn btn-primary" name="create_order">Create Order</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="table-responsive">
        <table class="table table-striped table-bordered" width="100%" cellspacing="0">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Order Number</th>
                    <th>Customer Name</th>
                    <th>Order Date</th>
                    <th>Status</th>
                    <th>Total Amount</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; // Initialize row number ?>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                        <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                        <td><?php echo htmlspecialchars($order['order_date']); ?></td>
                        <td><?php echo htmlspecialchars($order['status']); ?></td>
                        <td><?php echo number_format($order['total_amount'], 2); ?></td>
                        <td>
                            <a href="view_orders.php?details=<?php echo htmlspecialchars($order['id']); ?>" class="btn btn-info btn-sm">View</a>
                            <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#updateOrderModal" data-order-id="<?php echo htmlspecialchars($order['id']); ?>" data-status="<?php echo htmlspecialchars($order['status']); ?>">Update</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Update Order Modal -->
<div class="modal fade" id="updateOrderModal" tabindex="-1" role="dialog" aria-labelledby="updateOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateOrderModalLabel">Update Order Status</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" action="view_orders.php">
                    <input type="hidden" id="update_order_id" name="order_id">
                    <div class="form-group">
                        <label for="order_status">Order Status</label>
                        <select class="form-control" id="order_status" name="status" required>
                            <option value="pending">Pending</option>
                            <option value="completed">Completed</option>
                            <option value="canceled">Canceled</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" name="update_order">Update Order</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Include footer -->
<?php include('../includes/sales/footer.php'); ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Add event listener to Update Order button
    $('#updateOrderModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var orderId = button.data('order-id');
        var status = button.data('status');

        var modal = $(this);
        modal.find('#update_order_id').val(orderId);
        modal.find('#order_status').val(status);
    });

    // Add event listener to product selection
    $('#product-list').on('change', '.product-select', function () {
        var totalAmount = 0;
        $('#product-list .product-row').each(function () {
            var $row = $(this);
            var quantity = $row.find('.quantity-input').val();
            var price = $row.find('.product-select option:selected').data('price');
            totalAmount += (quantity * price);
        });
        $('#total_amount').val(totalAmount.toFixed(2));
    });

    // Add event listener to quantity input
    $('#product-list').on('input', '.quantity-input', function () {
        var totalAmount = 0;
        $('#product-list .product-row').each(function () {
            var $row = $(this);
            var quantity = $row.find('.quantity-input').val();
            var price = $row.find('.product-select option:selected').data('price');
            totalAmount += (quantity * price);
        });
        $('#total_amount').val(totalAmount.toFixed(2));
    });

    // Add new product row
    $('#add-product-btn').on('click', function () {
        var rowCount = $('#product-list .product-row').length;
        var newRow = `
            <div class="form-group product-row">
                <select class="form-control product-select" name="products[${rowCount}][product_id]" required>
                    <?php
                    // Fetch products for the dropdown
                    $product_query = "SELECT id, product_name, price FROM products";
                    $product_result = $conn->query($product_query);
                    while ($product = $product_result->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($product['id']); ?>" data-price="<?php echo htmlspecialchars($product['price']); ?>">
                            <?php echo htmlspecialchars($product['product_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <input type="number" class="form-control mt-2 quantity-input" name="products[${rowCount}][quantity]" placeholder="Quantity" required>
            </div>
        `;
        $('#product-list').append(newRow);
    });
});
</script>
