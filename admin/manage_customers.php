<?php
// Start session and ensure user is an admin
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Connect to the database
include('../db.php');

// Handle customer addition
if (isset($_POST['add_customer'])) {
    $customer_name = $_POST['customer_name'];
    $contact_number = $_POST['contact_number'];
    $email = $_POST['email'];
    $address = $_POST['address'];

    $sql = "INSERT INTO customers (customer_name, contact_number, email, address) VALUES ('$customer_name', '$contact_number', '$email', '$address')";
    if ($conn->query($sql)) {
        $message = "Customer added successfully.";
    } else {
        $message = "Error adding customer: " . $conn->error;
    }
}

// Handle customer update
if (isset($_POST['edit_customer'])) {
    $customer_id = $_POST['customer_id'];
    $customer_name = $_POST['customer_name'];
    $contact_number = $_POST['contact_number'];
    $email = $_POST['email'];
    $address = $_POST['address'];

    $sql = "UPDATE customers SET customer_name='$customer_name', contact_number='$contact_number', email='$email', address='$address' WHERE id='$customer_id'";
    if ($conn->query($sql)) {
        $message = "Customer updated successfully.";
    } else {
        $message = "Error updating customer: " . $conn->error;
    }
}

// Handle customer deletion
if (isset($_GET['delete'])) {
    $customer_id = $_GET['delete'];

    $sql = "DELETE FROM customers WHERE id='$customer_id'";
    if ($conn->query($sql)) {
        $message = "Customer deleted successfully.";
    } else {
        $message = "Error deleting customer: " . $conn->error;
    }
}

// Fetch all customers
$customers_query = "SELECT id, customer_name, contact_number, email, address FROM customers";
$customers_result = $conn->query($customers_query);
$customers = [];
if ($customers_result && $customers_result->num_rows > 0) {
    while ($customer_row = $customers_result->fetch_assoc()) {
        $customers[] = $customer_row;
    }
}

// Include header and sidebar
include('../includes/admin/header.php');
include('../includes/admin/sidebar.php');
?>

<!-- Content -->
<div class="container-fluid">
    <!-- Page Heading -->
    <h1 class="h3 mb-4 text-gray-800">Manage Customers</h1>

    <?php if (isset($message)): ?>
        <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <!-- Add Customer Button -->
    <button class="btn btn-primary mb-3" data-toggle="modal" data-target="#addCustomerModal">Add New Customer</button>

    <!-- Add Customer Modal -->
    <div class="modal fade" id="addCustomerModal" tabindex="-1" role="dialog" aria-labelledby="addCustomerModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCustomerModalLabel">Add New Customer</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="manage_customers.php">
                        <div class="form-group">
                            <label for="customer_name">Customer Name</label>
                            <input type="text" class="form-control" id="customer_name" name="customer_name" required>
                        </div>
                        <div class="form-group">
                            <label for="contact_number">Contact Number</label>
                            <input type="text" class="form-control" id="contact_number" name="contact_number">
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" class="form-control" id="email" name="email">
                        </div>
                        <div class="form-group">
                            <label for="address">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="3"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary" name="add_customer">Add Customer</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Customers Table -->
    <div class="card">
        <div class="card-header">
            Customers List
        </div>
        <div class="card-body">
            <table class="table table-striped table-bordered">
        <thead>
                    <tr>
                        <th>No</th>
                        <th>Customer Name</th>
                        <th>Contact Number</th>
                        <th>Email</th>
                        <th>Address</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $counter = 1; ?>
                    <?php foreach ($customers as $customer): ?>
                        <tr>
                            <td><?php echo $counter++; ?></td>
                            <td><?php echo htmlspecialchars($customer['customer_name']); ?></td>
                            <td><?php echo htmlspecialchars($customer['contact_number']); ?></td>
                            <td><?php echo htmlspecialchars($customer['email']); ?></td>
                            <td><?php echo htmlspecialchars($customer['address']); ?></td>
                            <td>
                                <!-- Edit Button -->
                                <a href="#" class="btn btn-warning btn-sm" data-toggle="modal" data-target="#editCustomerModal<?php echo $customer['id']; ?>">Edit</a>
                                <!-- Detail Button -->
                                <a href="#" class="btn btn-info btn-sm" data-toggle="modal" data-target="#detailCustomerModal<?php echo $customer['id']; ?>">Detail</a>
                                <!-- Delete Button -->
                                <a href="?delete=<?php echo $customer['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this customer?')">Delete</a>
                            </td>
                        </tr>

                        <!-- Edit Customer Modal -->
                        <div class="modal fade" id="editCustomerModal<?php echo $customer['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="editCustomerModalLabel" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="editCustomerModalLabel">Edit Customer</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <form method="POST" action="manage_customers.php">
                                            <input type="hidden" name="customer_id" value="<?php echo $customer['id']; ?>">
                                            <div class="form-group">
                                                <label for="edit_customer_name">Customer Name</label>
                                                <input type="text" class="form-control" id="edit_customer_name" name="customer_name" value="<?php echo htmlspecialchars($customer['customer_name']); ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label for="edit_contact_number">Contact Number</label>
                                                <input type="text" class="form-control" id="edit_contact_number" name="contact_number" value="<?php echo htmlspecialchars($customer['contact_number']); ?>">
                                            </div>
                                            <div class="form-group">
                                                <label for="edit_email">Email</label>
                                                <input type="email" class="form-control" id="edit_email" name="email" value="<?php echo htmlspecialchars($customer['email']); ?>">
                                            </div>
                                            <div class="form-group">
                                                <label for="edit_address">Address</label>
                                                <textarea class="form-control" id="edit_address" name="address" rows="3"><?php echo htmlspecialchars($customer['address']); ?></textarea>
                                            </div>
                                            <button type="submit" class="btn btn-primary" name="edit_customer">Save Changes</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Customer Detail Modal -->
                        <div class="modal fade" id="detailCustomerModal<?php echo $customer['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="detailCustomerModalLabel" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="detailCustomerModalLabel">Customer Details</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <?php
                                        // Fetch customer details if modal is triggered
                                        $details_query = "SELECT * FROM customers WHERE id='" . $customer['id'] . "'";
                                        $details_result = $conn->query($details_query);
                                        if ($details_result && $details_result->num_rows > 0) {
                                            $details = $details_result->fetch_assoc();
                                        }
                                        ?>
                                        <p><strong>Name:</strong> <?php echo htmlspecialchars($details['customer_name']); ?></p>
                                        <p><strong>Contact Number:</strong> <?php echo htmlspecialchars($details['contact_number']); ?></p>
                                        <p><strong>Email:</strong> <?php echo htmlspecialchars($details['email']); ?></p>
                                        <p><strong>Address:</strong> <?php echo htmlspecialchars($details['address']); ?></p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Include footer -->
<?php include('../includes/admin/footer.php'); ?>
