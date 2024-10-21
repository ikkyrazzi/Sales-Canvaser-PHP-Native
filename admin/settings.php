<?php
// Start session and ensure user is an admin
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Connect to the database
include('../db.php');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email_host = $_POST['email_host'];
    $email_port = $_POST['email_port'];
    $email_username = $_POST['email_username'];
    $email_password = $_POST['email_password'];
    $security_key = $_POST['security_key'];

    // Update settings in the database
    $sql = "UPDATE settings SET email_host = ?, email_port = ?, email_username = ?, email_password = ?, security_key = ? WHERE id = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $email_host, $email_port, $email_username, $email_password, $security_key);
    $stmt->execute();
    $stmt->close();

    $success_message = "Settings updated successfully.";
}

// Fetch current settings
$sql = "SELECT email_host, email_port, email_username, email_password, security_key FROM settings WHERE id = 1";
$result = $conn->query($sql);

// Check if the query was successful
if ($result) {
    $settings = $result->fetch_assoc();
} else {
    $settings = null; // Ensure $settings is defined
}

// Include header and sidebar
include('../includes/admin/header.php');
include('../includes/admin/sidebar.php');
?>

<!-- Content -->
<div class="container-fluid">
    <!-- Page Heading -->
    <h1 class="h3 mb-4 text-gray-800">System Settings</h1>

    <!-- Success Message -->
    <?php if (isset($success_message)): ?>
    <div class="alert alert-success" role="alert">
        <?php echo htmlspecialchars($success_message); ?>
    </div>
    <?php endif; ?>

    <!-- Settings Form -->
    <form method="POST" action="settings.php">
        <div class="form-group">
            <label for="email_host">Email Host</label>
            <input type="text" class="form-control" id="email_host" name="email_host" value="<?php echo isset($settings['email_host']) ? htmlspecialchars($settings['email_host']) : ''; ?>" required>
        </div>
        <div class="form-group">
            <label for="email_port">Email Port</label>
            <input type="number" class="form-control" id="email_port" name="email_port" value="<?php echo isset($settings['email_port']) ? htmlspecialchars($settings['email_port']) : ''; ?>" required>
        </div>
        <div class="form-group">
            <label for="email_username">Email Username</label>
            <input type="text" class="form-control" id="email_username" name="email_username" value="<?php echo isset($settings['email_username']) ? htmlspecialchars($settings['email_username']) : ''; ?>" required>
        </div>
        <div class="form-group">
            <label for="email_password">Email Password</label>
            <input type="password" class="form-control" id="email_password" name="email_password" value="<?php echo isset($settings['email_password']) ? htmlspecialchars($settings['email_password']) : ''; ?>" required>
        </div>
        <div class="form-group">
            <label for="security_key">Security Key</label>
            <input type="text" class="form-control" id="security_key" name="security_key" value="<?php echo isset($settings['security_key']) ? htmlspecialchars($settings['security_key']) : ''; ?>" required>
        </div>
        <button type="submit" class="btn btn-primary">Save Changes</button>
    </form>
</div>

<!-- Footer -->
<?php include('../includes/admin/footer.php'); ?>
