<?php
include "../config/db.php";
include "../config/auth.php";

if (!auth() || auth()['role'] !== 'user') {
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
        
        // Refresh session
        $_SESSION['auth']['username'] = $username;

        header("Location: dashboard.php?status=profile_updated");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings | Legal CMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --sidebar-width: 260px; }
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; color: #1e293b; }
        #sidebar { width: var(--sidebar-width); height: 100vh; position: fixed; background: #0f172a; z-index: 1050; display: flex; flex-direction: column; }
        #content { margin-left: var(--sidebar-width); padding: 40px; transition: 0.3s; min-height: 100vh; display: flex; flex-direction: column; }
        @media (max-width: 1024px) { #sidebar { left: -260px; } #sidebar.active { left: 0; } #content { margin-left: 0; } }
        
        .nav-link { color: #94a3b8; padding: 12px 18px; border-radius: 8px; font-weight: 500; margin-bottom: 5px; }
        .nav-link:hover, .nav-link.active { background: #1e293b; color: #38bdf8; }
        
        .profile-card { background: white; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); max-width: 500px; margin: 0 auto; width: 100%; }
        .profile-header { background: #f1f5f9; padding: 30px; border-bottom: 1px solid #e2e8f0; text-align: center; border-radius: 16px 16px 0 0; }
        .form-label { font-size: 0.8rem; font-weight: 700; text-transform: uppercase; color: #64748b; margin-bottom: 8px; }
        .form-control { border-radius: 10px; padding: 12px; border: 1px solid #cbd5e1; }
        .form-control:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
        
        .footer-copy { font-size: 0.75rem; color: #64748b; padding: 20px; text-align: center; margin-top: auto; border-top: 1px solid #1e293b; }
    </style>
</head>
<body>

<nav id="sidebar">
    <div class="p-4">
        <div class="d-flex align-items-center mb-5 text-white">
            <i class="fas fa-balance-scale fa-2x text-primary me-3"></i>
            <span class="fs-5 fw-bold">LEGAL CMS</span>
        </div>
        <div class="nav flex-column">
            <a href="dashboard.php" class="nav-link"><i class="fas fa-th-large me-2"></i> Dashboard</a>
            <a href="profile.php" class="nav-link active"><i class="fas fa-user-cog me-2"></i> Settings</a>
            <hr class="text-secondary opacity-25">
            <a href="../auth/logout.php" class="nav-link text-danger"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
        </div>
    </div>
    <div class="footer-copy">
        creativity by <br><strong>athavan ds</strong>
    </div>
</nav>

<div id="content">
    <div class="mb-4 d-flex align-items-center justify-content-between">
        <h4 class="fw-bold m-0">My Account</h4>
        <button id="toggleBtn" class="btn btn-outline-dark d-lg-none"><i class="fas fa-bars"></i></button>
    </div>

    <div class="profile-card">
        <div class="profile-header">
            <div class="bg-primary text-white d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width: 70px; height: 70px;">
                <i class="fas fa-user-edit fa-2x"></i>
            </div>
            <h4 class="fw-bold mb-1">Update Credentials</h4>
            <p class="text-muted small mb-0">Manage your login information</p>
        </div>

        <div class="p-4">
            <?php if(isset($error)): ?>
                <div class="alert alert-danger py-2 small border-0 mb-3">
                    <i class="fas fa-exclamation-circle me-2"></i><?= $error ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-user"></i></span>
                        <input type="text" name="username" class="form-control border-start-0" 
                               value="<?= htmlspecialchars(auth()['username']) ?>" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">New Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-lock"></i></span>
                        <input type="password" name="password" class="form-control border-start-0" 
                               placeholder="Leave empty to keep current">
                    </div>
                    <div class="form-text small text-muted mt-2">
                        <i class="fas fa-info-circle me-1"></i> Passwords must be secure.
                    </div>
                </div>

                <button class="btn btn-primary w-100 fw-bold py-2 mb-3" name="update">
                    Save Changes
                </button>
                
                <a href="dashboard.php" class="btn btn-link w-100 text-decoration-none text-muted small">
                    <i class="fas fa-times me-1"></i> Cancel Changes
                </a>
            </form>
        </div>
    </div>

    <div class="text-center mt-auto py-4 text-muted small">
        creativity by <strong>athavan ds</strong> &copy; <?= date('Y') ?>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $('#toggleBtn').click(function() { $('#sidebar').toggleClass('active'); });
</script>

</body>
</html>