<?php
// Start session and ensure user is an admin
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Connect to the database
include('../db.php');

// Default date range for export
$default_start_date = date('Y-m-01'); // Start of the current month
$default_end_date = date('Y-m-t');    // End of the current month

// Handle form submission for date range filtering
$start_date = isset($_POST['start_date']) ? $_POST['start_date'] : $default_start_date;
$end_date = isset($_POST['end_date']) ? $_POST['end_date'] : $default_end_date;

// Tambahkan waktu akhir hari untuk end_date
$end_date_inclusive = date('Y-m-d 23:59:59', strtotime($end_date));

$filter_query = '';
if ($start_date && $end_date) {
    $filter_query = "WHERE sr.sales_date BETWEEN '$start_date' AND '$end_date_inclusive'";
}

// Fetch sales report data with aliases and joins
$sql = "SELECT 
            o.order_number AS order_name, 
            u.name AS sales_name, 
            sr.total_sales, 
            sr.sales_date 
        FROM sales_report sr
        JOIN orders o ON sr.order_id = o.id
        JOIN users u ON sr.sales_id = u.id
        $filter_query 
        ORDER BY sr.sales_date DESC";

$result = $conn->query($sql);
$sales_reports = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $sales_reports[] = $row;
    }
} else {
    // Debugging: Output SQL query and error if no results or error
    error_log("SQL Query: $sql");
    if ($conn->error) {
        error_log("SQL Error: " . $conn->error);
    }
}

// Handle export to CSV
if (isset($_POST['export_csv'])) {
    $start_date = isset($_POST['start_date']) ? $_POST['start_date'] : $default_start_date;
    $end_date = isset($_POST['end_date']) ? $_POST['end_date'] : $default_end_date;
    
    // Tambahkan waktu akhir hari untuk end_date
    $end_date_inclusive = date('Y-m-d 23:59:59', strtotime($end_date));
    
    // Reapply filter query for export
    $filter_query = '';
    if ($start_date && $end_date) {
        $filter_query = "WHERE sr.sales_date BETWEEN '$start_date' AND '$end_date_inclusive'";
    }

    // Set headers for CSV export
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename=sales_report.csv');
    
    // Ensure output buffer is clean
    ob_clean();
    flush();
    
    // Open output stream for CSV
    $output = fopen('php://output', 'w');
    fputcsv($output, ['No', 'Order Name', 'Sales Name', 'Total Sales', 'Sales Date']);
    
    // Fetch filtered data for export
    $sql = "SELECT 
                o.order_number AS order_name, 
                u.name AS sales_name, 
                sr.total_sales, 
                sr.sales_date 
            FROM sales_report sr
            JOIN orders o ON sr.order_id = o.id
            JOIN users u ON sr.sales_id = u.id
            $filter_query 
            ORDER BY sr.sales_date DESC";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $row_number = 1; // Initialize row number
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, array_merge([$row_number++], $row)); // Add row number before data
        }
    }
    
    // Close the output stream
    fclose($output);
    exit();
}

// Include header and sidebar
include('../includes/admin/header.php');
include('../includes/admin/sidebar.php');
?>

<!-- Content -->
<div class="container-fluid">
    <!-- Page Heading -->
    <h1 class="h3 mb-4 text-gray-800">Sales Report</h1>

    <!-- Filter Form -->
    <form method="POST" action="view_sales_reports.php" class="mb-4">
        <div class="form-row">
            <div class="col-md-3">
                <input type="date" class="form-control" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
            </div>
            <div class="col-md-3">
                <input type="date" class="form-control" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary">Filter</button>
            </div>
            <div class="col-md-3">
                <button type="button" class="btn btn-success" data-toggle="modal" data-target="#exportModal">Export CSV</button>
            </div>
        </div>
    </form>

    <!-- Sales Report Table -->
    <div class="card">
        <div class="card-header">
            Sales Report List
        </div>
        <div class="card-body">
            <table class="table table-striped table-bordered" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Order Name</th>
                        <th>Sales Name</th>
                        <th>Total Sales</th>
                        <th>Sales Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($sales_reports)): ?>
                        <tr>
                            <td colspan="5" class="text-center">No records found</td>
                        </tr>
                    <?php else: ?>
                        <?php $row_number = 1; // Inisialisasi nomor urut ?>
                        <?php foreach ($sales_reports as $report): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row_number++); ?></td> <!-- Nomor urut -->
                                <td><?php echo htmlspecialchars($report['order_name']); ?></td>
                                <td><?php echo htmlspecialchars($report['sales_name']); ?></td>
                                <td><?php echo htmlspecialchars(number_format($report['total_sales'], 2)); ?></td>
                                <td><?php echo htmlspecialchars($report['sales_date']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Export Modal -->
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
                <form method="POST" action="view_sales_reports.php">
                    <div class="form-group">
                        <label for="start_date">Start Date</label>
                        <input type="date" class="form-control" id="start_date_modal" name="start_date" value="<?php echo htmlspecialchars($default_start_date); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="end_date">End Date</label>
                        <input type="date" class="form-control" id="end_date_modal" name="end_date" value="<?php echo htmlspecialchars($default_end_date); ?>" required>
                    </div>
                    <button type="submit" name="export_csv" class="btn btn-success">Export to CSV</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include('../includes/admin/footer.php');
?>
