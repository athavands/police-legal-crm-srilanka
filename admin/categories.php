<?php
include "../config/db.php";
include "../config/auth.php";

if (!isAdmin()) {
    header("Location: ../auth/login.php");
    exit;
}

if (isset($_POST['add'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $city = mysqli_real_escape_string($conn, $_POST['city']);
    mysqli_query($conn, "INSERT INTO categories (name, city) VALUES ('$name','$city')");
    header("Location: categories.php?success=1");
    exit;
}

$result = mysqli_query($conn, "SELECT * FROM categories ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories | Legal CMS</title>
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
        
        .form-control { border-radius: 8px; padding: 10px 12px; font-size: 0.9rem; }
        .table thead th { background: #f1f5f9; font-size: 0.8rem; text-transform: uppercase; color: #64748b; border-bottom: 2px solid #e2e8f0; }
        .table tbody td { font-size: 0.9rem; padding: 12px 10px; }
    </style>
</head>
<body>

<nav id="sidebar" class="p-4">
    <div class="d-flex align-items-center mb-5 text-white">
        <i class="fas fa-tags fa-2x text-primary me-3"></i>
        <span class="fs-5 fw-bold">LEGAL CMS</span>
    </div>
    <div class="nav flex-column">
        <a href="dashboard.php" class="nav-link"><i class="fas fa-th-large me-2"></i> Dashboard</a>
        <a href="crime_files_create.php" class="nav-link"><i class="fas fa-folder-plus me-2"></i> New File</a>
        <a href="create_user.php" class="nav-link"><i class="fas fa-users me-2"></i> Users</a>
        <a href="categories.php" class="nav-link active"><i class="fas fa-list-ul me-2"></i> Categories</a>
        <hr class="text-secondary opacity-25">
        <a href="profile.php" class="nav-link"><i class="fas fa-folder-plus"></i> Change Pasword</a>

        <a href="../auth/logout.php" class="nav-link text-danger"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
    </div>
</nav>

<div id="content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <button id="toggleBtn" class="btn btn-outline-dark d-lg-none"><i class="fas fa-bars"></i></button>
        <h4 class="fw-bold m-0">Category Management</h4>
    </div>

    <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="fas fa-check-circle me-2"></i> Category added successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-xl-4 col-lg-5 mb-4">
            <div class="card-custom p-4">
                <div class="section-title">Add New Category</div>
                <form method="POST">
                    <div class="mb-3">
                        <label class="small fw-bold text-muted mb-1">Category Name</label>
                        <input class="form-control" name="name" placeholder="e.g. Narcotics, Financial" required>
                    </div>

                    <div class="mb-4">
                        <label class="small fw-bold text-muted mb-1">City / Region</label>
                        <input class="form-control" name="city" placeholder="e.g. Colombo, Kandy" required>
                    </div>

                    <button class="btn btn-primary w-100 fw-bold" name="add">
                        <i class="fas fa-plus me-2"></i> Save Category
                    </button>
                </form>
            </div>
            
            <div class="mt-4 p-3 bg-primary-subtle rounded-3 border border-primary-subtle">
                <small class="text-primary fw-medium"><i class="fas fa-info-circle me-1"></i> Categories help group crime files for specific divisions or regional legal offices.</small>
            </div>
        </div>

        <div class="col-xl-8 col-lg-7">
            <div class="card-custom">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-white rounded-top-3">
                    <h6 class="m-0 fw-bold"><i class="fas fa-table me-2 text-primary"></i>Existing Categories</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">ID</th>
                                <th>Name</th>
                                <th>City</th>
                                <th class="text-end pe-3">Date Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(mysqli_num_rows($result) > 0): ?>
                                <?php while($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td class="ps-3 text-muted">#<?= $row['id'] ?></td>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($row['name']) ?></td>
                                    <td>
                                        <span class="badge bg-light text-dark border px-2 py-1">
                                            <i class="fas fa-map-marker-alt me-1 text-danger"></i><?= htmlspecialchars($row['city']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end pe-3 small text-muted"><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center py-5 text-muted small">No categories registered yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white p-3 border-top-0">
                    <a href="dashboard.php" class="btn btn-sm btn-light border fw-medium"><i class="fas fa-chevron-left me-1"></i> Dashboard</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    $('#toggleBtn').click(function() { $('#sidebar').toggleClass('active'); });
</script>

</body>
</html>