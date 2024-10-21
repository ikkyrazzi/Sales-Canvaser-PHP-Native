<?php
// Start session and ensure user is logged in as sales
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

// Initialize variables
$search_name = isset($_GET['search_name']) ? $_GET['search_name'] : '';
$search_category = isset($_GET['search_category']) ? $_GET['search_category'] : '';
$products = array();

// Fetch categories for filtering
$category_query = "SELECT id, category_name FROM categories";
$category_result = $conn->query($category_query);
if ($category_result && $category_result->num_rows > 0) {
    $categories = $category_result->fetch_all(MYSQLI_ASSOC);
}

// Build query for product search
$query = "SELECT p.id, p.product_name, p.description, p.price, p.stock, c.category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE 1=1";

if (!empty($search_name)) {
    $query .= " AND p.product_name LIKE '%" . $conn->real_escape_string($search_name) . "%'";
}

if (!empty($search_category)) {
    $query .= " AND c.category_name LIKE '%" . $conn->real_escape_string($search_category) . "%'";
}

$query .= " ORDER BY p.product_name ASC";

$result = $conn->query($query);
if ($result && $result->num_rows > 0) {
    $products = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<!-- Content -->
<div class="container-fluid">
    <!-- Page Heading -->
    <h1 class="h3 mb-4 text-gray-800">View Products</h1>

    <!-- Search Form -->
    <form action="view_products.php" method="GET" class="mb-4">
        <div class="row">
            <div class="col-md-4 mb-3">
                <input type="text" name="search_name" class="form-control" placeholder="Search by product name" value="<?php echo htmlspecialchars($search_name); ?>">
            </div>
            <div class="col-md-4 mb-3">
                <input type="text" name="search_category" class="form-control" placeholder="Search by category" value="<?php echo htmlspecialchars($search_category); ?>">
            </div>
            <div class="col-md-4 mb-3">
                <button type="submit" class="btn btn-primary w-100">Search</button>
            </div>
        </div>
    </form>

    <!-- Products Table -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Product Name</th>
                            <th>Description</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Category</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="6" class="text-center">No products found</td>
                            </tr>
                        <?php else: ?>
                            <?php $no = 1; ?>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                    <td><?php echo htmlspecialchars($product['description']); ?></td>
                                    <td>Rp <?php echo number_format($product['price'], 2, ',', '.'); ?></td>
                                    <td><?php echo htmlspecialchars($product['stock']); ?></td>
                                    <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Footer -->
<?php include('../includes/sales/footer.php'); ?>
