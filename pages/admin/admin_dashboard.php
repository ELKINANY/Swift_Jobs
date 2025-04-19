<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

require '../db_connection.php';

// جلب عدد الوظائف
$stmt = $conn->prepare("SELECT COUNT(*) AS job_count FROM jobs");
$stmt->execute();
$job_count = $stmt->get_result()->fetch_assoc()['job_count'];

// جلب عدد الشركات
$stmt = $conn->prepare("SELECT COUNT(*) AS company_count FROM users WHERE role = 'Employer'");
$stmt->execute();
$company_count = $stmt->get_result()->fetch_assoc()['company_count'];

// جلب عدد الباحثين عن العمل
$stmt = $conn->prepare("SELECT COUNT(*) AS job_seeker_count FROM users WHERE role = 'Applicant'");
$stmt->execute();
$job_seeker_count = $stmt->get_result()->fetch_assoc()['job_seeker_count'];

// جلب عدد المستخدمين الإداريين
$stmt = $conn->prepare("SELECT COUNT(*) AS admin_count FROM users WHERE role = 'admin'");
$stmt->execute();
$admin_count = $stmt->get_result()->fetch_assoc()['admin_count'];
?>

<?php include "../navBar.php"; ?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم الأدمن</title>
    <style>
        .dashboard-stats {
            background: linear-gradient(135deg, #00B67A 0%, #008C5F 100%);
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-card i {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        .stat-card h3 {
            font-size: 2rem;
            color: #333;
            margin-bottom: 0.5rem;
        }
        .stat-card p {
            color: #666;
            font-size: 1.1rem;
            margin: 0;
        }
    </style>
</head>
<body>

<div class="container-fluid px-0">
    <div class="dashboard-stats text-white">
        <div class="container">
            <h2 class="text-center mb-4">لوحة تحكم الأدمن</h2>
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="stat-card">
                        <i class="fas fa-briefcase text-primary"></i>
                        <h3><?= $job_count; ?></h3>
                        <p>الوظائف</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <i class="fas fa-building text-info"></i>
                        <h3><?= $company_count; ?></h3>
                        <p>الشركات</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <i class="fas fa-users text-success"></i>
                        <h3><?= $job_seeker_count; ?></h3>
                        <p>الباحثين عن عمل</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <i class="fas fa-user-shield text-danger"></i>
                        <h3><?= $admin_count; ?></h3>
                        <p>الإداريين</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container mt-4">
        <div class="row g-4">
            <div class="col-md-3">
                <a href="all_jobs.php" class="card text-decoration-none h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-briefcase fa-2x mb-3 text-primary"></i>
                        <h5 class="card-title">إدارة الوظائف</h5>
                        <p class="card-text">عرض وإدارة جميع الوظائف المتاحة</p>
                    </div>
                </a>
            </div>
            <div class="col-md-3">
                <a href="all_companies.php" class="card text-decoration-none h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-building fa-2x mb-3 text-info"></i>
                        <h5 class="card-title">إدارة الشركات</h5>
                        <p class="card-text">عرض وإدارة حسابات الشركات</p>
                    </div>
                </a>
            </div>
            <div class="col-md-3">
                <a href="all_job_seekers.php" class="card text-decoration-none h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-users fa-2x mb-3 text-success"></i>
                        <h5 class="card-title">إدارة الباحثين عن عمل</h5>
                        <p class="card-text">عرض وإدارة حسابات الباحثين عن عمل</p>
                    </div>
                </a>
            </div>
            <div class="col-md-3">
                <a href="all_admins.php" class="card text-decoration-none h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-user-shield fa-2x mb-3 text-danger"></i>
                        <h5 class="card-title">إدارة المشرفين</h5>
                        <p class="card-text">عرض وإدارة حسابات المشرفين</p>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>

</body>
</html>
