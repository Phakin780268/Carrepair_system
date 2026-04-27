<?php
session_start();
ob_start();

/* ✅ บังคับให้ต้อง Login ก่อน */
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

/* ✅ ตัวแปรที่ต้องใช้ */
$page = $_GET['page'] ?? 'home';
$loggedIn = true;

/* ✅ map หน้า */
$map = [
    'car'         => 'car_list.php',
    'car_edit'    => 'edit_car.php',
    'car_add'     => 'add_car.php',
    'repair'      => 'add_repair.php',
    'technician'  => 'update_repair.php',
    'status'      => 'dashboard.php',
    'history'     => 'history.php',
    'manage'      => 'manage_users.php',
    'manager'     => 'manager.php',
    'tec_comment' => 'technician_comment.php',
    'approve'     => 'repair_approve.php',
    'dashboard'   => 'dashboard_home.php'
];

$pageTitle = 'ภาพรวมระบบ';
if(isset($map[$page])) {
    // Basic title mapping logic (can be expanded)
    switch($page) {
        case 'car': $pageTitle = 'รายการรถทั้งหมด'; break;
        case 'repair': $pageTitle = 'แจ้งซ่อมบำรุง'; break;
        case 'tec_comment': $pageTitle = 'ประเมินงานซ่อม (ช่าง)'; break;
        case 'technician': $pageTitle = 'งานซ่อมที่ต้องดำเนินการ'; break;
        case 'status': $pageTitle = 'สถานะการซ่อม'; break;
        case 'history': $pageTitle = 'ประวัติการซ่อม'; break;
        case 'manager': $pageTitle = 'ส่วนหัวหน้างาน'; break;
        case 'approve': $pageTitle = 'อนุมัติงานซ่อม'; break;
        case 'manage': $pageTitle = 'จัดการผู้ใช้งาน'; break;
        case 'dashboard': $pageTitle = 'ภาพรวมระบบ'; break;

    }
}
?>
<!DOCTYPE html>
<html lang="th" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบยานยนต์และแจ้งซ่อมบำรุง</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="index.css?v=<?=time()?>">
    <link rel="stylesheet" href="tabs.css?v=<?=time()?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Page specific CSS -->
    <link rel="stylesheet" href="car_list.css?v=<?=time()?>">
    <link rel="stylesheet" href="add_car.css?v=<?=time()?>">
    <link rel="stylesheet" href="dashboard.css?v=<?=time()?>">
    <link rel="stylesheet" href="history_detail.css?v=<?=time()?>">
    <link rel="stylesheet" href="manage_users.css?v=<?=time()?>">
    <link rel="stylesheet" href="manager.css?v=<?=time()?>">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    
    <!-- Bootstrap 5.3.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="layout">

    <!-- ================= MOBILE MENU TOGGLE ================= -->
    <button class="menu-toggle" id="menuToggle" aria-label="Toggle menu">
        <i class="fa-solid fa-bars"></i>
    </button>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- ================= SIDEBAR ================= -->
    <aside class="sidebar">
        <div class="logo-box">
            <a href="index.php?page=home" class="logo-link">
                <!-- Ensure logo.png is transparent or use a new icon -->
                <img src="logo.png" alt="logo">

            </a>
        </div>

        <nav>
            <?php if($loggedIn){ ?>

                <a href="index.php?page=home" class="menu <?= $page=='home'?'active':'' ?>">
                    <i class="fa-solid fa-house"></i> หน้าหลัก
                </a>

                <div style="margin: 15px 15px 10px; font-size: 11px; color: #8898aa; text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em;">เมนูหลัก</div>

                <?php if($_SESSION['role']=='admin' || $_SESSION['role']=='owner' || $_SESSION['role']=='chief_technician'){ ?>
                    <a href="index.php?page=car" class="menu <?= $page=='car'?'active':'' ?>">
                        <i class="fa-solid fa-car"></i> รายการรถ
                    </a>
                <?php } ?>

                <?php if($_SESSION['role']=='admin' || $_SESSION['role']=='owner' || $_SESSION['role']=='chief_technician'){ ?>
                    <a href="index.php?page=repair" class="menu <?= $page=='repair'?'active':'' ?>">
                        <i class="fa-solid fa-wrench"></i> แจ้งซ่อม
                    </a>
                <?php } ?>

                <?php if($_SESSION['role']=='admin' || $_SESSION['role']=='technician' || $_SESSION['role']=='chief_technician'){ ?>
                    <a href="index.php?page=tec_comment" class="menu <?= $page=='tec_comment'?'active':'' ?>">
                        <i class="fa-solid fa-magnifying-glass"></i> ช่างประเมิน
                    </a>
                <?php } ?>

                <?php if($_SESSION['role']=='admin' || $_SESSION['role']=='technician' || $_SESSION['role']=='chief_technician'){ ?>
                    <a href="index.php?page=technician" class="menu <?= $page=='technician'?'active':'' ?>">
                        <i class="fa-solid fa-screwdriver-wrench"></i> งานช่าง
                    </a>
                <?php } ?>

                <?php if($_SESSION['role']=='admin' || $_SESSION['role']=='technician' || $_SESSION['role']=='owner' || $_SESSION['role']=='chief_technician'){ ?>
                    <a href="index.php?page=status" class="menu <?= $page=='status'?'active':'' ?>">
                        <i class="fa-solid fa-chart-simple"></i> สถานะรถ
                    </a>
                <?php } ?>

                <?php if($_SESSION['role']=='admin' || $_SESSION['role']=='owner' || $_SESSION['role']=='manager' || $_SESSION['role']=='chief_technician'){ ?>
                    <a href="index.php?page=history" class="menu <?= $page=='history'?'active':'' ?>">
                        <i class="fa-solid fa-folder-open"></i> ประวัติการซ่อม
                    </a>
                <?php } ?>

                <div style="margin: 15px 15px 10px; font-size: 11px; color: #8898aa; text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em;">ผู้ดูแล</div>

                <?php if($_SESSION['role']=='admin' || $_SESSION['role']=='manager' || $_SESSION['role']=='owner' || $_SESSION['role']=='technician'){ ?>
                    <a href="index.php?page=manager" class="menu <?= $page=='manager'?'active':'' ?>">
                        <i class="fa-solid fa-user-tie"></i> หัวหน้างาน
                    </a>
                <?php } ?>

                <?php if($_SESSION['role']=='admin' || $_SESSION['role']=='chief_technician' || $_SESSION['role']=='technician' || $_SESSION['role']=='owner'){ ?>
                    <a href="index.php?page=approve" class="menu <?= $page=='approve'?'active':'' ?>">
                        <i class="fa-solid fa-clock"></i> งานซ่อมรออนุมัติ
                    </a>
                <?php } ?>

                <?php if($_SESSION['role']=='admin'){ ?>
                    <a href="index.php?page=manage" class="menu <?= $page=='manage'?'active':'' ?>">
                        <i class="fa-solid fa-users-gear"></i> จัดการผู้ใช้งาน
                    </a>
                <?php } ?>

                <?php if($_SESSION['role']=='admin' || $_SESSION['role']=='manager' || $_SESSION['role']=='chief_technician'){ ?>
                    <a href="index.php?page=dashboard" class="menu <?= $page=='dashboard'?'active':'' ?>">
                        <i class="fa-solid fa-gauge-high"></i> ภาพรวมระบบ
                    </a>
                <?php } ?>

            <?php } ?>
        </nav>


    </aside>

    <!-- ================= MAIN ================= -->
    <main class="main">
        
        <header class="top-header">
            <div class="header-title">
                <h1><?= $pageTitle ?></h1>
                <small>ระบบบริหารจัดการยานยนต์และซ่อมบำรุง</small>
            </div>
            <div class="header-actions">
                <div class="user-profile">
                    <div class="user-info">
                        <div class="user-details" style="text-align:right; margin-right:10px;">
                            <span class="user-name"><?= $_SESSION['username'] ?? 'Guest' ?></span>
                            <span class="user-role" style="display:block; font-size:0.75rem; color:var(--text-muted);"><?= $_SESSION['role'] ?? 'User' ?></span>
                        </div>
                        <div class="user-avatar">
                           <?= strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)) ?>
                        </div>
                    </div>
                    <a href="logout.php" class="logout-btn">
                        <i class="fa-solid fa-right-from-bracket"></i>
                    </a>
                </div>
            </div>
        </header>

        <div class="content-wrapper">
            <?php
            // Include db.php for connection
            include_once 'db.php';

            if ($page === 'home') {
                // 1. Count Total Cars
                $count_cars = 0;
                if ($res = $conn->query("SELECT COUNT(*) as c FROM cars")) {
                    $count_cars = $res->fetch_assoc()['c'];
                }

                // 2. Count In Progress (status = 'กำลังซ่อม')
                $count_repair = 0;
                if ($res = $conn->query("SELECT COUNT(*) as c FROM repair_details WHERE status = 'กำลังซ่อม'")) {
                    $count_repair = $res->fetch_assoc()['c'];
                }

                // 3. Count Pending Manager Approval (approve_status IS NULL or 'รออนุมัติ')
                $count_done = 0;
                if ($res = $conn->query("SELECT COUNT(*) as c FROM repair_requests WHERE (approve_status IS NULL OR approve_status = 'รออนุมัติ') AND (repair_approve IS NULL OR repair_approve <> 'ประวัติ')")) {
                    $count_done = $res->fetch_assoc()['c'];
                }

                // 4. Count Pending Approval (repair_approve = 'รออนุมัติ')
                $count_wait = 0;
                if ($res = $conn->query("SELECT COUNT(*) as c FROM repair_requests WHERE repair_approve = 'รออนุมัติ'")) {
                    $count_wait = $res->fetch_assoc()['c'];
                }

                echo '
                <div class="fade-in-up">
                    <div class="welcome-card">
                        <div class="welcome-overlay"></div>
                        <div class="welcome-content">
                            <h1>ยินดีต้อนรับสู่ระบบยานยนต์</h1>
                            <p>จัดการข้อมูลยานพาหนะ การแจ้งซ่อม และติดตามสถานะงานซ่อมได้อย่างมีประสิทธิภาพ สะดวก รวดเร็ว และตรวจสอบได้</p>
                            <a href="index.php?page=repair" class="welcome-btn">
                                <i class="fa-solid fa-plus"></i> แจ้งซ่อมใหม่
                            </a>
                        </div>
                    </div>

                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon bg-blue">
                                <i class="fa-solid fa-car"></i>
                            </div>
                            <div class="stat-info">
                                <h3>รถทั้งหมด</h3>
                                <p>'. number_format($count_cars) .' คัน</p> 
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon bg-orange">
                                <i class="fa-solid fa-wrench"></i>
                            </div>
                            <div class="stat-info">
                                <h3>กำลังซ่อม</h3>
                                <p>'. number_format($count_repair) .' รายการ</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon bg-green">
                                <i class="fa-solid fa-check-circle"></i>
                            </div>
                            <div class="stat-info">
                                <h3>รอหัวหน้างานอนุมัติ</h3>
                                <p>'. number_format($count_done) .' รายการ</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon bg-red">
                                <i class="fa-solid fa-clock"></i>
                            </div>
                            <div class="stat-info">
                                <h3>รออนุมัติซ่อม</h3>
                                <p>'. number_format($count_wait) .' รายการ</p>
                            </div>
                        </div>
                    </div>

                    <div class="info-section">
                        <div class="info-header">
                            <i class="fa-solid fa-bullhorn" style="color: var(--warning-color); font-size: 1.5rem;"></i>
                            <div style="margin-left: 12px;">
                                <h2>ข่าวประชาสัมพันธ์</h2>
                                <span style="font-size: 0.85rem; color: var(--text-muted);">อัพเดทล่าสุดจากกองกายภาพและสิ่งแวดล้อม</span>
                            </div>
                        </div>
                        <div class="info-content">
                            <div style="margin-bottom:20px; background: rgba(245, 54, 92, 0.1); padding: 15px; border-left: 4px solid var(--danger-color); border-radius: 4px;">
                                <strong style="color: var(--danger-color); font-size: 1.1rem;">📢 ประกาศ: เริ่มใช้งานระบบรูปแบบใหม่</strong><br>
                                <span style="font-size:0.9rem; color:#666; display: block; margin-top: 5px;">ตั้งแต่วันที่ 5 มีนาคม 2569 เป็นต้นไป</span>
                            </div>
                            <p style="margin-bottom: 20px;">ทางกองกายภาพและสิ่งแวดล้อมได้ทำการพัฒนาระบบการแจ้งซ่อมรูปแบบใหม่ เพื่ออำนวยความสะดวกในการใช้งาน
                             และสามารถติดตามสถานะงานได้แบบ Real-time</p>
                            
                            <div style="background: rgba(255, 255, 255, 0.05); padding: 15px; border-radius: 8px; display: flex; align-items: center; gap: 15px;">
                                <i class="fa-solid fa-phone-volume" style="font-size: 24px; color: var(--primary-color);"></i>
                                <div>
                                    <div style="font-weight: 600; color: var(--text-main);">ต้องการความช่วยเหลือ?</div>
                                    <div style="font-size: 0.9rem; color: var(--text-muted);">ติดต่อกองกายภาพและสิ่งแวดล้อม โทร. 074-282183</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                ';
            } elseif (isset($map[$page])) {
                echo '<div class="fade-in-up">';
                include $map[$page];
                echo '</div>';
            } else {
                echo "<p>ไม่พบหน้า</p>";
            }
            ?>
        </div>

    </main>

</div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Sidebar Toggle Script -->
    <script>
    (function() {
        const toggle  = document.getElementById('menuToggle');
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.getElementById('sidebarOverlay');

        function openSidebar() {
            sidebar.classList.add('active');
            overlay.classList.add('active');
            toggle.innerHTML = '<i class="fa-solid fa-xmark"></i>';
        }

        function closeSidebar() {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
            toggle.innerHTML = '<i class="fa-solid fa-bars"></i>';
        }

        toggle.addEventListener('click', function() {
            sidebar.classList.contains('active') ? closeSidebar() : openSidebar();
        });

        overlay.addEventListener('click', closeSidebar);

        // Close sidebar when a menu link is clicked (mobile UX)
        document.querySelectorAll('.sidebar .menu').forEach(function(link) {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 992) {
                    closeSidebar();
                }
            });
        });
    })();
    </script>
</body>
</html>
