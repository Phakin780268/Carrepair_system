<?php
require 'db.php';
session_start();

if (!isset($_SESSION['role'])) {
    die("ไม่มีสิทธิ์");
}

if (!isset($_GET['id'])) {
    die("ไม่พบข้อมูล");
}

$id = (int)$_GET['id'];

$sql = "
    SELECT 
        rr.*,
        c.license_plate,
        c.vehicle_type,
        rd.responsible AS technician_responsible
    FROM repair_requests rr
    LEFT JOIN cars c ON rr.car_id = c.id
    LEFT JOIN repair_details rd ON rd.repair_id = rr.id
    WHERE rr.id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    die("ไม่พบข้อมูล");
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>รายละเอียดใบแจ้งซ่อม</title>

<!-- Google Font -->
<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500&display=swap" rel="stylesheet">

<style>
@media print {
    body {
        margin: 0;
    }
}

body {
    font-family: 'Kanit', sans-serif;
    background: #f5f5f5;
}

.document {
    width: 820px;
    margin: 60px auto;
    padding: 45px;
    background: #ffffff;
    border: 1px solid #ddd;
    font-size: 18px;      
    line-height: 1.7;
}

.title {
    text-align: center;
    font-size: 24px;      
    font-weight: 500;     
    margin-bottom: 30px;
}

.section {
    margin-bottom: 20px;
}

.label {
    font-weight: 500;     
    font-size: 20px;      
}

hr {
    border: none;
    border-top: 1px solid #ccc;
    margin: 20px 0;
}
</style>
</head>

<body onload="window.print()">

<div class="document">

<div class="title">รายละเอียดใบแจ้งซ่อม</div>

<div class="section">
    <div><span class="label">วันที่แจ้งซ่อม:</span> <?= htmlspecialchars($data['request_date'] ?? '-') ?></div>
    <div><span class="label">ทะเบียนรถ:</span> <?= htmlspecialchars($data['license_plate'] ?? '-') ?></div>
    <div><span class="label">ประเภทรถ:</span> <?= htmlspecialchars($data['vehicle_type'] ?? '-') ?></div>
</div>

<hr>

<div class="section">
    <div><span class="label">ผู้แจ้งซ่อม:</span> <?= htmlspecialchars($data['responsible'] ?? '-') ?></div>
    <div><span class="label">สังกัด:</span> <?= htmlspecialchars($data['department'] ?? '-') ?></div>
</div>

<hr>

<div class="section">
    <div class="label">รายการแจ้งซ่อม:</div>
    <div><?= nl2br(htmlspecialchars($data['request_item'] ?? '-')) ?></div>
</div>

<hr>

<div class="section">
    <div class="label">หมายเหตุ:</div>
    <div><?= nl2br(htmlspecialchars($data['note'] ?? '-')) ?></div>
</div>

<hr>

<div class="section">
    <div class="label">ช่างทำการประเมิน:</div>
    <div><?= nl2br(htmlspecialchars($data['technician_comment'] ?? '-')) ?></div>
</div>

<hr>

<div class="section">
    <div><span class="label">ประเภทการซ่อม:</span> <?= htmlspecialchars($data['repair_type'] ?? '-') ?></div>
</div>

<hr>

<div class="section">
    <div><span class="label">ผู้ประเมิน:</span> <?= htmlspecialchars($data['technician_responsible'] ?? '-') ?></div>
</div>

<div class="footer-space"></div>

</div>

</body>
</html>