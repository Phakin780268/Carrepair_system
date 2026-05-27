<?php
include 'db.php';
require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as WriterXlsx;

// โหลดไฟล์ Template
$templatePath = __DIR__ . "/templates.xlsx";

if (!file_exists($templatePath)) {
    die("ไม่พบไฟล์ Template: " . $templatePath);
}

$reader = new Xlsx();
$spreadsheet = $reader->load($templatePath);

if ($spreadsheet->getSheetCount() == 0) {
    die("Template ไม่มี sheet หรือไฟล์เสีย");
}

$sheet = $spreadsheet->getActiveSheet();


// ==========================
// 2) รับค่า Parameter
// ==========================
$car_id = (int)($_GET['car_id'] ?? 0);
$year   = $_GET['year'] ?? '';
$repair_type = trim($_GET['repair_type'] ?? '');

if ($car_id <= 0) {
    die("ไม่พบ car_id");
}

// ==========================
// 3) Query ข้อมูล
// ==========================
$sql = "
    SELECT
        rd.enter_date,
        ri.item_name,
        ri.quantity,
        ri.unit,
        ri.price,
        rd.responsible_company,
        rd.notes
    FROM repair_requests rr
    LEFT JOIN repair_details rd ON rr.id = rd.repair_id
    LEFT JOIN repair_items ri ON rr.id = ri.repair_id
    WHERE rr.car_id = ?
";

$params = [$car_id];
$types  = "i";

if ($year !== '') {
    $sql .= " AND YEAR(rd.enter_date) = ?";
    $params[] = (int)$year;
    $types .= "i";
}

if ($repair_type !== '') {
    $sql .= " AND rr.repair_type = ?";
    $params[] = $repair_type;
    $types .= "s";
}

$sql .= " ORDER BY rd.enter_date DESC, rr.id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// ==========================
// 4) ตั้งค่าตำแหน่งเริ่มต้น
// ==========================
$startRow = 6;
$row = $startRow;
$no  = 1;

$currentDate = null;
$groupStartRow = $startRow;
$groupTotal = 0;

// ==========================
// 5) ฟังก์ชัน Merge กลุ่ม
// ==========================
$mergeGroup = function($sheet, $start, $end) {
    if ($end >= $start) {
        $sheet->mergeCells("A{$start}:A{$end}");
        $sheet->mergeCells("B{$start}:B{$end}");
        $sheet->mergeCells("G{$start}:G{$end}");
        $sheet->mergeCells("H{$start}:H{$end}");

        foreach (['A','B','G','H'] as $col) {
            $sheet->getStyle("{$col}{$start}:{$col}{$end}")
                ->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        }
    }
};

// ==========================
// 6) ฟังก์ชันเขียนสรุป
// ==========================
$writeSummary = function($sheet, &$row, $total) {

    $vat = round($total * 7 / 100, 2);
    $net = $total + $vat;

    // Merge แนวตั้ง
    $sheet->mergeCells("A{$row}:A".($row+2));
    $sheet->mergeCells("B{$row}:B".($row+2));
    $sheet->mergeCells("G{$row}:G".($row+2));
    $sheet->mergeCells("H{$row}:H".($row+2));

    // รวม
    $sheet->setCellValue("C{$row}", "รวม");
    $sheet->mergeCells("C{$row}:E{$row}");
    $sheet->setCellValue("F{$row}", $total);
    $row++;

    // VAT
    $sheet->setCellValue("C{$row}", "ภาษีมูลค่าเพิ่ม 7%");
    $sheet->mergeCells("C{$row}:E{$row}");
    $sheet->setCellValue("F{$row}", $vat);
    $row++;

    // รวมสุทธิ
    $sheet->setCellValue("C{$row}", "รวมเป็นเงินทั้งสิ้น");
    $sheet->mergeCells("C{$row}:E{$row}");
    $sheet->setCellValue("F{$row}", $net);
    $sheet->getStyle("C{$row}:F{$row}")->getFont()->setBold(true);

    $row++;
};

// ==========================
// 7) เติมข้อมูลลง Excel
// ==========================
while ($data = $result->fetch_assoc()) {

    $repairDate = $data['enter_date'];

    if ($currentDate !== null && $repairDate != $currentDate) {

        // เขียนสรุปของวันก่อนหน้า
        $writeSummary($sheet, $row, $groupTotal);

        // merge กลุ่มรายการ
        $mergeGroup($sheet, $groupStartRow, $row - 4);

        // reset
        $groupStartRow = $row;
        $groupTotal = 0;
        $no++;
    }

    // copy style จาก template
    $sheet->duplicateStyle(
        $sheet->getStyle("A{$startRow}:H{$startRow}"),
        "A{$row}:H{$row}"
    );

    if ($repairDate != $currentDate) {
        $sheet->setCellValue("A{$row}", $no);
        $sheet->setCellValue("B{$row}", $repairDate);
        $sheet->setCellValue("G{$row}", $data['responsible_company']);
        $sheet->setCellValue("H{$row}", $data['notes']);
    }

    $sheet->setCellValue("C{$row}", $data['item_name']);
    $sheet->setCellValue("D{$row}", $data['quantity']);
    $sheet->setCellValue("E{$row}", $data['unit']);
    $sheet->setCellValue("F{$row}", $data['price']);

    $groupTotal += (float)$data['price'];

    $currentDate = $repairDate;
    $row++;
}

// ปิดกลุ่มสุดท้าย
if ($currentDate !== null) {
    $writeSummary($sheet, $row, $groupTotal);
    $mergeGroup($sheet, $groupStartRow, $row - 4);
}

// ==========================
// 8) Export Excel
// ==========================
$filename = "ประวัติการซ่อมบำรุง.xlsx";

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new WriterXlsx($spreadsheet);
$writer->save('php://output');
exit;
