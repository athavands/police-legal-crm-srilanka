<?php
include "../config/db.php";
include "../config/auth.php";

if (!auth() || auth()['role'] !== 'user') {
    header("Location: ../auth/login.php");
    exit;
}

$id  = (int)$_GET['id'];
$cat = auth()['category_id'];

// Fetch crime file
$q = mysqli_query($conn,"
SELECT * FROM crime_files
WHERE id=$id AND category_id=$cat AND deleted_at IS NULL
");

if (mysqli_num_rows($q) === 0) {
    exit("<div class='alert alert-danger'>Access Denied</div>");
}

$c = mysqli_fetch_assoc($q);

include "../assets/header.php"; // include header for Bootstrap & navbar
?>

<div class="container mt-5">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0"><?= htmlspecialchars($c['subject_number']) ?></h4>
        </div>

        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <ul class="list-group">
                        <li class="list-group-item"><b>Division:</b> <?= htmlspecialchars($c['division']) ?></li>
                        <li class="list-group-item"><b>Police Station:</b> <?= htmlspecialchars($c['police_station']) ?></li>
                        <li class="list-group-item"><b>Crime:</b> <?= htmlspecialchars($c['crime']) ?></li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <ul class="list-group">
                        <li class="list-group-item"><b>Court No:</b> <?= htmlspecialchars($c['court_number']) ?></li>
                        <li class="list-group-item"><b>GCR No:</b> <?= htmlspecialchars($c['gcr_number']) ?></li>
                        <li class="list-group-item"><b>In Date:</b> <?= htmlspecialchars($c['in_date']) ?></li>
                    </ul>
                </div>
            </div>

            <h5 class="mt-4">PDF Files</h5>
            <ul class="list-group mb-3">
                <?php
                $pdfs = mysqli_query($conn,"
                    SELECT * FROM pdf_stuff
                    WHERE crime_file_id={$c['id']} AND deleted_at IS NULL
                ");
                if(mysqli_num_rows($pdfs) === 0){
                    echo "<li class='list-group-item'>No PDF files uploaded.</li>";
                } else {
                    while($p=mysqli_fetch_assoc($pdfs)):
                ?>
                    <li class="list-group-item">
                        <a href="../uploads/crime_pdfs/<?= urlencode($p['file_path']) ?>" target="_blank">
                            📄 <?= htmlspecialchars($p['file_name']) ?>
                        </a>
                    </li>
                <?php endwhile; } ?>
            </ul>

            <a href="dashboard.php" class="btn btn-secondary">Back</a>
        </div>
    </div>
</div>

</body>
</html>
