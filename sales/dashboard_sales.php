<?php
// Start session and ensure user is logged in
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'sales') {
    header("Location: login.php");
    exit();
}

// Connect to the database
include('../db.php');

// Include header and sidebar
include('../includes/sales/header.php');
include('../includes/sales/sidebar.php');

// Initialize variables
$total_sales = 0;
$total_orders = 0;
$pending_orders = 0;
$completed_orders = 0;
$cancelled_orders = 0;
$newest_orders = array();

// Get total sales for the current sales rep
$sales_query = "SELECT SUM(total_amount) AS total_sales FROM orders WHERE status = 'completed' AND user_id = ?";
$stmt = $conn->prepare($sales_query);
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$sales_result = $stmt->get_result();
if ($sales_result && $sales_result->num_rows > 0) {
    $sales_row = $sales_result->fetch_assoc();
    $total_sales = $sales_row['total_sales'] ?? 0;
}

// Get total orders for the current sales rep
$orders_query = "SELECT COUNT(*) AS total_orders FROM orders WHERE user_id = ?";
$stmt = $conn->prepare($orders_query);
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$orders_result = $stmt->get_result();
if ($orders_result && $orders_result->num_rows > 0) {
    $orders_row = $orders_result->fetch_assoc();
    $total_orders = $orders_row['total_orders'] ?? 0;
}

// Get pending orders for the current sales rep
$pending_query = "SELECT COUNT(*) AS pending_orders FROM orders WHERE status = 'pending' AND user_id = ?";
$stmt = $conn->prepare($pending_query);
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$pending_result = $stmt->get_result();
if ($pending_result && $pending_result->num_rows > 0) {
    $pending_row = $pending_result->fetch_assoc();
    $pending_orders = $pending_row['pending_orders'] ?? 0;
}

// Get completed orders for the current sales rep
$completed_query = "SELECT COUNT(*) AS completed_orders FROM orders WHERE status = 'completed' AND user_id = ?";
$stmt = $conn->prepare($completed_query);
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$completed_result = $stmt->get_result();
if ($completed_result && $completed_result->num_rows > 0) {
    $completed_row = $completed_result->fetch_assoc();
    $completed_orders = $completed_row['completed_orders'] ?? 0;
}

// Get canceled orders for the current sales rep
$canceled_query = "SELECT COUNT(*) AS cancelled_orders FROM orders WHERE status = 'canceled' AND user_id = ?";
$stmt = $conn->prepare($canceled_query);
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$canceled_result = $stmt->get_result();
if ($canceled_result && $canceled_result->num_rows > 0) {
    $canceled_row = $canceled_result->fetch_assoc();
    $cancelled_orders = $canceled_row['cancelled_orders'] ?? 0;
}

// Get newest orders for the current sales rep
$orders_query = "SELECT o.id, c.customer_name, o.total_amount, o.status, o.order_date 
                 FROM orders o
                 JOIN customers c ON o.customer_id = c.id
                 WHERE o.user_id = ?
                 ORDER BY o.order_date DESC 
                 LIMIT 5";
$stmt = $conn->prepare($orders_query);
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$orders_result = $stmt->get_result();
if ($orders_result && $orders_result->num_rows > 0) {
    while ($order_row = $orders_result->fetch_assoc()) {
        $newest_orders[] = $order_row;
    }
}
?>

<!-- Content -->
<div class="container-fluid">
    <!-- Page Heading -->
    <h1 class="h3 mb-4 text-gray-800">Sales Dashboard</h1>

    <div class="row">
        <!-- Total Sales -->
        <div class="col-xl-6 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Sales</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">Rp <?php echo number_format($total_sales, 2, ',', '.'); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Orders -->
        <div class="col-xl-6 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Orders</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($total_orders); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-shopping-cart fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Orders -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending Orders</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($pending_orders); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-hourglass-half fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Completed Orders -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Completed Orders</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($completed_orders); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Canceled Orders -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Canceled Orders</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($cancelled_orders); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-times-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Newest Orders -->
        <div class="col-xl-12 col-md-12 mb-4">
            <div class="card shadow h-100 py-2">
                <div class="card-body">
                    <div class="card-header bg-primary text-white">
                        <h5 class="m-0">Newest Orders</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Order ID</th>
                                    <th>Customer Name</th>
                                    <th>Total Amount</th>
                                    <th>Status</th>
                                    <th>Order Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($newest_orders)): ?>
                                    <?php $no = 1; ?>
                                    <?php foreach ($newest_orders as $order): ?>
                                        <tr>
                                            <td><?php echo $no++; ?></td>
                                            <td><?php echo htmlspecialchars($order['id']); ?></td>
                                            <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                            <td>Rp <?php echo number_format($order['total_amount'], 2, ',', '.'); ?></td>
                                            <td><?php echo htmlspecialchars(ucfirst($order['status'])); ?></td>
                                            <td><?php echo htmlspecialchars(date('d M Y', strtotime($order['order_date']))); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No recent orders</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include footer -->
<?php include('../includes/sales/footer.php'); ?>
