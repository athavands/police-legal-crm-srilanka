<?php
include "../config/db.php";

if (isset($_POST['login'])) {

    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = md5($_POST['password']);

    $sql = "SELECT * FROM users WHERE username='$username' AND password='$password'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) == 1) {

        $user = mysqli_fetch_assoc($result);

        // Auth cache (Laravel style)
        $_SESSION['auth'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role'],
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

include "../assets/header.php";
?>

<div class="card p-4 mx-auto" style="max-width:400px;">
<h4 class="text-center">Login</h4>

<?php if(isset($error)): ?>
<div class="alert alert-danger"><?= $error ?></div>
<?php endif; ?>

<form method="POST">
    <input class="form-control mb-2" name="username" placeholder="Username" required>
    <input class="form-control mb-2" type="password" name="password" placeholder="Password" required>
    <button class="btn btn-primary w-100" name="login">Login</button>
</form>
</div>

</div>
</body>
</html>
