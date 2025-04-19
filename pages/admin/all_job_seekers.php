<?php
require '../db_connection.php';
session_start();
// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Handle status updates
if (isset($_GET['status'])) {
    $seeker_id = $_GET['seeker_id'];
    $status = $_GET['status'];
    try {
        $stmt = $conn->prepare("UPDATE job_applications SET status = ? WHERE application_id = ?");
        $stmt->execute([$status, $seeker_id]);
        $_SESSION['success_message'] = 'Job seeker status updated successfully';
        header('Location: all_job_seekers.php');
        exit();
    } catch (PDOException $e) {
        $_SESSION['error_message'] = 'Error updating status: ' . $e->getMessage();
        header('Location: all_job_seekers.php');
        exit();
    }
}

// Build query for job seekers list with joined tables
$query = "SELECT 
            ja.application_id,
            ja.cover_letter,
            ja.expected_salary,
            ja.applied_at,
            ja.status,
            ja.why_job,
            ja.why_company,
            ja.resume,
            ja.phone AS application_phone,
            ja.additional_questions,
            j.title AS job_title,
            j.description AS job_description,
            j.salary AS job_salary,
            j.location AS job_location,
            j.category AS job_category,
            j.job_type,
            j.test_question,
            j.created_at AS job_created_at,
            j.expiration_date,
            j.specialization AS job_specialization,
            u.name AS user_name,
            u.email AS user_email,
            u.phone AS user_phone,
            u.role AS user_role,
            u.specialization AS user_specialization,
            u.cv_link,
            u.created_at AS user_created_at,
            u.location AS user_location,
            u.profile_pic AS user_profile_pic
          FROM job_portal.job_applications ja
          JOIN job_portal.jobs j ON ja.job_id = j.job_id
          JOIN job_portal.users u ON ja.user_id = u.user_id
          WHERE 1 = 1";

// Add search filters
$search = $_GET['search'] ?? '';
if (!empty($search)) {
    $query .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ? OR j.title LIKE ?)";
}

// Add sorting
$sort_by = $_GET['sort_by'] ?? 'ja.applied_at';
$sort_order = $_GET['sort_order'] ?? 'desc';
$query .= " ORDER BY " . $sort_by . " " . $sort_order;

// Pagination
$limit = 10;
$page = $_GET['page'] ?? 1;
$offset = ($page - 1) * $limit;
$query .= " LIMIT ? OFFSET ?";

// Count total job seekers for pagination
$count_query = "SELECT COUNT(*) as total 
                FROM job_portal.job_applications ja
                JOIN job_portal.jobs j ON ja.job_id = j.job_id
                JOIN job_portal.users u ON ja.user_id = u.user_id";
$result = $conn->query($count_query);
$total_seekers = $result->fetch_assoc()['total'];
$total_pages = ceil($total_seekers / $limit);

// Execute the main query
$stmt = $conn->prepare($query);
if (!empty($search)) {
    $search_term = "%$search%";
    $stmt->bind_param("ssssii", $search_term, $search_term, $search_term, $search_term, $limit, $offset);
} else {
    $stmt->bind_param("ii", $limit, $offset);
}
$stmt->execute();
$result = $stmt->get_result();
$job_seekers = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Job Seekers - Swift Jobs Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../CSS/admin.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include 'includes/admin_header.php'; ?>
    
    <div class="admin-container">
    <div class="admin-header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="mb-0">All Job Seekers</h2>
            <div class="d-flex gap-2">
                <!-- <a href="add_job_seeker.php" class="btn btn-primary admin-btn">
                    <i class="bi bi-person-plus"></i> Add New Job Seeker
                </a> -->
                <a href="export_job_seekers.php" class="btn btn-secondary admin-btn">
                    <i class="bi bi-download"></i> Export List
                </a>
            </div>
        </div>
    </div>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success admin-alert">
                <?php 
                echo $_SESSION['success_message'];
                unset($_SESSION['success_message']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger admin-alert">
                <?php 
                echo $_SESSION['error_message'];
                unset($_SESSION['error_message']);
                ?>
            </div>
        <?php endif; ?>

        <!-- Search and Filters -->
        <form class="admin-search" method="GET">
            <div class="row">
                <div class="col-md-4">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search" 
                               value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Search job seekers...">
                        <button class="btn btn-outline-secondary" type="submit">Search</button>
                    </div>
                </div>
                <div class="col-md-4">
                    <select class="form-select" name="sort_by">
                        <option value="ja.applied_at" <?php echo $sort_by === 'ja.applied_at' ? 'selected' : ''; ?>>Date Applied</option>
                        <option value="u.name" <?php echo $sort_by === 'u.name' ? 'selected' : ''; ?>>Name</option>
                        <option value="j.title" <?php echo $sort_by === 'j.title' ? 'selected' : ''; ?>>Job Title</option>
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

        <div class="table-responsive admin-card">
            <table class="table table-striped admin-table">
                <thead>
                    <tr>
                        <th>Profile</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Job Title</th>
                        <th>Status</th>
                        <!-- <th>Actions</th> -->
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($job_seekers as $seeker): ?>
                        <tr>
                            <td>
                                <?php if (!empty($seeker['user_profile_pic'])): ?>
                                    <img src="<?php echo htmlspecialchars($seeker['user_profile_pic']); ?>" 
                                         alt="<?php echo htmlspecialchars($seeker['user_name']); ?>" 
                                         class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center" 
                                         style="width: 40px; height: 40px;">
                                        <?php echo strtoupper(substr($seeker['user_name'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($seeker['user_name']); ?></td>
                            <td><?php echo htmlspecialchars($seeker['user_email']); ?></td>
                            <td><?php echo !empty($seeker['user_phone']) ? htmlspecialchars($seeker['user_phone']) : 'Not provided'; ?></td>
                            <td><?php echo htmlspecialchars($seeker['job_title']); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $seeker['status'] === 'active' ? 'success' : 'warning'; ?>">
                                    <?php echo ucfirst($seeker['status']); ?>
                                </span>
                            </td>
                            <!-- <td>
                                <div class="btn-group">
                                    <a href="view_job_seeker.php?id=<?php echo $seeker['application_id']; ?>" 
                                       class="btn btn-sm btn-info" 
                                       title="View Profile">
                                        <i class="bi bi-person"></i>
                                    </a>
                                    <a href="edit_job_seeker.php?id=<?php echo $seeker['application_id']; ?>" 
                                       class="btn btn-sm btn-primary" 
                                       title="Edit Profile">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php if ($seeker['status'] === 'active'): ?>
                                        <a href="?status=inactive&seeker_id=<?php echo $seeker['application_id']; ?>" 
                                           class="btn btn-sm btn-warning" 
                                           title="Mark as Inactive">
                                            <i class="bi bi-toggle-off"></i>
                                        </a>
                                    <?php else: ?>
                                        <a href="?status=active&seeker_id=<?php echo $seeker['application_id']; ?>" 
                                           class="btn btn-sm btn-success" 
                                           title="Mark as Active">
                                            <i class="bi bi-toggle-on"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td> -->
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <nav aria-label="Page navigation" class="admin-pagination">
            <ul class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo htmlspecialchars($search); ?>&sort_by=<?php echo $sort_by; ?>&sort_order=<?php echo $sort_order; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
