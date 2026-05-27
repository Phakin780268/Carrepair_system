<!-- history_detail -->
<?php
include 'db.php';

/* ===============================
   รับค่า+Pagination
================================ */
$car_id = isset($_GET['car_id']) ? intval($_GET['car_id']) : 0;
$p      = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$limit  = 4;
$offset = ($p - 1) * $limit;
$year   = $_GET['year'] ?? '';
$repair_type = trim($_GET['repair_type'] ?? '');


if ($car_id <= 0) {
    echo "<p>ไม่พบข้อมูลรถ</p>";
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
$license_plate = $car_info['license_plate'] ?? '-';
$owner         = $car_info['responsible'] ?? '-';

/* ===============================
   ดึงปีที่มีในประวัติทั้งหมด (สำหรับ Dropdown)
================================ */
$year_sql = "
    SELECT DISTINCT YEAR(rd.enter_date) AS y
    FROM repair_requests rr
    INNER JOIN repair_details rd ON rr.id = rd.repair_id
    WHERE rr.car_id = ? AND rd.enter_date IS NOT NULL
    ORDER BY y DESC
";
$year_stmt = $conn->prepare($year_sql);
$year_stmt->bind_param("i", $car_id);
$year_stmt->execute();
$year_result = $year_stmt->get_result();
$available_years = [];
while ($yr = $year_result->fetch_assoc()) {
    $available_years[] = $yr['y'];
}

/* ===============================
   นับจำนวนวันที่ซ่อมทั้งหมด (สำหรับ Pagination)
================================ */
$count_sql = "
    SELECT COUNT(rd.id) AS total
    FROM repair_requests rr
    INNER JOIN repair_details rd ON rr.id = rd.repair_id
    WHERE rr.car_id = ?
    AND rd.enter_date IS NOT NULL
";

$params = [$car_id];
$types  = "i";

if ($year !== '') {
    $count_sql .= " AND YEAR(rd.enter_date) = ?";
    $params[] = (int)$year;
    $types .= "i";
}

if ($repair_type !== '') {
    $count_sql .= " AND rr.repair_type = ?";
    $params[] = $repair_type;
    $types .= "s";
}


$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$total = $count_stmt->get_result()->fetch_assoc()['total'];

$total_pages = ceil($total / $limit);

/* ===============================
   Step 1: ดึง detail_id ของหน้านี้ (4 วันที่ต่อหน้า)
================================ */
$base_sql = "
    SELECT rd.id AS detail_id
    FROM repair_requests rr
    INNER JOIN repair_details rd ON rr.id = rd.repair_id
    WHERE rr.car_id = ?
    AND rd.enter_date IS NOT NULL
";

$params = [$car_id];
$types  = "i";

if ($year !== '') {
    $base_sql .= " AND YEAR(rd.enter_date) = ?";
    $params[] = (int)$year;
    $types .= "i";
}

if ($repair_type !== '') {
    $base_sql .= " AND rr.repair_type = ?";
    $params[] = $repair_type;
    $types .= "s";
}

$base_sql .= "
    ORDER BY rd.enter_date DESC, rd.id DESC
    LIMIT ? OFFSET ?
";

$params[] = $limit;
$params[] = $offset;
$types .= "ii";


$base_stmt = $conn->prepare($base_sql);
$base_stmt->bind_param($types, ...$params);
$base_stmt->execute();

$detail_ids = [];
$res = $base_stmt->get_result();
while ($r = $res->fetch_assoc()) {
    $detail_ids[] = $r['detail_id'];
}

/* ===============================
   Step 2: ดึงข้อมูลจริงด้วย detail_ids
================================ */
$data = [];

if ($detail_ids) {
    $in = implode(',', array_fill(0, count($detail_ids), '?'));

    // ดึง repair_details ตาม detail_id
    $detail_sql = "
    SELECT 
        rd.id AS detail_id,
        rr.id AS repair_id,
        rd.enter_date,
        rd.responsible_company,
        rd.notes
    FROM repair_requests rr
    INNER JOIN repair_details rd ON rr.id = rd.repair_id
    WHERE rd.id IN ($in)
    ORDER BY rd.enter_date DESC, rd.id DESC
    ";

    $detail_stmt = $conn->prepare($detail_sql);
    $detail_stmt->bind_param(str_repeat("i", count($detail_ids)), ...$detail_ids);
    $detail_stmt->execute();
    $detail_result = $detail_stmt->get_result();

    $repair_ids = [];
    while ($row = $detail_result->fetch_assoc()) {
        $key = $row['detail_id'];
        $data[$key] = [
            'repair_id'           => $row['repair_id'],
            'date'                => $row['enter_date'],
            'responsible_company' => $row['responsible_company'],
            'notes'               => $row['notes'],
            'items'               => []
        ];
        $repair_ids[$row['repair_id']] = true;
    }

    // ดึง repair_items ตาม repair_id ที่เกี่ยวข้อง
    if ($repair_ids) {
        $repair_id_list = array_keys($repair_ids);
        $in_repair = implode(',', array_fill(0, count($repair_id_list), '?'));
        
        $item_sql = "
        SELECT repair_id, item_name, quantity, unit, price
        FROM repair_items
        WHERE repair_id IN ($in_repair)
        ORDER BY id ASC
        ";

        $item_stmt = $conn->prepare($item_sql);
        $item_stmt->bind_param(str_repeat("i", count($repair_id_list)), ...$repair_id_list);
        $item_stmt->execute();
        $item_result = $item_stmt->get_result();

        // เก็บ items ตาม repair_id ก่อน
        $items_by_repair = [];
        while ($row = $item_result->fetch_assoc()) {
            $rid = $row['repair_id'];
            if (!isset($items_by_repair[$rid])) {
                $items_by_repair[$rid] = [];
            }
            if (!empty($row['item_name'])) {
                $items_by_repair[$rid][] = [
                    'item'  => $row['item_name'],
                    'qty'   => $row['quantity'],
                    'unit'  => $row['unit'],
                    'price' => $row['price']
                ];
            }
        }

        // นำ items มาใส่ใน data ตาม repair_id
        foreach ($data as $key => &$group) {
            $rid = $group['repair_id'];
            if (isset($items_by_repair[$rid])) {
                $group['items'] = $items_by_repair[$rid];
            }
        }
        unset($group);
    }
}
?>

<link rel="stylesheet" href="history_detail.css?v=<?=time()?>">

<div class="history-card">
    <div class="history-header">
        <div class="header-title">
            <h2><i class="fa-solid fa-clock-rotate-left"></i> ประวัติการซ่อมบำรุง</h2>
            <div class="header-info">
                <div class="info-badge">
                    <i class="fa-solid fa-car-side"></i>
                    <span class="info-label">ทะเบียนรถ:</span>
                    <span class="info-value"><?= htmlspecialchars($license_plate) ?></span>
                </div>
                <div class="info-badge user">
                    <i class="fa-solid fa-user-circle"></i>
                    <span class="info-label">ผู้รับผิดชอบ:</span>
                    <span class="info-value"><?= htmlspecialchars($owner) ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="history-toolbar">
        <form method="GET" class="filter-group">
            <input type="hidden" name="page" value="history_detail">
            <input type="hidden" name="car_id" value="<?= $car_id ?>">
            
            <div class="filter-item">
                <label><i class="fa-regular fa-calendar"></i> ปีงบประมาณ</label>
                <select name="year" onchange="this.form.submit()" class="custom-select">
                    <option value="">ทั้งหมด</option>
                    <?php foreach ($available_years as $y): ?>
                        <option value="<?= $y ?>" <?= ($year == $y) ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-item">
                <label><i class="fa-solid fa-wrench"></i> ประเภทการซ่อม</label>
                <select name="repair_type" onchange="this.form.submit()" class="custom-select">
                    <option value="" <?= ($repair_type === '') ? 'selected' : '' ?>>ทั้งหมด</option>
                    <option value="ภายใน" <?= $repair_type=='ภายใน'?'selected':'' ?>>ส่งซ่อมภายใน</option>
                    <option value="ภายนอก" <?= $repair_type=='ภายนอก'?'selected':'' ?>>ส่งซ่อมภายนอก</option>
                </select>
            </div>
        </form>

        <div class="action-group">
            <a href="export_excel.php?car_id=<?= $car_id ?>&year=<?= urlencode($year) ?>&repair_type=<?= urlencode($repair_type) ?>" 
               class="btn-export">
                <i class="fa-solid fa-file-excel"></i> Export Excel
            </a>
            
            <a href="index.php?page=history" class="btn-back">
                <i class="fa-solid fa-arrow-left"></i> ย้อนกลับ
            </a>
        </div>
    </div>

    </div>
</div>

<table border="1" width="100%" cellpadding="6" cellspacing="0">
<tr style="background:#eee">
    <th>วันที่ซ่อม</th>
    <th>รายการ</th>
    <th>จำนวน</th>
    <th>หน่วย</th>
    <th>เงิน (บาท)</th>
    <th>ผู้ดำเนินการ</th>
    <th>หมายเหตุ</th>
</tr>

<?php foreach ($data as $group): 
    $rowspan = max(count($group['items']), 1);
    $first = true;
    $total_price = 0;
?>

<?php if (!empty($group['items'])): foreach ($group['items'] as $it): 
    $total_price += $it['price'];
?>
<tr>
<?php if ($first): ?>
    <td rowspan="<?= $rowspan ?>"><?= htmlspecialchars($group['date']) ?></td>
<?php endif; ?>

    <td><?= htmlspecialchars($it['item']) ?></td>
    <td align="center"><?= htmlspecialchars($it['qty']) ?></td>
    <td align="center"><?= htmlspecialchars($it['unit']) ?></td>
    <td align="right"><?= number_format($it['price'], 2) ?></td>

<?php if ($first): ?>
    <td rowspan="<?= $rowspan ?>"><?= htmlspecialchars($group['responsible_company']) ?></td>
    <td rowspan="<?= $rowspan ?>"><?= htmlspecialchars($group['notes'] ?? '') ?></td>
<?php endif; ?>
</tr>
<?php $first = false; endforeach; ?>

<?php
// ✅ คำนวณ VAT ต่อวัน
$vat = ($total_price * 7) / 100;
$net_total = $total_price + $vat;
?>

<!-- ✅ รวมต่อวัน -->
<tr style="">
    <td colspan="4" align="right">รวม</td>
    <td align="right"><?= number_format($total_price, 2) ?></td>
    <td colspan="2"></td>
</tr>

<tr>
    <td colspan="4" align="right">ภาษีมูลค่าเพิ่ม 7%</td>
    <td align="right"><?= number_format($vat, 2) ?></td>
    <td colspan="2"></td>
</tr>

<tr style="font-weight:bold;background:#ffe5e5;">
    <td colspan="4" align="right">รวมเป็นเงินทั้งสิ้น</td>
    <td align="right"><?= number_format($net_total, 2) ?></td>
    <td colspan="2"></td>
</tr>

<?php else: ?>
<tr>
    <td><?= htmlspecialchars($group['date']) ?></td>
    <td colspan="3"></td>
    <td align="right">0.00</td>
    <td><?= htmlspecialchars($group['responsible_company']) ?></td>
    <td><?= htmlspecialchars($group['notes'] ?? '') ?></td>
</tr>
<?php endif; ?>

<?php endforeach; ?>
</table>

<!-- ===============================
     Pagination
================================ -->
<?php if ($total_pages > 1): ?>
<div style="margin-top:20px;">

    <?php if ($p > 1): ?>
        <a href="?page=history_detail&car_id=<?= $car_id ?>&year=<?= urlencode($year) ?>&repair_type=<?= urlencode($repair_type) ?>&p=<?= $p-1 ?>">
            &lt; ก่อนหน้า
        </a>
    <?php endif; ?>

    <span style="margin:0 15px;">
        หน้า <?= $p ?> จาก <?= $total_pages ?>
    </span>

    <?php if ($p < $total_pages): ?>
        <a href="?page=history_detail&car_id=<?= $car_id ?>&year=<?= urlencode($year) ?>&repair_type=<?= urlencode($repair_type) ?>&p=<?= $p+1 ?>">
            ถัดไป &gt;
        </a>
    <?php endif; ?>

</div>
<?php endif; ?>
