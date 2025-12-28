<?php
include "../config/db.php";
include "../config/auth.php";

if (!auth()) {
    header("Location: ../auth/login.php");
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id     = auth()['id'];
$category_id = auth()['category_id'];

if ($id <= 0) {
    die("<div class='alert alert-danger m-5'>Invalid Crime File ID</div>");
}

/* ---------------- GET CRIME FILE ---------------- */
$where = "id = $id AND deleted_at IS NULL";
if (auth()['role'] !== 'super_admin') {
    $where .= " AND category_id = $category_id";
}

$sql = "SELECT * FROM crime_files WHERE $where";
$result = mysqli_query($conn, $sql);

if (!$result || mysqli_num_rows($result) == 0) {
    die("<div class='alert alert-danger m-5'>Crime file not found or access denied</div>");
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

$pdfs = mysqli_query($conn, "SELECT * FROM pdf_stuff WHERE crime_file_id = $id AND deleted_at IS NULL");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Details: <?= htmlspecialchars($crime['subject_number']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        :root { --sidebar-width: 260px; }
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; color: #1e293b; }

        #sidebar { width: var(--sidebar-width); height: 100vh; position: fixed; left: 0; top: 0; background: #0f172a; z-index: 1050; transition: 0.3s; }
        #content { margin-left: var(--sidebar-width); width: calc(100% - var(--sidebar-width)); padding: 25px; transition: 0.3s; }

        @media (max-width: 1024px) {
            #sidebar { left: -260px; }
            #sidebar.active { left: 0; }
            #content { margin-left: 0; width: 100%; }
        }

        .nav-link { color: #94a3b8; padding: 12px 18px; border-radius: 8px; font-weight: 500; margin-bottom: 5px; }
        .nav-link:hover, .nav-link.active { background: #1e293b; color: #38bdf8; }
        
        /* Detail Styling */
        .info-card { background: white; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .detail-label { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: #64748b; margin-bottom: 2px; }
        .detail-value { font-size: 0.95rem; font-weight: 500; color: #1e293b; margin-bottom: 15px; }
        
        .pdf-item { display: flex; align-items: center; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 10px; transition: 0.2s; text-decoration: none; color: inherit; }
        .pdf-item:hover { background: #f1f5f9; border-color: #cbd5e1; }
        .pdf-icon { width: 40px; height: 40px; background: #fee2e2; color: #ef4444; display: flex; align-items: center; justify-content: center; border-radius: 6px; margin-right: 15px; }

        .timeline-item { border-left: 2px solid #e2e8f0; padding-left: 15px; padding-bottom: 15px; position: relative; }
        .timeline-item::before { content: ""; width: 10px; height: 10px; background: #3b82f6; position: absolute; left: -6px; top: 5px; border-radius: 50%; }

        /* -------------------- PRINT STYLES -------------------- */
        @media print {
            #sidebar, #toggleBtn, .breadcrumb, .btn, .info-card:last-child { display: none !important; }
            #content { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
            .info-card { border: none !important; box-shadow: none !important; }
            body { background: white !important; }
        }
    </style>
</head>
<body>

<nav id="sidebar" class="p-4">
    <div class="d-flex align-items-center mb-5 text-white">
        <i class="fas fa-file-invoice fa-2x text-primary me-3"></i>
        <span class="fs-5 fw-bold">LEGAL CMS</span>
    </div>
    <div class="nav flex-column">
        <a href="dashboard.php" class="nav-link active"><i class="fas fa-th-large me-2"></i> Dashboard</a>
        <a href="crime_files_create.php" class="nav-link"><i class="fas fa-folder-plus me-2"></i> New File</a>
        <a href="create_user.php" class="nav-link"><i class="fas fa-users me-2"></i> Users</a>
        <a href="categories.php" class="nav-link"><i class="fas fa-list-ul me-2"></i> Categories</a>
        <hr class="text-secondary opacity-25">
        <a href="profile.php" class="nav-link"><i class="fas fa-folder-plus"></i> Change Pasword</a>
        <a href="../auth/logout.php" class="nav-link text-danger"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
    </div>
</nav>

<div id="content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <button id="toggleBtn" class="btn btn-outline-dark d-lg-none"><i class="fas fa-bars"></i></button>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb m-0">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active">File Details</li>
            </ol>
        </nav>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-primary fw-bold px-3">
                <i class="fas fa-print me-1"></i> Print
            </button>
            <a href="crime_files_edit.php?id=<?= $crime['id'] ?>" class="btn btn-warning fw-bold px-3">
                <i class="fas fa-edit me-1"></i> Edit File
            </a>
            <a href="dashboard.php" class="btn btn-outline-secondary px-3">
                <i class="fas fa-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="info-card p-4 h-100">
                <div class="d-flex align-items-center mb-4 border-bottom pb-3">
                    <div class="bg-primary-subtle text-primary p-3 rounded-3 me-3">
                        <i class="fas fa-folder-open fa-lg"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold m-0"><?= htmlspecialchars($crime['subject_number']) ?></h4>
                        <span class="badge bg-primary rounded-pill"><?= htmlspecialchars($crime['crime']) ?></span>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <p class="detail-label">Division / Police Station</p>
                        <p class="detail-value text-capitalize"><?= htmlspecialchars($crime['division']) ?> — <?= htmlspecialchars($crime['police_station']) ?></p>
                        
                        <p class="detail-label">In Date</p>
                        <p class="detail-value"><i class="fas fa-calendar-alt me-1 text-muted"></i> <?= htmlspecialchars($crime['in_date']) ?></p>
                        
                        <p class="detail-label">Court Number</p>
                        <p class="detail-value text-uppercase fw-bold"><?= htmlspecialchars($crime['court_number']) ?: 'N/A' ?></p>
                    </div>
                    <div class="col-md-6">
                        <p class="detail-label">GCR Number</p>
                        <p class="detail-value"><?= htmlspecialchars($crime['gcr_number']) ?: '---' ?></p>
                        
                        <p class="detail-label">Remember Date</p>
                        <p class="detail-value">
                            <?php if($crime['remember_date']): ?>
                                <span class="text-danger fw-bold"><i class="fas fa-bell me-1"></i> <?= htmlspecialchars($crime['remember_date']) ?></span>
                            <?php else: ?>
                                <span class="text-muted">No reminder set</span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>

                <hr class="my-4">

                <h6 class="fw-bold mb-3"><i class="fas fa-gavel me-2"></i>Legal & Office Correspondence</h6>
                <div class="row">
                    <div class="col-md-6">
                        <p class="detail-label">IN Word No / Date</p>
                        <p class="detail-value small"><?= htmlspecialchars($crime['in_word_no_date']) ?: '---' ?></p>
                        
                        <p class="detail-label">Div/Station OUT Word</p>
                        <p class="detail-value small"><?= htmlspecialchars($crime['division_station_out_word_date']) ?: '---' ?></p>
                    </div>
                    <div class="col-md-6">
                        <p class="detail-label">Dir Legal OUT Word / Date</p>
                        <p class="detail-value small"><?= htmlspecialchars($crime['dir_legal_out_word_date']) ?: '---' ?></p>
                        
                        <p class="detail-label">Dir Legal Subject Number</p>
                        <p class="detail-value small fw-bold text-primary"><?= htmlspecialchars($crime['dir_legal_subject_number']) ?: '---' ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="info-card p-4 mb-4">
                <h6 class="fw-bold mb-3"><i class="fas fa-file-pdf text-danger me-2"></i>Case Documents</h6>
                <div class="pdf-list">
                    <?php if (mysqli_num_rows($pdfs) === 0): ?>
                        <div class="text-center py-4 text-muted small">No PDF files attached.</div>
                    <?php else: ?>
                        <?php while ($pdf = mysqli_fetch_assoc($pdfs)) : ?>
                        <a href="../uploads/crime_pdfs/<?= urlencode($pdf['file_path']) ?>" target="_blank" class="pdf-item">
                            <div class="pdf-icon"><i class="fas fa-file-pdf"></i></div>
                            <div class="overflow-hidden">
                                <div class="small fw-bold text-truncate"><?= htmlspecialchars($pdf['file_name']) ?></div>
                                <div class="text-muted" style="font-size: 0.7rem;">Click to open PDF</div>
                            </div>
                        </a>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (auth()['role'] === 'super_admin') : ?>
            <div class="info-card p-4">
                <h6 class="fw-bold mb-3"><i class="fas fa-eye me-2"></i>Access History</h6>
                <div class="small">
                    <?php
                    $seen = mysqli_query($conn, "
                        SELECT u.username, s.created_at
                        FROM seen_info s
                        JOIN users u ON u.id = s.user_id
                        WHERE s.pdf_id IN (SELECT id FROM pdf_stuff WHERE crime_file_id = $id AND deleted_at IS NULL)
                        ORDER BY s.created_at DESC LIMIT 10
                    ");
                    if (mysqli_num_rows($seen) === 0): ?>
                        <div class="text-muted">No access logs yet.</div>
                    <?php else: while ($s = mysqli_fetch_assoc($seen)): ?>
                        <div class="timeline-item">
                            <div class="fw-bold"><?= htmlspecialchars($s['username']) ?></div>
                            <div class="text-muted" style="font-size: 0.75rem;">Accessed on <?= date('d M, Y H:i', strtotime($s['created_at'])) ?></div>
                        </div>
                    <?php endwhile; endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $('#toggleBtn').click(function() { $('#sidebar').toggleClass('active'); });
</script>

</body>
</html>