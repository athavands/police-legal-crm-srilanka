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
    $check = mysqli_query($conn,
        "SELECT id FROM users 
         WHERE username='$username' 
         AND id != $userId 
         AND deleted_at IS NULL"
    );

    if (mysqli_num_rows($check) > 0) {
        $error = "Username already exists.";

    } else {

        // Update query
        if (!empty($password)) {
            $password = md5($password);
            $sql = "UPDATE users 
                    SET username='$username', password='$password'
                    WHERE id=$userId";
        } else {
            $sql = "UPDATE users 
                    SET username='$username'
                    WHERE id=$userId";
        }

        mysqli_query($conn, $sql);

        // Refresh Auth cache
        $_SESSION['auth']['username'] = $username;

        header("Location: dashboard.php");
        exit;
    }
}

include "../assets/header.php";
?>

<div class="card p-4 mx-auto" style="max-width:500px;">
<h4>Change Credentials</h4>

<?php if(isset($error)): ?>
<div class="alert alert-danger"><?= $error ?></div>
<?php endif; ?>

<form method="POST">

    <input class="form-control mb-2"
           name="username"
           value="<?= auth()['username'] ?>"
           required>

    <input class="form-control mb-3"
           type="password"
           name="password"
           placeholder="New Password (leave empty to keep current)">

    <button class="btn btn-success w-100" name="update">
        Update
    </button>
</form>

<a href="dashboard.php" class="btn btn-secondary mt-3 w-100">Back</a>
</div>

</div>
</body>
</html>
