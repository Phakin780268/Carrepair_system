<!-- add_history -->
<?php
include 'db.php';

/* ===============================
   รับค่า car_id
================================ */
$car_id = isset($_GET['car_id']) ? intval($_GET['car_id']) : 0;

if ($car_id <= 0) {
    echo "<script>alert('ไม่พบข้อมูลรถ'); window.location='index.php?page=history';</script>";
    exit;
}

/* ===============================
   Get Car Info
================================ */
$car_sql = "SELECT license_plate, responsible FROM cars WHERE id = ?";
$car_stmt = $conn->prepare($car_sql);
$car_stmt->bind_param("i", $car_id);
$car_stmt->execute();
$car_res = $car_stmt->get_result();
$car_info = $car_res->fetch_assoc();

if (!$car_info) {
    echo "<script>alert('ไม่พบข้อมูลรถ'); window.location='index.php?page=history';</script>";
    exit;
}

$license_plate = $car_info['license_plate'] ?? '-';
$owner = $car_info['responsible'] ?? '-';

/* ===============================
   บันทึกข้อมูลประวัติการซ่อม
================================ */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $enter_date = $_POST['enter_date'] ?? '';
    $responsible_company = $_POST['responsible_company'] ?? '';
    $notes = $_POST['notes'] ?? '';
    
    $item_names = $_POST['item_name'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    $units = $_POST['unit'] ?? [];
    $prices = $_POST['price'] ?? [];
    
    if (empty($enter_date)) {
        echo "<script>alert('กรุณาระบุวันที่'); history.back();</script>";
        exit;
    }
    
    $conn->begin_transaction();
    
    try {
        // สร้าง repair_request ใหม่โดยระบุสถานะเป็น 'เสร็จสิ้น' และ 'ประวัติ' 
        // เพื่อไม่ให้ไปโผล่ใน Dashboard (check 'อนุมัติ') หรือหน้าหัวหน้างาน (check status!=เสร็จสิ้น)
        $insert_req = $conn->prepare("
            INSERT INTO repair_requests (car_id, request_date, repair_approve, status)
            VALUES (?, CURDATE(), 'ประวัติ', 'เสร็จสิ้น')
        ");
        $insert_req->bind_param("i", $car_id);
        $insert_req->execute();
        $repair_id = $conn->insert_id;
        
        $detail_stmt = $conn->prepare("
            INSERT INTO repair_details (repair_id, enter_date, responsible_company, notes, status)
            VALUES (?, ?, ?, ?, 'เสร็จสิ้น')
        ");
        $detail_stmt->bind_param("isss", $repair_id, $enter_date, $responsible_company, $notes);
        $detail_stmt->execute();
        
        $item_stmt = $conn->prepare("
            INSERT INTO repair_items (repair_id, item_name, quantity, unit, price)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        for ($i = 0; $i < count($item_names); $i++) {
            $item_name = trim($item_names[$i]);
            if (!empty($item_name)) {
                $qty = floatval($quantities[$i] ?? 0);
                $unit = trim($units[$i] ?? '');
                $price = floatval($prices[$i] ?? 0);
                
                $item_stmt->bind_param("isdsd", $repair_id, $item_name, $qty, $unit, $price);
                $item_stmt->execute();
            }
        }
        
        $conn->commit();
        echo "<script>alert('บันทึกประวัติการซ่อมสำเร็จ'); window.location='history_detail.php?car_id=$car_id';</script>";
        exit;
        
    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('เกิดข้อผิดพลาด: " . addslashes($e->getMessage()) . "'); history.back();</script>";
        exit;
    }
}
?>

<link rel="stylesheet" href="add_history.css?v=<?=time()?>">


<div class="add-history-container">
    <!-- Header -->
    <div class="page-header">
        <h2><i class="fa-solid fa-plus-circle"></i> เพิ่มประวัติการซ่อม</h2>
        <div class="car-info-badges">
            <div class="car-badge">
                <i class="fa-solid fa-car-side"></i>
                <div>
                    <div class="label">ทะเบียนรถ</div>
                    <div class="value"><?= htmlspecialchars($license_plate) ?></div>
                </div>
            </div>
            <div class="car-badge">
                <i class="fa-solid fa-user"></i>
                <div>
                    <div class="label">ผู้รับผิดชอบ</div>
                    <div class="value"><?= htmlspecialchars($owner) ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Form -->
    <div class="form-container">
        <form method="POST" action="add_history.php?car_id=<?= $car_id ?>" id="historyForm">
            
            <!-- Repair Items Section -->
            <h3 class="section-title"><i class="fa-solid fa-wrench"></i> รายการซ่อม</h3>
            
            <div class="items-container" id="itemsContainer">
                <div class="item-row">
                    <div class="form-field">
                        <label>รายการ</label>
                        <input type="text" name="item_name[]" placeholder="ระบุรายการซ่อม...">
                    </div>
                    <div class="form-field">
                        <label>จำนวน</label>
                        <input type="number" name="quantity[]" placeholder="0" min="0" step="0.01">
                    </div>
                    <div class="form-field">
                        <label>หน่วย</label>
                        <input type="text" name="unit[]" placeholder="ชิ้น">
                    </div>
                    <div class="form-field">
                        <label>ราคา (บาท)</label>
                        <input type="number" name="price[]" placeholder="0.00" min="0" step="0.01">
                    </div>
                    <button type="button" class="btn-remove" onclick="removeItem(this)" title="ลบรายการ">🗑️</button>
                </div>
            </div>
            
            <button type="button" class="btn-add-item" onclick="addItem()">
                <i class="fa-solid fa-plus"></i> เพิ่มรายการ
            </button>

            <!-- Details Section -->
            <h3 class="section-title"><i class="fa-solid fa-clipboard-list"></i> รายละเอียด</h3>
            
            <div class="form-grid">
                <div class="form-group">
                    <label><i class="fa-solid fa-calendar"></i> วันที่</label>
                    <input type="date" name="enter_date" id="enter_date" required>
                </div>
                
                <div class="form-group">
                    <label><i class="fa-solid fa-building"></i> ผู้ดำเนินการ</label>
                    <input type="text" name="responsible_company" id="responsible_company" placeholder="ระบุชื่อผู้ดำเนินการ..." required>
                </div>
                
                <div class="form-group full-width">
                    <label><i class="fa-solid fa-comment"></i> หมายเหตุ</label>
                    <textarea name="notes" placeholder="รายละเอียดเพิ่มเติม..."></textarea>
                </div>
            </div>

            <!-- Actions -->
            <div class="form-actions">
                <button type="submit" class="btn-submit">
                    <i class="fa-solid fa-save"></i> บันทึกข้อมูล
                </button>
                <a href="index.php?page=history" class="btn-back">
                    <i class="fa-solid fa-arrow-left"></i> ย้อนกลับ
                </a>
            </div>
        </form>
    </div>
</div>

<script>
function addItem() {
    const container = document.getElementById('itemsContainer');
    const row = document.createElement('div');
    row.className = 'item-row';
    row.innerHTML = `
        <div class="form-field">
            <label>รายการ</label>
            <input type="text" name="item_name[]" placeholder="ระบุรายการซ่อม...">
        </div>
        <div class="form-field">
            <label>จำนวน</label>
            <input type="number" name="quantity[]" placeholder="0" min="0" step="0.01">
        </div>
        <div class="form-field">
            <label>หน่วย</label>
            <input type="text" name="unit[]" placeholder="ชิ้น">
        </div>
        <div class="form-field">
            <label>ราคา (บาท)</label>
            <input type="number" name="price[]" placeholder="0.00" min="0" step="0.01">
        </div>
        <button type="button" class="btn-remove" onclick="removeItem(this)" title="ลบรายการ">🗑️</button>
    `;
    container.appendChild(row);
}

function removeItem(btn) {
    const container = document.getElementById('itemsContainer');
    if (container.children.length > 1) {
        btn.closest('.item-row').remove();
    } else {
        alert('ต้องมีรายการอย่างน้อย 1 รายการ');
    }
}

// Form Validation
document.getElementById('historyForm').addEventListener('submit', function(e) {
    let isValid = true;
    
    // Clear previous errors
    document.querySelectorAll('.error-msg').forEach(el => el.remove());
    
    const enterDate = document.getElementById('enter_date');
    const responsible = document.getElementById('responsible_company');
    
    if (!enterDate.value) {
        showError(enterDate, 'กรุณาระบุวันที่');
        isValid = false;
    }
    
    if (!responsible.value.trim()) {
        showError(responsible, 'กรุณาระบุผู้ดำเนินการ');
        isValid = false;
    }
    
    if (!isValid) {
        e.preventDefault();
    }
});

function showError(input, message) {
    const error = document.createElement('div');
    error.className = 'error-msg';
    error.innerHTML = '<i class="fa-solid fa-exclamation-circle"></i> ' + message;
    input.parentNode.appendChild(error);
    input.style.borderColor = '#e53e3e';
    
    input.addEventListener('input', function() {
        this.style.borderColor = '';
        const err = this.parentNode.querySelector('.error-msg');
        if (err) err.remove();
    }, { once: true });
}

// Set default date to today
document.addEventListener('DOMContentLoaded', function() {
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('enter_date').value = today;
});
</script>
