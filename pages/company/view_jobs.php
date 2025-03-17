<?php 
session_start();

// التحقق من تسجيل الدخول كشركة
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'company') {
    header('Location: ../login.php');
    exit();
}

require '../db_connection.php'; // الاتصال بقاعدة البيانات
$company_id = $_SESSION['user_id'];

// جلب الوظائف الخاصة بالشركة
$stmt = $conn->prepare("SELECT * FROM jobs WHERE company_id = ?");
$stmt->bind_param("i", $company_id);
$stmt->execute();
$jobs = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الوظائف</title>
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
        <a href="add_job.php"><i class="fas fa-plus-circle"></i> إضافة وظيفة</a>
        <a href="view_jobs.php" class="active"><i class="fas fa-briefcase"></i> إدارة الوظائف</a>
    
        <a href="edit_profile.php"><i class="fas fa-user-edit"></i> تعديل الحساب</a>
        <a href="logout.php" class="text-danger"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a>
    </div>

    <!-- المحتوى الرئيسي -->
    <div class="content">
        <h1 class="text-center mb-4">إدارة الوظائف</h1>

        <table class="table table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>المسمى الوظيفي</th>
                    <th>نوع الوظيفة</th>
                    <th>الراتب</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($job = $jobs->fetch_assoc()): ?>
                    <tr>
                        <td><?= $job['title']; ?></td>
                        <td><?= $job['job_type']; ?></td>
                        <td><?= number_format($job['salary'], 2); ?> جنيه</td>
                        <td>
                            <a href="view_applicants.php?job_id=<?= $job['job_id']; ?>" class="btn btn-info btn-sm"><i class="fas fa-users"></i> عرض المتقدمين</a>
                            <a href="edit_job.php?job_id=<?= $job['job_id']; ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i> تعديل</a>
                            <a href="delete_job.php?job_id=<?= $job['job_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('هل أنت متأكد من حذف هذه الوظيفة؟');"><i class="fas fa-trash"></i> حذف</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
