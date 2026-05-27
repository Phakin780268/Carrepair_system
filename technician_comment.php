<!-- technician_comment -->
<?php
include 'db.php';

// 1) ดึงรายการรถที่ได้รับอนุมัติจากหัวหน้างานมาแล้ว

$repairs = $conn->query("
    SELECT rr.id AS repair_id, c.license_plate, rr.request_date, rr.request_item
    FROM repair_requests rr
    JOIN cars c ON rr.car_id = c.id
    WHERE rr.approve_status = 'อนุมัติ'
    AND (rr.repair_approve IS NULL OR rr.repair_approve = 'รออนุมัติ')
    ORDER BY rr.request_date DESC
");

$detail = null;

if (!empty($_GET['repair_id'])) {
    $repair_id = (int)$_GET['repair_id'];

    $stmt = $conn->prepare("
        SELECT 
            rr.id,
            rr.request_item,
            rr.request_date,
            rr.technician_comment,
            rr.repair_type,
            c.license_plate,

            rd.responsible
        FROM repair_requests rr
        JOIN cars c ON rr.car_id = c.id
        LEFT JOIN repair_details rd ON rd.repair_id = rr.id
        WHERE rr.id = ?
    ");
    $stmt->bind_param("i", $repair_id);
    $stmt->execute();
    $detail = $stmt->get_result()->fetch_assoc();
}


if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $repair_id    = (int)($_POST['repair_id'] ?? 0);
    $tec_comment  = $_POST['tec_comment'] ?? '';
    $repair_type  = $_POST['repair_type'] ?? '';
    $responsible  = $_POST['responsible'] ?? '';

 $stmt2 = $conn->prepare("
    UPDATE repair_requests
    SET technician_comment = ?,
        repair_type = ?,
        repair_approve = 'รออนุมัติ',
        tech_submit_status = 'submitted'
    WHERE id = ?
");

    $stmt2->bind_param("ssi", $tec_comment, $repair_type, $repair_id);
    $stmt2->execute();

    $check = $conn->prepare("SELECT id FROM repair_details WHERE repair_id=?");
    $check->bind_param("i", $repair_id);
    $check->execute();
    $exists = $check->get_result()->num_rows > 0;

    if ($exists) {
        $u = $conn->prepare("UPDATE repair_details SET responsible=? WHERE repair_id=?");
        $u->bind_param("si", $responsible, $repair_id);
        $u->execute();
    } else {
        $i = $conn->prepare("INSERT INTO repair_details (responsible, repair_id) VALUES (?, ?)");
        $i->bind_param("si", $responsible, $repair_id);
        $i->execute();
    }

        echo "<script>
        alert('ส่งคำขออนุมัติสำเร็จแล้ว');
        window.location = 'index.php?page=dashboard';
        </script>";
        exit;
}
?>

<!-- Select2 Dependencies -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<h2>ช่างประเมินการซ่อม</h2>
<hr>

<form method="GET" class="form-card" style="margin-bottom:20px">

    <input type="hidden" name="page" value="tec_comment">

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

<!-- แสดงข้อมูลที่คนแจ้งกรอกมา -->
 <form method="POST" class="form-card"id="tecSubmitForm" novalidate>
<input type="hidden" name="repair_id" value="<?= (int)$detail['id'] ?>">

    <div class="form-group">
        <label>รายการแจ้งซ่อมที่ผู้แจ้งซ่อมกรอก:</label>
        <textarea rows="4" readonly style="background:#2d2d2d; color:#e5e7eb; border: 1px solid #444; border-radius: 6px; padding: 10px;">
<?= htmlspecialchars($detail['request_item'] ?? '') ?>
        </textarea>
    </div>

    <div class="form-group">
    <label>ช่างทำการประเมิน</label>
    <textarea name="tec_comment" rows="3"><?= htmlspecialchars($detail['technician_comment'] ?? '') ?></textarea>
</div>

    <div class="form-group">
        <label>ประเภทการซ่อม</label>
        <select name="repair_type" required>
            <option value="">-- เลือกประเภท --</option>
            <option value="ภายใน" <?= ($detail['repair_type'] ?? '')=='ภายใน'?'selected':'' ?>>ซ่อมภายใน</option>
            <option value="ภายนอก" <?= ($detail['repair_type'] ?? '')=='ภายนอก'?'selected':'' ?>>ส่งซ่อมภายนอก</option>
        </select>
    </div>

<div class="form-group">
    <label>ผู้ประเมิน (ลงชื่อ)</label>
    <input type="text" name="responsible"
           value="<?= htmlspecialchars($detail['responsible'] ?? '') ?>">
</div>



<div class="form-actions">
    <button type="submit" class="btn btn-success">💾 ส่งคำขออนุมัติ</button>
</div>
</form>

<script>
$(document).ready(function () {
    $('#repair_id').select2({
        placeholder: '-- เลือกรถ --',
        allowClear: true,
        width: '100%'
    }).on('select2:select', function (e) {
        this.form.submit();
    });

    $('select[name="repair_type"]').select2({
        minimumResultsForSearch: Infinity,
        width: '100%'
    });
});
</script>
<?php 
?>

<script>
document.getElementById('tecSubmitForm').addEventListener('submit', function (e) {

    let valid = true;

    document.querySelectorAll('.error-text').forEach(el => el.remove());

    const requiredFields = [
        'repair_id',
        'responsible',
        'tec_comment'
    ];

    requiredFields.forEach(name => {
        const field = document.querySelector(`[name="${name}"]`);
        if (!field || field.value.trim() === '') {
            valid = false;

            const group = field.closest('.form-group');
            if (!group) return;

            const msg = document.createElement('div');
            msg.className = 'error-text';
            msg.textContent = 'กรุณากรอกข้อมูลช่องนี้';
            group.appendChild(msg);
        }
    });

    if (!valid) {
        e.preventDefault();
    }
});
</script>