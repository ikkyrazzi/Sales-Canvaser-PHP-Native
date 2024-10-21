<?php
// Start session and ensure user is an admin
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Connect to the database
include('../db.php');

// Handle user addition
if (isset($_POST['add_user'])) {
    $username = $_POST['username'];
    $plain_password = $_POST['password'];  // Plain text password
    $password = password_hash($plain_password, PASSWORD_DEFAULT);  // Hashed password
    $name = $_POST['name'];
    $role = $_POST['role'];
    $email = $_POST['email'];

    // Insert user data with plain password
    $sql = "INSERT INTO users (username, plain_password, password, name, role, email) 
            VALUES ('$username', '$plain_password', '$password', '$name', '$role', '$email')";
    if ($conn->query($sql)) {
        $message = "User added successfully.";
    } else {
        $message = "Error adding user: " . $conn->error;
    }
}

// Handle user update
if (isset($_POST['edit_user'])) {
    $user_id = $_POST['user_id'];
    $name = $_POST['name'];
    $role = $_POST['role'];
    $email = $_POST['email'];

    $sql = "UPDATE users SET name='$name', role='$role', email='$email' WHERE id='$user_id'";
    if ($conn->query($sql)) {
        $message = "User updated successfully.";
    } else {
        $message = "Error updating user: " . $conn->error;
    }
}

// Handle user password change
if (isset($_POST['change_password'])) {
    $user_id = $_POST['user_id'];
    $new_plain_password = $_POST['new_password'];  // New plain text password
    $new_password = password_hash($new_plain_password, PASSWORD_DEFAULT);  // Hashed password

    // Update both plain and hashed password
    $sql = "UPDATE users SET plain_password='$new_plain_password', password='$new_password' WHERE id='$user_id'";
    if ($conn->query($sql)) {
        $message = "Password changed successfully.";
    } else {
        $message = "Error changing password: " . $conn->error;
    }
}

// Handle user deletion
if (isset($_GET['delete'])) {
    $user_id = $_GET['delete'];

    $sql = "DELETE FROM users WHERE id='$user_id'";
    if ($conn->query($sql)) {
        $message = "User deleted successfully.";
    } else {
        $message = "Error deleting user: " . $conn->error;
    }
}

// Fetch all users
$users_query = "SELECT id, username, name, role, email, plain_password FROM users";
$users_result = $conn->query($users_query);
$users = [];
if ($users_result && $users_result->num_rows > 0) {
    while ($user_row = $users_result->fetch_assoc()) {
        $users[] = $user_row;
    }
}

// Include header and sidebar
include('../includes/admin/header.php');
include('../includes/admin/sidebar.php');
?>

<!-- Content -->
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Manage Users</h1>

    <?php if (isset($message)): ?>
        <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <!-- Add User Button -->
    <button class="btn btn-primary mb-3" data-toggle="modal" data-target="#addUserModal">Add New User</button>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New User</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="manage_users.php">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="role">Role</label>
                            <select class="form-control" id="role" name="role" required>
                                <option value="sales">Sales</option>
                                <option value="admin">Admin</option>
                                <option value="supervisor">Supervisor</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <button type="submit" class="btn btn-primary" name="add_user">Add User</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card">
        <div class="card-header">
            Users List
        </div>
        <div class="card-body">
            <table class="table table-striped table-bordered" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Name</th>
                        <th>Role</th>
                        <th>Email</th>
                        <th>Password</th>
                        
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                            <td><?php echo htmlspecialchars($user['role']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password<?php echo $user['id']; ?>" value="<?php echo htmlspecialchars($user['plain_password']); ?>" readonly>
                                    <div class="input-group-append">
                                        <button type="button" onclick="togglePasswordVisibility('password<?php echo $user['id']; ?>')" class="btn btn-info btn-sm">Show</button>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <a href="#" class="btn btn-warning btn-sm" data-toggle="modal" data-target="#editUserModal<?php echo $user['id']; ?>">Edit</a>
                                <a href="?delete=<?php echo $user['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</a>
                                <!-- Change Password Button -->
                                <a href="#" class="btn btn-info btn-sm" data-toggle="modal" data-target="#changePasswordModal<?php echo $user['id']; ?>">Change Password</a>
                            </td>
                        </tr>

                        <!-- Edit User Modal -->
                        <div class="modal fade" id="editUserModal<?php echo $user['id']; ?>" tabindex="-1" role="dialog">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit User</h5>
                                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                                    </div>
                                    <div class="modal-body">
                                        <form method="POST" action="manage_users.php">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <div class="form-group">
                                                <label for="name">Name</label>
                                                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label for="role">Role</label>
                                                <select class="form-control" id="role" name="role" required>
                                                    <option value="sales" <?php echo ($user['role'] == 'sales') ? 'selected' : ''; ?>>Sales</option>
                                                    <option value="admin" <?php echo ($user['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                                                    <option value="supervisor" <?php echo ($user['role'] == 'supervisor') ? 'selected' : ''; ?>>Supervisor</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label for="email">Email</label>
                                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                            </div>
                                            <button type="submit" class="btn btn-primary" name="edit_user">Update User</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Change Password Modal -->
                        <div class="modal fade" id="changePasswordModal<?php echo $user['id']; ?>" tabindex="-1" role="dialog">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Change Password for <?php echo htmlspecialchars($user['username']); ?></h5>
                                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                                    </div>
                                    <div class="modal-body">
                                        <form method="POST" action="manage_users.php">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <div class="form-group">
                                                <label for="new_password">New Password</label>
                                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                                            </div>
                                            <button type="submit" class="btn btn-info" name="change_password">Change Password</button>
                                        </form>
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

<script>
function togglePasswordVisibility(passwordId) {
    var passwordField = document.getElementById(passwordId);
    var currentType = passwordField.type;
    if (currentType === 'password') {
        passwordField.type = 'text';
    } else {
        passwordField.type = 'password';
    }
}
</script>

<style>
.input-group {
    display: flex;
    align-items: center;
}

.input-group .form-control {
    margin-right: 5px;
    flex: 1;
}
</style>

<?php
// Include footer
include('../includes/admin/footer.php');
?>
