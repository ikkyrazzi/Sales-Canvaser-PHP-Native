<?php
// Start session and ensure user is logged in as a supervisor
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'supervisor') {
    header("Location: login.php");
    exit();
}

// Connect to the database
include('../db.php');

// Fetch supervisor ID
$user_id = $_SESSION['user_id'];

// Fetch team sales statistics
$team_sales_query = "
    SELECT 
        COUNT(o.id) AS total_orders,
        SUM(o.total_amount) AS total_sales,
        AVG(o.total_amount) AS avg_order_value
    FROM 
        orders o
    JOIN 
        users u ON o.user_id = u.id
    WHERE 
        u.role = 'sales'
";
$team_sales_result = $conn->query($team_sales_query);
$team_sales = $team_sales_result->fetch_assoc();
$total_orders = $team_sales['total_orders'] ?? 0;
$total_sales = $team_sales['total_sales'] ?? 0;
$avg_order_value = $team_sales['avg_order_value'] ?? 0;

// Fetch latest orders
$latest_orders_query = "
    SELECT 
        o.id, 
        o.order_number, 
        o.customer_id, 
        o.order_date, 
        o.status, 
        o.total_amount 
    FROM 
        orders o
    JOIN 
        users u ON o.user_id = u.id
    WHERE 
        u.role = 'sales'
    ORDER BY 
        o.order_date DESC
    LIMIT 5
";
$latest_orders_result = $conn->query($latest_orders_query);
$latest_orders = [];
if ($latest_orders_result && $latest_orders_result->num_rows > 0) {
    while ($row = $latest_orders_result->fetch_assoc()) {
        $latest_orders[] = $row;
    }
}

// Include header and sidebar
include('../includes/supervisor/header.php');
include('../includes/supervisor/sidebar.php');
?>

<!-- Content -->
<div class="container-fluid">
    <!-- Page Heading -->
    <h1 class="h3 mb-4 text-gray-800">Dashboard Supervisor</h1>

    <!-- Overview Cards -->
    <div class="row">
        <!-- Total Orders Card -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-primary shadow-lg h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Orders</div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800"><?php echo number_format($total_orders); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-boxes fa-2x text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Sales Card -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-success shadow-lg h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Sales</div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800">Rp <?php echo number_format($total_sales, 2, ',', '.'); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Average Order Value Card -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-info shadow-lg h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Avg Order Value</div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800">Rp <?php echo number_format($avg_order_value, 2, ',', '.'); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-credit-card fa-2x text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Latest Orders -->
    <div class="card shadow-lg">
        <div class="card-header bg-primary text-white">
            <h5 class="m-0">Latest Orders</h5>
        </div>
        <div class="card-body">
            <table class="table table-striped table-bordered" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Order Number</th>
                        <th>Customer ID</th>
                        <th>Order Date</th>
                        <th>Status</th>
                        <th>Total Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($latest_orders)): ?>
                        <?php $no = 1; ?>
                        <?php foreach ($latest_orders as $order): ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                                <td><?php echo htmlspecialchars($order['customer_id']); ?></td>
                                <td><?php echo htmlspecialchars(date('F j, Y', strtotime($order['order_date']))); ?></td>
                                <td><?php echo htmlspecialchars(ucfirst($order['status'])); ?></td>
                                <td>Rp <?php echo number_format($order['total_amount'], 2, ',', '.'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center">No recent orders.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Include footer -->
<?php include('../includes/supervisor/footer.php'); ?>
