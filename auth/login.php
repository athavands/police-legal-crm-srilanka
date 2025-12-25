<?php
include "../config/db.php";

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_POST['login'])) {

    $username = mysqli_real_escape_string($conn, $_POST['username']);
    // Note: Consider moving to password_hash/verify in the future for better security
    $password = md5($_POST['password']); 

    $sql = "SELECT * FROM users WHERE username='$username' AND password='$password' AND deleted_at IS NULL";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);

        $_SESSION['auth'] = [
            'id'          => $user['id'],
            'username'    => $user['username'],
            'role'        => $user['role'],
            'profile_pic' => $user['profile_pic'],
            'category_id' => $user['category_id']
        ];

        if ($user['role'] === 'super_admin') {
            header("Location: ../admin/dashboard.php");
        } else {
            header("Location: ../user/dashboard.php");
        }
        exit;

    } else {
        $error = "Invalid username or password";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Login | Legal CMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #0f172a; /* Deep dark background */
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }

        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            width: 100%;
            max-width: 400px;
            overflow: hidden;
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .login-header {
            background: #1e293b;
            padding: 40px 20px;
            text-align: center;
            color: white;
        }

        .login-logo {
            width: 60px;
            height: 60px;
            background: #38bdf8;
            color: #0f172a;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            font-size: 24px;
            margin-bottom: 15px;
        }

        .form-control {
            border-radius: 10px;
            padding: 12px 15px;
            border: 1px solid #e2e8f0;
            background-color: #f8fafc;
        }

        .form-control:focus {
            box-shadow: 0 0 0 4px rgba(56, 189, 248, 0.1);
            border-color: #38bdf8;
        }

        .btn-login {
            background: #3b82f6;
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            transition: 0.3s;
        }

        .btn-login:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }

        .copyright-tag {
            text-align: center;
            margin-top: 25px;
            color: #94a3b8;
            font-size: 0.8rem;
        }

        .copyright-tag strong {
            color: #cbd5e1;
        }
    </style>
</head>
<body>

<div class="container d-flex flex-column align-items-center">
    <div class="login-card">
        <div class="login-header">
            <div class="login-logo">
                <i class="fas fa-shield-halved"></i>
            </div>
            <h4 class="fw-bold m-0">LEGAL CMS</h4>
            <p class="text-muted small mb-0">Authorized Personnel Only</p>
        </div>

        <div class="p-4">
            <?php if(isset($error)): ?>
                <div class="alert alert-danger py-2 small border-0 text-center mb-3">
                    <i class="fas fa-circle-exclamation me-2"></i><?= $error ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted text-uppercase">Username</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-user"></i></span>
                        <input type="text" name="username" class="form-control border-start-0" placeholder="Enter username" required autofocus>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-bold text-muted text-uppercase">Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-lock"></i></span>
                        <input type="password" name="password" class="form-control border-start-0" placeholder="••••••••" required>
                    </div>
                </div>

                <button class="btn btn-primary btn-login w-100 mb-2" name="login">
                    Sign In <i class="fas fa-arrow-right-to-bracket ms-2"></i>
                </button>
            </form>
        </div>
    </div>

    <div class="copyright-tag">
        creativity by <strong>athavan ds</strong> &copy; <?= date('Y') ?>
    </div>
</div>

</body>
</html>