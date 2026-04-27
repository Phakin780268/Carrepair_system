<?php
include 'db.php';
include 'send_email.php';

/* ===============================
   ดึงรายการรถ
================================ */
$cars = $conn->query("SELECT id, license_plate FROM cars");

/* ===============================
   บันทึกการแจ้งซ่อม
================================ */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $car_id       = $_POST['car_id'];
    $request_item = $_POST['request_item'];
    $responsible  = trim($_POST['responsible']);
    $department   = trim($_POST['department']);
    $request_date = $_POST['request_date'];
    $note         = trim($_POST['note']);

    // ต้องเลือกจาก dropdown เท่านั้น
    if (!empty($car_id)) {

        $stmt = $conn->prepare("
            INSERT INTO repair_requests
            (car_id, request_item, responsible, Department, request_date, note)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "isssss",
            $car_id,
            $request_item,
            $responsible,
            $department,
            $request_date,
            $note
        );

        $stmt->execute();

        // ดึงทะเบียนรถ
$car_stmt = $conn->prepare("SELECT license_plate FROM cars WHERE id = ?");
$car_stmt->bind_param("i", $car_id);
$car_stmt->execute();
$car_result = $car_stmt->get_result();
$car        = $car_result->fetch_assoc();
$license_plate = $car['license_plate'] ?? '';

// ✅ ส่ง email ใน background ผ่าน process แยก
$args   = array_map('escapeshellarg', [
    $department, $license_plate, $request_item,
    $responsible, $request_date, $note,
]);
$script = escapeshellarg(__DIR__ . '/send_email_bg.php');

// ✅ popen แบบ Windows — ไม่รอให้เสร็จ
$cmd = 'php ' . $script . ' ' . implode(' ', $args);
popen($cmd, 'r');

echo "<script>alert('แจ้งซ่อมสำเร็จ และส่งอีเมลแจ้งเตือนเรียบร้อย'); window.location='index.php?page=status';</script>";
exit;

} else {
    echo "<script>alert('กรุณาเลือกรถจากระบบ'); history.back();</script>";
    exit;
}
}
?>

<!-- Select2 Dependencies -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<form method="POST" action="add_repair.php" class="form-card" id="repairForm" novalidate>

    <div class="form-group">
        <label>รถที่แจ้งซ่อม</label>
        <select name="car_id" id="car_id" required>
            <option value="">-- เลือกรถจากระบบ --</option>
            <?php while ($row = $cars->fetch_assoc()) { ?>
                <option value="<?= $row['id']; ?>">
                    <?= htmlspecialchars($row['license_plate']); ?>
                </option>
            <?php } ?>
        </select>
    </div>

    <div class="form-group">
        <label>รายการที่แจ้งซ่อม</label>
        <div id="repair-items-container">
            <div class="repair-item-row d-flex gap-2 mb-2">
                <input type="text" class="form-control repair-item-input" placeholder="ระบุรายการซ่อม..." required>
            </div>
        </div>
        <button type="button" class="btn btn-sm btn-success mt-2" id="add-item-btn">
            <i class="fa-solid fa-plus"></i> เพิ่มรายการ
        </button>
        <!-- Hidden textarea to store final concatenated string -->
        <textarea name="request_item" id="request_item_final" style="display:none;"></textarea>
    </div>

    <div class="form-group">
        <label>ผู้รับผิดชอบ</label>
        <input type="text" name="responsible">
    </div>

    <div class="form-group">
        <label>สังกัด</label>
        <select name="department" id="department" required>
            <option value="">-- เลือกสังกัด --</option>
            <option value="งานยุทธศาสตร์และบริการกลาง">งานยุทธศาสตร์และบริการกลาง</option>
            <option value="งานออกแบบและก่อสร้าง">งานออกแบบและก่อสร้าง</option>
            <option value="งานภูมิทัศน์และสิ่งแวดล้อม">งานภูมิทัศน์และสิ่งแวดล้อม</option>
            <option value="งานรักษาความปลอดภัย">งานรักษาความปลอดภัย</option>
            <option value="งานสาธารณูปโภค">งานสาธารณูปโภค</option>
            <option value="ศูนย์บริการฉุกเฉินและบรรเทาสาธารณภัย">ศูนย์บริการฉุกเฉินและบรรเทาสาธารณภัย</option>
        </select>
    </div>

    <div class="form-group">
        <label>วันที่แจ้งซ่อม</label>
        <input type="date" name="request_date" required>
    </div>

    <div class="form-group">
        <label>หมายเหตุ (เลขไมล์)</label>
        <textarea name="note" rows="3" required></textarea>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-success">🔧 แจ้งซ่อม</button>
        <a href="index.php?page=car" class="btn btn-secondary">
            « ย้อนกลับ
        </a>
    </div>

</form>

<script>
$(document).ready(function () {
    $('#car_id').select2({
        placeholder: '-- เลือกรถจากระบบ --',
        allowClear: true,
        width: '100%'
    });

    $('#department').select2({
        placeholder: '-- เลือกสังกัด --',
        allowClear: true,
        width: '100%'
    });

    // Dynamic List Logic
    const container = document.getElementById('repair-items-container');
    const addBtn = document.getElementById('add-item-btn');

    if (addBtn) {
        addBtn.addEventListener('click', function () {
            const row = document.createElement('div');
            row.className = 'repair-item-row d-flex gap-2 mb-2';
            row.innerHTML = `
                <input type="text" class="form-control repair-item-input" placeholder="ระบุรายการซ่อม..." required>
                <button type="button" class="btn btn-outline-danger btn-remove-item" style="width: auto;">
                    <i class="fa-solid fa-trash"></i>
                </button>
            `;
            container.appendChild(row);

            row.querySelector('.btn-remove-item').addEventListener('click', function () {
                row.remove();
            });
        });
    }
});
</script>

<script>
document.getElementById('repairForm').addEventListener('submit', function (e) {

    let valid = true;

    // 1. Concatenate Repair Items
    const inputs = document.querySelectorAll('.repair-item-input');
    const finalArea = document.getElementById('request_item_final');
    let items = [];
    let hasContent = false;

    inputs.forEach(input => {
        const val = input.value.trim();
        if (val) {
            items.push("- " + val);
            hasContent = true;
        }
    });

    if (!hasContent) {
        valid = false;
        if (inputs[0]) {
            inputs[0].classList.add('is-invalid');
            if (!inputs[0].parentNode.querySelector('.error-text')) {
                const msg = document.createElement('small');
                msg.className = 'text-danger error-text';
                msg.innerText = 'กรุณาระบุรายการซ่อมอย่างน้อย 1 รายการ';
                inputs[0].parentNode.appendChild(msg);
            }
        }
    } else {
        if (inputs[0]) inputs[0].classList.remove('is-invalid');
        if (inputs[0].parentNode.querySelector('.error-text')) {
            inputs[0].parentNode.querySelector('.error-text').remove();
        }
        finalArea.value = items.join("\n");
    }

    // 2. Other Validations
    const requiredFields = [
        'car_id',
        'responsible',
        'department',
        'request_date',
        'note'
    ];

    requiredFields.forEach(name => {
        const field = document.querySelector(`[name="${name}"]`);
        if (!field || field.value.trim() === '') {
            valid = false;
            const group = field.closest('.form-group');
            if (group && !group.querySelector('.error-text')) {
                const msg = document.createElement('div');
                msg.className = 'error-text text-danger';
                msg.textContent = name === 'note' ? 'กรุณากรอกเลขไมล์' : 'กรุณากรอกข้อมูลช่องนี้';
                group.appendChild(msg);
            }
        }
    });

    if (!valid) {
        e.preventDefault();
        alert('กรุณากรอกข้อมูลให้ครบถ้วน');
    }
});
</script>