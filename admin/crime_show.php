<?php
include "../config/db.php";
include "../config/auth.php";

if (!auth()) {
    header("Location: ../auth/login.php");
    exit;
}

// Get ID from GET and cast to int
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id     = auth()['id'];
$category_id = auth()['category_id']; // normal users only

if ($id <= 0) {
    die("<div class='alert alert-danger'>Invalid Crime File ID</div>");
}

/* ---------------- GET CRIME FILE ---------------- */
$where = "id = $id AND deleted_at IS NULL";

// Apply category filter only for normal users
if (auth()['role'] !== 'super_admin') {
    $where .= " AND category_id = $category_id";
}

$sql = "SELECT * FROM crime_files WHERE $where";
$result = mysqli_query($conn, $sql);

if (!$result || mysqli_num_rows($result) == 0) {
    die("<div class='alert alert-danger'>Crime file not found or access denied</div>");
}

$crime = mysqli_fetch_assoc($result);

/* ---------------- LOG SEEN INFO ---------------- */
if (auth()['role'] !== 'super_admin') {
    $pdf_list = mysqli_query($conn, "SELECT id FROM pdf_stuff WHERE crime_file_id = $id AND deleted_at IS NULL");
    foreach ($pdf_list as $pdf) {
        $pdf_id = $pdf['id'];
        $check = mysqli_query($conn, "SELECT id FROM seen_info WHERE pdf_id = $pdf_id AND user_id = $user_id");
        if (mysqli_num_rows($check) === 0) {
            mysqli_query($conn, "INSERT INTO seen_info (pdf_id, user_id, created_at) VALUES ($pdf_id, $user_id, NOW())");
        }
    }
}

/* ---------------- GET PDF FILES ---------------- */
$pdfs = mysqli_query($conn, "SELECT * FROM pdf_stuff WHERE crime_file_id = $id AND deleted_at IS NULL");

include "../assets/header.php";
?>

<div class="container mt-4">
    <div class="card p-4 shadow-sm">
        <h4 class="mb-3"><?= htmlspecialchars($crime['subject_number']) ?></h4>
        <a href="crime_files_edit.php?id=<?= $crime['id'] ?>" class="btn btn-warning btn-sm mb-3">✏ Edit</a>

        <ul class="list-group mb-3">
            <li class="list-group-item"><b>Division:</b> <?= htmlspecialchars($crime['division']) ?></li>
            <li class="list-group-item"><b>Police Station:</b> <?= htmlspecialchars($crime['police_station']) ?></li>
            <li class="list-group-item"><b>Crime:</b> <?= htmlspecialchars($crime['crime']) ?></li>
            <li class="list-group-item"><b>In Date:</b> <?= htmlspecialchars($crime['in_date']) ?></li>
            <li class="list-group-item"><b>Court Number:</b> <?= htmlspecialchars($crime['court_number']) ?></li>
            <li class="list-group-item"><b>GCR Number:</b> <?= htmlspecialchars($crime['gcr_number']) ?></li>
            <li class="list-group-item"><b>IN Word No – Date:</b> <?= htmlspecialchars($crime['in_word_no_date']) ?></li>
            <li class="list-group-item"><b>Division / Station OUT Word – Date:</b> <?= htmlspecialchars($crime['division_station_out_word_date']) ?></li>
            <li class="list-group-item"><b>Remember Date:</b> <?= htmlspecialchars($crime['remember_date']) ?></li>
            <li class="list-group-item"><b>Dir Legal OUT Word – Date:</b> <?= htmlspecialchars($crime['dir_legal_out_word_date']) ?></li>
            <li class="list-group-item"><b>Dir Legal Subject Number:</b> <?= htmlspecialchars($crime['dir_legal_subject_number']) ?></li>
            <li class="list-group-item"><b>Category ID:</b> <?= htmlspecialchars($crime['category_id']) ?></li>
            <li class="list-group-item"><b>Created By:</b> <?= htmlspecialchars($crime['created_by']) ?></li>
            <li class="list-group-item"><b>Updated By:</b> <?= htmlspecialchars($crime['updated_by']) ?></li>
            <li class="list-group-item"><b>Created At:</b> <?= htmlspecialchars($crime['created_at']) ?></li>
            <li class="list-group-item"><b>Updated At:</b> <?= htmlspecialchars($crime['updated_at']) ?></li>
        </ul>

        <h5>PDF Files</h5>
        <ul class="list-group mb-3">
            <?php
            if (mysqli_num_rows($pdfs) === 0) {
                echo "<li class='list-group-item'>No PDF files uploaded.</li>";
            } else {
                while ($pdf = mysqli_fetch_assoc($pdfs)) :
            ?>
                <li class="list-group-item">
                    <a href="../uploads/crime_pdfs/<?= urlencode($pdf['file_path']) ?>" target="_blank">
                        📄 <?= htmlspecialchars($pdf['file_name']) ?>
                    </a>
                </li>
            <?php endwhile; } ?>
        </ul>

        <?php if (auth()['role'] === 'super_admin') : ?>
            <h5>Seen Information</h5>
            <ul class="list-group mb-3">
                <?php
                $seen = mysqli_query($conn, "
                    SELECT u.username, s.created_at
                    FROM seen_info s
                    JOIN users u ON u.id = s.user_id
                    WHERE s.pdf_id IN (
                        SELECT id FROM pdf_stuff WHERE crime_file_id = $id AND deleted_at IS NULL
                    )
                ");
                if (mysqli_num_rows($seen) === 0) {
                    echo "<li class='list-group-item'>No views yet.</li>";
                } else {
                    while ($s = mysqli_fetch_assoc($seen)) :
                ?>
                    <li class="list-group-item">
                        <?= htmlspecialchars($s['username']) ?> — viewed at <?= $s['created_at'] ?>
                    </li>
                <?php endwhile; } ?>
            </ul>
        <?php endif; ?>

        <a href="dashboard.php" class="btn btn-secondary mt-3">Back</a>
    </div>
</div>

</body>
</html>
