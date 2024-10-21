<?php
// Mengambil nama sales dari database berdasarkan user_id
$user_id = $_SESSION['user_id'];
$sql = "SELECT name, photo FROM users WHERE id = '$user_id'";
$result = $conn->query($sql);
$name = "Sales";
$photo = "default.png"; // Default value jika tidak ditemukan

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $name = $row['name'];
    $photo = !empty($row['photo']) ? '../uploads/' . htmlspecialchars($row['photo']) : '../uploads/sales/default.png';
}
?>

<!-- Sidebar -->
<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

    <!-- Sidebar - Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="dashboard_sales.php">
        <div class="sidebar-brand-icon rotate-n-15">
            <i class="fas fa-solid fa-universal-access"></i>
        </div>
        <div class="sidebar-brand-text mx-3">Sales Dashboard</div>
    </a>

    <!-- Divider -->
    <hr class="sidebar-divider my-0">

    <!-- Heading -->
    <div class="sidebar-heading mt-1">
        Dashboard
    </div>

    <!-- Nav Item - Dashboard -->
    <li class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard_sales.php') ? 'active' : ''; ?> mb-1">
        <a class="nav-link" href="dashboard_sales.php">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Nav Item - Products -->
    <li class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'view_products.php') ? 'active' : ''; ?> mb-1">
        <a class="nav-link" href="view_products.php">
            <i class="fas fa-box"></i>
            <span>Products</span>
        </a>
    </li>

    <!-- Nav Item - Customers -->
    <li class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'view_customers.php') ? 'active' : ''; ?> mb-1">
        <a class="nav-link" href="view_customers.php">
            <i class="fas fa-users"></i>
            <span>Customers</span>
        </a>
    </li>

    <!-- Nav Item - Orders -->
    <li class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'view_orders.php') ? 'active' : ''; ?> mb-1">
        <a class="nav-link" href="view_orders.php">
            <i class="fas fa-shopping-cart"></i>
            <span>Orders</span>
        </a>
    </li>

    <!-- Nav Item - Sales Report -->
    <li class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'view_sales_reports.php') ? 'active' : ''; ?> mb-1">
        <a class="nav-link" href="view_sales_reports.php">
            <i class="fas fa-chart-line"></i>
            <span>Sales Reports</span>
        </a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Nav Item - Logout -->
    <li class="nav-item">
        <a class="nav-link" href="#" data-toggle="modal" data-target="#logoutModal">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Sidebar Toggler (Sidebar) -->
    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>

</ul>


<!-- Content Wrapper -->
<div id="content-wrapper" class="d-flex flex-column">

    <!-- Main Content -->
    <div id="content">

        <!-- Topbar -->
        <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
            <!-- Topbar Navbar -->
            <ul class="navbar-nav ml-auto">
                <!-- Nav Item - User Information -->
                <li class="nav-item dropdown no-arrow">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <span class="mr-2 d-none d-lg-inline text-gray-600 small">Welcome, <b> <?php echo htmlspecialchars($name); ?> :)</b></span>
                        <img class="img-profile rounded-circle" 
                            src="../uploads/<?php echo htmlspecialchars($photo); ?>" 
                            alt="Profile Picture" 
                            onerror="this.onerror=null; this.src='uploads/sales/default.png';">
                    </a>
                    <!-- Dropdown - User Information -->
                    <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                        <a class="dropdown-item" href="profile.php">
                            <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                            Profile
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal">
                            <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                            Logout
                        </a>
                    </div>
                </li>
            </ul>
        </nav>
        <!-- End of Topbar -->
