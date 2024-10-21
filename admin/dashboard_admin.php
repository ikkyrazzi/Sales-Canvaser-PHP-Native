<?php
// Start session and ensure user is an admin
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Connect to the database
include('../db.php');

// Include header and sidebar
include('../includes/admin/header.php');
include('../includes/admin/sidebar.php');

// Fetch statistics from the database
$total_sales = 0;
$total_orders = 0;
$newest_products = array();
$newest_orders = array();
$sales_data = array();

// Get total sales
$sales_query = "SELECT SUM(total_amount) as total_sales FROM orders WHERE status = 'completed'";
$sales_result = $conn->query($sales_query);
if ($sales_result && $sales_result->num_rows > 0) {
    $sales_row = $sales_result->fetch_assoc();
    $total_sales = $sales_row['total_sales'];
}

// Get total orders
$orders_query = "SELECT COUNT(*) as total_orders FROM orders";
$orders_result = $conn->query($orders_query);
if ($orders_result && $orders_result->num_rows > 0) {
    $orders_row = $orders_result->fetch_assoc();
    $total_orders = $orders_row['total_orders'];
}

// Get newest products
$products_query = "SELECT product_name, price, created_at FROM products ORDER BY created_at DESC LIMIT 5";
$products_result = $conn->query($products_query);
if ($products_result && $products_result->num_rows > 0) {
    while ($product_row = $products_result->fetch_assoc()) {
        $newest_products[] = $product_row;
    }
}

// Get 5 newest orders (include customer name using JOIN)
$newest_orders_query = "
    SELECT 
        o.order_number, 
        c.customer_name, 
        o.total_amount, 
        o.created_at 
    FROM orders o
    INNER JOIN customers c ON o.customer_id = c.id
    ORDER BY o.created_at DESC 
    LIMIT 5
";
$newest_orders_result = $conn->query($newest_orders_query);
if ($newest_orders_result && $newest_orders_result->num_rows > 0) {
    while ($order_row = $newest_orders_result->fetch_assoc()) {
        $newest_orders[] = $order_row;
    }
}

// Get sales data for top salespersons
$salesperson_query = "
    SELECT 
        u.name AS salesperson_name, 
        SUM(o.total_amount) AS total_sales 
    FROM orders o
    INNER JOIN users u ON o.user_id = u.id
    WHERE o.status = 'completed'
    GROUP BY u.name
    ORDER BY total_sales DESC 
    LIMIT 6
";
$salesperson_result = $conn->query($salesperson_query);
if ($salesperson_result && $salesperson_result->num_rows > 0) {
    while ($salesperson_row = $salesperson_result->fetch_assoc()) {
        $sales_data[] = $salesperson_row;
    }
}
?>

<!-- Content -->
<div class="container-fluid">
    <!-- Page Heading -->
    <h1 class="h3 mb-4 text-gray-800">Dashboard</h1>
    
    <div class="row">
        <!-- Total Sales -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Sales</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">Rp<?php echo number_format($total_sales, 2, ',', '.'); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Orders -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Orders</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_orders; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-shopping-cart fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6 col-md-12 mb-4">
            <div class="card shadow-lg rounded-lg">
                <div class="card-header bg-primary text-white">
                    <h5 class="m-0">Newest Products</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Name</th>
                                    <th>Price</th>
                                    <th>Added On</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($newest_products as $index => $product): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                        <td>Rp<?php echo number_format($product['price'], 2, ',', '.'); ?></td>
                                        <td><?php echo date('d M Y', strtotime($product['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Newest Orders -->
        <div class="col-xl-8 col-md-12 mb-4">
            <div class="card shadow-lg rounded-lg">
                <div class="card-header bg-primary text-white">
                    <h5 class="m-0">Newest Orders</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Order Number</th>
                                    <th>Customer Name</th>
                                    <th>Total Amount</th>
                                    <th>Order Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($newest_orders as $index => $order): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                                        <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                        <td>Rp<?php echo number_format($order['total_amount'], 2, ',', '.'); ?></td>
                                        <td><?php echo date('d M Y', strtotime($order['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Salesperson Performance Chart -->
        <div class="col-xl-4 col-md-4 mb-4">
            <div class="card shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Top Salespersons</div>
                    <canvas id="salesChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include Footer -->
<?php include('../includes/admin/footer.php'); ?>

<!-- Chart.js Script -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    var ctx = document.getElementById('salesChart').getContext('2d');
    var salesData = {
        labels: <?php echo json_encode(array_column($sales_data, 'salesperson_name')); ?>,
        datasets: [{
            label: 'Total Sales',
            data: <?php echo json_encode(array_column($sales_data, 'total_sales')); ?>,
            backgroundColor: [
                'rgba(255, 99, 132, 0.6)',
                'rgba(54, 162, 235, 0.6)',
                'rgba(255, 206, 86, 0.6)',
                'rgba(75, 192, 192, 0.6)',
                'rgba(153, 102, 255, 0.6)',
                'rgba(255, 159, 64, 0.6)'
            ],
            borderColor: [
                'rgba(255, 99, 132, 1)',
                'rgba(54, 162, 235, 1)',
                'rgba(255, 206, 86, 1)',
                'rgba(75, 192, 192, 1)',
                'rgba(153, 102, 255, 1)',
                'rgba(255, 159, 64, 1)'
            ],
            borderWidth: 1
        }]
    };

    var salesChart = new Chart(ctx, {
        type: 'pie',
        data: salesData,
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
</script>
