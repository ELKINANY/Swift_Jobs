<?php
require '../db_connection.php';
session_start();
// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Handle company deletion
if (isset($_GET['delete'])) {
    $company_id = $_GET['delete'];
    try {
        // First delete related jobs
        $stmt = $conn->prepare("DELETE FROM jobs WHERE company_id = ?");
        $stmt->bind_param("i", $company_id);
        $stmt->execute();
        
        // Then delete the company
        $stmt = $conn->prepare("DELETE FROM companies WHERE id = ?");
        $stmt->bind_param("i", $company_id);
        $stmt->execute();
        
        $_SESSION['success_message'] = 'Company and its jobs deleted successfully';
        header('Location: all_companies.php');
        exit();
    } catch (mysqli_sql_exception $e) {
        $_SESSION['error_message'] = 'Error deleting company: ' . $e->getMessage();
        header('Location: all_companies.php');
        exit();
    }
}

// Handle company status update
if (isset($_GET['status'])) {
    $company_id = $_GET['company_id'];
    $status = $_GET['status'];
    try {
        $stmt = $conn->prepare("UPDATE companies SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $company_id);
        $stmt->execute();
        $_SESSION['success_message'] = 'Company status updated successfully';
        header('Location: all_companies.php');
        exit();
    } catch (mysqli_sql_exception $e) {
        $_SESSION['error_message'] = 'Error updating company status: ' . $e->getMessage();
        header('Location: all_companies.php');
        exit();
    }
}

// Build query for companies list
$query = "SELECT c.*, 
                 COUNT(j.job_id ) as total_jobs,
                 COUNT(DISTINCT ja.user_id) as unique_applicants,
                 COUNT(ja.application_id) as total_applications,
                 'active' as status  
          FROM companies c
          LEFT JOIN jobs j ON c.company_id = j.company_id
          LEFT JOIN job_applications ja ON j.job_id = ja.job_id
          WHERE 1 = 1";

// Add search filters
$search = $_GET['search'] ?? '';
if (!empty($search)) {
    $query .= " AND (c.company_name LIKE ? OR c.description LIKE ? OR c.website LIKE ?)";
}

// Add sorting
$sort_by = $_GET['sort_by'] ?? 'created_at';
$sort_order = $_GET['sort_order'] ?? 'desc';
$query .= " GROUP BY company_id ORDER BY c." . $sort_by . " " . $sort_order;

// Pagination
$limit = 10;
$page = $_GET['page'] ?? 1;
$offset = ($page - 1) * $limit;
$query .= " LIMIT ? OFFSET ?";

// Count total companies for pagination
$count_query = "SELECT COUNT(*) as total FROM companies";
$result = $conn->query($count_query);
$total_companies = $result->fetch_assoc()['total'];
$total_pages = ceil($total_companies / $limit);

// Execute the main query
$stmt = $conn->prepare($query);
$params = [];
if (!empty($search)) {
    $search_term = "%$search%";
    $params = [$search_term, $search_term, $search_term, $limit, $offset];
} else {
    $params = [$limit, $offset];
}
if (!empty($search)) {
    $stmt->bind_param("ssssi", $params[0], $params[1], $params[2], $params[3], $params[4]);
} else {
    $stmt->bind_param("ii", $params[0], $params[1]);
}
$stmt->execute();
$result = $stmt->get_result();
$companies = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Companies - Swift Jobs Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include 'includes/admin_header.php'; ?>
    
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>All Companies</h2>
            <div class="d-flex gap-2">
                <!-- <a href="add_company.php" class="btn btn-primary">Add New Company</a> -->
                <a href="export_companies.php" class="btn btn-secondary">Export List</a>
            </div>
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
                               placeholder="Search companies...">
                        <button class="btn btn-outline-secondary" type="submit">Search</button>
                    </div>
                </div>
                <div class="col-md-4">
                    <select class="form-select" name="sort_by">
                        <option value="created_at" <?php echo $sort_by === 'created_at' ? 'selected' : ''; ?>>Date Joined</option>
                        <option value="company_name" <?php echo $sort_by === 'company_name' ? 'selected' : ''; ?>>Company Name</option>
                        <option value="total_jobs" <?php echo $sort_by === 'total_jobs' ? 'selected' : ''; ?>>Jobs Posted</option>
                        <option value="total_applications" <?php echo $sort_by === 'total_applications' ? 'selected' : ''; ?>>Applications</option>
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
                        <th>Logo</th>
                        <th>Company Name</th>
                        <th>Industry</th>
                        <th>Location</th>
                        <th>Jobs Posted</th>
                        <th>Unique Applicants</th>
                        <th>Total Applications</th>
                        <th>Status</th>
                        <!-- <th>Actions</th> -->
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($companies as $company): ?>
                        <tr>
                            <td>
                                <?php if (!empty($company['logo'])): ?>
                                    <img src="<?php echo htmlspecialchars($company['logo'] ?? ''); ?>" 
                                         alt="<?php echo htmlspecialchars($company['name'] ?? ''); ?>" 
                                         class="company-logo me-2" style="width: 30px; height: 30px;">
                                <?php endif; ?>
                                <a href="view_company.php?id=<?php echo htmlspecialchars($company['company_id'] ?? ''); ?>" 
                                   target="_blank"><?php echo htmlspecialchars($company['name'] ?? ''); ?></a>
                            </td>
                            <td><?php echo htmlspecialchars($company['name'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($company['category'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($company['location'] ?? ''); ?></td>
                            <td><?php echo !empty($company['total_jobs']) ? htmlspecialchars($company['total_jobs']) : '0'; ?></td>
                            <td><?php echo !empty($company['unique_applicants']) ? htmlspecialchars($company['unique_applicants']) : '0'; ?></td>
                            <td><?php echo !empty($company['total_applications']) ? htmlspecialchars($company['total_applications']) : '0'; ?></td>
                            <td>
                                <span class="badge bg-success">
                                    Active
                                </span>
                            </td>
                            <!-- <td>
                                <div class="btn-group">
                                    <a href="view_company.php?id=<?php echo htmlspecialchars($company['company_id'] ?? ''); ?>" 
                                       class="btn btn-sm btn-info" title="View Company">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="edit_company.php?id=<?php echo htmlspecialchars($company['company_id'] ?? ''); ?>" 
                                       class="btn btn-sm btn-primary" title="Edit Company">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="view_company_jobs.php?id=<?php echo htmlspecialchars($company['company_id'] ?? ''); ?>" 
                                       class="btn btn-sm btn-secondary" title="View Jobs">
                                        <i class="bi bi-journal-text"></i>
                                    </a>
                                </div>
                            </td> -->
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

        <!-- Statistics -->
        <div class="row mt-5">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Total Companies</h5>
                        <h2 class="card-text"><?php echo $total_companies; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Total Jobs Posted</h5>
                        <h2 class="card-text">
                            <?php
                            $stmt = $conn->query("SELECT COUNT(*) as count FROM jobs");
                            echo $stmt->fetch_assoc()['count'];
                            ?>
                        </h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Total Applications</h5>
                        <h2 class="card-text">
                            <?php
                            $stmt = $conn->query("SELECT COUNT(*) as count FROM job_applications");
                            echo $stmt->fetch_assoc()['count'];
                            ?>
                        </h2>
                    </div>
                </div>
            </div>
        </div>
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
                    { "orderable": false, "targets": [0, 7] }
                ]
            });
        });
    </script>
</body>
</html>