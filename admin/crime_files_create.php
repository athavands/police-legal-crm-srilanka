<?php
include "../config/db.php";
include "../config/auth.php";

if (!isAdmin()) {
    header("Location: ../auth/login.php");
    exit;
}

$errors = [];

/* ---------- FETCH CATEGORIES ---------- */
$categories_result = mysqli_query(
    $conn,
    "SELECT id, name FROM categories ORDER BY name ASC"
);

if (isset($_POST['save'])) {
    /* Logic remains identical to your original code */
    $subject_number = trim($_POST['subject_number'] ?? '');
    $division       = trim($_POST['division'] ?? '');
    $police_station = trim($_POST['police_station'] ?? '');
    $crime          = trim($_POST['crime'] ?? '');
    $in_date        = $_POST['in_date'] ?? '';
    $court_number   = trim($_POST['court_number'] ?? '');
    $gcr_number     = trim($_POST['gcr_number'] ?? '');

    $in_word_no_date                = trim($_POST['in_word_no_date'] ?? '');
    $division_station_out_word_date = trim($_POST['division_station_out_word_date'] ?? '');
    $remember_date                  = $_POST['remember_date'] ?? null;
    $dir_legal_out_word_date        = trim($_POST['dir_legal_out_word_date'] ?? '');
    $dir_legal_subject_number       = trim($_POST['dir_legal_subject_number'] ?? '');

    $category_id = (int)($_POST['category_id'] ?? 0);
    $created_by  = auth()['id'];

    if ($subject_number === '') $errors[] = "Subject Number is required";
    if ($division === '')       $errors[] = "Division is required";
    if ($police_station === '') $errors[] = "Police Station is required";
    if ($crime === '')          $errors[] = "Crime is required";
    if ($in_date === '')        $errors[] = "In Date is required";
    if ($category_id <= 0)      $errors[] = "Category selection is required";

    if (empty($errors)) {
        $sql = "INSERT INTO crime_files (subject_number, division, police_station, crime, in_date, court_number, gcr_number, in_word_no_date, division_station_out_word_date, remember_date, dir_legal_out_word_date, dir_legal_subject_number, category_id, created_by) 
                VALUES ('$subject_number', '$division', '$police_station', '$crime', '$in_date', '$court_number', '$gcr_number', '$in_word_no_date', '$division_station_out_word_date', " . ($remember_date ? "'$remember_date'" : "NULL") . ", '$dir_legal_out_word_date', '$dir_legal_subject_number', '$category_id', '$created_by')";

        mysqli_query($conn, $sql);
        $crime_file_id = mysqli_insert_id($conn);

        $uploadDir = "../uploads/crime_pdfs/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        foreach ($_FILES['pdf_files']['name'] as $key => $fileName) {
            $tmpName = $_FILES['pdf_files']['tmp_name'][$key];
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            if ($ext !== 'pdf') continue;
            $newName = time() . "_" . rand(1000, 9999) . ".pdf";
            if (move_uploaded_file($tmpName, $uploadDir . $newName)) {
                mysqli_query($conn, "INSERT INTO pdf_stuff (crime_file_id, file_name, file_path, category_id, created_by) VALUES ('$crime_file_id','$fileName','$newName','$category_id','$created_by')");
            }
        }
        header("Location: dashboard.php?success=crime_created");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Crime File | CMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        :root { --sidebar-width: 260px; }
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; color: #1e293b; overflow-x: hidden; }

        #sidebar { width: var(--sidebar-width); height: 100vh; position: fixed; left: 0; top: 0; background: #0f172a; z-index: 1050; transition: 0.3s; }
        #content { margin-left: var(--sidebar-width); width: calc(100% - var(--sidebar-width)); padding: 25px; transition: 0.3s; }

        @media (max-width: 1024px) {
            #sidebar { left: -260px; }
            #sidebar.active { left: 0; }
            #content { margin-left: 0; width: 100%; }
        }

        .nav-link { color: #94a3b8; padding: 12px 18px; border-radius: 8px; font-weight: 500; margin-bottom: 5px; }
        .nav-link:hover, .nav-link.active { background: #1e293b; color: #38bdf8; }
        .nav-link i { width: 25px; }

        .form-card { background: white; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .section-title { font-size: 0.9rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #64748b; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px; margin-bottom: 20px; }
        
        label { font-weight: 600; font-size: 0.85rem; color: #475569; margin-bottom: 5px; }
        .form-control, .form-select { border-radius: 8px; border: 1px solid #cbd5e1; padding: 10px 12px; font-size: 0.9rem; }
        .form-control:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
        
        .btn-primary { background-color: #2563eb; border: none; padding: 10px 24px; font-weight: 600; border-radius: 8px; }
        .btn-add-pdf { background-color: #f1f5f9; color: #475569; border: 1px dashed #cbd5e1; font-weight: 600; width: 100%; padding: 10px; margin-top: 10px; transition: 0.2s; }
        .btn-add-pdf:hover { background-color: #e2e8f0; }
    </style>
</head>
<body>

<nav id="sidebar" class="p-4">
    <div class="d-flex align-items-center mb-5 text-white">
        <i class="fas fa-gavel fa-2x text-primary me-3"></i>
        <span class="fs-5 fw-bold">LEGAL CMS</span>
    </div>
    <div class="nav flex-column">
        <a href="dashboard.php" class="nav-link"><i class="fas fa-th-large"></i> Dashboard</a>
        <a href="crime_files_create.php" class="nav-link active"><i class="fas fa-folder-plus"></i> New File</a>
        <a href="create_user.php" class="nav-link"><i class="fas fa-user-shield"></i> Users</a>
        <a href="categories.php" class="nav-link"><i class="fas fa-list-ul"></i> Categories</a>
        <hr class="text-secondary opacity-25">
        <a href="profile.php" class="nav-link"><i class="fas fa-folder-plus"></i> Change Pasword</a>

        <a href="../auth/logout.php" class="nav-link text-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</nav>

<div id="content">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <button id="toggleBtn" class="btn btn-outline-dark d-lg-none"><i class="fas fa-bars"></i></button>
        <h4 class="fw-bold m-0">Create New Crime File</h4>
        <a href="dashboard.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Back</a>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger shadow-sm border-0 rounded-3">
            <h6 class="fw-bold"><i class="fas fa-exclamation-triangle me-2"></i> Please correct the following errors:</h6>
            <ul class="mb-0 small">
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="form-card p-4 p-md-5">
        <form method="POST" enctype="multipart/form-data">
            
            <div class="section-title"><i class="fas fa-info-circle me-2"></i> Basic Information</div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label>Subject Number <span class="text-danger">*</span></label>
                    <input type="text" name="subject_number" class="form-control" placeholder="e.g. CR-2025-001" required>
                </div>
                <div class="col-md-4">
                    <label>Division <span class="text-danger">*</span></label>
                    <input type="text" name="division" class="form-control" placeholder="Enter Division" required>
                </div>
                <div class="col-md-4">
                    <label>Police Station <span class="text-danger">*</span></label>
                    <input type="text" name="police_station" class="form-control" placeholder="Enter Station" required>
                </div>
                <div class="col-md-8">
                    <label>Crime Description <span class="text-danger">*</span></label>
                    <input type="text" name="crime" class="form-control" placeholder="Nature of the crime" required>
                </div>
                <div class="col-md-4">
                    <label>In Date <span class="text-danger">*</span></label>
                    <input type="date" name="in_date" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label>Court Number</label>
                    <input type="text" name="court_number" class="form-control" placeholder="MC/HC No.">
                </div>
                <div class="col-md-4">
                    <label>GCR Number</label>
                    <input type="text" name="gcr_number" class="form-control" placeholder="GCR Reference">
                </div>
                <div class="col-md-4">
                    <label>Category <span class="text-danger">*</span></label>
                    <select name="category_id" class="form-select" required>
                        <option value="">-- Select Category --</option>
                        <?php mysqli_data_seek($categories_result, 0); while ($cat = mysqli_fetch_assoc($categories_result)): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>

            <div class="section-title mt-5"><i class="fas fa-file-invoice me-2"></i> Legal / Office Details</div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label>IN Word No – Date</label>
                    <input type="text" name="in_word_no_date" class="form-control" placeholder="Internal word reference">
                </div>
                <div class="col-md-6">
                    <label>Division / Station OUT Word – Date</label>
                    <input type="text" name="division_station_out_word_date" class="form-control">
                </div>
                <div class="col-md-4">
                    <label>Remember Date</label>
                    <input type="date" name="remember_date" class="form-control">
                </div>
                <div class="col-md-4">
                    <label>Dir Legal OUT Word – Date</label>
                    <input type="text" name="dir_legal_out_word_date" class="form-control">
                </div>
                <div class="col-md-4">
                    <label>Dir Legal Subject Number</label>
                    <input type="text" name="dir_legal_subject_number" class="form-control">
                </div>
            </div>

            <div class="section-title mt-5"><i class="fas fa-cloud-upload-alt me-2"></i> Document Evidence (PDF)</div>
            <div id="pdf-wrapper">
                <div class="mb-3">
                    <input type="file" name="pdf_files[]" class="form-control" accept="application/pdf">
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-add-pdf shadow-sm" onclick="addPdf()">
                <i class="fas fa-plus-circle me-1"></i> Add Another PDF File
            </button>

            <div class="mt-5 pt-4 border-top">
                <button class="btn btn-primary" name="save">
                    <i class="fas fa-save me-2"></i> Save Crime File
                </button>
                <a href="dashboard.php" class="btn btn-light border ms-2">Cancel</a>
            </div>

        </form>
    </div>

    <p class="text-center mt-4 text-muted small">&copy; <?= date('Y') ?> Police Legal Division | CMS V2.0</p>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    function addPdf() {
        const div = document.createElement('div');
        div.className = 'mb-2 animate__animated animate__fadeInUp';
        div.innerHTML = `<div class="input-group">
            <input type="file" name="pdf_files[]" class="form-control" accept="application/pdf">
            <button type="button" class="btn btn-outline-danger" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        </div>`;
        document.getElementById('pdf-wrapper').appendChild(div);
    }

    $('#toggleBtn').click(function() { $('#sidebar').toggleClass('active'); });
</script>

</body>
</html>