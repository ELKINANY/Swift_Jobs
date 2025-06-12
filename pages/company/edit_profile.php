<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'company') {
  header('Location: login.php');
  exit();
}

require '../db_connection.php';

$user_id = $_SESSION['user_id'];

// جلب بيانات الحساب
$stmt = $conn->prepare("SELECT name, email , phone , description , location , category FROM companies WHERE company_id = ?");
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
  $location = trim($_POST['location']);
  $description = trim($_POST['description']);
  $category = trim($_POST['category']);
  $phone = trim($_POST['phone']);
  $hashed_password = password_hash($password, PASSWORD_DEFAULT);

  if (empty($name) || empty($email)) {
    $error_message = "يجب ملء جميع الحقول المطلوبة.";
  } else {
    if (!empty($password)) {
      // تحديث مع كلمة المرور
      $hashed_password = password_hash($password, PASSWORD_DEFAULT);
      $stmt = $conn->prepare("UPDATE companies SET name = ?, email = ?, password = ?, phone = ?, location = ?, description = ?, category = ? WHERE company_id = ?");
      $stmt->bind_param("sssssssi", $name, $email, $hashed_password, $phone, $location, $description, $category, $user_id);
    } else {
      // تحديث بدون كلمة المرور
      $stmt = $conn->prepare("UPDATE companies SET name = ?, email = ?, phone = ?, location = ?, description = ?, category = ? WHERE company_id = ?");
      $stmt->bind_param("ssssssi", $name, $email, $phone, $location, $description, $category, $user_id);
    }

    // تنفيذ الاستعلام
    if ($stmt->execute()) {
      $success_message = "تم تحديث بيانات الشركة بنجاح!";
    } else {
      $error_message = "حدث خطأ أثناء تحديث البيانات: " . $stmt->error;
    }

    $stmt->close();
  }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="ltr">

<body>
  <div class="dashboard-container">
    <!-- الشريط الجانبي -->
    <?php include "./sidebar.php" ?>

    <!-- المحتوى الرئيسي -->
    <div class="content">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>تعديل الحساب</h1>
      </div>

      <?php if (!empty($success_message)): ?>
      <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <?= htmlspecialchars($success_message); ?>
      </div>
      <?php endif; ?>

      <?php if (!empty($error_message)): ?>
      <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i>
        <?= htmlspecialchars($error_message); ?>
      </div>
      <?php endif; ?>

      <div class="form-container">
        <form method="POST">
          <div class="mb-3">
            <label class="form-label">اسم الشركة:</label>
            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']); ?>"
              required>
          </div>

          <div class="mb-3">
            <label class="form-label">البريد الإلكتروني:</label>
            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']); ?>"
              required>
          </div>

          <div class="mb-3">
            <label class="form-label">كلمة المرور الجديدة (اختياري):</label>
            <input type="password" name="password" class="form-control"
              placeholder="اترك الحقل فارغًا إذا كنت لا تريد تغيير كلمة المرور">
          </div>

          <div class="mb-3">
            <label class="form-label">رقم الهاتف:</label>
            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone']); ?>"
              required>
          </div>

          <div class="mb-3">
            <label class="form-label">الموقع:</label>
            <input type="text" name="location" class="form-control" value="<?= htmlspecialchars($user['location']); ?>"
              required>
          </div>

          <div class="mb-3">
            <label class="form-label">وصف الشركة:</label>
            <textarea name="description" class="form-control"
              required><?= htmlspecialchars($user['description']); ?></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label">التخصص:</label>
            <input type="text" name="category" class="form-control" value="<?= htmlspecialchars($user['category']); ?>"
              required>
          </div>

          <div class="d-grid gap-2">
            <button type="submit" name="update_profile" class="btn btn-primary">
              <i class="fas fa-save"></i> تحديث الحساب
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script>
  document.addEventListener('DOMContentLoaded', function() {
    console.log('Edit Profile Page Loaded');
  });
  </script>
</body>

</html>