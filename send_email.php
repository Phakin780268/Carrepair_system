<?php
require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// สร้าง mailer instance ครั้งเดียว แล้วใช้ซ้ำ
function createMailer(): PHPMailer
{
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'buildingpsu@gmail.com';
    $mail->Password   = 'nyxt pdxk ikpp flzn';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->CharSet    = 'UTF-8';

    // ✅ เพิ่ม: คง connection ไว้เมื่อส่งหลายฉบับ
    $mail->SMTPKeepAlive = true;

    // ✅ เพิ่ม: จำกัดเวลารอ ไม่ให้ค้างนาน (วินาที)
    $mail->Timeout = 10;

    $mail->setFrom('buildingpsu@gmail.com', 'Carrepairsystem');
    return $mail;
}

// ✅ รับ $mail จากภายนอกได้ เพื่อ reuse connection
function sendRepairEmailByDepartment(
    string $department,
    string $license_plate,
    string $request_item,
    string $responsible,
    string $request_date,
    string $note,
    ?PHPMailer $mail = null
): bool {

    $departmentEmails = [
        "งานยุทธศาสตร์และบริการกลาง"      => "phakin780268@gmail.com",
        "งานออกแบบและก่อสร้าง"             => "soothi.so@psu.ac.th",
        "งานภูมิทัศน์และสิ่งแวดล้อม"       => "thewin.y@psu.ac.th",
        "งานรักษาความปลอดภัย"              => "pongsit.k@psu.ac.th",
        "งานสาธารณูปโภค"                   => "nattawat.bu@psu.ac.th",
        "ศูนย์บริการฉุกเฉินและบรรเทาสาธารณภัย" => "ongart.b@psu.ac.th",
    ];

    if (!isset($departmentEmails[$department])) {
        return false;
    }

    $to        = $departmentEmails[$department];
    $ownMailer = $mail === null;

    if ($ownMailer) {
        $mail = createMailer();
    }

    try {
        // ✅ clearAddresses() เพื่อ reuse instance ได้ปลอดภัย
        $mail->clearAddresses();
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = "แจ้งเตือน: มีการแจ้งซ่อมใหม่ ($department)";
        $mail->Body    = "
            <h3>มีการแจ้งซ่อมใหม่</h3>
            <b>ทะเบียนรถ:</b> $license_plate <br>
            <b>สังกัด:</b> $department <br>
            <b>ผู้รับผิดชอบ:</b> $responsible <br>
            <b>วันที่แจ้ง:</b> $request_date <br>
            <b>รายการซ่อม:</b><br>
            <pre>$request_item</pre>
            <b>หมายเหตุ:</b> $note
            <br><br>
            <a href='https://carpse.psu.ac.th'
               style='display:inline-block;padding:10px 15px;
                      background-color:#007bff;color:white;
                      text-decoration:none;border-radius:5px;'>
                เข้าสู่ระบบ ยานยนต์
            </a>
        ";

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;

    } finally {
        // ✅ ปิด connection เฉพาะเมื่อสร้าง mailer เอง
        if ($ownMailer) {
            $mail->smtpClose();
        }
    }
}