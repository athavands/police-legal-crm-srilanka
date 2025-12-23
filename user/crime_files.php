<?php
include "../config/db.php";
include "../config/auth.php";

if (!auth() || auth()['role'] !== 'user') {
    header("Location: ../auth/login.php");
    exit;
}

$cat = (int) auth()['category_id'];

$where = "category_id = $cat AND deleted_at IS NULL";

if (!empty($_GET['search'])) {
    $s = mysqli_real_escape_string($conn, $_GET['search']);
    $where .= " AND (
        subject_number LIKE '%$s%' OR
        police_station LIKE '%$s%' OR
        crime LIKE '%$s%' OR
        court_number LIKE '%$s%'
    )";
}

$sql = "SELECT * FROM crime_files 
        WHERE $where 
        ORDER BY created_at DESC";

$q = mysqli_query($conn, $sql);
?>

<h3>Crime Files</h3>

<form method="GET">
<input name="search" placeholder="Search">
<button>Search</button>
</form>

<table border="1" width="100%">
<tr>
<th>Subject</th>
<th>Division</th>
<th>Station</th>
<th>Crime</th>
<th>Date</th>
<th>View</th>
</tr>

<?php while($r = mysqli_fetch_assoc($q)): ?>
<tr>
<td><?= $r['subject_number'] ?></td>
<td><?= $r['division'] ?></td>
<td><?= $r['police_station'] ?></td>
<td><?= $r['crime'] ?></td>
<td><?= $r['in_date'] ?></td>
<td>
<a href="crime_show.php?id=<?= $r['id'] ?>">👁</a>
</td>
</tr>
<?php endwhile; ?>
</table>
