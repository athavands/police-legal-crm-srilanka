<?php
include "../config/db.php";
include "../config/auth.php";

if (!isAdmin()) {
    header("Location: ../auth/login.php");
    exit;
}

$authUserId = auth()['id'];

// Fetch categories
$categories = mysqli_query($conn, "SELECT id, name FROM categories");

// ---------------- SOFT DELETE USER ----------------
if (isset($_GET['delete'])) {
    $deleteId = (int) $_GET['delete'];

    mysqli_query($conn,
        "UPDATE users 
         SET deleted_at = NOW() 
         WHERE id = $deleteId 
         AND created_by = $authUserId"
    );

    header("Location: create_user.php");
    exit;
}

// ---------------- CREATE USER ----------------
if (isset($_POST['save'])) {

    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = md5($_POST['password']);
    $role     = $_POST['role'];
    $category = $_POST['category_id'];

    // Username uniqueness
    $check = mysqli_query($conn,
        "SELECT id FROM users 
         WHERE username='$username' 
         AND deleted_at IS NULL"
    );

    if (mysqli_num_rows($check) > 0) {
        $error = "Username already exists.";

    } elseif ($role === 'user' && empty($category)) {
        $error = "Category selection is required for users.";

    } else {

        if ($role === 'super_admin') {
            $category = NULL;
        }

        mysqli_query($conn,
            "INSERT INTO users 
            (username, password, role, category_id, created_by)
            VALUES (
                '$username',
                '$password',
                '$role',
                " . ($category ? "'$category'" : "NULL") . ",
                $authUserId
            )"
        );

        header("Location: create_user.php");
        exit;
    }
}

// ---------------- FETCH USERS CREATED BY ME ----------------
$users = mysqli_query($conn,
    "SELECT u.id, u.username, u.role, c.name AS category
     FROM users u
     LEFT JOIN categories c ON c.id = u.category_id
     WHERE u.created_by = $authUserId
     AND u.deleted_at IS NULL
     ORDER BY u.id DESC"
);

include "../assets/header.php";
?>

<div class="card p-4 mx-auto mb-4" style="max-width:600px;">
<h4>Create User</h4>

<?php if(isset($error)): ?>
<div class="alert alert-danger"><?= $error ?></div>
<?php endif; ?>

<form method="POST">

    <input class="form-control mb-2"
           name="username"
           placeholder="Username"
           required>

    <input class="form-control mb-2"
           type="password"
           name="password"
           placeholder="Password"
           required>

    <select class="form-control mb-2"
            name="role"
            id="roleSelect"
            required>
        <option value="">-- Select Role --</option>
        <option value="user">User</option>
        <option value="super_admin">Super Admin</option>
    </select>

    <select class="form-control mb-3"
            name="category_id"
            id="categorySelect">
        <option value="">-- Select Category --</option>
        <?php while ($cat = mysqli_fetch_assoc($categories)): ?>
            <option value="<?= $cat['id'] ?>">
                <?= $cat['name'] ?>
            </option>
        <?php endwhile; ?>
    </select>

    <button class="btn btn-success w-100" name="save">
        Create User
    </button>
</form>
</div>

<!-- ---------------- USERS LIST ---------------- -->

<div class="card p-4">
<h4>Users Created By You</h4>

<table class="table table-bordered">
<tr>
    <th>ID</th>
    <th>Username</th>
    <th>Role</th>
    <th>Category</th>
    <th>Action</th>
</tr>

<?php while ($u = mysqli_fetch_assoc($users)): ?>
<tr>
    <td><?= $u['id'] ?></td>
    <td><?= $u['username'] ?></td>
    <td><?= $u['role'] ?></td>
    <td><?= $u['category'] ?? '-' ?></td>
    <td>
        <a href="?delete=<?= $u['id'] ?>"
           class="btn btn-sm btn-danger"
           onclick="return confirm('Remove this user?')">
           Remove
        </a>
    </td>
</tr>
<?php endwhile; ?>
</table>

<a href="dashboard.php" class="btn btn-secondary">Back</a>
</div>

</div>

<script>
document.getElementById('roleSelect').addEventListener('change', function () {
    const category = document.getElementById('categorySelect');
    if (this.value === 'super_admin') {
        category.disabled = true;
        category.value = "";
    } else {
        category.disabled = false;
    }
});
</script>

</body>
</html>
