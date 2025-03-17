<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'company') {
    header('Location: login.php');
    exit();
}

require '../db_connection.php';

// التحقق من وجود job_id في الرابط
if (!isset($_GET['job_id'])) {
    die("لم يتم تحديد الوظيفة.");
}

$job_id = intval($_GET['job_id']);
$company_id = $_SESSION['user_id'];

// جلب بيانات الوظيفة للتعديل
$stmt = $conn->prepare("SELECT * FROM jobs WHERE job_id = ? AND company_id = ?");
$stmt->bind_param("ii", $job_id, $company_id);
$stmt->execute();
$job = $stmt->get_result()->fetch_assoc();

if (!$job) {
    die("الوظيفة غير موجودة أو لا تملك صلاحية لتعديلها.");
}

// تحديث بيانات الوظيفة
$success_message = "";
$error_message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_job'])) {
    $title = trim($_POST['title']);
    $job_type = trim($_POST['job_type']);
    $salary = trim($_POST['salary']);
    $description = trim($_POST['description']);
    $requirements = trim($_POST['requirements']);
    $test_question = trim($_POST['test_question']);

    if (empty($title) || empty($job_type) || empty($salary) || empty($description) || empty($requirements)) {
        $error_message = "يجب ملء جميع الحقول المطلوبة.";
    } else {
        $stmt = $conn->prepare("UPDATE jobs SET title = ?, job_type = ?, salary = ?, description = ?, requirements = ?, test_question = ? WHERE job_id = ? AND company_id = ?");
        $stmt->bind_param("ssdsssii", $title, $job_type, $salary, $description, $requirements, $test_question, $job_id, $company_id);

        if ($stmt->execute()) {
            $success_message = "تم تحديث الوظيفة بنجاح.";
        } else {
            $error_message = "حدث خطأ أثناء تحديث الوظيفة.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل وظيفة</title>
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
            <h1 class="text-center mb-4">تعديل الوظيفة</h1>

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success"><?= $success_message; ?></div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger"><?= $error_message; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">المسمى الوظيفي:</label>
                    <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($job['title']); ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">نوع الوظيفة:</label>
                    <select name="job_type" class="form-select" required>
                        <option value="full-time" <?= $job['job_type'] === 'full-time' ? 'selected' : ''; ?>>دوام كامل</option>
                        <option value="part-time" <?= $job['job_type'] === 'part-time' ? 'selected' : ''; ?>>دوام جزئي</option>
                        <option value="shift" <?= $job['job_type'] === 'shift' ? 'selected' : ''; ?>>ورديات</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">الراتب:</label>
                    <input type="number" name="salary" class="form-control" value="<?= htmlspecialchars($job['salary']); ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">الوصف:</label>
                    <textarea name="description" class="form-control" rows="5" required><?= htmlspecialchars($job['description']); ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">المتطلبات:</label>
                    <textarea name="requirements" class="form-control" rows="5" required><?= htmlspecialchars($job['requirements']); ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">السؤال الاختباري (اختياري):</label>
                    <textarea name="test_question" class="form-control" rows="3"><?= htmlspecialchars($job['test_question']); ?></textarea>
                </div>

                <button type="submit" name="update_job" class="btn btn-primary">تحديث الوظيفة</button>
            </form>
        </div>
    </div>
</div>

</body>
</html>
