<?php
// Start session and ensure user is an sales
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'sales') {
    header("Location: login.php");
    exit();
}

// Connect to the database
include('../db.php');

// Include header and sidebar
include('../includes/sales/header.php');
include('../includes/sales/sidebar.php');

// Fetch user details
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo "<div class='alert alert-danger'>User not found.</div>";
    exit();
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check if name and email keys exist in $_POST
    $name = isset($_POST['name']) ? $_POST['name'] : $user['name'];
    $email = isset($_POST['email']) ? $_POST['email'] : $user['email'];
    $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : $user['password'];

    // Handle photo upload
    $photo = $user['photo']; // Set to existing photo by default
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $target_dir = "../uploads/";
        $imageFileType = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($imageFileType, $allowed_types)) {
            // Generate a unique file name
            $unique_filename = uniqid('photo_', true) . '.' . $imageFileType;
            $target_file = $target_dir . $unique_filename;

            if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_file)) {
                $photo = $unique_filename;
            } else {
                echo "<div class='alert alert-danger'>Error uploading photo.</div>";
            }
        } else {
            echo "<div class='alert alert-danger'>Only JPG, JPEG, PNG & GIF files are allowed.</div>";
        }
    }

    $update_sql = "UPDATE users SET name = ?, email = ?, password = ?, photo = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param('ssssi', $name, $email, $password, $photo, $user_id);

    if ($update_stmt->execute()) {
        echo "<div class='alert alert-success'>Profile updated successfully.</div>";
        // Refresh user details after update
        $sql = "SELECT * FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
    } else {
        echo "<div class='alert alert-danger'>Error updating profile.</div>";
    }
}
?>

<!-- Content -->
<div class="container-fluid">
    <!-- Page Heading -->
    <h1 class="h3 mb-4 text-gray-800">Sales Profile</h1>

    <div class="row">
        <!-- Profile Information -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Profile Information</h5>
                </div>
                <div class="card-body text-center">
                    <?php
                    // Display profile picture if available, otherwise show default picture
                    $profile_picture = !empty($user['photo']) ? '../uploads/' . htmlspecialchars($user['photo']) : '../uploads/sales/default.png';
                    ?>
                    <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile Picture" class="img-fluid mb-3" style="width: 150px; height: 150px; object-fit: cover;">

                    <!-- Edit photo button below the profile picture -->
                    <br>
                    <button class="btn btn-primary" data-toggle="modal" data-target="#photoModal">
                        Edit Photo
                    </button>

                    <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($user['name']); ?></p>
                    <p><strong>Role:</strong> <?php echo htmlspecialchars($user['role']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                </div>
            </div>
        </div>

        <!-- Profile Edit Form -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Edit Profile</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="profile.php">
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email address</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Update Profile</button>
                    </form>
                    <form method="POST" action="profile.php" class="mt-3">
                        <div class="form-group">
                            <label for="password">New Password</label>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Leave blank to keep current password">
                        </div>
                        <button type="submit" class="btn btn-warning">Update Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Photo Upload Modal -->
<div class="modal fade" id="photoModal" tabindex="-1" role="dialog" aria-labelledby="photoModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="photoModalLabel">Edit Profile Photo</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="profile.php" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="photo">Select a new profile photo</label>
                        <input type="file" class="form-control" id="photo" name="photo" required onchange="previewPhoto(this);">
                    </div>
                    <!-- Preview of the new photo -->
                    <div class="form-group text-center">
                        <img id="photoPreview" src="" alt="New Profile Picture" class="img-fluid" style="width: 150px; height: 150px; object-fit: cover; display: none;">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JavaScript untuk Pratinjau Gambar Baru -->
<script>
function previewPhoto(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('photoPreview').src = e.target.result;
            document.getElementById('photoPreview').style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<!-- Footer -->
<?php include('../includes/sales/footer.php'); ?>
