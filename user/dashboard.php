<?php
include "../config/db.php";
include "../config/auth.php";

if (!auth() || auth()['role'] !== 'user') {
    header("Location: ../auth/login.php");
    exit;
}

$cat = (int) auth()['category_id'];

/* -------------------- SORTING -------------------- */
$allowedSort = [
    'subject_number', 'division', 'police_station', 'crime', 'in_date', 
    'court_number', 'gcr_number', 'in_word_no_date', 
    'division_station_out_word_date', 'remember_date', 
    'dir_legal_out_word_date', 'dir_legal_subject_number'
];

$sort  = $_GET['sort'] ?? 'in_date';
$order = $_GET['order'] ?? 'DESC';
if (!in_array($sort, $allowedSort)) $sort = 'in_date';
$order = ($order === 'ASC') ? 'ASC' : 'DESC';

/* -------------------- YEAR LIST -------------------- */
$yearsRes = mysqli_query($conn,"SELECT DISTINCT YEAR(in_date) AS year FROM crime_files WHERE category_id=$cat AND deleted_at IS NULL ORDER BY year DESC");

/* -------------------- FILTER & SEARCH -------------------- */
$where = "category_id=$cat AND deleted_at IS NULL";

if (!empty($_GET['year'])) {
    $year = (int) $_GET['year'];
    $where .= " AND YEAR(in_date) = $year";
}

if (!empty($_GET['search'])) {
    $s = mysqli_real_escape_string($conn, $_GET['search']);
    $where .= " AND (
        subject_number LIKE '%$s%' OR 
        division LIKE '%$s%' OR 
        police_station LIKE '%$s%' OR 
        crime LIKE '%$s%' OR 
        court_number LIKE '%$s%' OR 
        gcr_number LIKE '%$s%' OR
        in_word_no_date LIKE '%$s%' OR
        division_station_out_word_date LIKE '%$s%' OR
        dir_legal_out_word_date LIKE '%$s%' OR
        dir_legal_subject_number LIKE '%$s%'
    )";
}

/* -------------------- PAGINATION LOGIC -------------------- */
$limit = 25; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$totalQuery = mysqli_query($conn, "SELECT COUNT(*) as total FROM crime_files WHERE $where");
$totalRows = mysqli_fetch_assoc($totalQuery)['total'];
$totalPages = ceil($totalRows / $limit);

// Main display query
$q = mysqli_query($conn,"SELECT * FROM crime_files WHERE $where ORDER BY $sort $order LIMIT $offset, $limit");

// Full query for Export (all matching records)
$export_q = mysqli_query($conn, "SELECT * FROM crime_files WHERE $where ORDER BY $sort $order");

/* -------------------- SORT LINK HELPER -------------------- */
function sortLink($column, $label) {
    $currentSort  = $_GET['sort'] ?? '';
    $currentOrder = $_GET['order'] ?? 'DESC';
    $newOrder = ($currentSort === $column && $currentOrder === 'ASC') ? 'DESC' : 'ASC';
    
    $icon = '<i class="fas fa-sort text-muted opacity-25 ms-1"></i>';
    if ($currentSort === $column) {
        $icon = ($currentOrder === 'ASC') ? '<i class="fas fa-sort-up ms-1 text-primary"></i>' : '<i class="fas fa-sort-down ms-1 text-primary"></i>';
    }

    $query = $_GET;
    $query['sort']  = $column;
    $query['order'] = $newOrder;

    return '<a href="?' . http_build_query($query) . '" class="text-dark text-decoration-none fw-bold small text-uppercase">' . $label . $icon . '</a>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard | Legal CMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --sidebar-width: 260px; }
        body { font-family: 'Inter', sans-serif; background-color: #f1f5f9; color: #1e293b; }
        #sidebar { width: var(--sidebar-width); height: 100vh; position: fixed; background: #0f172a; z-index: 1050; transition: 0.3s; }
        #content { margin-left: var(--sidebar-width); padding: 25px; transition: 0.3s; }
        @media (max-width: 1024px) { #sidebar { left: -260px; } #sidebar.active { left: 0; } #content { margin-left: 0; } }
        
        .table-container { background: white; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .table thead th { background: #f8fafc; border-bottom: 2px solid #e2e8f0; white-space: nowrap; padding: 15px 12px; font-size: 0.75rem; }
        .table tbody td { padding: 12px; font-size: 0.8rem; vertical-align: middle; white-space: nowrap; }
        .text-wrap-crime { white-space: normal !important; min-width: 200px; max-width: 250px; line-height: 1.4; }
        
        .nav-link { color: #94a3b8; padding: 12px 18px; border-radius: 8px; font-weight: 500; }
        .nav-link.active { background: #1e293b; color: #38bdf8; }
        .badge-sub { background: #eff6ff; color: #1d4ed8; border: 1px solid #dbeafe; font-weight: 600; }
    </style>
</head>
<body>

<nav id="sidebar" class="p-4">
    <div class="d-flex align-items-center mb-5 text-white">
        <i class="fas fa-file-shield fa-2x text-primary me-3"></i>
        <span class="fs-5 fw-bold">LEGAL CMS</span>
    </div>
    <div class="nav flex-column">
        <a href="dashboard.php" class="nav-link active"><i class="fas fa-th-large me-2"></i> Dashboard</a>
        <a href="profile.php" class="nav-link"><i class="fas fa-user-cog me-2"></i> Settings</a>
        <hr class="text-secondary opacity-25">
        <a href="../auth/logout.php" class="nav-link text-danger"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
    </div>
</nav>

<div id="content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <button id="toggleBtn" class="btn btn-outline-dark d-lg-none"><i class="fas fa-bars"></i></button>
        <h4 class="fw-bold m-0">Case Files Dashboard</h4>
        
        <div class="d-flex align-items-center gap-3">
            <div class="btn-group shadow-sm">
                <button onclick="exportToExcel()" class="btn btn-white bg-white border text-success fw-bold small">
                    <i class="fas fa-file-excel me-1"></i> EXCEL
                </button>
                <button onclick="exportToPDF()" class="btn btn-white bg-white border text-danger fw-bold small">
                    <i class="fas fa-file-pdf me-1"></i> PDF
                </button>
            </div>
            <div class="small text-muted">Category ID: <span class="badge bg-secondary"><?= $cat ?></span></div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 p-3 mb-4">
        <form method="GET" class="row g-2">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input name="search" class="form-control border-start-0 ps-0" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" placeholder="Search all columns...">
                </div>
            </div>
            <div class="col-md-3">
                <select name="year" class="form-select">
                    <option value="">All Filing Years</option>
                    <?php mysqli_data_seek($yearsRes, 0); while ($y = mysqli_fetch_assoc($yearsRes)): ?>
                        <option value="<?= $y['year'] ?>" <?= (($_GET['year'] ?? '') == $y['year']) ? 'selected' : '' ?>><?= $y['year'] ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100 fw-bold">Filter</button>
            </div>
            <div class="col-md-2">
                <a href="dashboard.php" class="btn btn-light border w-100">Clear</a>
            </div>
        </form>
    </div>

    <div class="table-container overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th><?= sortLink('subject_number','Subject No') ?></th>
                        <th><?= sortLink('division','Division') ?></th>
                        <th><?= sortLink('police_station','Station') ?></th>
                        <th><?= sortLink('crime','Crime Type') ?></th>
                        <th><?= sortLink('in_date','In Date') ?></th>
                        <th><?= sortLink('court_number','Court No') ?></th>
                        <th><?= sortLink('gcr_number','GCR No') ?></th>
                        <th><?= sortLink('in_word_no_date','In Word/Date') ?></th>
                        <th><?= sortLink('division_station_out_word_date','Div Out/Date') ?></th>
                        <th><?= sortLink('remember_date','Reminder') ?></th>
                        <th><?= sortLink('dir_legal_out_word_date','Legal Out/Date') ?></th>
                        <th><?= sortLink('dir_legal_subject_number','Legal Sub No') ?></th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($q) > 0): ?>
                        <?php while ($r = mysqli_fetch_assoc($q)): ?>
                        <tr>
                            <td><span class="badge badge-sub"><?= $r['subject_number'] ?></span></td>
                            <td><?= $r['division'] ?></td>
                            <td><?= $r['police_station'] ?></td>
                            <td class="text-wrap-crime"><?= $r['crime'] ?></td>
                            <td><?= date('d-m-Y', strtotime($r['in_date'])) ?></td>
                            <td><?= $r['court_number'] ?: '---' ?></td>
                            <td><?= $r['gcr_number'] ?: '---' ?></td>
                            <td><?= $r['in_word_no_date'] ?: '---' ?></td>
                            <td><?= $r['division_station_out_word_date'] ?: '---' ?></td>
                            <td>
                                <?php if($r['remember_date']): ?>
                                    <span class="text-danger fw-bold"><i class="fas fa-bell me-1"></i><?= $r['remember_date'] ?></span>
                                <?php else: ?>
                                    <span class="text-muted">None</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $r['dir_legal_out_word_date'] ?: '---' ?></td>
                            <td class="fw-bold"><?= $r['dir_legal_subject_number'] ?: '---' ?></td>
                            <td class="text-center">
                                <a href="crime_show.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary px-3 rounded-pill">
                                    <i class="fas fa-eye me-1"></i> View
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="13" class="text-center py-5 text-muted">No records found matching your query.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
        <div class="p-3 border-top bg-light">
            <nav>
                <ul class="pagination pagination-sm m-0 justify-content-center">
                    <?php 
                        $queryParams = $_GET;
                        for ($i = 1; $i <= $totalPages; $i++): 
                        $queryParams['page'] = $i;
                    ?>
                    <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query($queryParams) ?>"><?= $i ?></a>
                    </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>

    <table id="hiddenExportTable" style="display:none;">
        <thead>
            <tr>
                <th>Subject Number</th><th>Division</th><th>Station</th><th>Crime Type</th>
                <th>In Date</th><th>Court Number</th><th>GCR Number</th><th>In Word/Date</th>
                <th>Div Out/Date</th><th>Reminder Date</th><th>Legal Out/Date</th><th>Legal Sub No</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            mysqli_data_seek($export_q, 0);
            while($ex = mysqli_fetch_assoc($export_q)): ?>
            <tr>
                <td><?= $ex['subject_number'] ?></td>
                <td><?= $ex['division'] ?></td>
                <td><?= $ex['police_station'] ?></td>
                <td><?= $ex['crime'] ?></td>
                <td><?= $ex['in_date'] ?></td>
                <td><?= $ex['court_number'] ?></td>
                <td><?= $ex['gcr_number'] ?></td>
                <td><?= $ex['in_word_no_date'] ?></td>
                <td><?= $ex['division_station_out_word_date'] ?></td>
                <td><?= $ex['remember_date'] ?></td>
                <td><?= $ex['dir_legal_out_word_date'] ?></td>
                <td><?= $ex['dir_legal_subject_number'] ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <p class="text-center mt-4 text-muted small">&copy; <?= date('Y') ?> Police Legal Division | Software Developed by Athavan DS</p>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

<script>
    $('#toggleBtn').click(function() { $('#sidebar').toggleClass('active'); });

    function exportToExcel() {
        var table = document.getElementById("hiddenExportTable");
        var wb = XLSX.utils.table_to_book(table, { sheet: "Case Records" });
        XLSX.writeFile(wb, "Case_Files_Export_<?= date('Ymd') ?>.xlsx");
    }

    function exportToPDF() {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('l', 'mm', 'a4'); 
        doc.setFontSize(16);
        doc.text("Police Legal Division - Case File Records", 14, 15);
        doc.setFontSize(10);
        doc.text("Generated on: <?= date('Y-m-d H:i') ?>", 14, 22);

        doc.autoTable({
            html: '#hiddenExportTable',
            startY: 28,
            theme: 'grid',
            styles: { fontSize: 7, cellPadding: 2 },
            headStyles: { fillColor: [15, 23, 42], textColor: [255, 255, 255] },
            alternateRowStyles: { fillColor: [245, 247, 250] }
        });
        doc.save("Case_Files_Export_<?= date('Ymd') ?>.pdf");
    }
</script>

</body>
</html>