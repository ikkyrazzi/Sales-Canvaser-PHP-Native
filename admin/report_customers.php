<?php
// Mulai sesi dan pastikan pengguna adalah admin
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Koneksi ke database
include('../db.php');

// Tanggal default untuk ekspor
$default_start_date = date('Y-m-01'); // Awal bulan ini
$default_end_date = date('Y-m-t');    // Akhir bulan ini

// Menangani pengiriman formulir untuk filter rentang tanggal
$start_date = isset($_POST['start_date']) ? $_POST['start_date'] : $default_start_date;
$end_date = isset($_POST['end_date']) ? $_POST['end_date'] : $default_end_date;

// Pastikan end_date mencakup akhir hari
$end_date_inclusive = date('Y-m-d 23:59:59', strtotime($end_date));

// Menyiapkan query dengan placeholder untuk mencegah SQL Injection
$filter_query = '';
$params = [];
if ($start_date && $end_date) {
    $filter_query = "WHERE cr.report_date BETWEEN ? AND ?";
    $params[] = $start_date;
    $params[] = $end_date_inclusive;
}

// Mengambil data laporan pelanggan dengan customer_name dan product_name
$sql = "
    SELECT
        cr.customer_id,
        c.customer_name,
        p.product_name,
        cr.total_orders,
        cr.total_amount,
        cr.report_date
    FROM
        customer_report cr
    JOIN
        customers c ON cr.customer_id = c.id
    JOIN
        orders o ON o.customer_id = c.id
    JOIN
        order_items oi ON o.id = oi.order_id
    JOIN
        products p ON oi.product_id = p.id
    $filter_query
    GROUP BY
        cr.customer_id, c.customer_name, p.product_name, cr.total_orders, cr.total_amount, cr.report_date
    ORDER BY
        cr.report_date DESC
";

// Gunakan prepared statements untuk mencegah SQL Injection
$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param(str_repeat('s', count($params)), ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$customer_reports = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $customer_reports[] = $row;
    }
} else {
    // Debugging: Output SQL query dan error jika tidak ada hasil atau error
    error_log("SQL Query: $sql");
    if ($conn->error) {
        error_log("SQL Error: " . $conn->error);
    }
}

// Menangani ekspor ke CSV
if (isset($_POST['export_csv'])) {
    // Terapkan kembali query filter untuk ekspor
    $filter_query = '';
    $params = [];
    if ($start_date && $end_date) {
        $filter_query = "WHERE cr.report_date BETWEEN ? AND ?";
        $params[] = $start_date;
        $params[] = $end_date_inclusive;
    }

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename=customer_report.csv');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['No', 'Customer Name', 'Product Name', 'Total Orders', 'Total Amount', 'Report Date']);

    // Gunakan query yang sama untuk ekspor CSV
    $sql = "
        SELECT
            cr.customer_id,
            c.customer_name,
            p.product_name,
            cr.total_orders,
            cr.total_amount,
            cr.report_date
        FROM
            customer_report cr
        JOIN
            customers c ON cr.customer_id = c.id
        JOIN
            orders o ON o.customer_id = c.id
        JOIN
            order_items oi ON o.id = oi.order_id
        JOIN
            products p ON oi.product_id = p.id
        $filter_query
        GROUP BY
            cr.customer_id, c.customer_name, p.product_name, cr.total_orders, cr.total_amount, cr.report_date
        ORDER BY
            cr.report_date DESC
    ";

    $stmt = $conn->prepare($sql);
    if ($params) {
        $stmt->bind_param(str_repeat('s', count($params)), ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $row_number = 1; // Inisialisasi nomor baris
        while ($row = $result->fetch_assoc()) {
            // Hapus 'customer_id' dari data untuk ekspor
            unset($row['customer_id']);
            fputcsv($output, array_merge([$row_number++], $row)); // Tambahkan nomor urut sebelum data
        }
    }

    fclose($output);
    exit();
}

// Sertakan header dan sidebar
include('../includes/admin/header.php');
include('../includes/admin/sidebar.php');
?>

<!-- Konten -->
<div class="container-fluid">
    <!-- Heading Halaman -->
    <h1 class="h3 mb-4 text-gray-800">Laporan Pelanggan</h1>

    <!-- Form Filter -->
    <form method="POST" action="report_customers.php" class="mb-4">
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
                <button type="button" class="btn btn-success" data-toggle="modal" data-target="#exportModal">Ekspor CSV</button>
            </div>
        </div>
    </form>

    <!-- Tabel Laporan Pelanggan -->
    <div class="card">
        <div class="card-header">
            Customer Report List
        </div>
        <div class="card-body">
            <table class="table table-striped table-bordered" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Customer Name</th>
                        <th>Product Name</th>
                        <th>Total Orders</th>
                        <th>Total Amount</th>
                        <th>Report Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($customer_reports)): ?>
                        <tr>
                            <td colspan="6" class="text-center">No records found</td>
                        </tr>
                    <?php else: ?>
                        <?php $row_number = 1; // Inisialisasi nomor urut ?>
                        <?php foreach ($customer_reports as $report): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row_number++); ?></td> <!-- Nomor urut -->
                                <td><?php echo htmlspecialchars($report['customer_name']); ?></td>
                                <td><?php echo htmlspecialchars($report['product_name']); ?></td>
                                <td><?php echo htmlspecialchars($report['total_orders']); ?></td>
                                <td><?php echo htmlspecialchars(number_format($report['total_amount'], 2)); ?></td>
                                <td><?php echo htmlspecialchars($report['report_date']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Ekspor -->
<div class="modal fade" id="exportModal" tabindex="-1" role="dialog" aria-labelledby="exportModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exportModalLabel">Ekspor Laporan Pelanggan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" action="report_customers.php">
                    <div class="form-group">
                        <label for="start_date">Tanggal Mulai</label>
                        <input type="date" class="form-control" id="start_date_modal" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="end_date">Tanggal Akhir</label>
                        <input type="date" class="form-control" id="end_date_modal" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>" required>
                    </div>
                    <button type="submit" name="export_csv" class="btn btn-success">Ekspor ke CSV</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// Sertakan footer
include('../includes/admin/footer.php');
?>
