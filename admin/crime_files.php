<?php
include "../config/db.php";
include "../config/auth.php";

if (!isAdmin()) {
    header("Location: ../auth/login.php");
    exit;
}

// ---------------- BASE WHERE ----------------
$where = "deleted_at IS NULL";

// ---------------- FILTERS ----------------
if (!empty($_GET['year'])) {
    $year = (int)$_GET['year'];
    $where .= " AND YEAR(created_at) = $year";
}

if (!empty($_GET['division'])) {
    $division = mysqli_real_escape_string($conn, $_GET['division']);
    $where .= " AND division = '$division'";
}

if (!empty($_GET['crime'])) {
    $crime = mysqli_real_escape_string($conn, $_GET['crime']);
    $where .= " AND crime = '$crime'";
}

if (!empty($_GET['search'])) {
    $s = mysqli_real_escape_string($conn, $_GET['search']);
    $where .= " AND (
        subject_number LIKE '%$s%' OR
        police_station LIKE '%$s%' OR
        court_number LIKE '%$s%' OR
        gcr_number LIKE '%$s%'
    )";
}

// ---------------- FINAL QUERY ----------------
$sql = "SELECT * FROM crime_files 
        WHERE $where 
        ORDER BY created_at DESC";

$q = mysqli_query($conn, $sql);
?>

<h3>All Crime Files (Admin)</h3>

<form method="GET">
    <input name="search" placeholder="Search anything">

    <select name="year">
        <option value="">Year</option>
        <option>2024</option>
        <option>2025</option>
    </select>

    <button>Filter</button>
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
