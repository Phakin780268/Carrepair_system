<?php
include 'db.php';

$username = "chaiyot.c";        // ชื่อ user ที่ต้องการเปลี่ยน
$newpass  = "Ch199128045**";         // รหัสผ่านใหม่

$hash = password_hash($newpass, PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE users SET password=? WHERE username=?");
$stmt->bind_param("ss", $hash, $username);
$stmt->execute();

echo "เปลี่ยนรหัสผ่านเรียบร้อยแล้ว";
