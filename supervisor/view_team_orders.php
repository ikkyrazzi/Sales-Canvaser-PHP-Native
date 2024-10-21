<?php
// Start session and ensure user is logged in as a supervisor
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'supervisor') {
    header("Location: login.php");
    exit();
}

// Connect to the database
include('../db.php');

// Fetch all sales representatives
$sales_query = "SELECT id FROM users WHERE role='sales'";
$sales_result = $conn->query($sales_query);
$sales_ids = [];
if ($sales_result && $sales_result->num_rows > 0) {
    while ($row = $sales_result->fetch_assoc()) {
        $sales_ids[] = $row['id'];
    }
}

// Handle searching for orders
$search_term = '';
if (isset($_GET['search'])) {
    $search_term = $_GET['search'];
}

// Build the SQL query for orders
$orders_query = "
    SELECT o.id, o.order_number, o.order_date, o.status, o.total_amount, c.customer_name, u.name AS sales_rep
    FROM orders o
    JOIN customers c ON o.customer_id = c.id
    JOIN users u ON o.user_id = u.id
    WHERE o.user_id IN (" . implode(',', $sales_ids) . ")
";
if ($search_term) {
    $search_term = $conn->real_escape_string($search_term);
    $orders_query .= " AND (o.order_number LIKE '%$search_term%' OR c.customer_name LIKE '%$search_term%' OR o.status LIKE '%$search_term%')";
}
$orders_query .= " ORDER BY o.order_date DESC";

$orders_result = $conn->query($orders_query);

// Handle updating order status
if (isset($_POST['update_order'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['status'];

    $update_query = "UPDATE orders SET status='$new_status' WHERE id='$order_id'";
    if ($conn->query($update_query)) {
        $message = "Order status updated successfully.";
    } else {
        $message = "Error updating order status: " . $conn->error;
    }
}

// Include header and sidebar
include('../includes/supervisor/header.php');
include('../includes/supervisor/sidebar.php');
?>

<!-- Content -->
<div class="container-fluid">
    <!-- Page Heading -->
    <h1 class="h3 mb-4 text-gray-800">Team Orders</h1>

    <?php if (isset($message)): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <!-- Search Form -->
    <form method="GET" action="view_team_orders.php" class="mb-4">
        <div class="input-group">
            <input type="text" class="form-control" name="search" placeholder="Search by order number, customer name, or status" value="<?php echo htmlspecialchars($search_term); ?>">
            <div class="input-group-append">
                <button class="btn btn-primary" type="submit">Search</button>
            </div>
        </div>
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
                        <th>Sales Representative</th>
                        <th>Order Date</th>
                        <th>Status</th>
                        <th>Total Amount</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($orders_result && $orders_result->num_rows > 0): ?>
                        <?php $no = 1; // Initialize row number ?>
                        <?php while ($order = $orders_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                <td><?php echo htmlspecialchars($order['sales_rep']); ?></td>
                                <td><?php echo htmlspecialchars(date('F j, Y', strtotime($order['order_date']))); ?></td>
                                <td>
                                    <?php
                                    $status_class = '';
                                    switch ($order['status']) {
                                        case 'pending':
                                            $status_class = 'badge badge-warning';
                                            break;
                                        case 'completed':
                                            $status_class = 'badge badge-success';
                                            break;
                                        case 'cancelled':
                                            $status_class = 'badge badge-danger';
                                            break;
                                    }
                                    ?>
                                    <span class="<?php echo $status_class; ?>"><?php echo htmlspecialchars(ucfirst($order['status'])); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars(number_format($order['total_amount'], 2)); ?></td>
                                <td>
                                    <!-- Update Status Button -->
                                    <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#updateOrderModal<?php echo $order['id']; ?>">Update Status</button>

                                    <!-- Update Status Modal -->
                                    <div class="modal fade" id="updateOrderModal<?php echo $order['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="updateOrderModalLabel<?php echo $order['id']; ?>" aria-hidden="true">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="updateOrderModalLabel<?php echo $order['id']; ?>">Update Order Status</h5>
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    <form method="POST" action="view_team_orders.php">
                                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                        <div class="form-group">
                                                            <label for="status<?php echo $order['id']; ?>">Status</label>
                                                            <select class="form-control" id="status<?php echo $order['id']; ?>" name="status" required>
                                                                <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                                <option value="completed" <?php echo $order['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                                <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                            </select>
                                                        </div>
                                                        <button type="submit" class="btn btn-primary" name="update_order">Save Changes</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">No orders found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Footer -->
<?php include('../includes/supervisor/footer.php'); ?>
