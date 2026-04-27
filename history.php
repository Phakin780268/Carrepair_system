<!-- history -->
<?php
include 'db.php';

/* ===============================
   1) Pagination
================================ */
$limit = 10;
$p = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$offset = ($p - 1) * $limit;

/* ===============================
   2) รับค่าจากฟอร์ม
================================ */
$plate = $_GET['plate'] ?? '';
$type  = $_GET['vehicle_type'] ?? '';

//  SQL ดึงข้อมูลโชว์ตาราง

$sql = "
SELECT 
    c.id,
    c.license_plate,
    c.responsible,
    c.vehicle_type,
    c.asset_code
FROM cars c
";

$where  = [];
$params = [];
$types  = '';

if ($plate !== '') {
    $where[]  = "c.license_plate LIKE ?";
    $params[] = "%$plate%";
    $types   .= "s";
}

if ($type !== '') {
    $where[]  = "c.vehicle_type = ?";
    $params[] = $type;
    $types   .= "s";
}

if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= "
 ORDER BY c.license_plate ASC
 LIMIT ? OFFSET ?
";

$params[] = $limit;
$params[] = $offset;
$types   .= "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

/* ===============================
   4) นับจำนวนทั้งหมด (pagination)
================================ */
$count_sql = "SELECT COUNT(*) c FROM cars c";
if ($where) {
    $count_sql .= " WHERE " . implode(" AND ", $where);
}

$count_stmt = $conn->prepare($count_sql);
if ($where) {
    $bind_params = array_slice($params, 0, count($params)-2);
    $bind_types  = substr($types, 0, -2);
    if ($bind_params) {
        $count_stmt->bind_param($bind_types, ...$bind_params);
    }
}

$count_stmt->execute();
$total_rows = $count_stmt->get_result()->fetch_assoc()['c'];
$total_pages = ceil($total_rows / $limit);
?>

<div class="card search-box">
    <form method="GET" class="search-form">
        <input type="hidden" name="page" value="history">

        <!-- ค้นหาทะเบียน -->
        <div class="form-group">
            <label>ค้นหาทะเบียนรถ</label>
            <input type="text"
                   name="plate"
                   value="<?= htmlspecialchars($plate) ?>"
                   placeholder="กรอกเลขทะเบียน...">
        </div>

        <!-- ประเภทรถ -->
        <div class="form-group">
            <label>ประเภทรถ</label>
            <select name="vehicle_type" onchange="this.form.submit()">
                <option value="">ทั้งหมด</option>
                <option value="รถยนต์" <?= $type=='รถยนต์'?'selected':'' ?>>รถยนต์</option>
                <option value="รถจักรยานยนต์" <?= $type=='รถจักรยานยนต์'?'selected':'' ?>>รถจักรยานยนต์</option>
            </select>
        </div>

        <!-- ปุ่ม -->
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-magnifying-glass"></i> ค้นหา
            </button>
            <a href="index.php?page=history" class="btn btn-secondary">
                <i class="fa-solid fa-rotate-left"></i> ล้างค่า
            </a>
        </div>
    </form>
</div>

<!-- ===============================
     Table
================================ -->
<?php if ($result->num_rows > 0): ?>
<div class="table-wrapper">
    <table class="data-table">
        <thead>
            <tr>
                <th>ทะเบียนรถ</th>
                <th>ผู้รับผิดชอบ</th>
                <th>ประเภทรถ</th>
                <th>รหัสครุภัณฑ์</th>
                <th class="action-cell">ประวัติการซ่อม</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['license_plate']) ?></td>
                <td><?= htmlspecialchars($row['responsible']) ?></td>
                <td><?= htmlspecialchars($row['vehicle_type']) ?></td>
                <td><?= htmlspecialchars($row['asset_code']) ?></td>
                <td class="action-cell">
                    <?php if($_SESSION['role'] !== 'owner' && $_SESSION['role'] !== 'manager'): ?>
                    <a href="add_history.php?car_id=<?= $row['id'] ?>"
                       class="btn-action btn-add"
                       title="เพิ่มประวัติการซ่อม">
                        <i class="fa-solid fa-plus"></i>
                    </a>
                    <?php endif; ?>
                    <a href="history_detail.php?car_id=<?= $row['id'] ?>"
                       class="btn-action btn-edit"
                       title="ดูประวัติ">
                        <i class="fa-solid fa-file-lines"></i>
                    </a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- ===============================
     Pagination
================================ -->
<?php if ($total_pages > 1){ ?>
<div style="margin-top:15px; display:flex; gap:8px; align-items:center; flex-wrap:wrap;">

    <?php
        $base = "index.php?page=history&plate=" . urlencode($plate) . "&vehicle_type=" . urlencode($type);
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

<?php else: ?>
    <div style="text-align: center; padding: 40px; color: #666;">
        <i class="fa-solid fa-box-open" style="font-size: 48px; margin-bottom: 10px; opacity: 0.5;"></i>
        <p>ไม่พบข้อมูลประวัติการซ่อม</p>
    </div>
<?php endif; ?>
</div>