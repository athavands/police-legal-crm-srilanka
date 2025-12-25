<?php
include "../config/db.php";
include "../config/auth.php";

if (!isAdmin()) {
    header("Location: ../auth/login.php");
    exit;
}

$errors = [];

/* ---------- GET ID ---------- */
$crime_file_id = (int)($_GET['id'] ?? 0);
if ($crime_file_id <= 0) {
    header("Location: dashboard.php");
    exit;
}

/* ---------- FETCH EXISTING DATA ---------- */
$res = mysqli_query($conn, "SELECT * FROM crime_files WHERE id='$crime_file_id' LIMIT 1");
$crime_file = mysqli_fetch_assoc($res);
if (!$crime_file) {
    header("Location: dashboard.php");
    exit;
}

/* ---------- FETCH CATEGORIES ---------- */
$categories_result = mysqli_query($conn, "SELECT id, name FROM categories ORDER BY name ASC");

if (isset($_POST['save'])) {

    /* ---------- BASIC FIELDS ---------- */
    $subject_number = trim($_POST['subject_number'] ?? '');
    $division       = trim($_POST['division'] ?? '');
    $police_station = trim($_POST['police_station'] ?? '');
    $crime          = trim($_POST['crime'] ?? '');
    $in_date        = $_POST['in_date'] ?? '';
    $court_number   = trim($_POST['court_number'] ?? '');
    $gcr_number     = trim($_POST['gcr_number'] ?? '');

    /* ---------- LEGAL / NEW FIELDS ---------- */
    $in_word_no_date                = trim($_POST['in_word_no_date'] ?? '');
    $division_station_out_word_date = trim($_POST['division_station_out_word_date'] ?? '');
    $remember_date                  = $_POST['remember_date'] ?? null;
    $dir_legal_out_word_date        = trim($_POST['dir_legal_out_word_date'] ?? '');
    $dir_legal_subject_number       = trim($_POST['dir_legal_subject_number'] ?? '');

    $category_id = (int)($_POST['category_id'] ?? 0);
    $updated_by  = auth()['id'];

    /* ---------- VALIDATION ---------- */
    if ($subject_number === '') $errors[] = "Subject Number is required";
    if ($division === '')       $errors[] = "Division is required";
    if ($police_station === '') $errors[] = "Police Station is required";
    if ($crime === '')          $errors[] = "Crime is required";
    if ($in_date === '')        $errors[] = "In Date is required";
    if ($category_id <= 0)      $errors[] = "Category selection is required";

    /* ---------- UPDATE DATA ---------- */
    if (empty($errors)) {

        $sql = "
            UPDATE crime_files SET
                subject_number='$subject_number',
                division='$division',
                police_station='$police_station',
                crime='$crime',
                in_date='$in_date',
                court_number='$court_number',
                gcr_number='$gcr_number',
                in_word_no_date='$in_word_no_date',
                division_station_out_word_date='$division_station_out_word_date',
                remember_date=" . ($remember_date ? "'$remember_date'" : "NULL") . ",
                dir_legal_out_word_date='$dir_legal_out_word_date',
                dir_legal_subject_number='$dir_legal_subject_number',
                category_id='$category_id',
                updated_by='$updated_by',
                updated_at=NOW()
            WHERE id='$crime_file_id'
        ";

        mysqli_query($conn, $sql);

        /* ---------- OPTIONAL PDF UPLOAD ---------- */
        if (!empty($_FILES['pdf_files']['name'][0])) {
            $uploadDir = "../uploads/crime_pdfs/";

            foreach ($_FILES['pdf_files']['name'] as $key => $fileName) {

                $tmpName = $_FILES['pdf_files']['tmp_name'][$key];
                $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                if ($ext !== 'pdf') continue;

                $newName = time() . "_" . rand(1000, 9999) . ".pdf";

                if (move_uploaded_file($tmpName, $uploadDir . $newName)) {
                    mysqli_query($conn, "
                        INSERT INTO pdf_stuff
                        (crime_file_id, file_name, file_path, category_id, created_by)
                        VALUES
                        ('$crime_file_id','$fileName','$newName','$category_id','$updated_by')
                    ");
                }
            }
        }

        header("Location: dashboard.php?success=crime_updated");
        exit;
    }
}

include "../assets/header.php";
?>

<div class="card p-4">
    <h4>Edit Crime File</h4>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">

        <div class="row">

            <div class="col-md-6 mb-3">
                <label>Subject Number *</label>
                <input type="text" name="subject_number" class="form-control" required
                       value="<?= htmlspecialchars($crime_file['subject_number']) ?>">
            </div>

            <div class="col-md-6 mb-3">
                <label>Division *</label>
                <input type="text" name="division" class="form-control" required
                       value="<?= htmlspecialchars($crime_file['division']) ?>">
            </div>

            <div class="col-md-6 mb-3">
                <label>Police Station *</label>
                <input type="text" name="police_station" class="form-control" required
                       value="<?= htmlspecialchars($crime_file['police_station']) ?>">
            </div>

            <div class="col-md-6 mb-3">
                <label>Crime *</label>
                <input type="text" name="crime" class="form-control" required
                       value="<?= htmlspecialchars($crime_file['crime']) ?>">
            </div>

            <div class="col-md-6 mb-3">
                <label>In Date *</label>
                <input type="date" name="in_date" class="form-control" required
                       value="<?= htmlspecialchars($crime_file['in_date']) ?>">
            </div>

            <div class="col-md-6 mb-3">
                <label>Court Number</label>
                <input type="text" name="court_number" class="form-control"
                       value="<?= htmlspecialchars($crime_file['court_number']) ?>">
            </div>

            <div class="col-md-6 mb-3">
                <label>GCR Number</label>
                <input type="text" name="gcr_number" class="form-control"
                       value="<?= htmlspecialchars($crime_file['gcr_number']) ?>">
            </div>

            <div class="col-md-6 mb-3">
                <label>Category *</label>
                <select name="category_id" class="form-control" required>
                    <option value="">-- Select Category --</option>
                    <?php 
                    mysqli_data_seek($categories_result, 0);
                    while ($cat = mysqli_fetch_assoc($categories_result)): ?>
                        <option value="<?= $cat['id'] ?>"
                            <?= ($cat['id'] == $crime_file['category_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

        </div>

        <hr>
        <h5>Legal / Office Details</h5>
        <div class="row">

            <div class="col-md-6 mb-3">
                <label>IN Word No – Date</label>
                <input type="text" name="in_word_no_date" class="form-control"
                       value="<?= htmlspecialchars($crime_file['in_word_no_date']) ?>">
            </div>

            <div class="col-md-6 mb-3">
                <label>Division / Station OUT Word – Date</label>
                <input type="text" name="division_station_out_word_date" class="form-control"
                       value="<?= htmlspecialchars($crime_file['division_station_out_word_date']) ?>">
            </div>

            <div class="col-md-6 mb-3">
                <label>Remember Date</label>
                <input type="date" name="remember_date" class="form-control"
                       value="<?= htmlspecialchars($crime_file['remember_date']) ?>">
            </div>

            <div class="col-md-6 mb-3">
                <label>Dir Legal OUT Word – Date</label>
                <input type="text" name="dir_legal_out_word_date" class="form-control"
                       value="<?= htmlspecialchars($crime_file['dir_legal_out_word_date']) ?>">
            </div>

            <div class="col-md-6 mb-3">
                <label>Dir Legal Subject Number</label>
                <input type="text" name="dir_legal_subject_number" class="form-control"
                       value="<?= htmlspecialchars($crime_file['dir_legal_subject_number']) ?>">
            </div>

        </div>

        <hr>

        <h6>Upload PDF Files (Optional)</h6>
        <div id="pdf-wrapper">
            <div class="mb-2">
                <input type="file" name="pdf_files[]" class="form-control" accept="application/pdf">
            </div>
        </div>

        <button type="button" class="btn btn-sm btn-secondary mb-3" onclick="addPdf()">
            + Add More PDF
        </button>

        <br>

        <button class="btn btn-primary" name="save">Update Crime File</button>
        <a href="dashboard.php" class="btn btn-secondary">Back</a>

    </form>
</div>

<script>
function addPdf() {
    const div = document.createElement('div');
    div.className = 'mb-2';
    div.innerHTML = `<input type="file" name="pdf_files[]" class="form-control" accept="application/pdf">`;
    document.getElementById('pdf-wrapper').appendChild(div);
}
</script>

</div>
</body>
</html>
