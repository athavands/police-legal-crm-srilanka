<?php
include "../config/db.php";
include "../config/auth.php";

if (!isAdmin()) header("Location: ../auth/login.php");

if (isset($_POST['add'])) {
    $name = $_POST['name'];
    $city = $_POST['city'];
    mysqli_query($conn,
        "INSERT INTO categories (name, city)
         VALUES ('$name','$city')"
    );
}

$result = mysqli_query($conn, "SELECT * FROM categories ORDER BY id DESC");
include "../assets/header.php";
?>

<div class="card p-4">
<h4>Categories</h4>

<form method="POST" class="row mb-3">
    <div class="col">
        <input class="form-control" name="name" placeholder="Name" required>
    </div>
    <div class="col">
        <input class="form-control" name="city" placeholder="City" required>
    </div>
    <div class="col">
        <button class="btn btn-success" name="add">Add</button>
    </div>
</form>

<table class="table table-bordered">
<tr>
<th>ID</th><th>Name</th><th>City</th><th>Created At</th>
</tr>

<?php while($row = mysqli_fetch_assoc($result)): ?>
<tr>
<td><?= $row['id'] ?></td>
<td><?= $row['name'] ?></td>
<td><?= $row['city'] ?></td>
<td><?= $row['created_at'] ?></td>
</tr>
<?php endwhile; ?>
</table>

<a href="dashboard.php" class="btn btn-secondary">Back</a>
</div>

</div>
</body>
</html>
