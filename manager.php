<?php
include 'db.php';

$dept = $_SESSION['department'] ?? '';
$role = $_SESSION['role'] ?? '';

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['repair_id'], $_POST['approve_status']) && $role !== 'owner' && $role !== 'technician'){
    $repair_id = (int)$_POST['repair_id'];
    $approve_status = $_POST['approve_status'];

    $allow = ['รออนุมัติ','อนุมัติ','ไม่อนุมัติ'];
    if(!in_array($approve_status, $allow)){
        $approve_status = 'รออนุมัติ';
    }

    // กันคนไม่ใช่ admin ไปเปลี่ยนของคนละแผนก
    if ($role === 'admin') {
        $stmt = $conn->prepare("UPDATE repair_requests SET approve_status=? WHERE id=?");
        $stmt->bind_param("si", $approve_status, $repair_id);
    } else {
        $stmt = $conn->prepare("UPDATE repair_requests SET approve_status=? WHERE id=? AND department=?");
        $stmt->bind_param("sis", $approve_status, $repair_id, $dept);
    }

    $stmt->execute();

    // กลับหน้าที่เดิม (คงหน้าปัจจุบัน)
    $p = isset($_GET['p']) ? (int)$_GET['p'] : 1;
    if($p < 1) $p = 1;
    echo "<script>window.location.href='index.php?page=manager&p=$p';</script>";
    exit;
}

//  pagination
$perPage = 6; 
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
if ($page < 1) $page = 1;

$offset = ($page - 1) * $perPage;

/* ===============================
   3) นับจำนวนรายการทั้งหมด
================================ */
if ($role === 'admin' || $role === 'owner' || $role === 'technician') {

    $countSql = "
        SELECT COUNT(*) AS total
        FROM repair_requests rr
        WHERE (rr.repair_approve IS NULL OR rr.repair_approve <> 'ประวัติ')
    ";
    $countStmt = $conn->prepare($countSql);

} else {

    $countSql = "
        SELECT COUNT(*) AS total
        FROM repair_requests rr
        WHERE (rr.repair_approve IS NULL OR rr.repair_approve <> 'ประวัติ')
          AND rr.department = ?
    ";
    $countStmt = $conn->prepare($countSql);
    $countStmt->bind_param("s", $dept);
}

$countStmt->execute();
$totalRows = (int)$countStmt->get_result()->fetch_assoc()['total'];
$totalPages = max(1, ceil($totalRows / $perPage));

if ($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $perPage;

// ดึงข้อมูล
if ($role === 'admin' || $role === 'owner' || $role === 'technician') {

    $sql = "
        SELECT 
            rr.id,
            rr.request_date,
            c.license_plate,
            c.brand_model,
            rr.request_item,
            rr.responsible,
            rr.department,
            rr.note,
            rr.approve_status
        FROM repair_requests rr
        LEFT JOIN cars c ON rr.car_id = c.id
        WHERE (rr.repair_approve IS NULL OR rr.repair_approve <> 'ประวัติ')
  ORDER BY 
CASE 
    WHEN rr.approve_status IS NULL THEN 1
    WHEN rr.approve_status = 'รออนุมัติ' THEN 2
    WHEN rr.approve_status = 'ไม่อนุมัติ' THEN 3
    WHEN rr.approve_status = 'อนุมัติ' THEN 4
END ASC,
rr.request_date DESC

        LIMIT ? OFFSET ?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $perPage, $offset);

} else {

    $sql = "
        SELECT 
            rr.id,
            rr.request_date,
            c.license_plate,
            c.brand_model,
            rr.request_item,
            rr.responsible,
            rr.department,
            rr.note,
            rr.approve_status
        FROM repair_requests rr
        LEFT JOIN cars c ON rr.car_id = c.id
        WHERE (rr.repair_approve IS NULL OR rr.repair_approve <> 'ประวัติ')
          AND rr.department = ?
        ORDER BY 
        CASE 
            WHEN rr.approve_status IS NULL THEN 1
            WHEN rr.approve_status = 'รออนุมัติ' THEN 2
            WHEN rr.approve_status = 'ไม่อนุมัติ' THEN 3
            WHEN rr.approve_status = 'อนุมัติ' THEN 4
END ASC,
rr.request_date DESC

        LIMIT ? OFFSET ?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $dept, $perPage, $offset);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<h2>ใบแจ้งซ่อม</h2>
<hr>

<?php if ($result && $result->num_rows > 0) { ?>
<div class="dashboard-table">
    <table class="data-table">
        <thead>
            <tr>
                <th>วันที่แจ้งซ่อม</th>
                <th>ทะเบียนรถ</th>
                <th>ยี่ห้อ/รุ่น</th>
                <th>รายการแจ้งซ่อม</th>
                <th>ผู้แจ้งซ่อม</th>
                <th>สังกัด</th>
                <th>หมายเหตุ<br>(เลขไมล์)</th>
                <th>สถานะ</th>
                <th style="text-align:center;">จัดการ</th>
            </tr>
        </thead>

        <tbody>
        <?php while ($row = $result->fetch_assoc()) { ?>

            <?php
                // ✅ สีของ badge
                $cls = "status-wait";
                if(($row['approve_status'] ?? '') === 'อนุมัติ') $cls = "status-approve";
                if(($row['approve_status'] ?? '') === 'ไม่อนุมัติ') $cls = "status-reject";
            ?>

            <tr>
                <td><?= htmlspecialchars($row['request_date']) ?></td>
                <td><?= htmlspecialchars($row['license_plate'] ?? '-') ?></td>
                <td><?= htmlspecialchars($row['brand_model'] ?? '-') ?></td>
                <td style="white-space:pre-line;"><?= htmlspecialchars($row['request_item']) ?></td>
                <td><?= htmlspecialchars($row['responsible'] ?: '-') ?></td>
                <td><?= htmlspecialchars($row['department'] ?: '-') ?></td>
                <td style="white-space:pre-line;"><?= htmlspecialchars($row['note'] ?: '-') ?></td>

                <td>
                    <span class="status-badge <?= $cls ?>">
                        <?= htmlspecialchars($row['approve_status'] ?: 'รออนุมัติ') ?>
                    </span>
                </td>

                <td style="text-align:center; white-space:nowrap;">
                    <?php if($_SESSION['role'] !== 'owner' && $_SESSION['role'] !== 'technician'): ?>
                    <!-- ✅ ปุ่มอนุมัติ -->
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="repair_id" value="<?= $row['id'] ?>">
                        <input type="hidden" name="approve_status" value="อนุมัติ">
                        <button type="submit" class="btn btn-success"
                            onclick="return confirm('อนุมัติรายการนี้ใช่ไหม')">
                            อนุมัติ
                        </button>
                    </form>

                    <!-- ✅ ปุ่มไม่อนุมัติ -->
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="repair_id" value="<?= $row['id'] ?>">
                        <input type="hidden" name="approve_status" value="ไม่อนุมัติ">
                        <button type="submit" class="btn btn-secondary"
                            onclick="return confirm('ไม่อนุมัติรายการนี้ใช่ไหม?')">
                            ไม่อนุมัติ
                        </button>
                    </form>
                    <?php endif; ?>
                </td>

            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>

<?php if($totalPages > 1){ ?>
<div style="margin-top:15px; display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
    <?php
        $base = "index.php?page=manager";
        $prev = max(1, $page - 1);
        $next = min($totalPages, $page + 1);
    ?>

    <a class="btn btn-secondary" href="<?= $base ?>&p=<?= $prev ?>">« ก่อนหน้า</a>

    <?php
    // Sliding Window Logic (Show 3 pages)
    $visiblePages = 3;
    $start = $page - 1; 
    if ($start < 1) $start = 1;
    
    $end = $start + $visiblePages - 1;
    
    if ($end > $totalPages) {
        $end = $totalPages;
        // Adjust start backwards if we hit the end
        $start = max(1, $end - $visiblePages + 1);
    }

    for($i=$start; $i<=$end; $i++){
        if($i == $page){
            echo '<span class="btn btn-primary" style="cursor:default;">'.$i.'</span>';
        }else{
            echo '<a class="btn btn-secondary" href="'.$base.'&p='.$i.'">'.$i.'</a>';
        }
    }
    ?>

    <a class="btn btn-secondary" href="<?= $base ?>&p=<?= $next ?>">ถัดไป »</a>

    <div style="color:#666; font-size:13px;">
        หน้า <?= $page ?> / <?= $totalPages ?>
    </div>
</div>
<?php } ?>

<?php } else { ?>
    <div style="padding:15px; color:#777; background:#fff; border-radius:8px;">
        ไม่มีรายการแจ้งซ่อม
    </div>
<?php } ?>