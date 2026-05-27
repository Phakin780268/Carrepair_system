<?php
include 'db.php';

$dept = $_SESSION['department'] ?? '';
$role = $_SESSION['role'] ?? '';

/* ===============================
   ✅ จำกัดสิทธิ์เข้าหน้า (เฉพาะ admin, chief_technician, technician)
================================ */
if ($role !== 'admin' && $role !== 'chief_technician' && $role !== 'technician' && $role !== 'owner') {
    echo "<div style='padding:15px;background:#fff;border-radius:10px;color:#c00;'>
            ❌ คุณไม่มีสิทธิ์เข้าหน้านี้
          </div>";
    exit;
}

/* ===============================
   1) อัปเดตสถานะอนุมัติ (POST)
================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['repair_id'], $_POST['repair_approve']) && $role !== 'technician' && $role !== 'owner') {

    $repair_id      = (int)$_POST['repair_id'];
    $repair_approve = $_POST['repair_approve'];
    $reject_remark  = $_POST['reject_remark'] ?? '';

    $allow = ['รออนุมัติ','อนุมัติ','ไม่อนุมัติ'];
    if (!in_array($repair_approve, $allow)) {
        $repair_approve = 'รออนุมัติ';
    }

    // ✅ admin + chief_technician อนุมัติได้ทุกแผนก
if ($repair_approve === 'ไม่อนุมัติ') {
    // ❌ ไม่อนุมัติ = จบงานทันที
    $stmt = $conn->prepare("
        UPDATE repair_requests 
        SET 
            repair_approve = ?,
            reject_remark  = ?,
            status         = 'เสร็จสิ้น'
        WHERE id = ?
    ");
} else {
    // ✅ อนุมัติ = เดิน workflow ต่อ + บันทึกเวลาอนุมัติ
    $stmt = $conn->prepare("
        UPDATE repair_requests 
        SET repair_approve = ?, reject_remark = ?, approved_at = NOW()
        WHERE id = ?
    ");
}

$stmt->bind_param("ssi", $repair_approve, $reject_remark, $repair_id);
$stmt->execute();


    // กลับหน้าที่เดิม (คงหน้า pagination)
    $p = isset($_GET['p']) ? (int)$_GET['p'] : 1;
    if ($p < 1) $p = 1;

    echo "<script>window.location.href='index.php?page=approve&p=$p';</script>";
    exit;
}

/* ===============================
   2) Pagination
================================ */
$perPage = 6;
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
if ($page < 1) $page = 1;

$offset = ($page - 1) * $perPage;

/* ===============================
   3) นับจำนวนรายการทั้งหมด
   ✅ เฉพาะงานที่ช่างส่งคำขออนุมัติแล้ว
================================ */
$countSql = "
    SELECT COUNT(*) AS total
    FROM repair_requests rr
    WHERE rr.tech_submit_status = 'submitted'
";
$countStmt = $conn->prepare($countSql);
$countStmt->execute();

$totalRows  = (int)$countStmt->get_result()->fetch_assoc()['total'];
$totalPages = max(1, ceil($totalRows / $perPage));

if ($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $perPage;

/* ===============================
   4) ดึงข้อมูลรายการแจ้งซ่อม
================================ */
$sql = "
    SELECT 
        rr.id,
        rr.request_date,
        c.license_plate,
        c.vehicle_type,
        rr.request_item,
        rr.responsible,
        rr.department,
        rr.note,
        rr.technician_comment,
        rr.repair_type,
        rd.responsible AS technician_responsible,
        rr.repair_approve,
        rr.reject_remark
    FROM repair_requests rr
    LEFT JOIN cars c ON rr.car_id = c.id
    LEFT JOIN repair_details rd ON rd.repair_id = rr.id
    WHERE rr.tech_submit_status = 'submitted'
    ORDER BY 
        CASE 
            WHEN rr.repair_approve IS NULL THEN 1
            WHEN rr.repair_approve = 'รออนุมัติ' THEN 2
            WHEN rr.repair_approve = 'ไม่อนุมัติ' THEN 3
            WHEN rr.repair_approve = 'อนุมัติ' THEN 4
        END ASC,
        rr.approved_at DESC,
        rr.request_date DESC
    LIMIT ? OFFSET ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $perPage, $offset);
$stmt->execute();
$result = $stmt->get_result();
?>

<h2>งานซ่อมรออนุมัติ</h2>
<hr>

<?php if ($result && $result->num_rows > 0) { 
    $rows = [];
    while($r = $result->fetch_assoc()) {
        $rows[] = $r;
    }
?>
<div class="dashboard-table">
    <table class="data-table">
        <thead>
            <tr>
                <th>วันที่อนุมัติ</th>
                <th>ทะเบียนรถ</th>
                <th>สถานะ</th>
                <th>หมายเหตุ</th>
                <th style="text-align:center;">จัดการ</th>
            </tr>
        </thead>

        <tbody>
        <?php foreach ($rows as $row) { ?>

            <?php
                $cls = "status-wait";
                if (($row['repair_approve'] ?? '') === 'อนุมัติ') $cls = "status-approve";
                if (($row['repair_approve'] ?? '') === 'ไม่อนุมัติ') $cls = "status-reject";

                $modalId = "modalRepair_" . (int)$row['id'];
                $rejectModalId = "modalReject_" . (int)$row['id'];
            ?>

            <tr>
                <td><?= htmlspecialchars($row['request_date'] ?? '-') ?></td>
                <td><?= htmlspecialchars($row['license_plate'] ?? '-') ?></td>


                <td>
                    <span class="status-badge <?= $cls ?>">
                        <?= htmlspecialchars($row['repair_approve'] ?: 'รออนุมัติ') ?>
                    </span>
                </td>
                
                <td>
                     <!-- Show Reject Remark if rejected -->
                     <?php if(($row['repair_approve'] ?? '') === 'ไม่อนุมัติ'): ?>
                        <span class="text-danger"><?= htmlspecialchars($row['reject_remark'] ?? '-') ?></span>
                     <?php else: ?>
                        -
                     <?php endif; ?>
                </td>

                <td style="text-align:center; white-space:nowrap;">

                    <!-- ปุ่มดูรายละเอียด -->
                    <button type="button" class="btn btn-primary btn-sm"
                            data-bs-toggle="modal" data-bs-target="#<?= $modalId ?>">
                        ดูรายละเอียด
                    </button>

                    <?php if($role !== 'technician' && $role !== 'owner'): ?>
                    <!-- ปุ่มอนุมัติ -->
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="repair_id" value="<?= (int)$row['id'] ?>">
                        <input type="hidden" name="repair_approve" value="อนุมัติ">
                        <button type="submit" class="btn btn-success btn-sm"
                                onclick="return confirm('อนุมัติรายการนี้ใช่ไหม')">
                            อนุมัติ
                        </button>
                    </form>

                    <!-- ปุ่มไม่อนุมัติ (เปิด Modal) -->
                    <button type="button" class="btn btn-secondary btn-sm" 
                            data-bs-toggle="modal" data-bs-target="#<?= $rejectModalId ?>">
                        ไม่อนุมัติ
                    </button>
                    <?php endif; ?>

                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>

<!-- ✅ Modals (Moved outside table) -->
<?php foreach ($rows as $row) { 
     $modalId = "modalRepair_" . (int)$row['id'];
     $rejectModalId = "modalReject_" . (int)$row['id'];
?>
    <!-- Reject Confirmation Modal -->
    <div class="modal fade" id="<?= $rejectModalId ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title text-danger">ยืนยันไม่อนุมัติรายการ</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>กรุณาระบุเหตุผลที่ไม่อนุมัติ:</p>
                        <input type="hidden" name="repair_id" value="<?= (int)$row['id'] ?>">
                        <input type="hidden" name="repair_approve" value="ไม่อนุมัติ">
                        <textarea name="reject_remark" class="form-control" rows="3" required placeholder="เช่น ยังไม่ถึงรอบซ่อม..."></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-danger">ยืนยันไม่อนุมัติ</button>
                    </div>
                </form>
            </div>
        </div>
    </div>    <div class="modal fade" id="<?= $modalId ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">รายละเอียดใบแจ้งซ่อม</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <div class="row g-3">
                    <div class="col-md-6">
                        <div><b>วันที่แจ้งซ่อม:</b> <span class="reply-blue"><?= htmlspecialchars($row['request_date'] ?? '-') ?></span></div>
                        <div><b>ทะเบียนรถ:</b> <span class="reply-blue"><?= htmlspecialchars($row['license_plate'] ?? '-') ?></span></div>
                        <div><b>ประเภทรถ:</b> <span class="reply-blue"><?= htmlspecialchars($row['vehicle_type'] ?? '-') ?></span></div>
                    </div>

                    <div class="col-md-6">
                        <div><b>ผู้แจ้งซ่อม:</b> <span class="reply-blue"><?= htmlspecialchars($row['responsible'] ?: '-') ?></span></div>
                        <div><b>สังกัด:</b> <span class="reply-blue"><?= htmlspecialchars($row['department'] ?: '-') ?></span></div>
                    </div>

                    <div class="col-12">
                        <hr>
                        <div><b>รายการแจ้งซ่อม:</b></div>
                        <div class="reply-blue" style="white-space:pre-line;">
                            <?= htmlspecialchars($row['request_item'] ?? '-') ?>
                        </div>
                    </div>

                    <div class="col-12">
                        <hr>
                        <div><b>หมายเหตุ(เลขไมล์):</b></div>
                        <div class="reply-blue" style="white-space:pre-line;">
                            <?= htmlspecialchars($row['note'] ?: '-') ?>
                        </div>
                    </div>

                    <div class="col-12">
                        <hr>
                        <div><b>ช่างทำการประเมิน:</b></div>
                        <div class="reply-blue" style="white-space:pre-line;">
                            <?= htmlspecialchars($row['technician_comment'] ?: '-') ?>
                        </div>
                    </div>

                    <div class="col-12">
                        <hr>
                        <div><b>ประเภทการซ่อม:</b></div>
                        <div class="reply-blue"><?= htmlspecialchars($row['repair_type'] ?: '-') ?></div>
                    </div>

                    <div class="col-12">
                        <hr>
                        <div><b>ผู้ประเมิน:</b></div>
                        <div class="reply-blue"><?= htmlspecialchars($row['technician_responsible'] ?: '-') ?></div>
                    </div>
                </div>


                </div>

                <div class="modal-footer">

                    <a href="export_repair.php?id=<?= (int)$row['id'] ?>" 
                    target="_blank"
                    class="btn btn-success">
                    Export PDF
                    </a>

                    <button class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                </div>

            </div>
        </div>
    </div>
<?php } ?>
<!-- ===============================
     Pagination
================================ -->
<?php if ($totalPages > 1) { ?>

<div style="margin-top:15px; display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
    <?php
        $base = "index.php?page=approve";
        $prev = max(1, $page - 1);
        $next = min($totalPages, $page + 1);

        $visiblePages = 3;
        $start = $page - 1; 
        if ($start < 1) $start = 1;
        
        $end = $start + $visiblePages - 1;
        
        if ($end > $totalPages) {
            $end = $totalPages;
           
            $start = max(1, $end - $visiblePages + 1);
        }
    ?>

    <a class="btn btn-secondary" href="<?= $base ?>&p=<?= $prev ?>">« ก่อนหน้า</a>

    <?php
    for ($i=$start; $i<=$end; $i++) {
        if ($i == $page) {
            echo '<span class="btn btn-primary" style="cursor:default;">'.$i.'</span>';
        } else {
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
        ไม่มีรายการที่ช่างส่งคำขออนุมัติเข้ามา
    </div>
<?php } ?>
