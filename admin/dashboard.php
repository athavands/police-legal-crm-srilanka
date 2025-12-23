<?php
include "../config/db.php";
include "../config/auth.php";

if (!isAdmin()) {
    header("Location: ../auth/login.php");
}
include "../assets/header.php";
?>

<div class="card p-4">
<h4>Super Admin Dashboard</h4>
<p>Welcome, <b><?= auth()['username'] ?></b></p>

<a href="create_user.php" class="btn btn-primary">Create User</a>
<a href="categories.php" class="btn btn-success">Manage Categories</a>
<a href="profile.php" class="btn btn-warning">Change Credentials</a>
<a href="../auth/logout.php" class="btn btn-danger float-end">Logout</a>
</div>

</div>
</body>
</html>
