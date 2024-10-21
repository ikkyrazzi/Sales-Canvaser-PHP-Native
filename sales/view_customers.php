<?php
// Start session and ensure user is logged in as a sales representative
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'sales') {
    header("Location: login.php");
    exit();
}

// Connect to the database
include('../db.php');

// Handle adding a new customer
if (isset($_POST['add_customer'])) {
    $customer_name = $_POST['customer_name'];
    $contact_number = $_POST['contact_number'];
    $address = $_POST['address'];
    $email = $_POST['email'];

    // Prepared statement to avoid SQL injection
    $stmt = $conn->prepare("INSERT INTO customers (customer_name, contact_number, address, email) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $customer_name, $contact_number, $address, $email);

    if ($stmt->execute()) {
        $message = "Customer added successfully.";
    } else {
        $message = "Error adding customer: " . $conn->error;
    }
    $stmt->close();
}

// Handle updating customer details
if (isset($_POST['edit_customer'])) {
    $customer_id = $_POST['customer_id'];
    $customer_name = $_POST['customer_name'];
    $contact_number = $_POST['contact_number'];
    $address = $_POST['address'];
    $email = $_POST['email'];

    // Prepared statement to avoid SQL injection
    $stmt = $conn->prepare("UPDATE customers SET customer_name=?, contact_number=?, address=?, email=? WHERE id=?");
    $stmt->bind_param("ssssi", $customer_name, $contact_number, $address, $email, $customer_id);

    if ($stmt->execute()) {
        $message = "Customer updated successfully.";
    } else {
        $message = "Error updating customer: " . $conn->error;
    }
    $stmt->close();
}

// Handle fetching all customers
$customers_query = "SELECT id, customer_name, contact_number, address, email FROM customers";
$customers_result = $conn->query($customers_query);
$customers = [];
if ($customers_result && $customers_result->num_rows > 0) {
    while ($customer_row = $customers_result->fetch_assoc()) {
        $customers[] = $customer_row;
    }
}

// Fetch customer details if modal is triggered
$customer_details = null;
if (isset($_GET['details'])) {
    $customer_id = $_GET['details'];
    $details_query = "SELECT * FROM customers WHERE id=?";
    $stmt = $conn->prepare($details_query);
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $details_result = $stmt->get_result();
    if ($details_result && $details_result->num_rows > 0) {
        $customer_details = $details_result->fetch_assoc();
    }
    $stmt->close();
}

// Include header and sidebar
include('../includes/sales/header.php');
include('../includes/sales/sidebar.php');
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
                    <form method="POST" action="view_customers.php">
                        <div class="form-group">
                            <label for="customer_name">Customer Name</label>
                            <input type="text" class="form-control" id="customer_name" name="customer_name" required>
                        </div>
                        <div class="form-group">
                            <label for="contact_number">Contact Number</label>
                            <input type="text" class="form-control" id="contact_number" name="contact_number" required>
                        </div>
                        <div class="form-group">
                            <label for="address">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="3" required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
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
            <table class="table table-striped table-bordered" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Customer Name</th>
                        <th>Contact Number</th>
                        <th>Address</th>
                        <th>Email</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($customers)): ?>
                        <tr>
                            <td colspan="6" class="text-center">No customers found</td>
                        </tr>
                    <?php else: ?>
                        <?php $count = 1; ?>
                        <?php foreach ($customers as $customer): ?>
                            <tr>
                                <td><?php echo $count++; ?></td>
                                <td><?php echo htmlspecialchars($customer['customer_name']); ?></td>
                                <td><?php echo htmlspecialchars($customer['contact_number']); ?></td>
                                <td><?php echo htmlspecialchars($customer['address']); ?></td>
                                <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                <td>
                                    <!-- Edit Button -->
                                    <a href="#" class="btn btn-warning btn-sm" data-toggle="modal" data-target="#editCustomerModal<?php echo $customer['id']; ?>">Edit</a>
                                    <!-- Detail Button -->
                                    <a href="?details=<?php echo $customer['id']; ?>" class="btn btn-info btn-sm" data-toggle="modal" data-target="#detailCustomerModal<?php echo $customer['id']; ?>">Detail</a>
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
                                            <form method="POST" action="view_customers.php">
                                                <input type="hidden" name="customer_id" value="<?php echo $customer['id']; ?>">
                                                <div class="form-group">
                                                    <label for="edit_customer_name">Customer Name</label>
                                                    <input type="text" class="form-control" id="edit_customer_name" name="customer_name" value="<?php echo htmlspecialchars($customer['customer_name']); ?>" required>
                                                </div>
                                                <div class="form-group">
                                                    <label for="edit_contact_number">Contact Number</label>
                                                    <input type="text" class="form-control" id="edit_contact_number" name="contact_number" value="<?php echo htmlspecialchars($customer['contact_number']); ?>" required>
                                                </div>
                                                <div class="form-group">
                                                    <label for="edit_address">Address</label>
                                                    <textarea class="form-control" id="edit_address" name="address" rows="3" required><?php echo htmlspecialchars($customer['address']); ?></textarea>
                                                </div>
                                                <div class="form-group">
                                                    <label for="edit_email">Email</label>
                                                    <input type="email" class="form-control" id="edit_email" name="email" value="<?php echo htmlspecialchars($customer['email']); ?>" required>
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
                                            <?php if ($customer_details): ?>
                                                <p><strong>Customer Name:</strong> <?php echo htmlspecialchars($customer_details['customer_name']); ?></p>
                                                <p><strong>Contact Number:</strong> <?php echo htmlspecialchars($customer_details['contact_number']); ?></p>
                                                <p><strong>Address:</strong> <?php echo htmlspecialchars($customer_details['address']); ?></p>
                                                <p><strong>Email:</strong> <?php echo htmlspecialchars($customer_details['email']); ?></p>
                                            <?php else: ?>
                                                <p>No details available for this customer.</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
// Include footer
include('../includes/sales/footer.php');
?>
