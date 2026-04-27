<!-- dashboard.php -->

<?php
include 'db.php';

/* ===============================
   1) Filter
================================ */
$status_filter = $_GET['status_filter'] ?? '';

$plate_filter = $_GET['plate'] ?? '';
$statuses = [
    'ส่งซ่อม',
    'รอซ่อม',
    'กำลังซ่อม',
    'ส่งซ่อมภายนอก',
    'ส่งมอบพัสดุ',
    'เสร็จสิ้น'
];

/* ===============================
   2) Pagination
================================ */
$limit = 6;
$p = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$offset = ($p - 1) * $limit;

/* ===============================
   3) Query หลัก
================================ */
$sql = "
SELECT 
    c.license_plate,
    r.request_date,
    r.request_item,
    r.responsible AS request_responsible,
    d.repair_date,
    d.operator,
    d.remarks,
    d.status
FROM repair_requests r
JOIN cars c ON r.car_id = c.id
LEFT JOIN repair_details d ON r.id = d.repair_id
";

$where[] = "r.repair_approve = 'อนุมัติ'";
$params = [];
$types  = '';

if ($status_filter !== '') {
    $where[]  = "d.status = ?";
    $params[] = $status_filter;
    $types   .= "s";
}
if ($plate_filter !== '') {
    $where[]  = "c.license_plate LIKE ?";
    $params[] = "%$plate_filter%";
    $types   .= "s";
}


if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= "
ORDER BY 
    CASE WHEN d.status = 'เสร็จสิ้น' THEN 1 ELSE 0 END,
    r.request_date DESC
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
   4) Count สำหรับ pagination
================================ */
$count_sql = "
SELECT COUNT(*) c
FROM repair_requests r
JOIN cars c ON r.car_id = c.id
LEFT JOIN repair_details d ON r.id = d.repair_id
";

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
$total_rows  = $count_stmt->get_result()->fetch_assoc()['c'];
$total_pages = ceil($total_rows / $limit);
?>

<h2>ตารางดูสถานะงานซ่อม</h2>
<hr>

<div class="card search-box">
    <form method="GET" id="dashboardFilter" class="search-form">
        <input type="hidden" name="page" value="status">

        <div class="form-group">
            <label>ค้นหาทะเบียนรถ</label>
            <input type="text"
                   name="plate"
                   value="<?= htmlspecialchars($plate_filter) ?>">
        </div>

        <div class="form-group">
            <label>สถานะงาน</label>
            <select name="status_filter"
                    onchange="document.getElementById('dashboardFilter').submit();">
                <option value="">-- แสดงทั้งหมด --</option>
                <?php foreach ($statuses as $st): ?>
                    <option value="<?= $st ?>"
                        <?= $status_filter === $st ? 'selected' : '' ?>>
                        <?= $st ?>
                    </option>
                <?php endforeach ?>
            </select>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-magnifying-glass"></i> ค้นหา
            </button>

            <a href="index.php?page=status" class="btn btn-secondary">
                <i class="fa-solid fa-xmark"></i> ล้างค่า
            </a>
        </div>
    </form>
</div>

<!-- ===============================
     Table
================================ -->
<div class="table-scroll-wrapper">
<table class="dashboard-table">

<tr>
    <th style="text-align:center;">ทะเบียนรถ</th>
    <th style="text-align:center;">วันที่แจ้งซ่อม</th>
    <th>รายการแจ้งซ่อม</th>
    <th style="text-align:center;">ผู้แจ้งซ่อม</th>
    <th style="text-align:center;">วันที่ซ่อม</th>
    <th style="text-align:center;">ช่างผู้รับผิดชอบ</th>
    <th style="text-align:center;">หมายเหตุ</th>
    <th style="text-align:center;">สถานะ</th>
</tr>

<?php while ($row = $result->fetch_assoc()): 
    switch ($row['status']) {
        case 'ส่งซ่อม':          $cls = 'status-send'; break;
        case 'รอเสนอราคา':          $cls = 'status-price'; break;
        case 'รอซ่อม':           $cls = 'status-wait'; break;
        case 'กำลังซ่อม':        $cls = 'status-work'; break;
        case 'รอคำสั่งจ้าง':        $cls = 'status-wait-order'; break;
        case 'ส่งซ่อมภายนอก':    $cls = 'status-out'; break;
        case 'ส่งมอบพัสดุ':      $cls = 'status-pack'; break;
        case 'เสร็จสิ้น':        $cls = 'status-done'; break;
        default:                  $cls = '';
    }
?>
<tr>
    <td style="text-align:center;"><?= htmlspecialchars($row['license_plate']) ?></td>
    <td style="text-align:center;"><?= htmlspecialchars($row['request_date']) ?></td>
    <td style="white-space:pre-line;"><?= htmlspecialchars($row['request_item']) ?></td>
    <td style="text-align:center;"><?= htmlspecialchars($row['request_responsible']) ?></td>
    <td style="text-align:center;"><?= htmlspecialchars($row['repair_date'] ?? '') ?></td>
    <td style="text-align:center;"><?= htmlspecialchars($row['operator']) ?></td>
    <td class="wrap"><?= htmlspecialchars($row['remarks']) ?></td>
    <td style="text-align:center;">
        <span class="status-badge <?= $cls ?>">
            <?= htmlspecialchars($row['status']) ?>
        </span>
    </td>
</tr>
<?php endwhile; ?>

</table>
</div>

<!-- Pagination -->
<?php if($total_pages > 1){ ?>
<div style="margin-top:15px; display:flex; gap:8px; align-items:center; flex-wrap:wrap;">

    <?php
        $base = "index.php?page=status&status_filter=" . urlencode($status_filter);

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


