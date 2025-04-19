<?php
require '../db_connection.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$job_id = $_GET['job_id'] ?? null;
if (!$job_id) {
    header('Location: index.php');
    exit();
}
// Fetch job details
$stmt = $conn->prepare("SELECT * FROM jobs WHERE job_id  = ?");
$stmt->execute([$job_id]);
$job = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$job) {
    $_SESSION['error_message'] = 'Job not found';
    header('Location: all_jobs.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $location = $_POST['location'];
    $salary = $_POST['salary'];
    $experience = $_POST['experience'];
    $status = $_POST['status'];

    try {
        $stmt = $conn->prepare("UPDATE jobs SET title = ?, description = ?, location = ?, salary = ?, experience = ?, status = ? WHERE id = ?");
        $stmt->execute([$title, $description, $location, $salary, $experience, $status, $job_id]);
        
        $_SESSION['success_message'] = 'Job updated successfully';
        header('Location: all_jobs.php');
        exit();
    } catch (PDOException $e) {
        $_SESSION['error_message'] = 'Error updating job: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Job - Swift Jobs Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/admin_header.php'; ?>

    <div class="container mt-5">
        <h2>Edit Job</h2>
        
        <form method="POST" class="mt-4">
            <div class="mb-3">
                <label for="title" class="form-label">Job Title</label>
                <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($job['title']); ?>" required>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="5" required><?php echo htmlspecialchars($job['description']); ?></textarea>
            </div>

            <div class="mb-3">
                <label for="location" class="form-label">Location</label>
                <input type="text" class="form-control" id="location" name="location" value="<?php echo htmlspecialchars($job['location']); ?>" required>
            </div>

            <div class="mb-3">
                <label for="salary" class="form-label">Salary</label>
                <input type="text" class="form-control" id="salary" name="salary" value="<?php echo htmlspecialchars($job['salary']); ?>">
            </div>

            <div class="mb-3">
                <label for="experience" class="form-label">Experience (years)</label>
                <input type="number" class="form-control" id="experience" name="experience" value="<?php echo htmlspecialchars($job['experience']); ?>">
            </div>

            <div class="mb-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="active" <?php echo $job['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $job['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Update Job</button>
            <a href="all_jobs.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
