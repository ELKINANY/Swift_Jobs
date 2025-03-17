<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'company') {
    header('Location: login.php');
    exit();
}

require '../db_connection.php';

// التحقق من تمرير job_id
if (!isset($_GET['job_id'])) {
    die("لم يتم تحديد الوظيفة.");
}

$job_id = intval($_GET['job_id']);
$company_id = $_SESSION['user_id'];

// الفلاتر
$order_by = "expected_salary"; // الافتراضي الترتيب حسب الراتب
$order_dir = "ASC"; // الترتيب تصاعدي افتراضيًا

if (isset($_GET['sort_by']) && in_array($_GET['sort_by'], ['name', 'email', 'expected_salary'])) {
    $order_by = $_GET['sort_by'];
}

if (isset($_GET['order']) && in_array($_GET['order'], ['ASC', 'DESC'])) {
    $order_dir = $_GET['order'];
}

// جلب المتقدمين بناءً على الفلترة
$stmt = $conn->prepare("
    SELECT users.user_id, users.name, users.email, users.cv_link, applications.expected_salary
    FROM applications
    JOIN users ON applications.user_id = users.user_id
    JOIN jobs ON applications.job_id = jobs.job_id
    WHERE applications.job_id = ? AND jobs.company_id = ?
    ORDER BY $order_by $order_dir
");
$stmt->bind_param("ii", $job_id, $company_id);
$stmt->execute();
$applicants = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>عرض المتقدمين</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        .dashboard-container { display: flex; min-height: 100vh; }
        .sidebar { width: 250px; background: #343a40; color: white; padding: 20px; }
        .sidebar a { color: white; text-decoration: none; display: block; padding: 10px; border-radius: 5px; }
        .sidebar a:hover { background: #495057; }
        .content { flex-grow: 1; padding: 20px; }
        .filter-form { display: flex; gap: 10px; align-items: center; }
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
        <h1 class="text-center mb-4">المتقدمون للوظيفة</h1>

        <!-- نموذج الفلترة -->
        <form method="GET" class="filter-form mb-4">
            <input type="hidden" name="job_id" value="<?= $job_id; ?>">
            <label>ترتيب حسب:</label>
            <select name="sort_by" class="form-select">
                <option value="expected_salary" <?= $order_by == 'expected_salary' ? 'selected' : ''; ?>>الراتب</option>
                <option value="name" <?= $order_by == 'name' ? 'selected' : ''; ?>>الاسم</option>
                <option value="email" <?= $order_by == 'email' ? 'selected' : ''; ?>>البريد الإلكتروني</option>
            </select>

            <select name="order" class="form-select">
                <option value="ASC" <?= $order_dir == 'ASC' ? 'selected' : ''; ?>>تصاعدي</option>
                <option value="DESC" <?= $order_dir == 'DESC' ? 'selected' : ''; ?>>تنازلي</option>
            </select>

            <button type="submit" class="btn btn-primary">تصفية</button>
        </form>

        <!-- عرض المتقدمين -->
        <?php if ($applicants->num_rows > 0): ?>
            <table class="table table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>الاسم</th>
                        <th>البريد الإلكتروني</th>
                        <th>السيرة الذاتية</th>
                        <th>الراتب المتوقع</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($applicant = $applicants->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($applicant['name']); ?></td>
                            <td><?= htmlspecialchars($applicant['email']); ?></td>
                            <td>
                                <?php if (!empty($applicant['cv_link'])): ?>
                                    <a href="<?= htmlspecialchars($applicant['cv_link']); ?>" target="_blank" class="btn btn-success btn-sm">
                                        <i class="fas fa-file-pdf"></i> عرض
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">غير متوفر</span>
                                <?php endif; ?>
                            </td>
                            <td><?= number_format($applicant['expected_salary'], 2); ?> جنيه</td>
                            <td>
                                <a href="contact_applicant.php?user_id=<?= $applicant['user_id']; ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-envelope"></i> تواصل
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-warning text-center">لا يوجد متقدمون لهذه الوظيفة حتى الآن.</div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
