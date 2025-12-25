<?php
include "../config/db.php";
include "../config/auth.php";

if (!isAdmin()) {
    header("Location: ../auth/login.php");
    exit;
}

$userId = auth()['id'];

if (isset($_POST['update'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    // Username uniqueness (exclude self)
    $check = mysqli_query($conn, "SELECT id FROM users WHERE username='$username' AND id != $userId AND deleted_at IS NULL");

    if (mysqli_num_rows($check) > 0) {
        $error = "Username already exists.";
    } else {
        if (!empty($password)) {
            $password = md5($password);
            $sql = "UPDATE users SET username='$username', password='$password' WHERE id=$userId";
        } else {
            $sql = "UPDATE users SET username='$username' WHERE id=$userId";
        }

        mysqli_query($conn, $sql);
        $_SESSION['auth']['username'] = $username;
        header("Location: dashboard.php?success=profile_updated");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings | Legal CMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        :root { --sidebar-width: 260px; }
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; color: #1e293b; }

        #sidebar { width: var(--sidebar-width); height: 100vh; position: fixed; left: 0; top: 0; background: #0f172a; z-index: 1050; }
        #content { margin-left: var(--sidebar-width); padding: 25px; transition: 0.3s; }

        @media (max-width: 1024px) {
            #sidebar { left: -260px; transition: 0.3s; }
            #sidebar.active { left: 0; }
            #content { margin-left: 0; }
        }

        .nav-link { color: #94a3b8; padding: 12px 18px; border-radius: 8px; font-weight: 500; }
        .nav-link.active { background: #1e293b; color: #38bdf8; }
        
        .settings-card { background: white; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); overflow: hidden; }
        .settings-header { background: #f1f5f9; padding: 25px; border-bottom: 1px solid #e2e8f0; }
        .form-label { font-size: 0.8rem; font-weight: 700; text-transform: uppercase; color: #64748b; margin-bottom: 8px; }
        .form-control { border-radius: 10px; padding: 12px; border: 1px solid #cbd5e1; }
        .form-control:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
    </style>
</head>
<body>

<nav id="sidebar" class="p-4">
    <div class="d-flex align-items-center mb-5 text-white">
        <i class="fas fa-user-gear fa-2x text-primary me-3"></i>
        <span class="fs-5 fw-bold">LEGAL CMS</span>
    </div>
    <div class="nav flex-column">
        <a href="dashboard.php" class="nav-link"><i class="fas fa-th-large me-2"></i> Dashboard</a>
        <a href="crime_files_create.php" class="nav-link"><i class="fas fa-folder-plus me-2"></i> New File</a>
        <a href="create_user.php" class="nav-link"><i class="fas fa-users me-2"></i> Users</a>
        <hr class="text-secondary opacity-25">
        <a href="../auth/logout.php" class="nav-link text-danger"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
    </div>
</nav>

<div id="content">
    <button id="toggleBtn" class="btn btn-outline-dark d-lg-none mb-4"><i class="fas fa-bars"></i></button>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-5 col-lg-7">
                <div class="settings-card">
                    <div class="settings-header text-center">
                        <div class="bg-primary text-white d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width: 60px; height: 60px;">
                            <i class="fas fa-user-shield fa-2x"></i>
                        </div>
                        <h4 class="fw-bold m-0">Account Settings</h4>
                        <p class="text-muted small mb-0">Update your login credentials</p>
                    </div>

                    <div class="p-4">
                        <?php if(isset($error)): ?>
                            <div class="alert alert-danger py-2 small border-0"><i class="fas fa-exclamation-triangle me-2"></i><?= $error ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-user text-muted"></i></span>
                                    <input type="text" name="username" class="form-control" value="<?= auth()['username'] ?>" required>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">New Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-lock text-muted"></i></span>
                                    <input type="password" name="password" class="form-control" placeholder="••••••••">
                                </div>
                                <div class="form-text small text-muted mt-2">
                                    <i class="fas fa-info-circle me-1"></i> Leave blank to keep your current password.
                                </div>
                            </div>

                            <button class="btn btn-primary w-100 fw-bold py-2 mb-3" name="update">
                                Save Changes
                            </button>
                            
                            <a href="dashboard.php" class="btn btn-link w-100 text-decoration-none text-muted small">
                                <i class="fas fa-chevron-left me-1"></i> Cancel and go back
                            </a>
                        </form>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <p class="small text-muted">Logged in as: <strong><?= strtoupper(auth()['role']) ?></strong></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $('#toggleBtn').click(function() { $('#sidebar').toggleClass('active'); });
</script>

</body>
</html>