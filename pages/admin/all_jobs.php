<?php
require '../db_connection.php';
session_start();
// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Handle job deletion
if (isset($_GET['delete'])) {
    $job_id = $_GET['delete'];
    try {
        // First check if the job exists
        $check_stmt = $conn->prepare("SELECT job_id  FROM jobs WHERE job_id  = ?");
        $check_stmt->execute([$job_id]);
        if ($check_stmt->fetchColumn()) {
            $stmt = $conn->prepare("DELETE FROM jobs WHERE job_id  = ?");
            $stmt->execute([$job_id]);
            $_SESSION['success_message'] = 'Job deleted successfully';
        } else {
            $_SESSION['error_message'] = 'Job not found';
        }
        header('Location: all_jobs.php');
        exit();
    } catch (PDOException $e) {
        $_SESSION['error_message'] = 'Error deleting job: ' . $e->getMessage();
        header('Location: all_jobs.php');
        exit();
    }
}

// Handle job status update
if (isset($_GET['status'])) {
    $job_id = $_GET['job_id'];
    $status = $_GET['status'];
    try {
        $stmt = $conn->prepare("UPDATE jobs SET status = ? WHERE job_id = ?");
        $stmt->execute([$status, $job_id]);
        $_SESSION['success_message'] = 'Job status updated successfully';
        header('Location: all_jobs.php');
        exit();
    } catch (PDOException $e) {
        $_SESSION['error_message'] = 'Error updating job status: ' . $e->getMessage();
        header('Location: all_jobs.php');
        exit();
    }
}

// Build query for jobs list
$query = "SELECT j.*, c.name, c.logo FROM jobs j 
          LEFT JOIN companies c ON j.company_id = c.company_id
          WHERE 1 = 1";

// Add search filters
$search = $_GET['search'] ?? '';
if (!empty($search)) {
    $query .= " AND (j.title LIKE ? OR j.description LIKE ? OR c.name LIKE ?)";
}

// Add sorting
$sort_by = $_GET['sort_by'] ?? 'created_at';
$sort_order = $_GET['sort_order'] ?? 'desc';
$query .= " ORDER BY j." . $sort_by . " " . $sort_order;

// Pagination
$limit = 10;
$page = $_GET['page'] ?? 1;
$offset = ($page - 1) * $limit;
$query .= " LIMIT ? OFFSET ?";

// Count total jobs for pagination
$count_query = "SELECT COUNT(*) as total FROM jobs";
$stmt = $conn->query($count_query);
$total_jobs = $stmt->num_rows;
$total_pages = ceil($total_jobs / $limit);

// Execute the main query
$stmt = $conn->prepare($query);
$params = [];

// Add search parameters if search is performed
if (!empty($search)) {
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

// Add pagination parameters
$params[] = $limit;
$params[] = $offset;

try {
    $stmt->execute($params);
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Unknown column') !== false) {
        $query = str_replace('c.company_name', 'c.name', $query);
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
    } else {
        throw $e;
    }
}

// Fetch results using mysqli style
$result = $stmt->get_result();
$jobs = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Jobs - Swift Jobs Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include 'includes/admin_header.php'; ?>
    
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>All Jobs</h2>
            <!-- <a href="add_job.php" class="btn btn-primary">Add New Job</a> -->
        </div>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <?php 
                echo $_SESSION['success_message'];
                unset($_SESSION['success_message']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger">
                <?php 
                echo $_SESSION['error_message'];
                unset($_SESSION['error_message']);
                ?>
            </div>
        <?php endif; ?>

        <!-- Search and Filters -->
        <form class="mb-4" method="GET">
            <div class="row">
                <div class="col-md-4">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search" 
                               value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Search jobs...">
                        <button class="btn btn-outline-secondary" type="submit">Search</button>
                    </div>
                </div>
                <div class="col-md-4">
                    <select class="form-select" name="sort_by">
                        <option value="created_at" <?php echo $sort_by === 'created_at' ? 'selected' : ''; ?>>Date Added</option>
                        <option value="title" <?php echo $sort_by === 'title' ? 'selected' : ''; ?>>Title</option>
                        <option value="salary" <?php echo $sort_by === 'salary' ? 'selected' : ''; ?>>Salary</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="sort_order">
                        <option value="asc" <?php echo $sort_order === 'asc' ? 'selected' : ''; ?>>Ascending</option>
                        <option value="desc" <?php echo $sort_order === 'desc' ? 'selected' : ''; ?>>Descending</option>
                    </select>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Company Logo</th>
                        <th>Job Title</th>
                        <th>Company</th>
                        <th>Location</th>
                        <th>Salary</th>
                        <th>Experience</th>
                        <th>Status</th>
                        <!-- <th>Actions</th> -->
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($jobs as $job): ?>
                        <tr>
                            <td>
                                <?php if (!empty($job['logo'])): ?>
                                    <img src="<?php echo htmlspecialchars($job['logo'] ?? ''); ?>" 
                                         alt="<?php echo htmlspecialchars($job['name'] ?? ''); ?>" 
                                         class="company-logo me-2" style="width: 30px; height: 30px;">
                                <?php endif; ?>
                                <a href="job_details.php?job_id=<?php echo htmlspecialchars($job['job_id'] ?? ''); ?>" 
                                   target="_blank"><?php echo htmlspecialchars($job['title'] ?? ''); ?></a>
                            </td>
                            <td><?php echo htmlspecialchars($job['title'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($job['name'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($job['location'] ?? ''); ?></td>
                            <td><?php echo !empty($job['salary']) ? htmlspecialchars($job['salary']) : 'Not specified'; ?></td>
                            <td><?php echo !empty($job['experience']) ? htmlspecialchars($job['experience']) . ' years' : 'Not specified'; ?></td>
                            <td>
                                <span class="badge bg-<?php echo ($job['status'] ?? 'active') === 'active' ? 'success' : 'warning'; ?>">
                                    <?php echo ucfirst($job['status'] ?? 'active'); ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <!-- <a href="job_details.php?job_id=<?php echo htmlspecialchars($job['job_id'] ?? ''); ?>" 
                                       class="btn btn-sm btn-info" title="View Job Details" target="_blank">
                                       <i class="fas fa-eye"></i> View
                                    </a>
                                    <a href="edit_job.php?job_id=<?php echo htmlspecialchars($job['job_id'] ?? ''); ?>" 
                                       class="btn btn-sm btn-primary" title="Edit Job">
                                       <i class="fas fa-edit"></i> Edit
                                    </a> -->
                                    <!-- <?php // if (($job['status'] ?? 'active') === 'active'): ?>
                                        <a href="?status=inactive&job_id=<?php //echo htmlspecialchars($job['id'] ?? ''); ?>" 
                                           class="btn btn-sm btn-warning" 
                                           onclick="return confirm('Are you sure you want to deactivate this job?')"
                                           title="Deactivate Job">
                                           <i class="fas fa-ban"></i> Deactivate
                                        </a>
                                    <?php //else: ?> -->
                                        <!-- <a href="?status=active&job_id=<?php echo htmlspecialchars($job['id'] ?? ''); ?>" 
                                           class="btn btn-sm btn-success" 
                                           onclick="return confirm('Are you sure you want to activate this job?')"
                                           title="Activate Job">
                                           <i class="fas fa-check"></i> Activate -->
                                        </a>
                                    <?php// endif; ?>
                                    <!-- <a href="?delete=<?php echo htmlspecialchars($job['id'] ?? ''); ?>" 
                                       class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Are you sure you want to delete this job?')"
                                       title="Delete Job">
                                       <i class="fas fa-trash"></i> Delete
                                    </a> -->
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&sort_by=<?php echo $sort_by; ?>&sort_order=<?php echo $sort_order; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            var table = $('.table').DataTable({
                "pageLength": 10,
                "paging": false,
                "info": false,
                "searching": false,
                "order": [],
                "columnDefs": [
                    { "orderable": false, "targets": [6] }
                ]
            });
        });
    </script>
</body>
</html>