<?php
// Ensure db connection is available (index.php includes it)
// Fetch Data
$carStatus = $conn->query("
    SELECT * FROM v_dashboard_car_status
")->fetch_assoc();

$year = $_GET['year'] ?? date('Y');

// สร้างเงื่อนไขปีแบบ dynamic (ว่าง = ทั้งหมด)
$year_condition = ($year !== '') ? "v.repair_year = '$year'" : "1=1";

if ($year !== '') {
    $topRepairCars = $conn->query("
        SELECT v.*, c.brand_model, c.vehicle_type
        FROM v_top_repair_cars v
        LEFT JOIN cars c ON v.license_plate = c.license_plate
        WHERE v.repair_year = '$year'
        ORDER BY v.repair_count DESC
        LIMIT 5
    ");
} else {
    $topRepairCars = $conn->query("
        SELECT v.license_plate, SUM(v.repair_count) AS repair_count, c.brand_model, c.vehicle_type
        FROM v_top_repair_cars v
        LEFT JOIN cars c ON v.license_plate = c.license_plate
        GROUP BY v.license_plate, c.brand_model, c.vehicle_type
        ORDER BY repair_count DESC
        LIMIT 5
    ");
}

if ($year !== '') {
    $topRepairMotorcycles = $conn->query("
        SELECT v.*, c.brand_model, c.vehicle_type
        FROM v_top_repair_cars v
        LEFT JOIN cars c ON v.license_plate = c.license_plate
        WHERE v.repair_year = '$year'
        AND c.vehicle_type = 'รถจักรยานยนต์'
        ORDER BY v.repair_count DESC
        LIMIT 5
    ");
} else {
    $topRepairMotorcycles = $conn->query("
        SELECT v.license_plate, SUM(v.repair_count) AS repair_count, c.brand_model, c.vehicle_type
        FROM v_top_repair_cars v
        LEFT JOIN cars c ON v.license_plate = c.license_plate
        WHERE c.vehicle_type = 'รถจักรยานยนต์'
        GROUP BY v.license_plate, c.brand_model, c.vehicle_type
        ORDER BY repair_count DESC
        LIMIT 5
    ");
}



// 2.2 Get available years for dropdown
$years_query = $conn->query("SELECT DISTINCT repair_year FROM v_repair_cost_by_year_car ORDER BY repair_year DESC");
$available_years = [];
while($y = $years_query->fetch_assoc()){
    $available_years[] = $y['repair_year'];
}

// Default to current year if not set or not in list
$current_year = date('Y');
$year = $_GET['year'] ?? $current_year;

// Ensure we have at least the current year if DB is empty
if(empty($available_years)) {
    $available_years[] = $current_year;
}

// Filter Logic for Vehicle Type
$vehicle_type = $_GET['vehicle_type'] ?? 'all';
$type_condition = "";
if($vehicle_type == 'car'){
    $type_condition = " AND c.vehicle_type = 'รถยนต์' ";
} elseif($vehicle_type == 'motorcycle'){
    $type_condition = " AND c.vehicle_type = 'รถจักรยานยนต์' ";
}

// Fetch Top Repair Cars with dynamic filter
// NOTE: v_top_repair_cars view might not have vehicle_type inside it directly depending on definition, 
// strictly speaking we should join cars table to filter.
if ($year !== '') {
    $topRepairCars = $conn->query("
        SELECT v.*, c.brand_model, c.vehicle_type
        FROM v_top_repair_cars v
        LEFT JOIN cars c ON v.license_plate = c.license_plate
        WHERE v.repair_year = '$year'
        $type_condition
        ORDER BY v.repair_count DESC
        LIMIT 5
    ");
} else {
    $topRepairCars = $conn->query("
        SELECT v.license_plate, SUM(v.repair_count) AS repair_count, c.brand_model, c.vehicle_type
        FROM v_top_repair_cars v
        LEFT JOIN cars c ON v.license_plate = c.license_plate
        WHERE 1=1 $type_condition
        GROUP BY v.license_plate, c.brand_model, c.vehicle_type
        ORDER BY repair_count DESC
        LIMIT 5
    ");
}

if ($year !== '') {
    $costYear = $conn->query("
        SELECT v.*, c.brand_model, c.vehicle_type
        FROM v_repair_cost_by_year_car v
        LEFT JOIN cars c ON v.license_plate = c.license_plate
        WHERE v.repair_year = '$year'
        ORDER BY v.total_cost DESC
        LIMIT 5
    ");
} else {
    $costYear = $conn->query("
        SELECT v.license_plate, SUM(v.total_cost) AS total_cost, c.brand_model, c.vehicle_type
        FROM v_repair_cost_by_year_car v
        LEFT JOIN cars c ON v.license_plate = c.license_plate
        GROUP BY v.license_plate, c.brand_model, c.vehicle_type
        ORDER BY total_cost DESC
        LIMIT 5
    ");
}

// Safely get counts with defaults
$totalCars = $carStatus['total_car'] ?? 0;
$normalCars = $carStatus['total_available'] ?? 0;
$repairingCars = $carStatus['total_repairing'] ?? 0;
$externalCars = $carStatus['repair_count'] ?? 0;
$waitCars = $carStatus['wait_for_repair'] ?? 0; 
?>

<div class="container-fluid p-4">
    
    <!-- Status Cards Row -->
    <div class="row g-4 mb-5">
        <!-- Total Cars -->
        <div class="col-xl-4 col-md-6">
            <div class="stat-card" style="animation-delay: 0.1s;">
                <div class="stat-icon bg-blue">
                    <i class="fa-solid fa-car"></i>
                </div>
                <div class="stat-info">
                    <h3>ทั้งหมด</h3>
                    <p><?= number_format($totalCars) ?> คัน</p>
                </div>
            </div>
        </div>
        
        <!-- Normal -->
        <div class="col-xl-4 col-md-6">
            <div class="stat-card" style="animation-delay: 0.2s;">
                <div class="stat-icon bg-green">
                    <i class="fa-solid fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3>พร้อมใช้งาน</h3>
                    <p><?= number_format($normalCars) ?> คัน</p>
                </div>
            </div>
        </div>

        <!-- Repairing -->
        <div class="col-xl-4 col-md-6">
            <div class="stat-card" style="animation-delay: 0.3s;">
                <div class="stat-icon bg-orange">
                    <i class="fa-solid fa-wrench"></i>
                </div>
                <div class="stat-info">
                    <h3>กำลังซ่อม(ติดสถานะ)</h3>
                    <p><?= number_format($repairingCars) ?> คัน</p>
                </div>
            </div>
        </div>


    </div>

    <!-- Charts & Tables Row -->
    <div class="row g-4">
        
        <!-- Top Repaired Cars -->
        <div class="col-lg-6">
            <div class="glass-container h-100 p-4">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="card-title mb-0">
                        <i class="fa-solid fa-ranking-star me-2 text-warning"></i> 
                        5 อันดับรถซ่อมบ่อย
                    </div>
                    <form method="GET" action="index.php" class="d-flex gap-2">
                        <input type="hidden" name="page" value="dashboard">
                        <!-- Preserve Year Selection -->
                        <input type="hidden" name="year" value="<?= $year ?>">
                        
                        <!-- Vehicle Type Filter -->
                        <select name="vehicle_type" class="form-select form-select-sm" 
                                style="width:auto; background-color: var(--bg-body); border: 1px solid rgba(255,255,255,0.1); color: var(--text-main);"
                                onchange="this.form.submit()">
                            <option value="all" <?= $vehicle_type == 'all' ? 'selected' : '' ?>>ประเภททั้งหมด</option>
                            <option value="car" <?= $vehicle_type == 'car' ? 'selected' : '' ?>>รถยนต์</option>
                            <option value="motorcycle" <?= $vehicle_type == 'motorcycle' ? 'selected' : '' ?>>รถจักรยานยนต์</option>
                        </select>
                    </form>
                </div>
                <div class="table-responsive">
                    <table class="dashboard-table">
                        <thead>
                            <tr>
                                <th>ทะเบียน</th>
                                <th>ยี่ห้อ/รุ่น</th>
                                <th class="text-end">จำนวนครั้ง</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $topRepairCars->fetch_assoc()){ 
                                $icon = 'fa-car-side';
                                if($row['vehicle_type'] == 'รถจักรยานยนต์') $icon = 'fa-motorcycle';
                            ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div style="width:40px; height:40px; background:rgba(255,255,255,0.1); border-radius:50%; display:flex; align-items:center; justify-content:center; margin-right:12px;">
                                            <i class="fa-solid <?= $icon ?> text-warning"></i>
                                        </div>
                                        <span class="fw-bold"><?= $row['license_plate'] ?></span>
                                    </div>
                                </td>
                                <td><?= $row['brand_model'] ?></td>
                                <td class="text-end">
                                    <span class="badge bg-warning text-dark pill-badge">
                                        <?= number_format($row['repair_count']) ?> ครั้ง
                                    </span>
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Top Cost Cars -->
        <div class="col-lg-6">
            <div class="glass-container h-100 p-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="card-title mb-0">
                        <i class="fa-solid fa-coins me-2 text-danger"></i>
                        5 อันดับค่าซ่อมสูงสุด
                    </div>
                    <form method="GET" action="index.php">
                        <input type="hidden" name="page" value="dashboard">
                        <!-- Preserve Vehicle Type Selection -->
                        <input type="hidden" name="vehicle_type" value="<?= $vehicle_type ?>">
                        
                        <select name="year" class="form-select form-select-sm" 
                                style="width:auto; display:inline-block; background-color: var(--bg-body); border: 1px solid rgba(255,255,255,0.1); color: var(--text-main); cursor: pointer;" 
                                onchange="this.form.submit()">
                            <option value="" <?= $year === '' ? 'selected' : '' ?>>ทั้งหมด</option>
                            <?php foreach($available_years as $y): ?>
                            <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>>
                                ปี <?= $y ?>
                            </option>
                            <?php endforeach ?>
                        </select>
                    </form>
                </div>
                <div class="table-responsive">
                    <table class="dashboard-table">
                        <thead>
                            <tr>
                                <th>ทะเบียน</th>
                                <th>ยี่ห้อ/รุ่น</th>
                                <th class="text-end">รวมค่าใช้จ่าย</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $costYear->fetch_assoc()){ 
                                 $icon = 'fa-tags';
                                 // Optional: distinct icon for moto in cost table too?
                            ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div style="width:40px; height:40px; background:rgba(255,255,255,0.1); border-radius:50%; display:flex; align-items:center; justify-content:center; margin-right:12px;">
                                            <i class="fa-solid <?= $icon ?> text-danger"></i>
                                        </div>
                                        <span class="fw-bold"><?= $row['license_plate'] ?></span>
                                    </div>
                                </td>
                                <td><?= $row['brand_model'] ?> <?= $row['vehicle_type'] ?></td>
                                <td class="text-end">
                                    <span class="text-danger fw-bold">
                                        ฿<?= number_format($row['total_cost'], 2) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <!-- Chart.js Section (Optional Visual) -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="glass-container p-4">
               <div class="row align-items-center">
                   <div class="col-md-4 text-center">
                       <h4 class="mb-4">สัดส่วนสถานะรถ</h4>
                       <div style="max-height: 250px; position: relative;">
                           <canvas id="statusChart"></canvas>
                       </div>
                   </div>
                   <div class="col-md-8">
                       <div class="p-4" style="background: rgba(255,255,255,0.02); border-radius: 12px;">
                           <h5><i class="fa-solid fa-chart-pie me-2"></i>ภาพรวมระบบ</h5>
                           <p class="text-muted">
                                ปัจจุบันมีรถในระบบทั้งหมด <?= $totalCars ?> คัน โดยแบ่งเป็นรถที่ใช้งานได้ปกติ <?= $normalCars ?> คัน 
                                และกำลังดำเนินการซ่อม <?= $repairingCars ?> คัน
                            </p>
                       </div>
                   </div>
               </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('statusChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['ปกติ', 'กำลังซ่อม'],
            datasets: [{
                data: [<?= $normalCars ?>, <?= $repairingCars ?>],
                backgroundColor: [
                    '#10b981', // Green for Normal
                    '#f59e0b', // Orange for Repairing
                ],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: '#9ca3af',
                        font: {
                            family: "'Prompt', sans-serif"
                        }
                    }
                }
            },
            cutout: '70%'
        }
    });
});
</script>
