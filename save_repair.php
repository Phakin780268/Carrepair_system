 <!-- save_repair-form update_repair -->
<?php
include 'db.php';

$repair_id   = $_POST['repair_id'];
$note        = $_POST['mechanic_note'];
$cost        = $_POST['cost'];
$responsible = $_POST['responsible'];
$status      = $_POST['status'];

/* UPDATE / INSERT */
$check = $conn->prepare(
    "SELECT id FROM repair_details WHERE repair_id = ?"
);
$check->bind_param("i", $repair_id);
$check->execute();
$exists = $check->get_result()->num_rows > 0;

if ($exists) {
    $stmt = $conn->prepare("
        UPDATE repair_details
        SET mechanic_note=?, cost=?, responsible=?, status=?
        WHERE repair_id=?
    ");
    $stmt->bind_param("sdssi",
        $note, $cost, $responsible, $status, $repair_id
    );
} else {
    $stmt = $conn->prepare("
        INSERT INTO repair_details
        (mechanic_note, cost, responsible, status, repair_id)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("sdssi",
        $note, $cost, $responsible, $status, $repair_id
    );
}
$stmt->execute();

/* sync status */
$u = $conn->prepare(
    "UPDATE repair_requests SET status=? WHERE id=?"
);
$u->bind_param("si", $status, $repair_id);
$u->execute();

echo "<script>alert('บันทึกข้อมูลสำเร็จ'); window.location=index.php?page=technician';</script>"; 
    exit; 


