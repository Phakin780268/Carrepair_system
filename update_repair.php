<!-- update _repair -->
<?php
include 'db.php';

// 1) ดึงรายการรถที่ยังไม่เสร็จ

$repairs = $conn->query("
    SELECT rr.id AS repair_id, c.license_plate, rr.request_date
    FROM repair_requests rr
    JOIN cars c ON rr.car_id = c.id
    WHERE rr.repair_approve = 'อนุมัติ'
AND (rr.status <> 'เสร็จสิ้น' OR rr.status IS NULL)
    ORDER BY rr.request_date DESC
");

 //  2) บันทึกข้อมูล

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $repair_id   = $_POST['repair_id'];
    $operator = $_POST['operator'];
    $remarks     = $_POST['remarks'];
    $status      = $_POST['status'];
    $repair_date = $_POST['repair_date'];

    // เช็กว่ามี repair_details แล้วยัง
    $check = $conn->prepare("SELECT id FROM repair_details WHERE repair_id=?");
    $check->bind_param("i", $repair_id);
    $check->execute();
    $exists = $check->get_result()->num_rows > 0;

    if ($exists) {
       $stmt = $conn->prepare("
        UPDATE repair_details
        SET operator=?, remarks=?, status=?, repair_date=?
        WHERE repair_id=?
        ");
            $stmt->bind_param("ssssi", $operator, $remarks, $status, $repair_date, $repair_id);

    } else {
      $stmt = $conn->prepare("
        INSERT INTO repair_details (operator, remarks, status, repair_date, repair_id)
        VALUES (?, ?, ?, ?, ?)
        ");
            $stmt->bind_param("ssssi", $operator, $remarks, $status, $repair_date, $repair_id);
    }
    $stmt->execute();

    // ลบรายการเดิม
    $del = $conn->prepare("DELETE FROM repair_items WHERE repair_id=?");
    $del->bind_param("i", $repair_id);
    $del->execute();

    // เพิ่มรายการใหม่
    $itemStmt = $conn->prepare("
        INSERT INTO repair_items (repair_id, item_name, quantity, unit, price)
        VALUES (?, ?, ?, ?, ?)
    ");

    foreach ($_POST['item_name'] as $i => $item) {
        if (trim($item) === '') continue;

        $itemStmt->bind_param(
            "isisd",
            $repair_id,
            $_POST['item_name'][$i],
            $_POST['quantity'][$i],
            $_POST['unit'][$i],
            $_POST['price'][$i]
        );
        $itemStmt->execute();
    }

    // อัปเดตสถานะในใบแจ้งซ่อม
    $u = $conn->prepare("UPDATE repair_requests SET status=? WHERE id=?");
    $u->bind_param("si", $status, $repair_id);
    $u->execute();

    echo "<script>
        alert('บันทึกข้อมูลสำเร็จ');
        window.location='index.php?page=status';
    </script>";
    exit;
}

/* ===============================
   3) โหลดข้อมูลเดิม
================================ */
$detail = null;
$items  = [];
$techEval = null;

if (!empty($_GET['repair_id'])) {

    $stmt = $conn->prepare("SELECT * FROM repair_details WHERE repair_id=?");
    $stmt->bind_param("i", $_GET['repair_id']);
    $stmt->execute();
    $detail = $stmt->get_result()->fetch_assoc();

    $it = $conn->prepare("SELECT * FROM repair_items WHERE repair_id=?");
    $it->bind_param("i", $_GET['repair_id']);
    $it->execute();
    $items = $it->get_result()->fetch_all(MYSQLI_ASSOC);

    // ดึงข้อมูลที่ช่างประเมินไว้
    $techStmt = $conn->prepare("
        SELECT 
            rr.technician_comment,
            rr.repair_type,
            rd.responsible AS technician_responsible
        FROM repair_requests rr
        LEFT JOIN repair_details rd ON rd.repair_id = rr.id
        WHERE rr.id = ?
    ");
    $techStmt->bind_param("i", $_GET['repair_id']);
    $techStmt->execute();
    $techEval = $techStmt->get_result()->fetch_assoc();
}

$statuses = ['ส่งซ่อม','รอเสนอราคา','รอซ่อม','กำลังซ่อม','รอคำสั่งจ้าง','ส่งซ่อมภายนอก','ส่งมอบพัสดุ','เสร็จสิ้น'];
?>

<!-- Select2 Dependencies -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<h2>รายละเอียดงานช่าง</h2>
<hr>

<form method="GET" class="form-card" style="margin-bottom:20px">
    <input type="hidden" name="page" value="technician">

    <div class="form-group">
        <label>เลือกรถที่ถูกแจ้งซ่อม</label>
        <select name="repair_id" id="repair_id" onchange="this.form.submit()">
            <option value="">-- เลือกรถ --</option>
            <?php while($r=$repairs->fetch_assoc()): ?>
            <option value="<?= $r['repair_id'] ?>"
                <?= ($_GET['repair_id'] ?? '')==$r['repair_id']?'selected':'' ?>>
                <?= $r['license_plate'].' | '.$r['request_date'] ?>
            </option>
            <?php endwhile ?>
        </select>
    </div>
</form>

<?php if(!empty($_GET['repair_id'])): ?>

<form method="POST" class="form-card" id="updateRepairForm" novalidate>

<input type="hidden" name="repair_id" value="<?= $_GET['repair_id'] ?>">

<?php if ($techEval): ?>
    <div class="form-group">
        <label>รายการที่ช่างประเมิน:</label>
        <textarea rows="3" readonly style="background:#2d2d2d; color:#e5e7eb; border:1px solid #444; border-radius:6px; padding:10px;"><?= htmlspecialchars($techEval['technician_comment'] ?? '-') ?></textarea>
    </div>
<?php endif; ?>



<div class="form-group">
    <label>ช่างผู้ดำเนินการ</label>
    <input name="operator" value="<?= $detail['operator'] ?? '' ?>" required>
</div>

<div class="form-group">
        <label>วันที่ซ่อม</label>
        <input type="date" name="repair_date" value="<?= $detail['repair_date'] ?? '' ?>" required>
    </div>

<div class="form-group">
    <label>หมายเหตุ</label>
    <textarea name="remarks" rows="3"><?= $detail['remarks'] ?? '' ?></textarea>
</div>

<div class="form-group">
    <label>สถานะ</label>
    <select name="status">
        <?php foreach($statuses as $s): ?>
        <option <?= (($detail['status']??'')==$s)?'selected':'' ?>>
            <?= $s ?>
        </option>
        <?php endforeach ?>
    </select>
</div>

<div class="form-actions">
    <button type="submit" class="btn btn-success">💾 บันทึก</button>
</div>

</form>
<?php endif ?>

<script>
$(document).ready(function () {
    $('#repair_id').select2({
        placeholder: '-- เลือกรถ --',
        allowClear: true,
        width: '100%'
    }).on('select2:select', function (e) {
        this.form.submit();
    });
});
</script>

<script>


const updateForm = document.getElementById('updateRepairForm');
if (updateForm) {
    updateForm.addEventListener('submit', function (e) {
        let valid = true;
        
        // Clear old errors
        document.querySelectorAll('.error-text').forEach(el => el.remove());

        const fields = ['operator', 'repair_date'];

        fields.forEach(name => {
            const el = this.querySelector(`[name="${name}"]`);
            if(el && el.value.trim() === ''){
                valid = false;
                
                const group = el.closest('.form-group');
                if(group){
                    const msg = document.createElement('div');
                    msg.className = 'error-text';
                    msg.innerText = 'กรุณากรอกข้อมูลช่องนี้';
                    msg.style.color = 'var(--danger-color, #f5365c)'; 
                    msg.style.fontSize = '0.85rem';
                    msg.style.marginTop = '5px';
                    group.appendChild(msg);
                }
            }
        });

        if(!valid) {
            e.preventDefault();
        }
    });
}
</script>
