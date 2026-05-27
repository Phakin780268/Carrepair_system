<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role     = $_POST['role'];
    $department = $_POST['department'] ?? '';

if($role === 'manager' && $department === ''){
    echo "<script>
        alert('❌ กรุณาเลือก Department สำหรับหัวหน้างาน');
        history.back();
    </script>";
    exit;
}

    $allowRoles = ['admin','owner','technician','manager','chief_technician'];
if(!in_array($role, $allowRoles)){
    echo "<script>
        alert('❌ Role ไม่ถูกต้อง');
        history.back();
    </script>";
    exit;
}


    //  เช็ก username ซ้ำ
    $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $check->bind_param("s", $username);
    $check->execute();
    $exists = $check->get_result()->num_rows > 0;

    if ($exists) {
        echo "<script>
            alert('❌ Username นี้มีอยู่แล้ว');
            history.back();
        </script>";
        exit;
    }

    // ถ้าไม่ซ้ำ → ค่อย insert
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("
        INSERT INTO users (username, password, role, department)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("ssss", $username, $hash, $role, $department);

    if ($stmt->execute()) {
        echo "<script>
            alert('✅ เพิ่มผู้ใช้สำเร็จ');
            window.location='index.php?page=home';
        </script>";
        exit;
    }
}
?>


<h2>สร้างผู้ใช้งาน</h2>
<hr>

<form method="POST" class="form-card">

    <div class="form-group">
        <label>Username</label>
        <input type="text" name="username" required>
    </div>

    <div class="form-group password-group">
    <label>Password</label>

    <div class="password-wrapper">
        <input type="password" name="password" id="password" required>

        <span class="toggle-password"
              onclick="togglePassword()">
            <i class="fa-solid fa-eye" id="eyeIcon"></i>
        </span>
    </div>
</div>


    <div class="form-group">
        <label>สิทธิ์ผู้ใช้งาน (Role)</label>
        <select name="role" id="roleSelect" required onchange="toggleDepartment()">
            <option value="">-- เลือกสิทธิ์ --</option>
            <option value="admin">Admin</option>
            <option value="owner">ผู้ใช้งาน(เจ้าของรถ)</option>
            <option value="technician">ช่าง</option>
            <option value="manager">หัวหน้างาน</option>
            <option value="chief_technician">หัวหน้าช่าง</option>
        </select>
    </div>

    <div class="form-group" id="deptGroup" style="display:none;">
    <label>Department</label>
    <select name="department" id="department">
        <option value="">-- เลือกแผนก --</option>
        <option value="งานยุทธศาสตร์และบริการกลาง">งานยุทธศาสตร์และบริการกลาง</option>
        <option value="งานออกแบบและก่อสร้าง">งานออกแบบและก่อสร้าง</option>
        <option value="งานภูมิทัศน์และสิ่งแวดล้อม">งานภูมิทัศน์และสิ่งแวดล้อม</option>
        <option value="งานรักษาความปลอดภัย">งานรักษาความปลอดภัย</option>
        <option value="งานสาธารณูปโภค">งานสาธารณูปโภค</option>
        <option value="ศูนย์บริการฉุกเฉินและบรรเทาสาธารณภัย">ศูนย์บริการฉุกเฉินและบรรเทาสาธารณภัย</option>
        <option value="หน่วยยานพาหนะ">หน่วยยานพาหนะ</option>
    </select>
</div>


    <div class="form-actions">
        <button type="submit" class="btn btn-success">
            💾 สร้างผู้ใช้งาน
        </button>

        <a href="index.php?page=home" class="btn btn-secondary">
            « กลับหน้ารายการผู้ใช้
        </a>
    </div>

</form>

<!-- Javascript -->
 <script>
function togglePassword() {
    const pwd = document.getElementById('password');
    const icon = document.getElementById('eyeIcon');

    if (pwd.type === 'password') {
        pwd.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        pwd.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
</script>

<script>
function toggleDepartment(){
    const role = document.getElementById('roleSelect').value;
    const deptGroup = document.getElementById('deptGroup');
    const deptSelect = document.getElementById('department');

    if(role === 'manager'){
        deptGroup.style.display = 'block';
        deptSelect.required = true;
    }else{
        deptGroup.style.display = 'none';
        deptSelect.required = false;
        deptSelect.value = '';
    }
}

document.addEventListener("DOMContentLoaded", toggleDepartment);
</script>
