<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'company') {
    header('Location: ../login.php');
    exit();
}

require '../db_connection.php'; // الاتصال بقاعدة البيانات

$company_id = $_SESSION['user_id'];

// جلب عدد الوظائف التي نشرتها الشركة
$stmt = $conn->prepare("SELECT COUNT(*) AS job_count FROM jobs WHERE company_id = ?");
$stmt->bind_param("i", $company_id);
$stmt->execute();
$job_result = $stmt->get_result()->fetch_assoc();
$job_count = $job_result['job_count'];

// جلب عدد التقييمات (إذا كان هناك نظام تقييم)
$review_count = 0; // يمكن تعديل هذا لاحقًا بناءً على النظام
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم الشركة</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 250px;
            background: #343a40;
            color: white;
            padding: 20px;
        }
        .sidebar a {
            color: white;
            text-decoration: none;
            display: block;
            padding: 10px;
            border-radius: 5px;
        }
        .sidebar a:hover {
            background: #495057;
        }
        .content {
            flex-grow: 1;
            padding: 20px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0px 4px 6px rgba(0,0,0,0.1);
        }
        .stat-card i {
            font-size: 30px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    <!-- الشريط الجانبي -->
    <?php include "sidebar_company.php"; ?>
    <!-- المحتوى الرئيسي -->
    <div class="content">
        <h2 class="mb-4">مرحبًا بك في لوحة تحكم الشركة</h2>
        
        <div class="row">
            <div class="col-md-6">
                <div class="stat-card">
                    <i class="fas fa-briefcase text-primary"></i>
                    <h3><?= $job_count; ?></h3>
                    <p>الوظائف المنشورة</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="stat-card">
                    <i class="fas fa-star text-warning"></i>
                    <h3><?= $review_count; ?></h3>
                    <p>التقييمات</p>
                </div>
            </div>
        </div>

    </div>
</div>

</body>
</html>
