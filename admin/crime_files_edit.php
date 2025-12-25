<?php
include "../config/db.php";
include "../config/auth.php";

if (!isAdmin()) {
    header("Location: ../auth/login.php");
    exit;
}

$errors = [];
$crime_file_id = (int)($_GET['id'] ?? 0);

if ($crime_file_id <= 0) {
    header("Location: dashboard.php");
    exit;
}

/* ---------- FETCH EXISTING DATA ---------- */
$res = mysqli_query($conn, "SELECT * FROM crime_files WHERE id='$crime_file_id' AND deleted_at IS NULL LIMIT 1");
$crime_file = mysqli_fetch_assoc($res);
if (!$crime_file) {
    header("Location: dashboard.php");
    exit;
}

/* ---------- FETCH CATEGORIES ---------- */
$categories_result = mysqli_query($conn, "SELECT id, name FROM categories ORDER BY name ASC");

/* ---------- FETCH EXISTING PDFS ---------- */
$existing_pdfs = mysqli_query($conn, "SELECT id, file_name FROM pdf_stuff WHERE crime_file_id = $crime_file_id AND deleted_at IS NULL");

if (isset($_POST['save'])) {
    $subject_number = mysqli_real_escape_string($conn, trim($_POST['subject_number']));
    $division       = mysqli_real_escape_string($conn, trim($_POST['division']));
    $police_station = mysqli_real_escape_string($conn, trim($_POST['police_station']));
    $crime          = mysqli_real_escape_string($conn, trim($_POST['crime']));
    $in_date        = $_POST['in_date'];
    $court_number   = mysqli_real_escape_string($conn, trim($_POST['court_number']));
    $gcr_number     = mysqli_real_escape_string($conn, trim($_POST['gcr_number']));
    $in_word_no_date = mysqli_real_escape_string($conn, trim($_POST['in_word_no_date']));
    $division_station_out_word_date = mysqli_real_escape_string($conn, trim($_POST['division_station_out_word_date']));
    $remember_date  = $_POST['remember_date'] ?: null;
    $dir_legal_out_word_date = mysqli_real_escape_string($conn, trim($_POST['dir_legal_out_word_date']));
    $dir_legal_subject_number = mysqli_real_escape_string($conn, trim($_POST['dir_legal_subject_number']));
    $category_id    = (int)$_POST['category_id'];
    $updated_by     = auth()['id'];

    if (!$subject_number || !$division || !$police_station || !$crime || !$in_date || $category_id <= 0) {
        $errors[] = "Please fill in all required fields (*)";
    }

    if (empty($errors)) {
        $sql = "UPDATE crime_files SET 
                subject_number='$subject_number', division='$division', police_station='$police_station', 
                crime='$crime', in_date='$in_date', court_number='$court_number', gcr_number='$gcr_number', 
                in_word_no_date='$in_word_no_date', division_station_out_word_date='$division_station_out_word_date', 
                remember_date=" . ($remember_date ? "'$remember_date'" : "NULL") . ", 
                dir_legal_out_word_date='$dir_legal_out_word_date', dir_legal_subject_number='$dir_legal_subject_number', 
                category_id='$category_id', updated_by='$updated_by', updated_at=NOW() 
                WHERE id='$crime_file_id'";

        if (mysqli_query($conn, $sql)) {
            // Handle PDF Uploads
            if (!empty($_FILES['pdf_files']['name'][0])) {
                $uploadDir = "../uploads/crime_pdfs/";
                foreach ($_FILES['pdf_files']['name'] as $key => $fileName) {
                    $tmpName = $_FILES['pdf_files']['tmp_name'][$key];
                    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                    if ($ext === 'pdf') {
                        $newName = time() . "_" . rand(1000, 9999) . ".pdf";
                        if (move_uploaded_file($tmpName, $uploadDir . $newName)) {
                            mysqli_query($conn, "INSERT INTO pdf_stuff (crime_file_id, file_name, file_path, category_id, created_by) VALUES ('$crime_file_id','$fileName','$newName','$category_id','$updated_by')");
                        }
                    }
                }
            }
            header("Location: dashboard.php?success=updated");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Case: <?= htmlspecialchars($crime_file['subject_number']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --sidebar-width: 260px; }
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; color: #1e293b; }
        #sidebar { width: var(--sidebar-width); height: 100vh; position: fixed; background: #0f172a; z-index: 1050; }
        #content { margin-left: var(--sidebar-width); padding: 25px; transition: 0.3s; }
        @media (max-width: 1024px) { #sidebar { left: -260px; } #content { margin-left: 0; } }
        .nav-link { color: #94a3b8; padding: 12px 18px; border-radius: 8px; }
        .nav-link.active { background: #1e293b; color: #38bdf8; }
        .card-custom { background: white; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .form-label { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: #64748b; margin-bottom: 5px; }
        .form-control, .form-select { border-radius: 8px; padding: 10px; font-size: 0.9rem; }
        .section-title { font-size: 0.85rem; font-weight: 700; color: #3b82f6; border-bottom: 2px solid #f1f5f9; padding-bottom: 5px; margin-bottom: 20px; text-transform: uppercase; }
    </style>
</head>
<body>

<nav id="sidebar" class="p-4">
    <div class="d-flex align-items-center mb-5 text-white">
        <i class="fas fa-shield-halved fa-2x text-primary me-3"></i>
        <span class="fs-5 fw-bold">LEGAL CMS</span>
    </div>
    <div class="nav flex-column">
        <a href="dashboard.php" class="nav-link active"><i class="fas fa-th-large me-2"></i> Dashboard</a>
        <a href="crime_files_create.php" class="nav-link"><i class="fas fa-folder-plus me-2"></i> New File</a>
        <hr class="text-secondary opacity-25">
        <a href="profile.php" class="nav-link"><i class="fas fa-folder-plus"></i> Change Pasword</a>

        <a href="../auth/logout.php" class="nav-link text-danger"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
    </div>
</nav>

<div id="content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold m-0">Edit Case File</h4>
        <a href="crime_file_view.php?id=<?= $crime_file_id ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-eye me-1"></i> View File
        </a>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger shadow-sm border-0"><?= implode('<br>', $errors) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card-custom p-4 mb-4">
                    <div class="section-title"><i class="fas fa-id-card me-2"></i>Case Identity</div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Subject Number *</label>
                            <input type="text" name="subject_number" class="form-control" required value="<?= htmlspecialchars($crime_file['subject_number']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Category / Office *</label>
                            <select name="category_id" class="form-select" required>
                                <option value="">-- Select Category --</option>
                                <?php mysqli_data_seek($categories_result, 0); while ($cat = mysqli_fetch_assoc($categories_result)): ?>
                                    <option value="<?= $cat['id'] ?>" <?= ($cat['id'] == $crime_file['category_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Division *</label>
                            <input type="text" name="division" class="form-control" required value="<?= htmlspecialchars($crime_file['division']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Police Station *</label>
                            <input type="text" name="police_station" class="form-control" required value="<?= htmlspecialchars($crime_file['police_station']) ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Crime Description *</label>
                            <input type="text" name="crime" class="form-control" required value="<?= htmlspecialchars($crime_file['crime']) ?>">
                        </div>
                    </div>
                </div>

                <div class="card-custom p-4 mb-4">
                    <div class="section-title"><i class="fas fa-gavel me-2"></i>Court & Registry</div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">In Date *</label>
                            <input type="date" name="in_date" class="form-control" required value="<?= htmlspecialchars($crime_file['in_date']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Court Number</label>
                            <input type="text" name="court_number" class="form-control" value="<?= htmlspecialchars($crime_file['court_number']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">GCR Number</label>
                            <input type="text" name="gcr_number" class="form-control" value="<?= htmlspecialchars($crime_file['gcr_number']) ?>">
                        </div>
                    </div>
                </div>

                <div class="card-custom p-4">
                    <div class="section-title"><i class="fas fa-envelope-open-text me-2"></i>Legal Correspondence</div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">IN Word No – Date</label>
                            <input type="text" name="in_word_no_date" class="form-control" value="<?= htmlspecialchars($crime_file['in_word_no_date']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Div/Station OUT Word – Date</label>
                            <input type="text" name="division_station_out_word_date" class="form-control" value="<?= htmlspecialchars($crime_file['division_station_out_word_date']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-danger">Remember Date</label>
                            <input type="date" name="remember_date" class="form-control border-danger-subtle" value="<?= htmlspecialchars($crime_file['remember_date']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Dir Legal OUT Word – Date</label>
                            <input type="text" name="dir_legal_out_word_date" class="form-control" value="<?= htmlspecialchars($crime_file['dir_legal_out_word_date']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Dir Legal Subject No</label>
                            <input type="text" name="dir_legal_subject_number" class="form-control" value="<?= htmlspecialchars($crime_file['dir_legal_subject_number']) ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card-custom p-4 mb-4">
                    <h6 class="fw-bold mb-3 small text-uppercase">Current Documents</h6>
                    <div class="list-group list-group-flush mb-3">
                        <?php if(mysqli_num_rows($existing_pdfs) > 0): while($epdf = mysqli_fetch_assoc($existing_pdfs)): ?>
                            <div class="list-group-item d-flex align-items-center px-0 small">
                                <i class="fas fa-file-pdf text-danger me-2"></i>
                                <span class="text-truncate"><?= htmlspecialchars($epdf['file_name']) ?></span>
                            </div>
                        <?php endwhile; else: ?>
                            <div class="text-muted small italic">No documents uploaded.</div>
                        <?php endif; ?>
                    </div>

                    <h6 class="fw-bold mb-3 small text-uppercase">Attach New PDF</h6>
                    <div id="pdf-wrapper">
                        <div class="mb-2">
                            <input type="file" name="pdf_files[]" class="form-control form-control-sm" accept="application/pdf">
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-light border w-100 mb-3" onclick="addPdf()">
                        <i class="fas fa-plus me-1"></i> Add Another
                    </button>
                </div>

                <div class="card-custom p-3 bg-light border-0">
                    <button class="btn btn-primary w-100 fw-bold mb-2" name="save">
                        <i class="fas fa-save me-2"></i> Save Changes
                    </button>
                    <a href="dashboard.php" class="btn btn-outline-secondary w-100 btn-sm">Cancel</a>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    function addPdf() {
        const div = document.createElement('div');
        div.className = 'mb-2';
        div.innerHTML = `<input type="file" name="pdf_files[]" class="form-control form-control-sm" accept="application/pdf">`;
        document.getElementById('pdf-wrapper').appendChild(div);
    }
</script>
</body>
</html>