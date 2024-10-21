<?php
// Start session and ensure user is logged in as a sales representative
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'sales') {
    header("Location: login.php");
    exit();
}

// Connect to the database
include('../db.php');

// Fetch sales report data
$sales_id = $_SESSION['user_id'];
$sales_report_query = "
    SELECT 
        orders.id AS order_id, 
        orders.order_number, 
        customers.customer_name, 
        orders.order_date, 
        orders.status, 
        orders.total_amount 
    FROM 
        orders 
    JOIN 
        customers ON orders.customer_id = customers.id 
    WHERE 
        orders.user_id = '$sales_id'
    ORDER BY 
        orders.order_date DESC
";

$sales_report_result = $conn->query($sales_report_query);
$sales_data = [];
if ($sales_report_result && $sales_report_result->num_rows > 0) {
    while ($row = $sales_report_result->fetch_assoc()) {
        $sales_data[] = $row;
    }
}

// Calculate total sales
$total_sales_query = "
    SELECT 
        SUM(total_amount) AS total_sales 
    FROM 
        orders 
    WHERE 
        user_id = '$sales_id'
";
$total_sales_result = $conn->query($total_sales_query);
$total_sales_row = $total_sales_result->fetch_assoc();
$total_sales = $total_sales_row['total_sales'] ?? 0;

// Include header and sidebar
include('../includes/sales/header.php');
include('../includes/sales/sidebar.php');
?>

<!-- Content -->
<div class="container-fluid">
    <!-- Page Heading -->
    <h1 class="h3 mb-4 text-gray-800">Sales Report</h1>

    <!-- Total Sales -->
    <div class="alert alert-info">
        <h4 class="alert-heading">Total Sales:</h4>
        <p class="mb-0">Rp <?php echo number_format($total_sales, 2, ',', '.'); ?></p>
    </div>

    <!-- Sales Report Table -->
    <div class="card">
        <div class="card-header">
            Sales Orders List
        </div>
        <div class="card-body">
            <table class="table table-striped table-bordered" width="100%" cellspacing="0">
            <thead>
                    <tr>
                        <th>Order Number</th>
                        <th>Customer Name</th>
                        <th>Order Date</th>
                        <th>Status</th>
                        <th>Total Amount</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($sales_data)): ?>
                        <?php foreach ($sales_data as $order): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                <td><?php echo htmlspecialchars($order['order_date']); ?></td>
                                <td><?php echo htmlspecialchars($order['status']); ?></td>
                                <td>Rp <?php echo number_format($order['total_amount'], 2, ',', '.'); ?></td>
                                <td>
                                    <!-- View Details Button -->
                                    <button class="btn btn-info btn-sm view-details-btn" data-order-id="<?php echo $order['order_id']; ?>">View Details</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center">No sales data available.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal for Order Details -->
<div class="modal fade" id="orderDetailsModal" tabindex="-1" aria-labelledby="orderDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="orderDetailsModalLabel">Order Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="orderDetailsContent">
                <!-- Order details will be loaded here using AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Include footer -->
<?php include('../includes/sales/footer.php'); ?>

<!-- Include necessary JS libraries (like Bootstrap, jQuery) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.min.js"></script>

<script>
    $(document).ready(function() {
        $('.view-details-btn').click(function() {
            var orderId = $(this).data('order-id');
            
            // Fetch order details using AJAX
            $.ajax({
                url: 'fetch_order_details.php', // Update with your file path
                type: 'GET',
                data: { order_id: orderId },
                success: function(response) {
                    $('#orderDetailsContent').html(response);
                    $('#orderDetailsModal').modal('show');
                },
                error: function() {
                    $('#orderDetailsContent').html('<p>An error occurred while fetching order details.</p>');
                    $('#orderDetailsModal').modal('show');
                }
            });
        });
    });
</script>
