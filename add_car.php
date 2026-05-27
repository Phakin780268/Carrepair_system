<!-- add_car -->
<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $required_fields = [
        'license_plate',
        'brand_model',
        'vehicle_type',
        'asset_code',
        'department',
        'responsible',
        'tax_expire'
    ];

    foreach ($required_fields as $field) {
        if (empty(trim($_POST[$field] ?? ''))) {
            echo "<script>
                alert('กรุณากรอกข้อมูลให้ครบถ้วน');
                history.back();
            </script>";
            exit;
        }
    }

    $stmt = $conn->prepare("
        INSERT INTO cars (
            license_plate, brand_model, vehicle_type, asset_code,
            department, responsible,
            tax_expire, note
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "ssssssss",
        $_POST['license_plate'],
        $_POST['brand_model'],
        $_POST['vehicle_type'],
        $_POST['asset_code'],
        $_POST['department'],
        $_POST['responsible'],
        $_POST['tax_expire'],
        $_POST['note']
    );

    $stmt->execute();

    echo "<script>window.location.href='index.php?page=car&added=1';</script>";
    exit;
}
?>

<h2>เพิ่มข้อมูลรถ</h2>
<hr>

<form method="POST" class="form-card" id="carForm" novalidate>

    <div class="form-group">
        <label>ทะเบียนรถ</label>
        <input type="text" name="license_plate" required>
    </div>

    <div class="form-group">
        <label>ยี่ห้อ / รุ่น</label>
        <input type="text" name="brand_model">
    </div>

    <div class="form-group">
        <label>ประเภทรถ</label>
        <select name="vehicle_type" required>
            <option value="">-- เลือกประเภทรถ --</option>
            <option value="รถยนต์">รถยนต์</option>
            <option value="รถจักรยานยนต์">รถจักรยานยนต์</option>
        </select>
    </div>

    <div class="form-group">
        <label>รหัสครุภัณฑ์</label>
        <input type="text" name="asset_code">
    </div>

    <div class="form-group">
        <label>หน่วยงาน</label>
        <input type="text" name="department">
    </div>

    <div class="form-group">
        <label>ผู้รับผิดชอบ</label>
        <input type="text" name="responsible">
    </div>

    <div class="form-row">



        <div class="form-group">
            <label>ภาษีหมดอายุ</label>
            <input type="date" name="tax_expire">
        </div>
    </div>

    <div class="form-group">
        <label>หมายเหตุ</label>
        <textarea name="note" rows="3"></textarea>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-success">💾 บันทึก</button>
        <a href="index.php?page=car" class="btn btn-secondary">
            « กลับหน้ารายการรถ
        </a>
    </div>

</form>

<script>
document.getElementById('carForm').addEventListener('submit', function (e) {

    let valid = true;

    // ล้าง error เดิม
    document.querySelectorAll('.form-group').forEach(group => {
        group.classList.remove('error');
        const err = group.querySelector('.error-text');
        if (err) err.remove();
    });

    // ช่องที่ต้องกรอก (ยกเว้น note)
    const requiredFields = [
        'license_plate',
        'brand_model',
        'vehicle_type',
        'asset_code',
        'department',
        'responsible',
        'tax_expire'
    ];

    requiredFields.forEach(name => {
        const field = document.querySelector(`[name="${name}"]`);
        if (!field || field.value.trim() === '') {
            valid = false;

            const group = field.closest('.form-group');
            group.classList.add('error');

            const msg = document.createElement('div');
            msg.className = 'error-text';
            msg.innerText = 'กรุณากรอกข้อมูลช่องนี้';
            group.appendChild(msg);
        }
    });

    if (!valid) {
        e.preventDefault();
        alert('⚠️ กรุณากรอกข้อมูลให้ครบถ้วน');
    }
});
</script>
