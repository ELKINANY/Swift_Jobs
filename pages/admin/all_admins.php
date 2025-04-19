<?php
require '../db_connection.php';
session_start();
// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Handle admin deletion
if (isset($_GET['delete'])) {
    $admin_id = $_GET['delete'];
    // Prevent deletion of current admin
    if ($admin_id != $_SESSION['user_id']) {
        try {
            $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ? AND role = 'admin'");
            $stmt->execute([$admin_id]);
            $_SESSION['success_message'] = 'Admin deleted successfully';
            header('Location: all_admins.php');
            exit();
        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'Error deleting admin: ' . $e->getMessage();
            header('Location: all_admins.php');
            exit();
        }
    } else {
        $_SESSION['error_message'] = 'You cannot delete your own account';
        header('Location: all_admins.php');
        exit();
    }
}

// Handle admin status update
if (isset($_GET['status'])) {
    $admin_id = $_GET['admin_id'];
    $status = $_GET['status'];
    try {
        $stmt = $conn->prepare("UPDATE users SET status = ? WHERE user_id = ? AND role = 'admin'");
        $stmt->execute([$status, $admin_id]);
        $_SESSION['success_message'] = 'Admin status updated successfully';
        header('Location: all_admins.php');
        exit();
    } catch (PDOException $e) {
        $_SESSION['error_message'] = 'Error updating admin status: ' . $e->getMessage();
        header('Location: all_admins.php');
        exit();
    }
}

// Build query for admins list
$query = "SELECT u.*
          FROM users u
          WHERE u.role = 'admin'";

// Add search filters
$search = $_GET['search'] ?? '';
if (!empty($search)) {
    $query .= " AND (u.name LIKE ? OR u.email LIKE ?)";
}

// Add sorting
$sort_by = $_GET['sort_by'] ?? 'created_at';
$sort_order = $_GET['sort_order'] ?? 'desc';
$query .= " GROUP BY u.user_id ORDER BY u." . $sort_by . " " . $sort_order;

// Pagination
$limit = 10;
$page = $_GET['page'] ?? 1;
$offset = ($page - 1) * $limit;
$query .= " LIMIT ? OFFSET ?";

// Count total admins for pagination
$count_query = "SELECT COUNT(*) as total FROM users WHERE role = 'admin'";
$result = $conn->query($count_query);
$total_admins = $result->fetch_assoc()['total'];
$total_pages = ceil($total_admins / $limit);

// Execute the main query
$stmt = $conn->prepare($query);
$params = [];
if (!empty($search)) {
    $search_term = "%$search%";
    $params = [$search_term, $search_term, $limit, $offset];
} else {
    $params = [$limit, $offset];
}
if (!empty($search)) {
    $stmt->bind_param("ssii", $params[0], $params[1], $params[2], $params[3]);
} else {
    $stmt->bind_param("ii", $params[0], $params[1]);
}
$stmt->execute();
$result = $stmt->get_result();
$admins = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Admins - Swift Jobs Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include 'includes/admin_header.php'; ?>
    
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>All Admins</h2>
            <div class="d-flex gap-2">
                <!-- <a href="add_admin.php" class="btn btn-primary">Add New Admin</a> -->
                <a href="export_admins.php" class="btn btn-secondary">Export List</a>
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
                               placeholder="Search admins...">
                        <button class="btn btn-outline-secondary" type="submit">Search</button>
                    </div>
                </div>
                <div class="col-md-4">
                    <select class="form-select" name="sort_by">
                        <option value="created_at" <?php echo $sort_by === 'created_at' ? 'selected' : ''; ?>>Date Joined</option>
                        <option value="name" <?php echo $sort_by === 'name' ? 'selected' : ''; ?>>Name</option>
                        <option value="login_count" <?php echo $sort_by === 'login_count' ? 'selected' : ''; ?>>Login Count</option>
                        <option value="last_login" <?php echo $sort_by === 'last_login' ? 'selected' : ''; ?>>Last Login</option>
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
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <!-- <th>Actions</th> -->
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($admins as $admin): ?>
                        <tr>
                            <td>
                                <?php echo htmlspecialchars($admin['name']); ?>
                                <?php if ($admin['user_id'] == $_SESSION['user_id']): ?>
                                    <span class="badge bg-info ms-2">Current</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($admin['email']); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $admin['role'] === 'super_admin' ? 'danger' : 'primary'; ?>">
                                    <?php echo ucfirst($admin['role']); ?>
                                </span>
                            </td>
                            <!-- <td>
                                <div class="btn-group">
                                    <a href="view_admin.php?id=<?php echo $admin['user_id']; ?>" 
                                       class="btn btn-sm btn-info" 
                                       title="View Profile">
                                        <i class="bi bi-person"></i>
                                    </a>
                                    <a href="edit_admin.php?id=<?php echo $admin['user_id']; ?>" 
                                       class="btn btn-sm btn-primary" 
                                       title="Edit Profile">
                                        <i class="bi bi-pencil"></i>
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
                        <h5 class="card-title">Total Admins</h5>
                        <h2 class="card-text"><?php echo $total_admins; ?></h2>
                    </div>
                </div>
            </div>
     
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Super Admins</h5>
                        <h2 class="card-text">
                            <?php
                            $stmt = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'super_admin'");
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
                    { "orderable": false, "targets": [5] }
                ]
            });
        });
    </script>
</body>
</html>