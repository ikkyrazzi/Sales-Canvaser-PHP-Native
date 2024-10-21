<?php
session_start(); // Pastikan session_start() dipanggil di bagian atas

include('db.php');

$error = ''; // Inisialisasi variabel error

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Gunakan prepared statement untuk mencegah SQL Injection
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            // Set session berdasarkan data user
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['role'] = $row['role'];

            // Redirect berdasarkan peran user
            if ($row['role'] === 'admin') {
                header("Location: admin/dashboard_admin.php");
                exit();
            } elseif ($row['role'] === 'sales') {
                header("Location: sales/dashboard_sales.php");
                exit();
            } elseif ($row['role'] === 'supervisor') {
                header("Location: supervisor/dashboard_supervisor.php");
                exit();
            }
        } else {
            $error = "<div class='alert alert-danger' role='alert'>Password yang Anda masukkan salah</div>";
        }
    } else {
        $error = "<div class='alert alert-danger' role='alert'>Akun tidak ditemukan</div>";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="assets/bootstrap/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/dist/css/AdminLTE.min.css">
    <link rel="stylesheet" href="assets/plugins/iCheck/square/blue.css">
    <style>
        .login-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: 10%;
        }
        .login-buttons {
            display: flex;
            justify-content: space-between;
        }
        .login-buttons .btn {
            margin: 0 5px;
        }
    </style>
</head>
<body class="hold-transition login-page">
    <div class="login-box">
        <div class="login-logo">
            <a href="#"><b>Login</b> Sistem</a>
        </div>
        <div class="login-box-body">
            <p class="login-box-msg">Silahkan login untuk memulai sesi Anda</p>

            <?php if ($error) { echo $error; } ?>

            <form method="POST" action="login.php">
                <div class="form-group has-feedback">
                    <input type="email" name="email" class="form-control" placeholder="Email" required>
                    <span class="glyphicon glyphicon-user form-control-feedback"></span>
                </div>
                <div class="form-group has-feedback">
                    <input type="password" name="password" class="form-control" placeholder="Password" required>
                    <span class="glyphicon glyphicon-lock form-control-feedback"></span>
                </div>
                <div class="row">
                    <div class="col-xs-12">
                        <div class="login-buttons">
                            <button type="submit" class="btn btn-primary btn-flat">Login</button>
                            <a href="index.php" class="btn btn-default btn-flat">Kembali</a>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-xs-12 text-center">
                        <a href="forgot_password.php">Lupa Password?</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="assets/plugins/jQuery/jquery-2.2.3.min.js"></script>
    <script src="assets/bootstrap/dist/js/bootstrap.min.js"></script>
    <script src="assets/plugins/iCheck/icheck.min.js"></script>
    <script>
      $(function () {
        $('input').iCheck({
          checkboxClass: 'icheckbox_square-blue',
          radioClass: 'iradio_square-blue',
          increaseArea: '20%' // optional
        });
      });
    </script>
</body>
</html>
