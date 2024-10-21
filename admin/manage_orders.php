<?php
// Start session and ensure user is an admin
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Connect to the database
include('../db.php');

// Handle order status update
if (isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];

    // Update the order status
    $sql = "UPDATE orders SET status='$status' WHERE id='$order_id'";
    if ($conn->query($sql)) {
        $message = "Order status updated successfully.";
        
        // Check if status is 'completed' and update receipt URL if needed
        if ($status == 'completed') {
            // Fetch customer name for the order
            $customer_query = "SELECT c.customer_name FROM customers c JOIN orders o ON c.id = o.customer_id WHERE o.id='$order_id'";
            $customer_result = $conn->query($customer_query);
            $customer_name = "";
            if ($customer_result && $customer_result->num_rows > 0) {
                $customer_row = $customer_result->fetch_assoc();
                $customer_name = $customer_row['customer_name'];
            }

            // Create receipt content
            $receipt_content = "<html>
            <head><style>
                body { font-family: Arial, sans-serif; margin: 0; padding: 0; }
                .receipt { width: 300px; margin: 0 auto; padding: 20px; border: 1px solid #ccc; border-radius: 5px; }
                .receipt-header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
                .receipt-header h1 { margin: 0; font-size: 18px; }
                .receipt-details { margin-bottom: 20px; }
                .receipt-details p { margin: 5px 0; font-size: 14px; }
                .receipt-footer { text-align: center; margin-top: 20px; border-top: 1px solid #ccc; padding-top: 10px; font-size: 12px; }
                .receipt-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                .receipt-table th, .receipt-table td { padding: 8px; border: 1px solid #ccc; }
                .receipt-table th { background-color: #f2f2f2; }
                .receipt-table td { text-align: left; }
            </style></head>
            <body>
                <div class='receipt'>
                    <div class='receipt-header'>
                        <h1>Receipt</h1>
                    </div>
                    <div class='receipt-details'>
                        <p><strong>Order Number:</strong> {$_POST['order_number']}</p>
                        <p><strong>Customer Name:</strong> $customer_name</p>
                        <p><strong>Status:</strong> Completed</p>
                        <p><strong>Total Amount:</strong> {$_POST['total_amount']}</p>
                        <p><strong>Date:</strong> {$_POST['order_date']}</p>
                    </div>
                    <div class='receipt-footer'>
                        <p>Thank you for your purchase!</p>
                    </div>
                </div>
            </body>
            </html>";

            $receipt_filename = "receipts/receipt_$order_id.html";
            file_put_contents($receipt_filename, $receipt_content);

            $receipt_url = "receipts/receipt_$order_id.html";
            $sql = "UPDATE orders SET receipt_url='$receipt_url' WHERE id='$order_id'";
            $conn->query($sql);
        }
    } else {
        $message = "Error updating order status: " . $conn->error;
    }
}

// Search functionality
$search_query = "";
if (isset($_GET['search'])) {
    $search_term = $_GET['search_term'];
    $search_query = " WHERE order_number LIKE '%$search_term%' OR 
                            customer_id IN (SELECT id FROM customers WHERE customer_name LIKE '%$search_term%') OR
                            status LIKE '%$search_term%'";
}

// Fetch all orders
$orders_query = "SELECT o.id, o.order_number, o.order_date, o.status, 
                        c.customer_name, o.total_amount, o.receipt_url
                        FROM orders o 
                        JOIN customers c ON o.customer_id = c.id" . $search_query;

$orders_result = $conn->query($orders_query);
$orders = [];
if ($orders_result && $orders_result->num_rows > 0) {
    while ($order_row = $orders_result->fetch_assoc()) {
        $orders[] = $order_row;
    }
}

// Include header and sidebar
include('../includes/admin/header.php');
include('../includes/admin/sidebar.php');
?>

<!-- Content -->
<div class="container-fluid">
    <!-- Page Heading -->
    <h1 class="h3 mb-4 text-gray-800">Manage Orders</h1>

    <?php if (isset($message)): ?>
        <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <!-- Search Form -->
    <form method="GET" action="manage_orders.php" class="mb-4">
        <div class="form-group">
            <input type="text" class="form-control" name="search_term" placeholder="Search by Order Number, Customer Name, or Status" value="<?php echo isset($_GET['search_term']) ? htmlspecialchars($_GET['search_term']) : ''; ?>">
        </div>
        <button type="submit" class="btn btn-primary" name="search">Search</button>
    </form>

    <!-- Orders Table -->
    <div class="card">
        <div class="card-header">
            Orders List
        </div>
        <div class="card-body">
            <table class="table table-striped table-bordered" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Order Number</th>
                        <th>Customer Name</th>
                        <th>Order Date</th>
                        <th>Status</th>
                        <th>Total Amount</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; ?>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                            <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                            <td><?php echo htmlspecialchars($order['order_date']); ?></td>
                            <td><?php echo htmlspecialchars($order['status']); ?></td>
                            <td><?php echo htmlspecialchars($order['total_amount']); ?></td>
                            <td>
                                <!-- Change Status Button -->
                                <a href="#" class="btn btn-warning btn-sm" data-toggle="modal" data-target="#changeStatusModal<?php echo $order['id']; ?>">Change Status</a>
                            </td>
                        </tr>

                        <!-- Change Status Modal -->
                        <div class="modal fade" id="changeStatusModal<?php echo $order['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="changeStatusModalLabel" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="changeStatusModalLabel">Change Order Status</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <form method="POST" action="manage_orders.php">
                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                            <input type="hidden" name="order_number" value="<?php echo $order['order_number']; ?>">
                                            <input type="hidden" name="total_amount" value="<?php echo $order['total_amount']; ?>">
                                            <input type="hidden" name="order_date" value="<?php echo $order['order_date']; ?>">
                                            <div class="form-group">
                                                <label for="status">Status</label>
                                                <select class="form-control" id="status" name="status" required>
                                                    <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="completed" <?php echo $order['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                    <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                </select>
                                            </div>
                                            <button type="submit" class="btn btn-primary" name="update_status">Update Status</button>
                                        </form>
                                    </div>
                                    <div class="modal-footer">
                                        <?php if ($order['status'] == 'completed' && !empty($order['receipt_url'])): ?>
                                            <a href="<?php echo htmlspecialchars($order['receipt_url']); ?>" class="btn btn-info" target="_blank">View Receipt</a>
                                        <?php endif; ?>
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Include footer -->
<?php include('../includes/admin/footer.php'); ?>
