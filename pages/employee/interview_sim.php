<?php
session_start();

// Check if user is logged in and is an employee
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    header('Location: login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>محاكاة المقابلات - Swift Jobs</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
  <style>
  :root {
    --primary: #1e3a8a;
    --secondary: #3b82f6;
    --accent: #60a5fa;
    --dark: #1f2937;
    --light: #f9fafb;
    --success: #10b981;
    --warning: #f59e0b;
    --danger: #ef4444;
  }

  body {
    font-family: 'Tajawal', sans-serif;
    background-color: var(--light);
    margin: 0;
    padding: 0;
    color: var(--dark);
  }

  /* Navbar Styles */
  .navbar-container {
    width: 100%;
    padding: 0;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  }

  .nav {
    display: flex;
    flex-direction: row;
    justify-content: center;
    gap: 1.5rem;
    padding: 1rem 0;
    background-color: transparent;
    /* Remove gradient background */
  }

  .nav-item {
    list-style: none;
  }

  .nav-link {
    color: var(--dark);
    font-weight: 500;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    transition: all 0.3s ease;
  }

  .nav-link:hover {
    background-color: rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
  }

  .dropdown-menu {
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  }

  .dropdown-item {
    color: var(--dark);
    padding: 0.5rem 1rem;
    transition: all 0.3s ease;
  }

  .dropdown-item:hover {
    background-color: var(--light);
    color: var(--primary);
  }

  .content {
    padding: 2rem;
    transition: all 0.3s ease;
  }

  h1 {
    font-size: 1.8rem;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 2rem;
    position: relative;
    text-align: right;
  }

  h1::after {
    content: "";
    position: absolute;
    bottom: -0.5rem;
    right: 0;
    width: 80px;
    height: 4px;
    background-color: var(--secondary);
    border-radius: 2px;
  }

  .sim-container {
    max-width: 900px;
    margin: 0 auto;
    background: white;
    padding: 2rem;
    border-radius: 1rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    animation: fadeIn 0.5s ease-out;
  }

  @keyframes fadeIn {
    from {
      opacity: 0;
      transform: translateY(10px);
    }

    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  .sim-header {
    text-align: center;
    margin-bottom: 2rem;
  }

  .sim-header h2 {
    font-size: 1.5rem;
    color: var(--primary);
    margin-bottom: 1rem;
  }

  .sim-header p {
    color: #6b7280;
    font-size: 1rem;
  }

  .sim-step {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    padding: 1rem;
    margin-bottom: 1rem;
    background: #f8fafc;
    border-radius: 0.5rem;
    transition: all 0.3s ease;
  }

  .sim-step:hover {
    background: #f1f5f9;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  }

  .sim-step i {
    font-size: 1.5rem;
    color: var(--secondary);
  }

  .sim-step h5 {
    font-size: 1.1rem;
    color: var(--dark);
    margin: 0;
  }

  .sim-step p {
    color: #6b7280;
    margin: 0;
  }

  .btn-start {
    background: linear-gradient(90deg, var(--primary), var(--secondary));
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 0.5rem;
    font-weight: 500;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .btn-start:hover {
    background: linear-gradient(90deg, var(--primary), #1e40af);
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
  }

  .coming-soon {
    text-align: center;
    margin-top: 2rem;
    color: var(--warning);
    font-weight: 500;
  }

  /* Responsive Design */
  @media (max-width: 768px) {
    .content {
      padding: 1.5rem;
    }

    h1 {
      font-size: 1.5rem;
    }

    .sim-container {
      padding: 1.5rem;
    }

    .sim-step {
      flex-direction: column;
      text-align: center;
    }

    .nav {
      flex-direction: column;
      align-items: center;
    }

    .nav-link {
      padding: 0.5rem;
    }
  }
  </style>
</head>

<body>
  <!-- الـ Navbar في الأعلى -->
  <div class="navbar-container">
    <?php include "../navBar.php"; ?>
  </div>

  <!-- المحتوى الرئيسي -->
  <div class="content">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1>محاكاة المقابلات</h1>
    </div>

    <div class="sim-container">
      <div class="sim-header">
        <h2>استعد لمقابلتك القادمة!</h2>
        <p>جرب محاكاة مقابلة عمل واقعية تساعدك على تحسين مهاراتك وتجهيزك للنجاح.</p>
      </div>

      <div class="sim-step">
        <i class="fas fa-play-circle"></i>
        <div>
          <h5>ابدأ المحاكاة</h5>
          <p>اختر نوع المقابلة (تقنية، سلوكية، عامة) وابدأ التدريب.</p>
        </div>
      </div>

      <div class="sim-step">
        <i class="fas fa-question-circle"></i>
        <div>
          <h5>أجب عن الأسئلة</h5>
          <p>ستظهر لك أسئلة متنوعة مع وقت محدد للإجابة.</p>
        </div>
      </div>

      <div class="sim-step">
        <i class="fas fa-star"></i>
        <div>
          <h5>احصل على تقييمك</h5>
          <p>بعد الانتهاء، ستحصل على تقييم شامل مع نصائح للتحسين.</p>
        </div>
      </div>

      <div class="text-center mt-4">
        <button class="btn-start">
          <i class="fas fa-play"></i> ابدأ الآن
        </button>
      </div>

      <div class="coming-soon">
        <i class="fas fa-info-circle me-1"></i> هذه الميزة قيد التطوير وستكون متاحة قريبًا!
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>