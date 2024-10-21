<?php
// Start session and ensure user is logged in as a admin
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Connect to the database
include('../db.php');

// Handle export CSV request
if (isset($_POST['export_csv'])) {
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    // Fetch sales reports
    $sales_reports_query = "
        SELECT u.name AS sales_name,
               COUNT(o.id) AS total_orders, SUM(o.total_amount) AS total_sales
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE u.role = 'sales' AND o.order_date BETWEEN '$start_date' AND '$end_date'
        GROUP BY u.id
        ORDER BY total_sales DESC
    ";
    $sales_reports_result = $conn->query($sales_reports_query);

    // Create CSV
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="sales_report_' . date('Ymd') . '.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Sales Representative', 'Total Orders', 'Total Sales']);

    if ($sales_reports_result && $sales_reports_result->num_rows > 0) {
        while ($report = $sales_reports_result->fetch_assoc()) {
            fputcsv($output, [
                $report['sales_name'],
                $report['total_orders'],
                number_format($report['total_sales'], 2)
            ]);
        }
    }

    fclose($output);
    exit();
}

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
include('../includes/admin/header.php');
include('../includes/admin/sidebar.php');
?>

<!-- Content -->
<div class="container-fluid">
    <!-- Page Heading -->
    <h1 class="h3 mb-4 text-gray-800">Sales Reports</h1>

    <!-- Date Range Form -->
    <form method="GET" action="report_sales.php" class="mb-4">
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
                <!-- Button trigger modal -->
                <button type="button" class="btn btn-success ml-2" data-toggle="modal" data-target="#exportModal">Export to CSV</button>
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

<!-- Modal Export CSV -->
<div class="modal fade" id="exportModal" tabindex="-1" role="dialog" aria-labelledby="exportModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exportModalLabel">Export Sales Report</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" action="report_sales.php">
                    <div class="form-group">
                        <label for="start_date_modal">Start Date</label>
                        <input type="date" class="form-control" id="start_date_modal" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="end_date_modal">End Date</label>
                        <input type="date" class="form-control" id="end_date_modal" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>" required>
                    </div>
                    <button type="submit" name="export_csv" class="btn btn-success">Export to CSV</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Footer -->
<?php include('../includes/admin/footer.php'); ?>
