<?php
session_start();

if(isset($_SESSION['user_id'])){
    header("Location: index.php?page=home");
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>เข้าสู่ระบบ - ระบบยานยนต์</title>

<!-- Google Fonts: Noto Sans Thai -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="login.css?v=<?=time()?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>

<div class="login-wrapper">
    <div class="login-card">
        
        <div class="logo-section">
            <img src="pse.png" alt="Logo">
        </div>

        <div class="header-section">
            <h1>ระบบยานยนต์</h1>
            <p>กรุณาลงชื่อเข้าใช้เพื่อดำเนินการต่อ</p>
        </div>

        <form method="POST" action="auth_login.php" class="login-form">

            <div class="input-group">
                <label for="username">ชื่อผู้ใช้</label>
                <div class="input-field">
                    <i class="fa-solid fa-user"></i>
                    <input type="text" id="username" name="username" placeholder="ระบุชื่อผู้ใช้งาน" required autocomplete="off">
                </div>
            </div>

            <div class="input-group">
                <label for="password">รหัสผ่าน</label>
                <div class="input-field">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="ระบุรหัสผ่าน" required>
                    <span class="toggle-password" onclick="togglePassword()">
                        <i class="fa-solid fa-eye" id="eyeIcon"></i>
                    </span>
                </div>
            </div>

            <button type="submit" class="btn-login">
                เข้าสู่ระบบ <i class="fa-solid fa-arrow-right"></i>
            </button>

        </form>

        <div class="footer-section">
            <p>© 2026 Vehicle Management System</p>
        </div>
    </div>
</div>

<script>
function togglePassword() {
    const pwd = document.getElementById('password');
    const icon = document.getElementById('eyeIcon');

    if (pwd.type === 'password') {
        pwd.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        pwd.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
</script>

</body>
</html>
