<?php
// Mulai session
session_start();

include('db.php');

// Tambahkan PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/phpmailer/phpmailer/src/Exception.php';
require 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
require 'vendor/phpmailer/phpmailer/src/SMTP.php';

$message = ''; // Variabel pesan untuk ditampilkan

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];

    // Periksa apakah email ada di database
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Ambil data user dari hasil query
        $user = $result->fetch_assoc();
        $plain_password = $user['plain_password']; // Ambil password dari database

        // Buat instance PHPMailer
        $mail = new PHPMailer(true);

        try {
            // Pengaturan server SMTP
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'youremail@gmail.com';   // Ganti dengan email Anda
            $mail->Password   = 'yourpassword';          // Ganti dengan password atau kata sandi aplikasi
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            // Pengaturan email
            $mail->setFrom('salescanvaser@gmail.com', 'Admin'); // Ganti dengan email dan nama pengirim
            $mail->addAddress($email);  // Alamat email tujuan (email user yang lupa password)

            // Konten email
            $mail->isHTML(true);
            $mail->Subject = 'Password Anda';
            $mail->Body    = "Berikut adalah password Anda: <b>$plain_password</b>";
            $mail->AltBody = "Berikut adalah password Anda: $plain_password"; // Jika email tidak mendukung HTML

            // Kirim email
            $mail->send();
            $message = "<div class='alert alert-success'>Password telah dikirim ke email Anda.</div>";
        } catch (Exception $e) {
            $message = "<div class='alert alert-danger'>Pengiriman email gagal. Error: {$mail->ErrorInfo}</div>";
        }
    } else {
        $message = "<div class='alert alert-danger'>Email tidak ditemukan.</div>";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password</title>
    <link rel="stylesheet" href="assets/bootstrap/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/dist/css/AdminLTE.min.css">
    <link rel="stylesheet" href="assets/plugins/iCheck/square/blue.css">
    <style>
        .login-box {
            width: 360px;
            margin: 7% auto;
        }
        .login-logo a {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        .login-box-body {
            padding: 20px;
            background: #fff;
            border-top: 0;
            color: #666;
            box-shadow: 0 0 8px rgba(0, 0, 0, 0.1);
        }
        .login-box-msg {
            margin: 0;
            text-align: center;
            padding: 0 20px 20px 20px;
        }
        .alert {
            margin-bottom: 10px;
        }
        .btn {
            margin-top: 10px;
        }
    </style>
</head>
<body class="hold-transition login-page">
    <div class="login-box">
        <div class="login-logo">
            <a href="#"><b>Lupa</b> Password</a>
        </div>
        <div class="login-box-body">
            <p class="login-box-msg">Masukkan email Anda untuk mendapatkan password</p>

            <!-- Tampilkan pesan sukses/gagal -->
            <?php if ($message) { echo $message; } ?>

            <form method="POST" action="forgot_password.php">
                <div class="form-group has-feedback">
                    <input type="email" name="email" class="form-control" placeholder="Email" required>
                    <span class="glyphicon glyphicon-envelope form-control-feedback"></span>
                </div>
                <div class="row">
                    <div class="col-xs-12">
                        <button type="submit" class="btn btn-primary btn-block btn-flat">Kirim Password ke Email</button>
                    </div>
                </div>
            </form>

            <div class="row">
                <div class="col-xs-12 text-center" style="margin-top: 10px;">
                    <a href="login.php">Kembali ke Login</a>
                </div>
            </div>
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
