<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'company') {
    header('Location: login.php');
    exit();
}

if (!isset($_SESSION['email'])) {
    header('Location:../login.php');
    exit();
}

require '../db_connection.php';

// تضمين مكتبة PHPMailer
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// استخدام الفئات مع الأسماء المكانية
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// التحقق من وجود user_id في الرابط
if (!isset($_GET['user_id'])) {
    die("لم يتم تحديد المتقدم.");
}

$user_id = intval($_GET['user_id']);

// جلب بيانات المتقدم
$stmt = $conn->prepare("SELECT name, email FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$applicant = $stmt->get_result()->fetch_assoc();

if (!$applicant) {
    die("المتقدم غير موجود.");
}

$success_message = "";
$error_message = "";

// معالجة الإرسال عند النقر على زر الإرسال
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    $to_email = $applicant['email'];

    if (empty($subject) || empty($message)) {
        $error_message = "يجب ملء جميع الحقول.";
    } else {
        // إعدادات PHPMailer
        $mail = new PHPMailer(true); // استخدام true لتمكين الاستثناءات

        // إعدادات SMTP لـ Mailtrap
        $mail->isSMTP();
        $mail->Host = 'sandbox.smtp.mailtrap.io'; // SMTP Mailtrap
        $mail->SMTPAuth = true;
        $mail->Username = '6611e8ac20aaa7'; // اسم المستخدم Mailtrap
        $mail->Password = 'd41e52c6842962'; // كلمة المرور Mailtrap
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        // عنوان المرسل يجب أن يكون عنوان Mailtrap
        $mail->setFrom('smtp@mailtrap.io', 'اسمك هنا'); // عنوان البريد الإلكتروني الخاص بـ Mailtrap
        $mail->addAddress($to_email, $applicant['name']); // إرسال الرسالة إلى المتقدم

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = nl2br($message);

        try {
            if ($mail->send()) {
                $success_message = "تم إرسال الرسالة بنجاح!";
            } else {
                $error_message = "فشل إرسال البريد الإلكتروني.";
            }
        } catch (Exception $e) {
            $error_message = "فشل إرسال البريد الإلكتروني: " . $mail->ErrorInfo;
        }
    }
}
?>


<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إرسال رسالة</title>
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
            <h1 class="text-center mb-4">إرسال رسالة إلى المتقدم</h1>

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success"><?= $success_message; ?></div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger"><?= $error_message; ?></div>
            <?php endif; ?>

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
                    <label class="form-label">موضوع الرسالة:</label>
                    <input type="text" name="subject" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">نص الرسالة:</label>
                    <textarea name="message" class="form-control" rows="5" required></textarea>
                </div>

                <button type="submit" name="send_message" class="btn btn-primary">إرسال</button>
            </form>
        </div>
    </div>
</div>

</body>
</html>
