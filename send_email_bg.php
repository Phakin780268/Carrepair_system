<?php
// send_email_bg.php
// แก้เป็น — ใช้ path เต็มจริงๆ บนเซิร์ฟเวอร์
$base = dirname(__FILE__);
require $base . '/vendor/autoload.php';
include $base . '/send_email.php';


$department    = $argv[1] ?? '';
$license_plate = $argv[2] ?? '';
$request_item  = $argv[3] ?? '';
$responsible   = $argv[4] ?? '';
$request_date  = $argv[5] ?? '';
$note          = $argv[6] ?? '';

if (empty($department)) {
    error_log("send_email_bg.php: ไม่มี argument department");
    exit(1);
}

$result = sendRepairEmailByDepartment(
    $department,
    $license_plate,
    $request_item,
    $responsible,
    $request_date,
    $note
);

if (!$result) {
    error_log("send_email_bg.php: ส่ง email ไม่สำเร็จ — department=$department license_plate=$license_plate");
    exit(1);
}

exit(0);