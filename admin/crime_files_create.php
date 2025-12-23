<?php
include "../config/db.php";
include "../config/auth.php";

if (!isAdmin()) {
    header("Location: ../auth/login.php");
    exit;
}

$errors = [];

/* ---------- FETCH CATEGORIES ---------- */
$categories_result = mysqli_query($conn, "SELECT id, name FROM categories ORDER BY name ASC");

if (isset($_POST['save'])) {

    $subject_number  = trim($_POST['subject_number']);
    $division        = trim($_POST['division']);
    $police_station  = trim($_POST['police_station']);
    $crime           = trim($_POST['crime']);
    $in_date         = $_POST['in_date'];
    $court_number    = trim($_POST['court_number']);
    $gcr_number      = trim($_POST['gcr_number']);
    $category_id     = (int)$_POST['category_id']; // from form
    $created_by      = auth()['id'];

    /* ---------- VALIDATION ---------- */
    if ($subject_number == '')  $errors[] = "Subject Number is required";
    if ($division == '')        $errors[] = "Division is required";
    if ($police_station == '')  $errors[] = "Police Station is required";
    if ($crime == '')           $errors[] = "Crime is required";
    if ($in_date == '')         $errors[] = "In Date is required";
    if ($category_id <= 0)      $errors[] = "Category selection is required";

    if (empty($_FILES['pdf_files']['name'][0])) {
        $errors[] = "At least one PDF file is required";
    }

    /* ---------- SAVE DATA ---------- */
    if (empty($errors)) {

        mysqli_query($conn, "
            INSERT INTO crime_files 
            (subject_number, division, police_station, crime, in_date, court_number, gcr_number, category_id, created_by)
            VALUES (
                '$subject_number',
                '$division',
                '$police_station',
                '$crime',
                '$in_date',
                '$court_number',
                '$gcr_number',
                '$category_id',
                '$created_by'
            )
        ");

        $crime_file_id = mysqli_insert_id($conn);

        /* ---------- PDF UPLOAD ---------- */
        $uploadDir = "../uploads/crime_pdfs/";

        foreach ($_FILES['pdf_files']['name'] as $key => $fileName) {

            $tmpName = $_FILES['pdf_files']['tmp_name'][$key];
            $ext     = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            if ($ext !== 'pdf') continue;

            $newName = time() . "_" . rand(1000,9999) . ".pdf";
            $path    = $uploadDir . $newName;

            if (move_uploaded_file($tmpName, $path)) {
                mysqli_query($conn, "
                    INSERT INTO pdf_stuff
                    (crime_file_id, file_name, file_path, category_id, created_by)
                    VALUES
                    (
                        '$crime_file_id',
                        '$fileName',
                        '$newName',
                        '$category_id',
                        '$created_by'
                    )
                ");
            }
        }

        header("Location: dashboard.php?success=crime_created");
        exit;
    }
}

include "../assets/header.php";
?>

<div class="card p-4">
    <h4>Create Crime File</h4>

    <?php if (!empty($errors)) : ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $e) : ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">

        <div class="row">
            <div class="col-md-6 mb-3">
                <label>Subject Number *</label>
                <input type="text" name="subject_number" class="form-control" required>
            </div>

            <div class="col-md-6 mb-3">
                <label>Division *</label>
                <input type="text" name="division" class="form-control" required>
            </div>

            <div class="col-md-6 mb-3">
                <label>Police Station *</label>
                <input type="text" name="police_station" class="form-control" required>
            </div>

            <div class="col-md-6 mb-3">
                <label>Crime *</label>
                <input type="text" name="crime" class="form-control" required>
            </div>

            <div class="col-md-6 mb-3">
                <label>In Date *</label>
                <input type="date" name="in_date" class="form-control" required>
            </div>

            <div class="col-md-6 mb-3">
                <label>Court Number</label>
                <input type="text" name="court_number" class="form-control">
            </div>

            <div class="col-md-6 mb-3">
                <label>GCR Number</label>
                <input type="text" name="gcr_number" class="form-control">
            </div>

            <div class="col-md-6 mb-3">
                <label>Category *</label>
                <select name="category_id" class="form-control" required>
                    <option value="">-- Select Category --</option>
                    <?php while ($cat = mysqli_fetch_assoc($categories_result)) : ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>

        <hr>

        <h6>Upload PDF Files *</h6>
        <div id="pdf-wrapper">
            <div class="mb-2">
                <input type="file" name="pdf_files[]" class="form-control" accept="application/pdf" required>
            </div>
        </div>

        <button type="button" class="btn btn-sm btn-secondary mb-3" onclick="addPdf()">
            + Add More PDF
        </button>

        <br>

        <button class="btn btn-success" name="save">Save Crime File</button>
        <a href="dashboard.php" class="btn btn-secondary">Back</a>
    </form>
</div>

<script>
function addPdf() {
    const div = document.createElement('div');
    div.className = 'mb-2';
    div.innerHTML = `<input type="file" name="pdf_files[]" class="form-control" accept="application/pdf" required>`;
    document.getElementById('pdf-wrapper').appendChild(div);
}
</script>

</div>
</body>
</html>
