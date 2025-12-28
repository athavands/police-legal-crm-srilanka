<?php
include "../config/db.php";
include "../config/auth.php";

if (!auth() || auth()['role'] !== 'user') {
    header("Location: ../auth/login.php");
    exit;
}

$id  = (int)($_GET['id'] ?? 0);
$cat = auth()['category_id'];

// Fetch crime file with security check (category_id must match)
$q = mysqli_query($conn,"SELECT * FROM crime_files WHERE id=$id AND category_id=$cat AND deleted_at IS NULL");

if (mysqli_num_rows($q) === 0) {
    exit("<div class='alert alert-danger m-5'>Access Denied or File Not Found.</div>");
}

$c = mysqli_fetch_assoc($q);

// Log seen info (Tracking who viewed which PDF)
$pdf_list = mysqli_query($conn, "SELECT id FROM pdf_stuff WHERE crime_file_id={$c['id']} AND deleted_at IS NULL");
foreach ($pdf_list as $pdf) {
    $pdf_id = $pdf['id'];
    $u_id = auth()['id'];
    $check = mysqli_query($conn, "SELECT id FROM seen_info WHERE pdf_id = $pdf_id AND user_id = $u_id");
    if (mysqli_num_rows($check) === 0) {
        mysqli_query($conn, "INSERT INTO seen_info (pdf_id, user_id, created_at) VALUES ($pdf_id, $u_id, NOW())");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Details | Legal CMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --sidebar-width: 260px; }
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; color: #1e293b; }
        #sidebar { width: var(--sidebar-width); height: 100vh; position: fixed; background: #0f172a; z-index: 1050; display: flex; flex-direction: column; }
        #content { margin-left: var(--sidebar-width); padding: 40px; transition: 0.3s; min-height: 100vh; display: flex; flex-direction: column; }
        @media (max-width: 1024px) { #sidebar { left: -260px; } #sidebar.active { left: 0; } #content { margin-left: 0; } }
        
        .detail-card { background: white; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); overflow: hidden; }
        .detail-header { background: #f1f5f9; padding: 25px; border-bottom: 1px solid #e2e8f0; }
        .info-label { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: #64748b; margin-bottom: 2px; }
        .info-value { font-size: 0.95rem; font-weight: 500; color: #0f172a; margin-bottom: 15px; }
        
        .pdf-item { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; transition: 0.2s; }
        .pdf-item:hover { background: #eff6ff; border-color: #3b82f6; }
        
        .nav-link { color: #94a3b8; padding: 12px 18px; border-radius: 8px; font-weight: 500; }
        .nav-link.active { background: #1e293b; color: #38bdf8; }
        .footer-copy { font-size: 0.75rem; color: #64748b; padding: 20px; text-align: center; margin-top: auto; border-top: 1px solid #1e293b; }

        /* -------------------- PRINT STYLES -------------------- */
        @media print {
            #sidebar, #toggleBtn, .btn, .text-muted.italic, hr, .footer-copy { display: none !important; }
            #content { margin-left: 0 !important; padding: 0 !important; width: 100% !important; }
            .detail-card { border: 1px solid #000 !important; box-shadow: none !important; }
            .detail-header { background-color: #f1f5f9 !important; -webkit-print-color-adjust: exact; }
            body { background: white !important; }
            .info-value { color: black !important; }
        }
    </style>
</head>
<body>

<nav id="sidebar">
    <div class="p-4">
        <div class="d-flex align-items-center mb-5 text-white">
            <i class="fas fa-file-invoice fa-2x text-primary me-3"></i>
            <span class="fs-5 fw-bold">LEGAL CMS</span>
        </div>
        <div class="nav flex-column">
            <a href="dashboard.php" class="nav-link"><i class="fas fa-house me-2"></i> Dashboard</a>
            <a href="profile.php" class="nav-link"><i class="fas fa-user-shield me-2"></i> My Account</a>
            <hr class="text-secondary opacity-25">
            <a href="../auth/logout.php" class="nav-link text-danger"><i class="fas fa-power-off me-2"></i> Logout</a>
        </div>
    </div>
    <div class="footer-copy">
        creativity by <br><strong>athavan ds</strong>
    </div>
</nav>

<div id="content">
    <div class="mb-4 d-flex align-items-center justify-content-between">
        <a href="dashboard.php" class="btn btn-link text-decoration-none p-0 text-muted">
            <i class="fas fa-arrow-left me-2"></i> Back to Repository
        </a>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-primary fw-bold shadow-sm">
                <i class="fas fa-print me-2"></i> PRINT FILE
            </button>
            <button id="toggleBtn" class="btn btn-outline-dark d-lg-none"><i class="fas fa-bars"></i></button>
        </div>
    </div>

    <div class="detail-card">
        <div class="detail-header d-flex justify-content-between align-items-center">
            <div>
                <span class="badge bg-primary mb-2">Subject Number</span>
                <h3 class="fw-bold m-0"><?= htmlspecialchars($c['subject_number']) ?></h3>
            </div>
            <div class="text-end">
                <p class="info-label m-0">In Date</p>
                <p class="fw-bold text-primary m-0"><?= date('d M Y', strtotime($c['in_date'])) ?></p>
            </div>
        </div>

        <div class="p-4">
            <div class="row">
                <div class="col-md-4">
                    <div class="info-label"><i class="fas fa-map-marker-alt me-1"></i> Division</div>
                    <div class="info-value"><?= htmlspecialchars($c['division']) ?></div>

                    <div class="info-label"><i class="fas fa-building-shield me-1"></i> Police Station</div>
                    <div class="info-value"><?= htmlspecialchars($c['police_station']) ?></div>

                    <div class="info-label"><i class="fas fa-handcuffs me-1"></i> Crime Type</div>
                    <div class="info-value text-primary fw-bold"><?= htmlspecialchars($c['crime']) ?></div>
                </div>

                <div class="col-md-4 border-start border-end">
                    <div class="info-label"><i class="fas fa-gavel me-1"></i> Court Number</div>
                    <div class="info-value"><?= htmlspecialchars($c['court_number']) ?: '---' ?></div>

                    <div class="info-label"><i class="fas fa-list-ol me-1"></i> GCR Number</div>
                    <div class="info-value"><?= htmlspecialchars($c['gcr_number']) ?: '---' ?></div>

                    <div class="info-label"><i class="fas fa-calendar-day me-1"></i> Remember Date</div>
                    <div class="info-value <?= $c['remember_date'] ? 'text-danger fw-bold' : '' ?>">
                        <?= $c['remember_date'] ?: 'Not Set' ?>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="info-label">IN Word No / Date</div>
                    <div class="info-value small"><?= htmlspecialchars($c['in_word_no_date']) ?></div>

                    <div class="info-label">Legal Subject Number</div>
                    <div class="info-value fw-bold"><?= htmlspecialchars($c['dir_legal_subject_number']) ?></div>

                    <div class="info-label text-muted">Last Updated</div>
                    <div class="info-value small text-muted"><?= $c['updated_at'] ?></div>
                </div>
            </div>

            <hr class="my-4">

            <h5 class="fw-bold mb-3 text-dark"><i class="fas fa-file-pdf text-danger me-2"></i>Attached Documents</h5>
            <div class="row g-3">
                <?php
                $pdfs = mysqli_query($conn,"SELECT * FROM pdf_stuff WHERE crime_file_id={$c['id']} AND deleted_at IS NULL");
                if(mysqli_num_rows($pdfs) === 0): ?>
                    <div class="col-12 text-muted italic">No PDF documents have been attached to this case file.</div>
                <?php else: 
                    while($p=mysqli_fetch_assoc($pdfs)): ?>
                    <div class="col-md-6">
                        <div class="pdf-item p-3 d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center overflow-hidden">
                                <i class="fas fa-file-pdf fa-2x text-danger me-3"></i>
                                <span class="text-truncate fw-medium"><?= htmlspecialchars($p['file_name']) ?></span>
                            </div>
                            <a href="../uploads/crime_pdfs/<?= urlencode($p['file_path']) ?>" target="_blank" class="btn btn-sm btn-primary">
                                <i class="fas fa-eye me-1"></i> View
                            </a>
                        </div>
                    </div>
                <?php endwhile; endif; ?>
            </div>
        </div>
    </div>
    
    <div class="text-center mt-5 text-muted small">
        creativity by <strong>athavan ds</strong> &copy; <?= date('Y') ?>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $('#toggleBtn').click(function() { $('#sidebar').toggleClass('active'); });
</script>

</body>
</html>