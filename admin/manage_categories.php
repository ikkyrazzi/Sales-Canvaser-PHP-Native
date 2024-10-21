<?php
// Start session and ensure user is an admin
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Connect to the database
include('../db.php');

// Handle category addition
if (isset($_POST['add_category'])) {
    $category_name = trim($_POST['category_name']);
    $description = trim($_POST['description']);

    $stmt = $conn->prepare("INSERT INTO categories (category_name, description) VALUES (?, ?)");
    $stmt->bind_param('ss', $category_name, $description);
    if ($stmt->execute()) {
        $message = "Category added successfully.";
    } else {
        $message = "Error adding category: " . htmlspecialchars($conn->error);
    }
    $stmt->close();
}

// Handle category update
if (isset($_POST['edit_category'])) {
    $category_id = $_POST['category_id'];
    $category_name = trim($_POST['category_name']);
    $description = trim($_POST['description']);

    $stmt = $conn->prepare("UPDATE categories SET category_name=?, description=? WHERE id=?");
    $stmt->bind_param('ssi', $category_name, $description, $category_id);
    if ($stmt->execute()) {
        $message = "Category updated successfully.";
    } else {
        $message = "Error updating category: " . htmlspecialchars($conn->error);
    }
    $stmt->close();
}

// Handle category deletion
if (isset($_GET['delete'])) {
    $category_id = $_GET['delete'];

    $stmt = $conn->prepare("DELETE FROM categories WHERE id=?");
    $stmt->bind_param('i', $category_id);
    if ($stmt->execute()) {
        $message = "Category deleted successfully.";
    } else {
        $message = "Error deleting category: " . htmlspecialchars($conn->error);
    }
    $stmt->close();
}

// Fetch all categories
$categories_query = "SELECT id, category_name, description, created_at, updated_at FROM categories";
$categories_result = $conn->query($categories_query);
$categories = [];
if ($categories_result && $categories_result->num_rows > 0) {
    while ($category_row = $categories_result->fetch_assoc()) {
        $categories[] = $category_row;
    }
}

// Include header and sidebar
include('../includes/admin/header.php');
include('../includes/admin/sidebar.php');
?>

<!-- Content -->
<div class="container-fluid">
    <!-- Page Heading -->
    <h1 class="h3 mb-4 text-gray-800">Manage Categories</h1>

    <?php if (isset($message)): ?>
        <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <!-- Add Category Button -->
    <button class="btn btn-primary mb-3" data-toggle="modal" data-target="#addCategoryModal">Add New Category</button>

    <!-- Add Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1" role="dialog" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCategoryModalLabel">Add New Category</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="manage_categories.php">
                        <div class="form-group">
                            <label for="category_name">Category Name</label>
                            <input type="text" class="form-control" id="category_name" name="category_name" required>
                        </div>
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary" name="add_category">Add Category</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Categories Table -->
    <div class="card">
        <div class="card-header">
            Categories List
        </div>
        <div class="card-body">
                <table class="table table-striped table-bordered">
            <thead>
                    <tr>
                        <th>No</th>
                        <th>Category Name</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $counter = 1; ?>
                    <?php foreach ($categories as $category): ?>
                        <tr>
                            <td><?php echo $counter++; ?></td>
                            <td><?php echo htmlspecialchars($category['category_name']); ?></td>
                            <td><?php echo htmlspecialchars($category['description']); ?></td>
                            <td>
                                <!-- Edit Button -->
                                <a href="#" class="btn btn-warning btn-sm" data-toggle="modal" data-target="#editCategoryModal<?php echo $category['id']; ?>">Edit</a>
                                <!-- Detail Button -->
                                <a href="#" class="btn btn-info btn-sm" data-toggle="modal" data-target="#detailCategoryModal<?php echo $category['id']; ?>">Detail</a>
                                <!-- Delete Button -->
                                <a href="?delete=<?php echo $category['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this category?')">Delete</a>
                            </td>
                        </tr>

                        <!-- Edit Category Modal -->
                        <div class="modal fade" id="editCategoryModal<?php echo $category['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="editCategoryModalLabel">Edit Category</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <form method="POST" action="manage_categories.php">
                                            <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                            <div class="form-group">
                                                <label for="edit_category_name">Category Name</label>
                                                <input type="text" class="form-control" id="edit_category_name" name="category_name" value="<?php echo htmlspecialchars($category['category_name']); ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label for="edit_description">Description</label>
                                                <textarea class="form-control" id="edit_description" name="description" rows="3" required><?php echo htmlspecialchars($category['description']); ?></textarea>
                                            </div>
                                            <button type="submit" class="btn btn-primary" name="edit_category">Save Changes</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Category Detail Modal -->
                        <div class="modal fade" id="detailCategoryModal<?php echo $category['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="detailCategoryModalLabel" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="detailCategoryModalLabel">Category Details</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <p><strong>Category Name:</strong> <?php echo htmlspecialchars($category['category_name']); ?></p>
                                        <p><strong>Description:</strong> <?php echo htmlspecialchars($category['description']); ?></p>
                                        <p><strong>Created At:</strong> <?php echo htmlspecialchars($category['created_at']); ?></p>
                                        <p><strong>Updated At:</strong> <?php echo htmlspecialchars($category['updated_at']); ?></p>
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

<?php
// Include footer
include('../includes/admin/footer.php');
?>
