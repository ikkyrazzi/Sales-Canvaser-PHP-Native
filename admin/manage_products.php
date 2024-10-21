<?php
// Start session and ensure user is an admin
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Connect to the database
include('../db.php');

// Handle product addition
if (isset($_POST['add_product'])) {
    $product_name = $_POST['product_name'];
    $price = $_POST['price'];
    $description = $_POST['description'];
    $stock = $_POST['stock'];
    $category_id = $_POST['category_id']; // New field

    $sql = "INSERT INTO products (product_name, price, description, stock, category_id) 
            VALUES ('$product_name', '$price', '$description', '$stock', '$category_id')";
    if ($conn->query($sql)) {
        $message = "Product added successfully.";
    } else {
        $message = "Error adding product: " . $conn->error;
    }
}

// Handle product update
if (isset($_POST['edit_product'])) {
    $product_id = $_POST['product_id'];
    $product_name = $_POST['product_name'];
    $price = $_POST['price'];
    $description = $_POST['description'];
    $stock = $_POST['stock'];
    $category_id = $_POST['category_id']; // New field

    $sql = "UPDATE products SET product_name='$product_name', price='$price', description='$description', 
            stock='$stock', category_id='$category_id' WHERE id='$product_id'";
    if ($conn->query($sql)) {
        $message = "Product updated successfully.";
    } else {
        $message = "Error updating product: " . $conn->error;
    }
}

// Handle product deletion
if (isset($_GET['delete'])) {
    $product_id = $_GET['delete'];

    $sql = "DELETE FROM products WHERE id='$product_id'";
    if ($conn->query($sql)) {
        $message = "Product deleted successfully.";
    } else {
        $message = "Error deleting product: " . $conn->error;
    }
}

// Fetch all products
$products_query = "SELECT id, product_name, price, description, stock, category_id FROM products";
$products_result = $conn->query($products_query);
$products = [];
if ($products_result && $products_result->num_rows > 0) {
    while ($product_row = $products_result->fetch_assoc()) {
        $products[] = $product_row;
    }
}

// Fetch product details if modal is triggered
$product_details = null;
if (isset($_GET['details'])) {
    $product_id = $_GET['details'];
    $details_query = "SELECT * FROM products WHERE id='$product_id'";
    $details_result = $conn->query($details_query);
    if ($details_result && $details_result->num_rows > 0) {
        $product_details = $details_result->fetch_assoc();
    }
}

// Fetch categories for dropdown
$categories_query = "SELECT id, category_name FROM categories";
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
    <h1 class="h3 mb-4 text-gray-800">Manage Products</h1>

    <?php if (isset($message)): ?>
        <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <!-- Add Product Button -->
    <button class="btn btn-primary mb-3" data-toggle="modal" data-target="#addProductModal">Add New Product</button>

    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1" role="dialog" aria-labelledby="addProductModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addProductModalLabel">Add New Product</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="manage_products.php">
                        <div class="form-group">
                            <label for="product_name">Product Name</label>
                            <input type="text" class="form-control" id="product_name" name="product_name" required>
                        </div>
                        <div class="form-group">
                            <label for="price">Price</label>
                            <input type="number" class="form-control" id="price" name="price" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="stock">Stock</label>
                            <input type="number" class="form-control" id="stock" name="stock" required>
                        </div>
                        <div class="form-group">
                            <label for="category_id">Category</label>
                            <select class="form-control" id="category_id" name="category_id" required>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['category_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary" name="add_product">Add Product</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Products Table -->
    <div class="card">
        <div class="card-header">
            Products List
        </div>
        <div class="card-body">
            <table class="table table-striped table-bordered" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th><a href="#" id="sort-product" data-order="asc">Product Name</th>
                        <th><a href="#" id="sort-price" data-order="asc">Price</th>
                        <th>Description</th>
                        <th><a href="#" id="sort-stock" data-order="asc">Stock</th>
                        <th><a href="#" id="sort-category" data-order="asc">Category</th> <!-- Added Category Column -->
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                            <td><?php echo htmlspecialchars($product['price']); ?></td>
                            <td><?php echo htmlspecialchars($product['description']); ?></td>
                            <td><?php echo htmlspecialchars($product['stock']); ?></td>
                            <td>
                                <?php
                                // Fetch category name
                                $category_query = "SELECT category_name FROM categories WHERE id='" . $product['category_id'] . "'";
                                $category_result = $conn->query($category_query);
                                if ($category_result && $category_result->num_rows > 0) {
                                    $category_row = $category_result->fetch_assoc();
                                    echo htmlspecialchars($category_row['category_name']);
                                } else {
                                    echo "Unknown";
                                }
                                ?>
                            </td>
                            <td>
                                <!-- Edit Button -->
                                <a href="#" class="btn btn-warning btn-sm" data-toggle="modal" data-target="#editProductModal<?php echo $product['id']; ?>">Edit</a>
                                <!-- Detail Button -->
                                <a href="#" class="btn btn-info btn-sm" data-toggle="modal" data-target="#detailProductModal<?php echo $product['id']; ?>">Detail</a>
                                <!-- Delete Button -->
                                <a href="?delete=<?php echo $product['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this product?')">Delete</a>
                            </td>
                        </tr>

                        <!-- Edit Product Modal -->
                        <div class="modal fade" id="editProductModal<?php echo $product['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="editProductModalLabel" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="editProductModalLabel">Edit Product</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <form method="POST" action="manage_products.php">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <div class="form-group">
                                                <label for="product_name">Product Name</label>
                                                <input type="text" class="form-control" name="product_name" value="<?php echo htmlspecialchars($product['product_name']); ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label for="price">Price</label>
                                                <input type="number" class="form-control" name="price" value="<?php echo htmlspecialchars($product['price']); ?>" step="0.01" required>
                                            </div>
                                            <div class="form-group">
                                                <label for="description">Description</label>
                                                <textarea class="form-control" name="description" rows="3" required><?php echo htmlspecialchars($product['description']); ?></textarea>
                                            </div>
                                            <div class="form-group">
                                                <label for="stock">Stock</label>
                                                <input type="number" class="form-control" name="stock" value="<?php echo htmlspecialchars($product['stock']); ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label for="category_id">Category</label>
                                                <select class="form-control" name="category_id" required>
                                                    <?php foreach ($categories as $category): ?>
                                                        <option value="<?php echo $category['id']; ?>" <?php if ($product['category_id'] == $category['id']) echo 'selected'; ?>>
                                                            <?php echo htmlspecialchars($category['category_name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <button type="submit" class="btn btn-primary" name="edit_product">Update Product</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Detail Product Modal -->
                        <div class="modal fade" id="detailProductModal<?php echo $product['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="detailProductModalLabel" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="detailProductModalLabel">Product Details</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <p><strong>Product Name:</strong> <?php echo htmlspecialchars($product['product_name']); ?></p>
                                        <p><strong>Price:</strong> <?php echo htmlspecialchars($product['price']); ?></p>
                                        <p><strong>Description:</strong> <?php echo htmlspecialchars($product['description']); ?></p>
                                        <p><strong>Stock:</strong> <?php echo htmlspecialchars($product['stock']); ?></p>
                                        <p><strong>Category:</strong>
                                            <?php
                                            $category_query = "SELECT category_name FROM categories WHERE id='" . $product['category_id'] . "'";
                                            $category_result = $conn->query($category_query);
                                            if ($category_result && $category_result->num_rows > 0) {
                                                $category_row = $category_result->fetch_assoc();
                                                echo htmlspecialchars($category_row['category_name']);
                                            } else {
                                                echo "Unknown";
                                            }
                                            ?>
                                        </p>
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

<script>
document.addEventListener('DOMContentLoaded', () => {
    const sortTable = (columnIndex, order) => {
        const table = document.querySelector('table tbody');
        const rows = Array.from(table.querySelectorAll('tr'));

        rows.sort((rowA, rowB) => {
            const cellA = rowA.children[columnIndex].textContent.trim();
            const cellB = rowB.children[columnIndex].textContent.trim();

            if (order === 'asc') {
                return cellA.localeCompare(cellB);
            } else {
                return cellB.localeCompare(cellA);
            }
        });

        rows.forEach(row => table.appendChild(row));
    };

    document.getElementById('sort-product').addEventListener('click', (e) => {
        e.preventDefault();
        const order = e.target.dataset.order;
        sortTable(0, order);
        e.target.dataset.order = order === 'asc' ? 'desc' : 'asc';
    });

    document.getElementById('sort-price').addEventListener('click', (e) => {
        e.preventDefault();
        const order = e.target.dataset.order;
        sortTable(1, order);
        e.target.dataset.order = order === 'asc' ? 'desc' : 'asc';
    });

    document.getElementById('sort-stock').addEventListener('click', (e) => {
        e.preventDefault();
        const order = e.target.dataset.order;
        sortTable(3, order);
        e.target.dataset.order = order === 'asc' ? 'desc' : 'asc';
    });

    document.getElementById('sort-category').addEventListener('click', (e) => {
        e.preventDefault();
        const order = e.target.dataset.order;
        sortTable(3, order);
        e.target.dataset.order = order === 'asc' ? 'desc' : 'asc';
    });
});
</script>
