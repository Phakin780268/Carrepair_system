<!-- car_list -->
<?php
include 'db.php';

/* ===============================
   1) ตั้งค่าแท็บ
================================ */
$tab = $_GET['tab'] ?? 'all';

$vehicleType = '';
if ($tab === 'car') {
    $vehicleType = 'รถยนต์';
} elseif ($tab === 'bike') {
    $vehicleType = 'รถจักรยานยนต์';
}

/* ===============================
   2) นับจำนวนรถ (Badge)
================================ */
$total_count = $conn->query("SELECT COUNT(*) c FROM cars")->fetch_assoc()['c'];

$car_count = $conn->query("
    SELECT COUNT(*) c FROM cars WHERE vehicle_type='รถยนต์'
")->fetch_assoc()['c'];

$bike_count = $conn->query("
    SELECT COUNT(*) c FROM cars WHERE vehicle_type='รถจักรยานยนต์'
")->fetch_assoc()['c'];

/* ===============================
   3) ค้นหา + เงื่อนไข
================================ */
$search_plate = $_GET['search_plate'] ?? '';

$where  = [];
$params = [];
$types  = '';

if ($search_plate !== '') {
    $where[]  = "license_plate LIKE ?";
    $params[] = "%$search_plate%";
    $types   .= "s";
}

if ($vehicleType !== '') {
    $where[]  = "vehicle_type = ?";
    $params[] = $vehicleType;
    $types   .= "s";
}


 //  4) Pagination

$limit = 10;
$p = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$offset = ($p - 1) * $limit;


  // 5) ดึงข้อมูลรถ

$sql = "SELECT * FROM cars";
if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY id DESC LIMIT ? OFFSET ?";


$stmt = $conn->prepare($sql);

$bindTypes  = $types . "ii";
$bindValues = array_merge($params, [$limit, $offset]);

$args = [];
$args[] = $bindTypes;
foreach ($bindValues as $k => $v) {
    $args[] = &$bindValues[$k];
}

call_user_func_array([$stmt, 'bind_param'], $args);

$stmt->execute();
$result = $stmt->get_result();



 //  นับจำนวน (Pagination)

$count_sql = "SELECT COUNT(*) c FROM cars";
if ($where) {
    $count_sql .= " WHERE " . implode(" AND ", $where);
}

$count_stmt = $conn->prepare($count_sql);
if ($params) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_filtered = $count_stmt->get_result()->fetch_assoc()['c'];
$total_pages = ceil($total_filtered / $limit);

/* ===============================
   ลบข้อมูลรถ
================================ */
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);

    // ใช้ Transaction เพื่อลบข้อมูลที่เกี่ยวข้องทั้งหมด
    $conn->begin_transaction();
    try {
        // 1) ลบ repair_items ที่ผูกกับ repair_requests ของรถคันนี้
        $del1 = $conn->prepare("
            DELETE ri FROM repair_items ri
            INNER JOIN repair_requests rr ON ri.repair_id = rr.id
            WHERE rr.car_id = ?
        ");
        $del1->bind_param("i", $id);
        $del1->execute();

        // 2) ลบ repair_details ที่ผูกกับ repair_requests ของรถคันนี้
        $del2 = $conn->prepare("
            DELETE rd FROM repair_details rd
            INNER JOIN repair_requests rr ON rd.repair_id = rr.id
            WHERE rr.car_id = ?
        ");
        $del2->bind_param("i", $id);
        $del2->execute();

        // 3) ลบ repair_requests ของรถคันนี้
        $del3 = $conn->prepare("DELETE FROM repair_requests WHERE car_id = ?");
        $del3->bind_param("i", $id);
        $del3->execute();

        // 4) ลบรถ
        $del4 = $conn->prepare("DELETE FROM cars WHERE id = ?");
        $del4->bind_param("i", $id);
        $del4->execute();

        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('เกิดข้อผิดพลาดในการลบ: " . addslashes($e->getMessage()) . "');</script>";
    }

    echo "<script>window.location.href='index.php?page=car';</script>";
    exit;
}

/* ===============================
   เพิ่มข้อมูลรถ
================================ */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $insert = $conn->prepare("
        INSERT INTO cars (
            license_plate, brand_model, vehicle_type, asset_code,
            department, responsible,
            tax_expire, note
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $insert->bind_param(
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

    $insert->execute();
    echo "<script>alert('บันทึกข้อมูลสำเร็จ'); location='index.php?page=car';</script>";
    exit;
}
?>
<?php if (isset($_GET['updated'])): ?>
<script>alert('แก้ไขข้อมูลเรียบร้อยแล้ว');</script>
<?php endif; ?>

<?php if (isset($_GET['added'])): ?>
<script>
    alert('เพิ่มข้อมูลรถเรียบร้อยแล้ว');
</script>
<?php endif; ?>

<h2>รายการข้อมูลรถ</h2>

<hr>

<!-- ===============================
     Search
================================ -->
<div class="card search-box">
    <form method="GET"
      id="filterForm"
      class="search-form">
        <input type="hidden" name="page" value="car">

        <!-- ค้นหาทะเบียน -->
        <div class="form-group">
            <label>ค้นหาทะเบียนรถ</label>
            <input type="text"
                   name="search_plate"
                   value="<?= htmlspecialchars($search_plate) ?>">
        </div>

        <!-- Dropdown เลือกประเภท -->
        <div class="form-group">
            <label>ประเภทรถ</label>
            <select name="tab" onchange="document.getElementById('filterForm').submit();">
    <option value="all"  <?= $tab==='all'?'selected':'' ?>>
        ทั้งหมด 
    </option>
    <option value="car"  <?= $tab==='car'?'selected':'' ?>>
        รถยนต์ 
    </option>
    <option value="bike" <?= $tab==='bike'?'selected':'' ?>>
        รถจักรยานยนต์ 
    </option>
</select>

        </div>

        <!-- ปุ่ม -->
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-magnifying-glass"></i> ค้นหา
            </button>

            <a href="index.php?page=car" class="btn btn-secondary">
                <i class="fa-solid fa-xmark"></i> ล้างค่า
            </a>
        </div>
    </form>
</div>

     
<div class="table-header">
    <div class="table-info">
        รายการทั้งหมด: <?= $total_filtered ?> รายการ
    </div>

  <?php if ($_SESSION['role'] === 'owner'|| $_SESSION['role']=='manager'|| $_SESSION['role']=='technician'): ?>
    <a class="btn btn-success disabled-btn" title="ไม่มีสิทธิ์เพิ่มข้อมูล">
         + เพิ่มรายการรถ
    </a>
<?php else: ?>
    <a href="index.php?page=car_add" class="btn btn-success">
        + เพิ่มรายการรถ
    </a>
<?php endif; ?>


</div>
<?php if ($result->num_rows > 0) { ?>
<div class="table-wrapper">
<table class="data-table">
    <thead>
        <tr>
            <th>ทะเบียนรถ</th>
            <th>ยี่ห้อ/รุ่น</th>
            <th>ประเภทรถ</th>
            <th>รหัสครุภัณฑ์</th>
            <th>หน่วยงาน</th>
            <th>ผู้รับผิดชอบ</th>
            <th>ภาษีหมดอายุ</th>
            <th>หมายเหตุ</th>
            <th>จัดการ</th>
        </tr>
    </thead>
    <tbody>
    <?php while ($row = $result->fetch_assoc()) { ?>
        <tr>
            <td><?= htmlspecialchars($row['license_plate']) ?></td>
            <td><?= htmlspecialchars($row['brand_model']) ?></td>
            <td><?= htmlspecialchars($row['vehicle_type']) ?></td>
            <td><?= htmlspecialchars($row['asset_code']) ?></td>
            <td><?= htmlspecialchars($row['department']) ?></td>
            <td><?= htmlspecialchars($row['responsible']) ?></td>


            <td><?= htmlspecialchars($row['tax_expire']) ?></td>
            <td><?= htmlspecialchars($row['note']) ?></td>
            <td class="action-cell">


            <?php if ($_SESSION['role'] === 'owner'|| $_SESSION['role']=='manager'|| $_SESSION['role']=='technician'): ?>
            <span class="btn-action btn-edit disabled-btn" title="ไม่มีสิทธิ์แก้ไขข้อมูล">✏️</span>
            <?php else: ?>
            <a href="index.php?page=car_edit&id=<?= $row['id'] ?>"
            class="btn-action btn-edit"
            title="แก้ไข">✏️</a>
            <?php endif; ?>

            <?php if ($_SESSION['role'] === 'owner'|| $_SESSION['role']=='manager'|| $_SESSION['role']=='technician'): ?>
            <span class="btn-action btn-delete disabled-btn" title="ไม่มีสิทธิ์ลบ">🗑</span>
                <?php else: ?>
            <a href="index.php?page=car&delete_id=<?= $row['id'] ?>"
            class="btn-action btn-delete"
            onclick="return confirm('คุณแน่ใจว่าต้องการลบข้อมูลนี้?')">🗑</a>
            <?php endif; ?>
        </td>

        </tr>
    <?php } ?>
    </tbody>
</table>
</div>

<?php if($total_pages > 1){ ?>
<div style="margin-top:15px; display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
    <?php
        // base url ของหน้ารถ (คง search/tab ไว้เหมือนเดิม)
        $base = "index.php?page=car&tab=".$tab."&search_plate=".$search_plate;

        $prev = max(1, $p - 1);
        $next = min($total_pages, $p + 1);
    ?>

    <a class="btn btn-secondary" href="<?= $base ?>&p=<?= $prev ?>">« ก่อนหน้า</a>

    <?php
        // Sliding Window Logic (Show 3 pages)
        $visiblePages = 3;
        $start = $p - 1;
        if ($start < 1) $start = 1;

        $end = $start + $visiblePages - 1;

        if ($end > $total_pages) {
            $end = $total_pages;
            // Adjust start backwards if we hit the end
            $start = max(1, $end - $visiblePages + 1);
        }

        for($i=$start; $i<=$end; $i++){
            if($i == $p){
                echo '<span class="btn btn-primary" style="cursor:default;">'.$i.'</span>';
            }else{
                echo '<a class="btn btn-secondary" href="'.$base.'&p='.$i.'">'.$i.'</a>';
            }
        }
    ?>

    <a class="btn btn-secondary" href="<?= $base ?>&p=<?= $next ?>">ถัดไป »</a>

    <div style="color:#666; font-size:13px;">
        หน้า <?= $p ?> / <?= $total_pages ?>
    </div>
</div>
<?php } ?>

<script>
function toggleForm() {
    const form = document.getElementById('addCarForm');
    form.style.display = (form.style.display === 'none') ? 'block' : 'none';
}
</script>

<?php } else { ?>

    <?php if ($search_plate !== '') { ?>
        <p style="padding:15px; color:#777; background:#fff; border-radius:8px;">
            ไม่พบทะเบียนรถที่ค้นหา: <?= htmlspecialchars($search_plate) ?>
        </p>
    <?php } else { ?>
        <p style="padding:15px; color:#777; background:#fff; border-radius:8px;">
            ยังไม่มีข้อมูลรถ
        </p>
    <?php } ?>

<?php } ?>

                