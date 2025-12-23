<?php
include "../config/db.php";
include "../config/auth.php";

if (!auth()) {
    header("Location: ../auth/login.php");
    exit;
}

// Get ID from GET and cast to int
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$category_id = auth()['category_id']; // can be NULL
$user_id     = auth()['id'];

if ($id <= 0) {
    die("Invalid Crime File ID");
}

/* ---------------- GET CRIME FILE ---------------- */
$sql = "SELECT * FROM crime_files WHERE id = $id AND deleted_at IS NULL";

// Apply category filter only if user has category_id
if ($category_id !== null) {
    $sql .= " AND category_id = $category_id";
}

$result = mysqli_query($conn, $sql);

if (!$result || mysqli_num_rows($result) == 0) {
    die("Crime file not found or access denied");
}

$crime = mysqli_fetch_assoc($result);

/* ---------------- LOG SEEN INFO ---------------- */
// Only for normal users
if (auth()['role'] !== 'super_admin') {
    foreach (mysqli_query($conn, "SELECT id FROM pdf_stuff WHERE crime_file_id = $id AND deleted_at IS NULL") as $pdf) {
        $pdf_id = $pdf['id'];
        $seenCheck = mysqli_query($conn, "
            SELECT id FROM seen_info
            WHERE pdf_id = $pdf_id AND users_id = $user_id
        ");

        if (mysqli_num_rows($seenCheck) == 0) {
            mysqli_query($conn, "
                INSERT INTO seen_info (pdf_id, users_id, created_at)
                VALUES ($pdf_id, $user_id, NOW())
            ");
        }
    }
}

/* ---------------- GET PDF FILES ---------------- */
$pdfs = mysqli_query($conn, "
    SELECT * FROM pdf_stuff
    WHERE crime_file_id = $id AND deleted_at IS NULL
");

include "../assets/header.php";
?>

<div class="card p-4">
    <h4>Crime File Details</h4>

    <table class="table table-bordered">
        <tr><th>Subject Number</th><td><?= htmlspecialchars($crime['subject_number']) ?></td></tr>
        <tr><th>Division</th><td><?= htmlspecialchars($crime['division']) ?></td></tr>
        <tr><th>Police Station</th><td><?= htmlspecialchars($crime['police_station']) ?></td></tr>
        <tr><th>Crime</th><td><?= htmlspecialchars($crime['crime']) ?></td></tr>
        <tr><th>In Date</th><td><?= htmlspecialchars($crime['in_date']) ?></td></tr>
        <tr><th>Court Number</th><td><?= htmlspecialchars($crime['court_number']) ?></td></tr>
        <tr><th>GCR Number</th><td><?= htmlspecialchars($crime['gcr_number']) ?></td></tr>
        <tr><th>Created At</th><td><?= $crime['created_at'] ?></td></tr>
    </table>

    <h5>PDF Files</h5>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>#</th>
                <th>File</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php $i=1; while ($pdf = mysqli_fetch_assoc($pdfs)) : ?>
            <tr>
                <td><?= $i++ ?></td>
                <td><?= htmlspecialchars($pdf['file_name']) ?></td>
                <td>
                    <a class="btn btn-sm btn-primary"
                       href="../uploads/crime_pdfs/<?= htmlspecialchars($pdf['file_path']) ?>"
                       target="_blank">View</a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

    <?php if (auth()['role'] === 'super_admin') : ?>
        <h5>Seen Information</h5>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Seen At</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $seen = mysqli_query($conn, "
                SELECT u.username, s.created_at
                FROM seen_info s
                JOIN users u ON u.id = s.user_id
                WHERE s.pdf_id IN (
                    SELECT id FROM pdf_stuff WHERE crime_file_id = $id AND deleted_at IS NULL
                )
            ");

            while ($row = mysqli_fetch_assoc($seen)) :
            ?>
                <tr>
                    <td><?= htmlspecialchars($row['username']) ?></td>
                    <td><?= $row['created_at'] ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <a href="dashboard.php" class="btn btn-secondary mt-3">Back</a>
</div>

</div>
</body>
</html>
