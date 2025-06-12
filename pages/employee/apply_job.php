<?php
require '../db_connection.php';
include "../navBar.php";

// Define all possible questions
$allQuestions = [
    'q1' => 'ما هي أهم مهاراتك التي تتناسب مع هذه الوظيفة؟',
    'q2' => 'ما هي أهدافك المهنية على المدى الطويل؟',
    'q3' => 'كيف علمت عن هذه الوظيفة؟',
    'q4' => 'ما هي أكبر إنجازاتك المهنية حتى الآن؟',
    'q5' => 'كيف تتعامل مع ضغط العمل؟',
    'q6' => 'ما هي نقاط القوة والضعف لديك؟',
    'q7' => 'ما هو أكبر تحدٍ واجهته في عملك السابق وكيف تغلبت عليه؟',
    'q8' => 'لماذا تعتقد أنك مؤهل لهذه الوظيفة؟',
    'q9' => 'ما هي توقعاتك من بيئة العمل؟',
    'q10' => 'كيف تتعامل مع النزاعات في مكان العمل؟'
];

// Select 5 random questions
$randomKeys = array_rand($allQuestions, 5);
$selectedQuestions = array_intersect_key($allQuestions, array_flip($randomKeys));

// Check if user is logged in (assuming user_id is in $_SESSION)
// Redirect or handle if not logged in - added a basic check
if (!isset($_SESSION['user_id'])) {
    // Handle unauthorized access, maybe redirect to login
    header("Location: login.php"); // Replace login.php with your actual login page
    exit();
}
$userId = $_SESSION['user_id'];

// استقبال معرف الوظيفة
// Basic check for job_id
if (!isset($_GET['job_id'])) {
    // Handle missing job ID, maybe redirect to jobs list
    header("Location: findJobs.php"); // Replace jobs.php with your actual jobs list page
    exit();
}
$jobId = $_GET['job_id'];


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // معالجة تحميل السيرة الذاتية
    $resume = $_FILES['resume'];
    $uploadDir = 'uploads/';
    $uploadOk = 1;
    $fileType = strtolower(pathinfo($resume['name'], PATHINFO_EXTENSION));

    // Check if directory exists, if not create it
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true); // Use 0777 for testing, consider stricter permissions in production
    }

    $newFileName = uniqid() . '.' . $fileType; // Generate unique file name
    $targetFile = $uploadDir . $newFileName;

    // Check file size (e.g., 5MB limit)
    if ($resume['size'] > 5000000) {
        echo '<script>alert("عذراً، ملف السيرة الذاتية كبير جداً.");</script>';
        $uploadOk = 0;
    }

    // Allow certain file formats
    if($fileType != "pdf" && $fileType != "doc" && $fileType != "docx") {
        echo '<script>alert("عذراً، يُسمح فقط بملفات PDF و DOC و DOCX.");</script>';
        $uploadOk = 0;
    }

    // Check if $uploadOk is set to 0 by an error
    if ($uploadOk == 0) {
        echo '<script>alert("عذراً، ملف السيرة الذاتية لم يتم تحميله.");</script>';
    } else {
        // Try to upload file
        if (move_uploaded_file($resume['tmp_name'], $targetFile)) {
            $questionsAnswers = serialize($_POST['questions']);

            // Sanitize input data
            $coverLetter = htmlspecialchars($_POST['cover_letter']);
            $expectedSalary = filter_var($_POST['expected_salary'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            $whyJob = htmlspecialchars($_POST['why_job']);
            $whyCompany = htmlspecialchars($_POST['why_company']);
            $phone = htmlspecialchars($_POST['phone']);

            // Check if salary is a valid number
            if ($expectedSalary === false) {
                echo '<script>alert("الراتب المتوقع يجب أن يكون رقماً صالحاً.");</script>';
                // Handle invalid salary input, maybe don't insert into DB
            } else {
                $query = "INSERT INTO job_applications (job_id, user_id, cover_letter, expected_salary, resume, applied_at, why_job, why_company, phone, additional_questions)
                        VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?)";
                $stmt = $conn->prepare($query);

                // Use the new unique file name for the database
                $dbResumeName = $newFileName;

                $stmt->bind_param("iisssssss",
                    $jobId,
                    $userId,
                    $coverLetter,
                    $expectedSalary,
                    $dbResumeName, // Use the new file name here
                    $whyJob,
                    $whyCompany,
                    $phone,
                    $questionsAnswers
                );

                if ($stmt->execute()) {
                    echo '<script>alert("تم تقديم طلبك بنجاح!"); window.location.href = "findJobs.php";</script>'; // Redirect after success
                } else {
                    echo '<script>alert("حدث خطأ أثناء حفظ بيانات الطلب: ' . $stmt->error . '");</script>';
                }
                $stmt->close();
            }
        } else {
            echo '<script>alert("عذراً، حدث خطأ أثناء تحميل ملف السيرة الذاتية.");</script>';
        }
    }
    // Close DB connection after use
    $conn->close();
    exit(); // Stop execution after POST
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">



<body>

  <div class="form-container">
    <h2>تقديم طلب وظيفة</h2>
    <form method="post" enctype="multipart/form-data">
      <label for="resume">تحميل السيرة الذاتية (PDF, DOC, DOCX):</label>
      <input type="file" name="resume" id="resume" accept=".pdf,.doc,.docx" required>

      <label for="cover_letter">خطاب التقديم:</label>
      <textarea name="cover_letter" id="cover_letter" required
        placeholder="اكتب هنا عن اهتمامك بالوظيفة وكيف تتناسب مؤهلاتك..."></textarea>

      <label for="expected_salary">الراتب المتوقع (بالعملة المحلية):</label>
      <input type="number" name="expected_salary" id="expected_salary" step="0.01" required placeholder="مثال: 5000">

      <h3>الاسئلة الخاصة بالتقديم:</h3>
      <label for="why_job">لماذا تريد العمل في هذه الوظيفة؟</label>
      <textarea name="why_job" id="why_job" required placeholder="اشرح دوافعك للتقدم لهذه الوظيفة بالذات..."></textarea>

      <label for="why_company">لماذا تريد العمل في هذه الشركة؟</label>
      <textarea name="why_company" id="why_company" required
        placeholder="وضح لماذا هذه الشركة تحديداً تثير اهتمامك..."></textarea>

      <p>يرجى الاجابة على الاسئلة التالية:</p>
      <?php foreach ($selectedQuestions as $key => $question): ?>
      <label for="<?php echo $key; ?>"><?php echo $question; ?></label>
      <textarea name="questions[<?php echo $key; ?>]" id="<?php echo $key; ?>" required
        placeholder="اجابتك على السؤال..."></textarea>
      <?php endforeach; ?>

      <label for="phone">رقم الهاتف (واتساب):</label>
      <input type="text" name="phone" id="phone" required placeholder="مثال: 009665... أو +9665...">
      <input type="submit" value="تقديم الطلب">
    </form>
  </div>

</body>

</html>