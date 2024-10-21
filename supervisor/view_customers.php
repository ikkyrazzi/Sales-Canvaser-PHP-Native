<?php
// Start session and ensure user is logged in as a supervisor
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'supervisor') {
    header("Location: login.php");
    exit();
}

// Connect to the database
include('../db.php');

// Fetch all customers handled by sales team
$customers_query = "
    SELECT DISTINCT 
        c.id,
        c.customer_name,
        c.contact_number,
        c.email,
        c.address
    FROM 
        customers c
    JOIN 
        orders o ON c.id = o.customer_id
    JOIN 
        users u ON o.user_id = u.id
    WHERE 
        u.role = 'sales'
";
$customers_result = $conn->query($customers_query);
$customers = [];
if ($customers_result && $customers_result->num_rows > 0) {
    while ($row = $customers_result->fetch_assoc()) {
        $customers[] = $row;
    }
}

// Handle customer update
if (isset($_POST['update_customer'])) {
    $customer_id = $_POST['customer_id'];
    $customer_name = $_POST['customer_name'];
    $contact_number = $_POST['contact_number'];
    $email = $_POST['email'];
    $address = $_POST['address'];

    $update_query = "
        UPDATE customers
        SET customer_name = ?, contact_number = ?, email = ?, address = ?
        WHERE id = ?
    ";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param('ssssi', $customer_name, $contact_number, $email, $address, $customer_id);

    if ($stmt->execute()) {
        header("Location: view_customers.php?success=Customer updated successfully.");
        exit();
    } else {
        $error_message = "Error updating customer: " . $stmt->error;
    }
}

// Include header and sidebar
include('../includes/supervisor/header.php');
include('../includes/supervisor/sidebar.php');
?>

<!-- Content -->
<div class="container-fluid">
    <!-- Page Heading -->
    <h1 class="h3 mb-4 text-gray-800">View Customers</h1>

    <!-- Alerts -->
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
    <?php endif; ?>

    <!-- Customer List -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title">Customer List</h5>
        </div>
        <div class="card-body">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Name</th>
                        <th>Contact Number</th>
                        <th>Email</th>
                        <th>Address</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($customers)): ?>
                        <?php $i = 1; // Initialize counter ?>
                        <?php foreach ($customers as $customer): ?>
                            <tr>
                                <td><?php echo $i++; // Increment counter ?></td>
                                <td><?php echo htmlspecialchars($customer['customer_name']); ?></td>
                                <td><?php echo htmlspecialchars($customer['contact_number']); ?></td>
                                <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                <td><?php echo htmlspecialchars($customer['address']); ?></td>
                                <td>
                                    <a href="#" class="btn btn-warning btn-sm" data-toggle="modal" data-target="#editCustomerModal" 
                                       data-customer-id="<?php echo $customer['id']; ?>"
                                       data-customer-name="<?php echo htmlspecialchars($customer['customer_name']); ?>"
                                       data-contact-number="<?php echo htmlspecialchars($customer['contact_number']); ?>"
                                       data-email="<?php echo htmlspecialchars($customer['email']); ?>"
                                       data-address="<?php echo htmlspecialchars($customer['address']); ?>">
                                       <i class="fas fa-edit"></i> Edit
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center">No customers found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Edit Customer Modal -->
<div class="modal fade" id="editCustomerModal" tabindex="-1" role="dialog" aria-labelledby="editCustomerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editCustomerModalLabel">Edit Customer</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="view_customers.php" method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="customer_name">Name</label>
                        <input type="text" class="form-control" id="customer_name" name="customer_name" required>
                    </div>
                    <div class="form-group">
                        <label for="contact_number">Contact Number</label>
                        <input type="text" class="form-control" id="contact_number" name="contact_number" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="3" required></textarea>
                    </div>
                    <input type="hidden" id="customer_id" name="customer_id">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" name="update_customer" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Include footer -->
<?php include('../includes/supervisor/footer.php'); ?>

<!-- JavaScript for handling modal data -->
<script>
    $('#editCustomerModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget); // Button that triggered the modal
        var customerId = button.data('customer-id');
        var customerName = button.data('customer-name');
        var contactNumber = button.data('contact-number');
        var email = button.data('email');
        var address = button.data('address');
        
        var modal = $(this);
        modal.find('#customer_id').val(customerId);
        modal.find('#customer_name').val(customerName);
        modal.find('#contact_number').val(contactNumber);
        modal.find('#email').val(email);
        modal.find('#address').val(address);
    });
</script>
