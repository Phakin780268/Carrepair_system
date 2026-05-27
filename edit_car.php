<!-- edit_car from add_car -->
<?php
include 'db.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    echo "<p>ไม่พบข้อมูลรถ</p>";
    return;
}

/* ===============================
   บันทึกการแก้ไข
================================ */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $sql = "
        UPDATE cars SET
            license_plate   = ?,
            brand_model     = ?,
            vehicle_type    = ?,
            asset_code      = ?,
            department      = ?,
            responsible     = ?,
            tax_expire      = ?,
            note            = ?
        WHERE id = ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "ssssssssi",
        $_POST['license_plate'],
        $_POST['brand_model'],
        $_POST['vehicle_type'],
        $_POST['asset_code'],
        $_POST['department'],
        $_POST['responsible'],
        $_POST['tax_expire'],
        $_POST['note'],
        $id
    );
    $stmt->execute();

   echo "<script>window.location.href='index.php?page=car&updated=1';</script>";
exit;

}

/* ===============================
   โหลดข้อมูลเดิม
================================ */
$stmt = $conn->prepare("SELECT * FROM cars WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$car = $stmt->get_result()->fetch_assoc();

if (!$car) {
    echo "<p>ไม่พบข้อมูลรถ</p>";
    return;
}
?>

<h2>แก้ไขข้อมูลรถ</h2>
<hr>

<form method="POST" class="form-card">

    <div class="form-group">
        <label>ทะเบียนรถ</label>
        <input type="text" name="license_plate"
               value="<?= htmlspecialchars($car['license_plate']) ?>" required>
    </div>

    <div class="form-group">
        <label>ยี่ห้อ / รุ่น</label>
        <input type="text" name="brand_model"
               value="<?= htmlspecialchars($car['brand_model']) ?>">
    </div>

    <div class="form-group">
        <label>ประเภทรถ</label>
        <select name="vehicle_type" required>
            <option value="">-- เลือกประเภทรถ --</option>
            <option value="รถยนต์" <?= $car['vehicle_type']=='รถยนต์'?'selected':'' ?>>รถยนต์</option>
            <option value="รถจักรยานยนต์" <?= $car['vehicle_type']=='รถจักรยานยนต์'?'selected':'' ?>>
                รถจักรยานยนต์
            </option>
        </select>
    </div>

    <div class="form-group">
        <label>รหัสครุภัณฑ์</label>
        <input type="text" name="asset_code"
               value="<?= htmlspecialchars($car['asset_code']) ?>">
    </div>

    <div class="form-group">
        <label>หน่วยงาน</label>
        <input type="text" name="department"
               value="<?= htmlspecialchars($car['department']) ?>">
    </div>

    <div class="form-group">
        <label>ผู้รับผิดชอบ</label>
        <input type="text" name="responsible"
               value="<?= htmlspecialchars($car['responsible']) ?>">
    </div>

    <div class="form-row">

        <div class="form-group">
            <label>ภาษีหมดอายุ</label>
            <input type="date" name="tax_expire"
                   value="<?= htmlspecialchars($car['tax_expire']) ?>">
        </div>
    </div>

    <div class="form-group">
        <label>หมายเหตุ</label>
        <textarea name="note" rows="3"><?= htmlspecialchars($car['note']) ?></textarea>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-success">💾 บันทึกการแก้ไข</button>
        <a href="index.php?page=car" class="btn btn-secondary">
            « กลับหน้ารายการรถ
        </a>
    </div>

</form>

