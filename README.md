# 🚗 ระบบยานยนต์และแจ้งซ่อมบำรุง

> **Vehicle & Maintenance Management System**  
> กองกายภาพและสิ่งแวดล้อม มหาวิทยาลัยสงขลานครินทร์

[![PHP](https://img.shields.io/badge/PHP-8.0%2B-777BB4?style=flat-square&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-4479A1?style=flat-square&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3.3-7952B3?style=flat-square&logo=bootstrap&logoColor=white)](https://getbootstrap.com/)
[![License](https://img.shields.io/badge/License-Internal%20Use-red?style=flat-square)](.)

---

## 📋 สารบัญ

- [ภาพรวม](#-ภาพรวม)
- [คุณสมบัติหลัก](#-คุณสมบัติหลัก)
- [Tech Stack](#-tech-stack)
- [ความต้องการของระบบ](#-ความต้องการของระบบ)
- [การติดตั้ง](#-การติดตั้ง)
- [โครงสร้างไฟล์](#-โครงสร้างไฟล์)
- [ระบบสิทธิ์ผู้ใช้งาน](#-ระบบสิทธิ์ผู้ใช้งาน)
- [Workflow การซ่อมบำรุง](#-workflow-การซ่อมบำรุง)
- [ฐานข้อมูล](#-ฐานข้อมูล)

---

## 📌 ภาพรวม

ระบบยานยนต์และแจ้งซ่อมบำรุง เป็น Web Application สำหรับบริหารจัดการยานพาหนะและกระบวนการซ่อมบำรุงภายในองค์กร ครอบคลุมตั้งแต่การแจ้งซ่อม → ประเมินโดยช่าง → อนุมัติ → ดำเนินการซ่อม → บันทึกประวัติ

**🌐 URL ใช้งาน:** `https://carpse.psu.ac.th`

---

## ✨ คุณสมบัติหลัก

| ฟีเจอร์ | รายละเอียด |
|--------|-----------|
| 🚗 จัดการยานพาหนะ | เพิ่ม/แก้ไข/ลบ ข้อมูลรถยนต์และรถจักรยานยนต์ |
| 🔧 แจ้งซ่อมออนไลน์ | แจ้งซ่อมพร้อมส่ง Email แจ้งเตือนอัตโนมัติ |
| 👔 อนุมัติ 2 ระดับ | หัวหน้างาน → หัวหน้าช่าง |
| 🛠️ งานช่าง | ประเมินงานซ่อม บันทึกผล รายการอะไหล่/ค่าใช้จ่าย |
| 📊 Dashboard | ติดตามสถานะงานซ่อม Real-time พร้อมกราฟ |
| 📁 ประวัติการซ่อม | บันทึกและ Export ประวัติการซ่อมเป็น Excel |
| 🔐 RBAC | ระบบสิทธิ์ 5 ระดับ |
| 📧 Email แจ้งเตือน | แจ้งหัวหน้าแผนกอัตโนมัติเมื่อมีใบแจ้งซ่อมใหม่ |

---

## 🛠 Tech Stack

### Backend
- **PHP** 8.0+
- **MySQL** 5.7+ / MariaDB 10.3+
- **PHPMailer** 6.9 — ส่ง Email ผ่าน Gmail SMTP
- **PhpSpreadsheet** — Export ไฟล์ Excel (.xlsx)

### Frontend
- **Bootstrap** 5.3.3
- **jQuery** 3.7.1
- **Select2** 4.1.0-rc — Searchable Dropdown
- **Chart.js** — กราฟ Dashboard
- **Font Awesome** 6.5.1
- **Google Fonts** — Prompt

### Infrastructure
- **Apache** (XAMPP)
- **Composer** — PHP Package Manager

---

## 💻 ความต้องการของระบบ

| Component | Version |
|-----------|---------|
| PHP | 8.0+ |
| MySQL | 5.7+ หรือ MariaDB 10.3+ |
| Apache | XAMPP (แนะนำ) |
| Composer | ล่าสุด |

---

## 🚀 การติดตั้ง

### 1. Clone / วางไฟล์

```bash
# วางโฟลเดอร์ไว้ที่ htdocs ของ XAMPP
C:\xampp\htdocs\Car_repair\
```

### 2. ติดตั้ง PHP Dependencies

```bash
cd Car_repair
composer install
```

### 3. ตั้งค่าฐานข้อมูล

```bash
# สร้าง Database ชื่อ car_repair ใน MySQL
# จากนั้น Import ไฟล์ SQL ของระบบ
```

### 4. แก้ไขการเชื่อมต่อ DB

แก้ไขไฟล์ `db.php`:

```php
$host   = "localhost";
$user   = "root";       // username MySQL
$pass   = "";           // password MySQL
$dbname = "car_repair"; // ชื่อ Database
```

### 5. ตั้งค่า Email (ถ้าต้องการ)

แก้ไขไฟล์ `send_email.php` — ตั้งค่า Gmail SMTP credentials

### 6. เข้าใช้งาน

```
http://localhost/Car_repair/
```

---

## 📁 โครงสร้างไฟล์

```
Car_repair/
│
├── 🔐 Authentication
│   ├── login.php              # หน้าเข้าสู่ระบบ
│   ├── login.css
│   ├── auth_login.php         # ตรวจสอบ username/password
│   ├── logout.php             # ออกจากระบบ
│   └── update_password.php
│
├── 🏠 Core Layout
│   ├── index.php              # หน้าหลัก (SPA-like layout + Router)
│   ├── index.css
│   └── db.php                 # เชื่อมต่อ MySQL
│
├── 🚗 จัดการรถ
│   ├── car_list.php           # รายการรถ (ค้นหา, กรอง, pagination)
│   ├── add_car.php            # เพิ่มข้อมูลรถ
│   └── edit_car.php           # แก้ไขข้อมูลรถ
│
├── 🔧 แจ้งซ่อมบำรุง
│   ├── add_repair.php         # ฟอร์มแจ้งซ่อม + ส่ง email
│   ├── technician_comment.php # ช่างประเมินงานซ่อม
│   ├── repair_approve.php     # อนุมัติงานซ่อม (หัวหน้าช่าง)
│   ├── update_repair.php      # ช่างบันทึกผลงานซ่อม + อะไหล่
│   └── save_repair.php
│
├── 📊 Dashboard & สถานะ
│   ├── dashboard.php          # ตารางสถานะงานซ่อม
│   ├── dashboard_home.php     # ภาพรวมระบบ (กราฟ + อันดับ)
│   └── manager.php            # หน้าหัวหน้างาน (อนุมัติระดับ 1)
│
├── 📁 ประวัติการซ่อม
│   ├── history.php            # รายการรถ → ดูประวัติ
│   ├── history_detail.php     # รายละเอียดประวัติการซ่อมรายคัน
│   ├── add_history.php        # เพิ่มประวัติซ่อมย้อนหลัง
│   └── export_excel.php       # ส่งออกประวัติซ่อมเป็น .xlsx
│
├── 👥 จัดการผู้ใช้
│   └── manage_users.php       # สร้างผู้ใช้งานใหม่ (admin only)
│
├── 📧 Email & Utility
│   ├── send_email.php         # ฟังก์ชันส่ง email (PHPMailer)
│   └── test_email.php
│
└── 📦 Assets & Dependencies
    ├── logo.png
    ├── pse.png
    ├── composer.json
    ├── vendor/                # Composer packages
    └── templates/
        └── templates.xlsx     # Template สำหรับ export Excel
```

---

## 👥 ระบบสิทธิ์ผู้ใช้งาน

ระบบมี **5 ระดับสิทธิ์** (Role-Based Access Control)

| Role | ชื่อ | สิทธิ์ |
|------|------|--------|
| `admin` | ผู้ดูแลระบบ | เข้าถึงทุกหน้า + จัดการผู้ใช้ + CRUD รถ + อนุมัติทุกแผนก |
| `owner` | เจ้าของรถ | ดูรายการรถ + ดูสถานะ + แจ้งซ่อม + ดูประวัติ |
| `technician` | ช่าง | ช่างประเมิน + งานช่าง + ดูสถานะ |
| `manager` | หัวหน้างาน | อนุมัติใบแจ้งซ่อม (เฉพาะแผนกตัวเอง) + ภาพรวม |
| `chief_technician` | หัวหน้าช่าง | ทุกอย่างของช่าง + เพิ่ม/แก้ไขรถ + อนุมัติงานซ่อม |

### ตาราง Access Matrix

| เมนู | admin | owner | technician | manager | chief_tech |
|------|:-----:|:-----:|:----------:|:-------:|:----------:|
| หน้าหลัก | ✅ | ✅ | ✅ | ✅ | ✅ |
| รายการรถ | ✅ | ✅ | ❌ | ❌ | ✅ |
| แจ้งซ่อม | ✅ | ✅ | ❌ | ❌ | ✅ |
| ช่างประเมิน | ✅ | ❌ | ✅ | ❌ | ✅ |
| งานช่าง | ✅ | ❌ | ✅ | ❌ | ✅ |
| สถานะรถ | ✅ | ✅ | ✅ | ❌ | ✅ |
| ประวัติการซ่อม | ✅ | ✅ | ❌ | ✅ | ✅ |
| หัวหน้างาน | ✅ | ✅ | ❌ | ✅ | ❌ |
| งานซ่อมรออนุมัติ | ✅ | ❌ | ✅ | ❌ | ✅ |
| จัดการผู้ใช้ | ✅ | ❌ | ❌ | ❌ | ❌ |
| ภาพรวมระบบ | ✅ | ❌ | ❌ | ✅ | ✅ |

---

## 🔄 Workflow การซ่อมบำรุง

```
👤 ผู้แจ้งซ่อม                    📧 ส่ง Email แจ้งหัวหน้าแผนก
   add_repair.php          ─────►
                                         │
                                         ▼
👔 หัวหน้างาน (ระดับ 1)          อนุมัติ / ไม่อนุมัติ
   manager.php             ◄─────  (approve_status)
         │
         │ อนุมัติ
         ▼
🔧 ช่างประเมิน                   ระบุประเภทซ่อม + ลงชื่อ
   technician_comment.php  ─────►
                                         │
                                         ▼
⏳ หัวหน้าช่าง (ระดับ 2)         อนุมัติ / ไม่อนุมัติ
   repair_approve.php      ◄─────  (repair_approve)
         │
         │ อนุมัติ
         ▼
🛠️ ช่างดำเนินการ                 บันทึกผล + สถานะ + อะไหล่
   update_repair.php       ─────►
                                         │
                                         ▼
📊 ติดตามสถานะ                   dashboard.php
📁 บันทึกประวัติ                 history_detail.php
📤 Export Excel                  export_excel.php
```

### สถานะงานซ่อม

| สถานะ | ความหมาย |
|-------|---------|
| `ส่งซ่อม` | แจ้งซ่อมแล้ว ยังไม่ดำเนินการ |
| `รอเสนอราคา` | รอใบเสนอราคาจากร้าน |
| `รอซ่อม` | ยืนยันราคาแล้ว รอคิวซ่อม |
| `กำลังซ่อม` | อยู่ระหว่างดำเนินการ |
| `ส่งซ่อมภายนอก` | ส่งอู่ภายนอก |
| `ส่งมอบพัสดุ` | ซ่อมเสร็จ รอรับรถ |
| `เสร็จสิ้น` | ดำเนินการเสร็จสมบูรณ์ |

---

## 🗄 ฐานข้อมูล

**Database:** `car_repair` (MySQL, charset: UTF-8)

### ตารางหลัก

| ตาราง | คำอธิบาย |
|-------|---------|
| `users` | ข้อมูลผู้ใช้งาน (username, password bcrypt, role, department) |
| `cars` | ข้อมูลยานพาหนะ (ทะเบียน, ยี่ห้อ, ประเภท, รหัสครุภัณฑ์) |
| `repair_requests` | ใบแจ้งซ่อม (สถานะ, ผู้แจ้ง, วันที่, approval flow) |
| `repair_details` | รายละเอียดงานซ่อม (ช่าง, วันที่ซ่อม, ค่าใช้จ่าย) |
| `repair_items` | รายการอะไหล่/ชิ้นส่วน (ชื่อ, จำนวน, หน่วย, ราคา) |

### Views (ใช้ใน Dashboard)

| View | คำอธิบาย |
|------|---------|
| `v_dashboard_car_status` | สรุปจำนวนรถแยกตามสถานะ |
| `v_top_repair_cars` | อันดับรถที่ซ่อมบ่อยแยกตามปี |
| `v_repair_cost_by_year_car` | ค่าซ่อมรวมแยกตามรถและปี |

---

## 📧 ระบบ Email แจ้งเตือน

- **Library:** PHPMailer 6.9
- **SMTP:** Gmail (`smtp.gmail.com:587`, STARTTLS)
- **ทริกเกอร์:** เมื่อมีใบแจ้งซ่อมใหม่ → ส่ง HTML Email แจ้งหัวหน้าแผนกที่เกี่ยวข้อง

---

## 📤 Export Excel

- **Library:** PhpSpreadsheet
- **Template:** `templates/templates.xlsx`
- **ฟีเจอร์:** กรองตามปี/ประเภทซ่อม, สรุปค่าใช้จ่าย + VAT 7%, Merge cells อัตโนมัติ

---

## 📖 เอกสารเพิ่มเติม

ดูเอกสารฉบับเต็มได้ที่ [DOCUMENTATION.md](./DOCUMENTATION.md)

---

## 👨‍💻 การพัฒนา

```
Car repair system
├── Version: 1.0
├── วันที่: กุมภาพันธ์ 2569
└── พัฒนาโดย: กองกายภาพและสิ่งแวดล้อม มหาวิทยาลัยสงขลานครินทร์
```

---

> **© 2026 กองกายภาพและสิ่งแวดล้อม มหาวิทยาลัยสงขลานครินทร์**  
> ระบบพัฒนาเพื่อใช้งานภายในองค์กร — `https://carpse.psu.ac.th`
