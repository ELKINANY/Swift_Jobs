<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'company') {
    header('Location: login.php');
    exit();
}

require '../db_connection.php';

$user_id = $_SESSION['user_id'];

// جلب بيانات الحساب
$stmt = $conn->prepare("SELECT name, email FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    die("المستخدم غير موجود.");
}

// تحديث بيانات الحساب
$success_message = "";
$error_message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    if (empty($name) || empty($email)) {
        $error_message = "يجب ملء جميع الحقول المطلوبة.";
    } else {
        if (!empty($password)) {
            // تحديث مع كلمة المرور
            $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, password = ? WHERE user_id = ?");
            $stmt->bind_param("sssi", $name, $email, $hashed_password, $user_id);
        } else {
            // تحديث بدون كلمة المرور
            $stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE user_id = ?");
            $stmt->bind_param("ssi", $name, $email, $user_id);
        }

        if ($stmt->execute()) {
            $success_message = "تم تحديث الحساب بنجاح.";
            // تحديث بيانات الجلسة
            $_SESSION['name'] = $name;
        } else {
            $error_message = "حدث خطأ أثناء تحديث الحساب.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل الحساب</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        .dashboard-container { display: flex; min-height: 100vh; }
        .sidebar { width: 250px; background: #343a40; color: white; padding: 20px; }
        .sidebar a { color: white; text-decoration: none; display: block; padding: 10px; border-radius: 5px; }
        .sidebar a:hover { background: #495057; }
        .content { flex-grow: 1; padding: 20px; }
    </style>
</head>
<body>

<div class="dashboard-container">
    <!-- الشريط الجانبي -->
    <div class="sidebar">
        <h4 class="text-center">لوحة التحكم</h4>
        <hr>
        <a href="company_dashboard.php"><i class="fas fa-th-large"></i> الرئيسية</a>
        <a href="add_job.php"><i class="fas fa-plus-circle"></i> إضافة وظيفة</a>
        <a href="view_jobs.php"><i class="fas fa-briefcase"></i> إدارة الوظائف</a>
      
        <a href="edit_profile.php" class="active"><i class="fas fa-user-edit"></i> تعديل الحساب</a>
        <a href="logout.php" class="text-danger"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a>
    </div>

    <!-- المحتوى الرئيسي -->
    <div class="content">
        <div class="container mt-5">
            <h1 class="text-center mb-4">تعديل الحساب</h1>

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success"><?= $success_message; ?></div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger"><?= $error_message; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">اسم الشركة:</label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']); ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">البريد الإلكتروني:</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']); ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">كلمة المرور الجديدة (اختياري):</label>
                    <input type="password" name="password" class="form-control" placeholder="اترك الحقل فارغًا إذا كنت لا تريد تغيير كلمة المرور">
                </div>

                <button type="submit" name="update_profile" class="btn btn-primary">تحديث الحساب</button>
            </form>
        </div>
    </div>
</div>

</body>
</html>
