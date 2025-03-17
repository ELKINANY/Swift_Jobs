<?php
session_start();


// التحقق من تسجيل الدخول كشركة
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'company') {
    header('Location: ../login.php');
    exit();
}

require '../db_connection.php'; // الاتصال بقاعدة البيانات
$company_id = $_SESSION['user_id'];

// إضافة وظيفة جديدة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_job'])) {
    $title = $_POST['title'];
    $job_type = $_POST['job_type'];
    $salary = $_POST['salary'];
    $description = $_POST['description'];
    $requirements = $_POST['requirements'];
    $test_question = $_POST['test_question'];
    $specialization = isset($_POST['specialization']) ? $_POST['specialization'] : null;

    $stmt = $conn->prepare("INSERT INTO jobs (company_id, title, job_type, salary, description, requirements, test_question, specialization) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issdssss", $company_id, $title, $job_type, $salary, $description, $requirements, $test_question, $specialization);

    if ($stmt->execute()) {
        $success_message = "تمت إضافة الوظيفة بنجاح.";
    } else {
        $error_message = "حدث خطأ أثناء إضافة الوظيفة.";
    }
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إضافة وظيفة</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
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
        <a href="add_job.php" class="active"><i class="fas fa-plus-circle"></i> إضافة وظيفة</a>
        <a href="view_jobs.php"><i class="fas fa-briefcase"></i> إدارة الوظائف</a>
        <a href="edit_profile.php"><i class="fas fa-user-edit"></i> تعديل الحساب</a>
        <a href="logout.php" class="text-danger"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a>
    </div>

    <!-- المحتوى الرئيسي -->
    <div class="content">
        <h1 class="text-center mb-4">إضافة وظيفة جديدة</h1>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"> <?= $success_message; ?> </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"> <?= $error_message; ?> </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">المسمى الوظيفي:</label>
                <input type="text" name="title" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">نوع الوظيفة:</label>
                <select name="job_type" class="form-select" required>
                    <option value="Full Time">دوام كامل</option>
                    <option value="Part Time">دوام جزئي</option>
                    <option value="Contract">عقد</option>
                    <option value="Internship">تدريب</option>
                    <option value="WARDIA"> ورديه</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">الراتب:</label>
                <input type="number" name="salary" class="form-control" step="0.01" required>
            </div>

            <div class="mb-3">
                <label class="form-label">الوصف:</label>
                <textarea name="description" class="form-control" required></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">المتطلبات:</label>
                <textarea name="requirements" class="form-control" required></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">التخصص:</label>
                <input type="text" name="specialization" class="form-control" required>
            </div>


            <div class="mb-3">
                <label class="form-label">السؤال الاختباري (اختياري):</label>
                <textarea name="test_question" class="form-control"></textarea>
            </div>

            <button type="submit" name="add_job" class="btn btn-primary">إضافة الوظيفة</button>
        </form>
    </div>
</div>

</body>
</html>
