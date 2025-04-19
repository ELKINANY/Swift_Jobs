<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'company') {
    header('Location: login.php');
    exit();
}

// if (!isset($_SESSION['email'])) {
//     header('Location:../ login.php');
//     exit();
// }

require '../db_connection.php';

// التحقق من وجود user_id في الرابط
if (!isset($_GET['user_id'])) {
    die("لم يتم تحديد المتقدم.");
}

$user_id = intval($_GET['user_id']);

// جلب بيانات المتقدم
$stmt = $conn->prepare("SELECT name, email, phone FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$applicant = $stmt->get_result()->fetch_assoc();

if (!$applicant) {
    die("المتقدم غير موجود.");
}

$whatsapp_url = '';

// معالجة الإرسال عند النقر على زر الإرسال
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    
    // تأكد من أن الحقول ليست فارغة
    if (!empty($subject) && !empty($message)) {
        // إنشاء رابط WhatsApp مع الموضوع والنص المدخل
        $whatsapp_url = "https://api.whatsapp.com/send?phone=20" . htmlspecialchars($applicant['phone']);
        $whatsapp_url .= "&text=" . urlencode("موضوع: " . $subject);
        $whatsapp_url .= "%0A" . "نص الرسالة: " . $message;
        $whatsapp_url .= "%0A" . "رقم الهاتف: " . htmlspecialchars($applicant['phone']);
    }
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التواصل مع المتقدم</title>
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
        <a href="edit_profile.php"><i class="fas fa-user-edit"></i> تعديل الحساب</a>
        <a href="logout.php" class="text-danger"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a>
    </div>

    <!-- المحتوى الرئيسي -->
    <div class="content">
        <div class="container mt-5">
            <h1 class="text-center mb-4">التواصل مع المتقدم</h1>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">اسم المتقدم:</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($applicant['name']); ?>" disabled>
                </div>

                <div class="mb-3">
                    <label class="form-label">البريد الإلكتروني:</label>
                    <input type="email" class="form-control" value="<?= htmlspecialchars($applicant['email']); ?>" disabled>
                </div>

                <div class="mb-3">
                    <label class="form-label">رقم الهاتف:</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($applicant['phone']); ?>" disabled>
                </div>

                <div class="mb-3">
                    <label class="form-label">موضوع الرسالة:</label>
                    <input type="text" name="subject" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">نص الرسالة:</label>
                    <textarea name="message" class="form-control" rows="5" required></textarea>
                </div>

                <button type="submit" class="btn btn-primary">إرسال</button>
            </form>

            <?php if ($whatsapp_url): ?>
                <div class="mt-4">
                    <a href="<?= $whatsapp_url ?>" class="btn btn-success btn-sm" target="_blank">
                        <i class="fab fa-whatsapp"></i> إرسال عبر واتساب
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>
