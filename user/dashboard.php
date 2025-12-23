<?php
include "../config/db.php";
include "../config/auth.php";

if (!auth() || auth()['role'] !== 'user') {
    header("Location: ../auth/login.php");
}
include "../assets/header.php";
?>

<div class="card p-4">
<h4>User Dashboard</h4>
<p>Welcome, <?= auth()['username'] ?></p>

<img src="../uploads/<?= auth()['profile_pic'] ?>" width="100" class="rounded-circle">
<a href="profile.php" class="btn btn-warning mt-2">Change Credentials</a>

<a href="../auth/logout.php" class="btn btn-danger mt-3">Logout</a>
</div>

</div>
</body>
</html>
