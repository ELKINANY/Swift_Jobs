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

$userId = $_SESSION['user_id'];

// استقبال معرف الوظيفة
$jobId = $_GET['job_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // معالجة تحميل السيرة الذاتية
    $resume = $_FILES['resume']['name'];
    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true); // Create the directory with proper permissions
    }
    
    $targetFile = $uploadDir . basename($_FILES['resume']['name']);
    if (move_uploaded_file($_FILES['resume']['tmp_name'], $targetFile)) {
        $questionsAnswers = serialize($_POST['questions']);
        
        $query = "INSERT INTO job_applications (job_id, user_id, cover_letter, expected_salary, resume, applied_at, why_job, why_company, phone, additional_questions) 
                  VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $coverLetter = $_POST['cover_letter'];
        $expectedSalary = $_POST['expected_salary'];
        $whyJob = $_POST['why_job'];
        $whyCompany = $_POST['why_company'];
        $phone = $_POST['phone'];
        
        $stmt->bind_param("iisssssss", 
            $jobId,
            $userId,
            $coverLetter,
            $expectedSalary,
            $resume,
            $whyJob,
            $whyCompany,
            $phone,
            $questionsAnswers
        );
        $stmt->execute();

        echo '<script>alert("تم تقديم طلبك بنجاح!");</script>';
    } else {
        echo '<script>alert("حدث خطأ أثناء تحميل السيرة الذاتية.");</script>';
    }
}
?>
<style>
    form {
        width: 50%;
        margin: 20px auto;
        text-align: right;
        background-color: #f1f1f1;
        padding: 20px;
        border-radius: 10px;
    }
    label {
        display: block;
        margin-bottom: 10px;
        font-weight: bold;
    }
    input[type="file"] {
        display: block;
        margin-bottom: 20px;
        width: 100%;
    }
    textarea {
        width: 100%;
        height: 100px;
        margin-bottom: 20px;
    }
    input[type="submit"] {
        background-color: #4CAF50;
        color: white;
        padding: 12px 20px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }
    input[type="number"] {
        width: 100%;
        margin-bottom: 20px;
    }

</style>

<form method="post" enctype="multipart/form-data">
    <label for="resume">تحميل السيرة الذاتية:</label>
    <input type="file" name="resume" id="resume" required>
    <br>
    <label for="cover_letter">خطاب التقديم:</label>
    <textarea name="cover_letter" id="cover_letter" required></textarea>
    <br>
    <label for="expected_salary">الراتب المتوقع:</label>
    <input type="number" name="expected_salary" id="expected_salary" required>
    <br>

    <h3>الاسئلة الخاصة بالتقديم:</h3>
    <label for="why_job">لماذا تريد العمل في هذه الوظيفة؟</label>
    <textarea name="why_job" id="why_job" required></textarea>
    <br>

    <label for="why_company">لماذا تريد العمل في هذه الشركة؟</label>
    <textarea name="why_company" id="why_company" required></textarea>
    <br>
    <p>يرجى الاجابة على الاسئلة التالية:</p>
    <?php foreach ($selectedQuestions as $key => $question): ?>
    <label for="<?php echo $key; ?>"><?php echo $question; ?></label>
    <textarea name="questions[<?php echo $key; ?>]" id="<?php echo $key; ?>" required></textarea>
    <br>
    <?php endforeach; ?>

    <label for="phone">رقم الهاتف: الواتساب</label>
    <input type="number" name="phone" id="phone" required>
    <br>

    <input type="submit" value="تقديم">
</form>