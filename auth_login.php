<!-- หน้าล็อคอิน -->
<?php
session_start();
include 'db.php';

$stmt = $conn->prepare("SELECT * FROM users WHERE username=?");
$stmt->bind_param("s", $_POST['username']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if($user && password_verify($_POST['password'], $user['password'])){
    $_SESSION['user_id']=$user['id'];
    $_SESSION['username']=$user['username'];
    $_SESSION['role']=$user['role'];
    $_SESSION['department'] = $user['department'];
    header("Location: index.php?page=home");
}else{
    echo "<script>alert('Login ไม่ถูกต้อง');location='index.php';</script>";
}
