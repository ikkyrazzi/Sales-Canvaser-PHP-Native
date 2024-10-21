<?php
// Mengambil nama admin dari database berdasarkan user_id
$user_id = $_SESSION['user_id'];
$sql = "SELECT name, photo FROM users WHERE id = '$user_id'";
$result = $conn->query($sql);
$name = "Admin";
$photo = "default.png"; // Default value jika tidak ditemukan

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $name = $row['name'];
    $photo = !empty($row['photo']) ? '../uploads/' . htmlspecialchars($row['photo']) : '../uploads/admin/default.png';
}
?>

<!-- Sidebar -->
<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

    <!-- Sidebar - Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="dashboard_admin.php">
        <div class="sidebar-brand-icon rotate-n-15">
            <i class="fas fa-solid fa-universal-access"></i>
        </div>
        <div class="sidebar-brand-text mx-3">Admin Dashboard</div>
    </a>

    <!-- Divider -->
    <hr class="sidebar-divider my-0">

    <!-- Heading -->
    <div class="sidebar-heading mt-1">
        Dashboard Admin
    </div>

    <!-- Nav Item - Dashboard -->
    <li class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard_admin.php') ? 'active' : ''; ?> mb-1">
        <a class="nav-link" href="dashboard_admin.php">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
    </li>

    <!-- Nav Item - Manage Users -->
    <li class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'manage_users.php') ? 'active' : ''; ?> mb-1">
        <a class="nav-link" href="manage_users.php">
            <i class="fas fa-fw fa-users"></i>
            <span>Manajemen Pengguna</span>
        </a>
    </li>

    <!-- Nav Item - Manage Products -->
    <li class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'manage_products.php') ? 'active' : ''; ?> mb-1">
        <a class="nav-link" href="manage_products.php">
            <i class="fas fa-fw fa-box"></i>
            <span>Manajemen Produk</span>
        </a>
    </li>

    <!-- Nav Item - Manage Categories -->
    <li class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'manage_categories.php') ? 'active' : ''; ?> mb-1">
        <a class="nav-link" href="manage_categories.php">
            <i class="fas fa-fw fa-tags"></i>
            <span>Manajemen Kategori</span>
        </a>
    </li>

    <!-- Nav Item - Manage Customers -->
    <li class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'manage_customers.php') ? 'active' : ''; ?> mb-1">
        <a class="nav-link" href="manage_customers.php">
            <i class="fas fa-fw fa-users"></i>
            <span>Manajemen Pelanggan</span>
        </a>
    </li>

    <!-- Nav Item - Manage Orders -->
    <li class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'manage_orders.php') ? 'active' : ''; ?> mb-1">
        <a class="nav-link" href="manage_orders.php">
            <i class="fas fa-fw fa-shopping-cart"></i>
            <span>Manajemen Pesanan</span>
        </a>
    </li>

    <!-- Nav Item - View Sales Reports -->
    <li class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'view_sales_reports.php') ? 'active' : ''; ?> mb-1">
        <a class="nav-link" href="view_sales_reports.php">
            <i class="fas fa-fw fa-chart-line"></i>
            <span>Laporan Penjualan</span>
        </a>
    </li>

    <!-- Nav Item - Customer Reports -->
    <li class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'report_customers.php') ? 'active' : ''; ?> mb-1">
        <a class="nav-link" href="report_customers.php">
            <i class="fas fa-fw fa-users"></i>
            <span>Laporan Pelanggan</span>
        </a>
    </li>

    <!-- Nav Item - Sales Reports -->
    <li class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'report_sales.php') ? 'active' : ''; ?> mb-1">
        <a class="nav-link" href="report_sales.php">
            <i class="fas fa-fw fa-chart-line"></i>
            <span>Laporan Sales</span>
        </a>
    </li>

    <!-- Nav Item - Product Reports -->
    <li class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'report_products.php') ? 'active' : ''; ?> mb-1">
        <a class="nav-link" href="report_products.php">
            <i class="fas fa-fw fa-box"></i>
            <span>Laporan Produk</span>
        </a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Nav Item - Settings -->
    <li class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'settings.php') ? 'active' : ''; ?> mb-1">
        <a class="nav-link" href="settings.php">
            <i class="fas fa-fw fa-cogs"></i>
            <span>Pengaturan</span>
        </a>
    </li>

    <!-- Nav Item - Logout -->
    <li class="nav-item">
        <a class="nav-link" href="#" data-toggle="modal" data-target="#logoutModal">
            <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
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
                        <span class="mr-2 d-none d-lg-inline text-gray-600 small">Selamat Datang, <b><?php echo htmlspecialchars($name); ?> :)</b></span>
                        <img class="img-profile rounded-circle" 
                            src="../uploads/<?php echo htmlspecialchars($photo); ?>" 
                            alt="Profile Picture" 
                            onerror="this.onerror=null; this.src='uploads/admin/default.png';">
                    </a>                    
                    <!-- Dropdown - User Information -->
                    <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                        <a class="dropdown-item" href="profile.php">
                            <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                            Profil
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
