<?php
session_start();
require 'db_connection.php'; // الاتصال بقاعدة البيانات

// معالجة التسجيل
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // التحقق من صحة البيانات
    if ($password !== $confirm_password) {
        $error_message = "كلمتا المرور غير متطابقتين.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'company')");
        $stmt->bind_param("sss", $name, $email, $hashed_password);

        if ($stmt->execute()) {
            $_SESSION['user_id'] = $conn->insert_id;
            $_SESSION['role'] = 'company';
            $_SESSION['name'] = $name;

            // توجيه المستخدم إلى لوحة التحكم
            header('Location:company/company_dashboard.php');
            exit();
        } else {
            $error_message = "حدث خطأ أثناء التسجيل. يرجى المحاولة مرة أخرى.";
        }
    }
}
?>

<?php include "./navBar.php" ?>

<div class="reg-wrapper">
    <div class="reg-form">
        <h1 class="reg-heading">سجل كمنشأة</h1>
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"> <?= $error_message; ?> </div>
        <?php endif; ?>
        <form method="POST">
            <div class="input-box">
                <input type="text" class="reg-input" name="name" placeholder="الاسم الكامل" required>
            </div>
            <div class="input-box">
                <input type="tel" class="reg-input" name="phone" placeholder="رقم الجوال" required>
            </div>
            <div class="input-box">
                <input type="email" class="reg-input" name="email" placeholder="البريد الالكتروني" required>
            </div>
            <div class="input-box">
                <input type="password" class="reg-input" name="password" placeholder="كلمة المرور" required>
            </div>
            <div class="input-box">
                <input type="password" class="reg-input" name="confirm_password" placeholder="تأكيد كلمة المرور" required>
            </div>
            <button type="submit" class="reg-submit">التالي</button>
            <div class="existing-account">
                <a href="login.php">لديك حساب بالفعل؟</a>
            </div>
        </form>
    </div>
    <div class="reg-image">
        <img src="<?= BASE_URL ?>page-form-thumb.webp" alt="Registration illustration">
    </div>
</div>

<?php include "./footer.php" ?>
