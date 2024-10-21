<?php
// Start session and ensure user is logged in as a supervisor
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'supervisor') {
    header("Location: login.php");
    exit();
}

// Connect to the database
include('../db.php');

// Handle date range for the report
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

// Fetch sales reports
$sales_reports_query = "
    SELECT u.id AS sales_id, u.name AS sales_name,
           COUNT(o.id) AS total_orders, SUM(o.total_amount) AS total_sales
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE u.role = 'sales' AND o.order_date BETWEEN '$start_date' AND '$end_date'
    GROUP BY u.id
    ORDER BY total_sales DESC
";
$sales_reports_result = $conn->query($sales_reports_query);

// Include header and sidebar
include('../includes/supervisor/header.php');
include('../includes/supervisor/sidebar.php');
?>

<!-- Content -->
<div class="container-fluid">
    <!-- Page Heading -->
    <h1 class="h3 mb-4 text-gray-800">Sales Reports</h1>

    <!-- Date Range Form -->
    <form method="GET" action="view_sales_reports.php" class="mb-4">
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label for="start_date">Start Date</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>" required>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="end_date">End Date</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>" required>
                </div>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button class="btn btn-primary" type="submit">Generate Report</button>
            </div>
        </div>
    </form>

    <!-- Sales Report Table -->
    <div class="card shadow-sm">
        <div class="card-header">
            Sales Report from <?php echo htmlspecialchars($start_date); ?> to <?php echo htmlspecialchars($end_date); ?>
        </div>
        <div class="card-body">
            <table class="table table-striped table-bordered" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Sales Representative</th>
                        <th>Total Orders</th>
                        <th>Total Sales</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($sales_reports_result && $sales_reports_result->num_rows > 0): ?>
                        <?php $no = 1; // Initialize row number ?>
                        <?php while ($report = $sales_reports_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo htmlspecialchars($report['sales_name']); ?></td>
                                <td><?php echo htmlspecialchars($report['total_orders']); ?></td>
                                <td><?php echo htmlspecialchars(number_format($report['total_sales'], 2)); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center">No sales reports available for the selected period.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Footer -->
<?php include('../includes/supervisor/footer.php'); ?>
