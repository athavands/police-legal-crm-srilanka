<?php
include "../config/db.php";
include "../config/auth.php";

if (!isAdmin()) {
    header("Location: ../auth/login.php");
    exit;
}

$authUserId = auth()['id'];

// Fetch categories
$categories = mysqli_query($conn, "SELECT id, name FROM categories ORDER BY name ASC");

// ---------------- SOFT DELETE USER ----------------
if (isset($_GET['delete'])) {
    $deleteId = (int) $_GET['delete'];
    mysqli_query($conn, "UPDATE users SET deleted_at = NOW() WHERE id = $deleteId AND created_by = $authUserId");
    header("Location: create_user.php");
    exit;
}

// ---------------- CREATE USER ----------------
if (isset($_POST['save'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = md5($_POST['password']);
    $role     = $_POST['role'];
    $category = $_POST['category_id'];

    $check = mysqli_query($conn, "SELECT id FROM users WHERE username='$username' AND deleted_at IS NULL");

    if (mysqli_num_rows($check) > 0) {
        $error = "Username already exists.";
    } elseif ($role === 'user' && empty($category)) {
        $error = "Category selection is required for users.";
    } else {
        if ($role === 'super_admin') { $category = NULL; }
        mysqli_query($conn, "INSERT INTO users (username, password, role, category_id, created_by) VALUES ('$username', '$password', '$role', " . ($category ? "'$category'" : "NULL") . ", $authUserId)");
        header("Location: create_user.php");
        exit;
    }
}

// ---------------- FETCH USERS ----------------
$users = mysqli_query($conn, "SELECT u.id, u.username, u.role, c.name AS category FROM users u LEFT JOIN categories c ON c.id = u.category_id WHERE u.created_by = $authUserId AND u.deleted_at IS NULL ORDER BY u.id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users | Legal CMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        :root { --sidebar-width: 260px; }
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; color: #1e293b; }

        #sidebar { width: var(--sidebar-width); height: 100vh; position: fixed; left: 0; top: 0; background: #0f172a; z-index: 1050; transition: 0.3s; }
        #content { margin-left: var(--sidebar-width); width: calc(100% - var(--sidebar-width)); padding: 25px; transition: 0.3s; }

        @media (max-width: 1024px) {
            #sidebar { left: -260px; }
            #sidebar.active { left: 0; }
            #content { margin-left: 0; width: 100%; }
        }

        .nav-link { color: #94a3b8; padding: 12px 18px; border-radius: 8px; font-weight: 500; margin-bottom: 5px; }
        .nav-link:hover, .nav-link.active { background: #1e293b; color: #38bdf8; }
        
        .card-custom { background: white; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .section-title { font-size: 0.85rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #64748b; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px; margin-bottom: 20px; }
        
        .form-control, .form-select { border-radius: 8px; padding: 10px 12px; font-size: 0.9rem; }
        .table thead th { background: #f1f5f9; font-size: 0.8rem; text-transform: uppercase; color: #64748b; }
    </style>
</head>
<body>

<nav id="sidebar" class="p-4">
    <div class="d-flex align-items-center mb-5 text-white">
        <i class="fas fa-user-shield fa-2x text-primary me-3"></i>
        <span class="fs-5 fw-bold">LEGAL CMS</span>
    </div>
    <div class="nav flex-column">
        <a href="dashboard.php" class="nav-link"><i class="fas fa-th-large me-2"></i> Dashboard</a>
        <a href="crime_files_create.php" class="nav-link"><i class="fas fa-folder-plus me-2"></i> New File</a>
        <a href="create_user.php" class="nav-link active"><i class="fas fa-users me-2"></i> Users</a>
        <a href="categories.php" class="nav-link"><i class="fas fa-list-ul me-2"></i> Categories</a>
        <hr class="text-secondary opacity-25">
        <a href="profile.php" class="nav-link"><i class="fas fa-folder-plus"></i> Change Pasword</a>

        <a href="../auth/logout.php" class="nav-link text-danger"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
    </div>
</nav>

<div id="content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <button id="toggleBtn" class="btn btn-outline-dark d-lg-none"><i class="fas fa-bars"></i></button>
        <h4 class="fw-bold m-0">Account Management</h4>
    </div>

    <div class="row">
        <div class="col-xl-4 col-lg-5 mb-4">
            <div class="card-custom p-4">
                <div class="section-title">Register New User</div>
                
                <?php if(isset($error)): ?>
                    <div class="alert alert-danger py-2 small"><?= $error ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="small fw-bold text-muted mb-1">Username</label>
                        <input class="form-control" name="username" placeholder="Enter username" required>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold text-muted mb-1">Password</label>
                        <input class="form-control" type="password" name="password" placeholder="Create password" required>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold text-muted mb-1">Access Role</label>
                        <select class="form-select" name="role" id="roleSelect" required>
                            <option value="">-- Select Role --</option>
                            <option value="user">Operational User</option>
                            <option value="super_admin">System Super Admin</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="small fw-bold text-muted mb-1">Assigned Category</label>
                        <select class="form-select" name="category_id" id="categorySelect">
                            <option value="">-- Select Category --</option>
                            <?php mysqli_data_seek($categories, 0); while ($cat = mysqli_fetch_assoc($categories)): ?>
                                <option value="<?= $cat['id'] ?>"><?= $cat['name'] ?></option>
                            <?php endwhile; ?>
                        </select>
                        <div id="catNote" class="small text-muted mt-1 d-none">Super Admins have access to all categories.</div>
                    </div>

                    <button class="btn btn-primary w-100 fw-bold" name="save">
                        <i class="fas fa-user-plus me-2"></i> Create Account
                    </button>
                </form>
            </div>
        </div>

        <div class="col-xl-8 col-lg-7">
            <div class="card-custom">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="m-0 fw-bold text-primary">Managed Accounts</h6>
                    <span class="badge bg-light text-dark border"><?= mysqli_num_rows($users) ?> Total</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">ID</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Assigned Category</th>
                                <th class="text-end pe-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($u = mysqli_fetch_assoc($users)): ?>
                            <tr>
                                <td class="ps-3 text-muted small">#<?= $u['id'] ?></td>
                                <td class="fw-bold"><?= $u['username'] ?></td>
                                <td>
                                    <span class="badge rounded-pill <?= $u['role'] == 'super_admin' ? 'bg-danger-subtle text-danger' : 'bg-primary-subtle text-primary' ?>">
                                        <?= strtoupper(str_replace('_', ' ', $u['role'])) ?>
                                    </span>
                                </td>
                                <td><?= $u['category'] ? '<i class="fas fa-tag me-1 text-muted"></i>'.$u['category'] : '<span class="text-muted italic small">All Access</span>' ?></td>
                                <td class="text-end pe-3">
                                    <a href="?delete=<?= $u['id'] ?>"
                                       class="btn btn-sm btn-outline-danger border-0"
                                       onclick="return confirm('Remove access for this user?')">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if(mysqli_num_rows($users) == 0): ?>
                                <tr><td colspan="5" class="text-center py-4 text-muted small">No users found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white p-3">
                    <a href="dashboard.php" class="btn btn-sm btn-light border"><i class="fas fa-arrow-left me-1"></i> Dashboard</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    document.getElementById('roleSelect').addEventListener('change', function () {
        const category = document.getElementById('categorySelect');
        const note = document.getElementById('catNote');
        if (this.value === 'super_admin') {
            category.disabled = true;
            category.value = "";
            note.classList.remove('d-none');
        } else {
            category.disabled = false;
            note.classList.add('d-none');
        }
    });

    $('#toggleBtn').click(function() { $('#sidebar').toggleClass('active'); });
</script>

</body>
</html>