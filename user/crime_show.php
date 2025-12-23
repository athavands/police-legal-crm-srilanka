<?php
include "../config/db.php";
include "../config/auth.php";

if (!auth() || auth()['role'] !== 'user') {
    header("Location: ../auth/login.php");
    exit;
}

$id  = (int)$_GET['id'];
$cat = auth()['category_id'];

$q = mysqli_query($conn,"
SELECT * FROM crime_files
WHERE id=$id AND category_id=$cat AND deleted_at IS NULL
");

if (mysqli_num_rows($q) === 0) {
    exit("Access Denied");
}

$c = mysqli_fetch_assoc($q);
?>

<h3><?= $c['subject_number'] ?></h3>

<ul>
<li>Division: <?= $c['division'] ?></li>
<li>Police Station: <?= $c['police_station'] ?></li>
<li>Crime: <?= $c['crime'] ?></li>
<li>Court No: <?= $c['court_number'] ?></li>
<li>GCR No: <?= $c['gcr_number'] ?></li>
</ul>

<h4>PDF Files</h4>

<?php
$pdfs = mysqli_query($conn,"
SELECT * FROM pdf_stuff
WHERE crime_file_id={$c['id']} AND deleted_at IS NULL
");

while($p=mysqli_fetch_assoc($pdfs)):
?>
<a href="../pdf/view.php?id=<?= $p['id'] ?>" target="_blank">
📄 <?= $p['file_name'] ?>
</a><br>
<?php endwhile; ?>
