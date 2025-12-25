<?php
include "../config/db.php";
include "../config/auth.php";

if (!auth() || auth()['role'] !== 'user') {
    header("Location: ../auth/login.php");
    exit;
}

$cat = (int) auth()['category_id'];

/* -------------------- SORTING LOGIC -------------------- */
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

/* -------------------- FILTER & SEARCH -------------------- */
$where = "category_id = $cat AND deleted_at IS NULL";

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

$sql = "SELECT * FROM crime_files WHERE $where ORDER BY $sort $order";
$q = mysqli_query($conn, $sql);

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
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; color: #1e293b; }
        #sidebar { width: var(--sidebar-width); height: 100vh; position: fixed; background: #0f172a; z-index: 1050; display: flex; flex-direction: column; }
        #content { margin-left: var(--sidebar-width); padding: 25px; transition: 0.3s; min-height: 100vh; display: flex; flex-direction: column; }
        @media (max-width: 1024px) { #sidebar { left: -260px; } #sidebar.active { left: 0; } #content { margin-left: 0; } }
        
        .nav-link { color: #94a3b8; padding: 12px 18px; border-radius: 8px; font-weight: 500; margin-bottom: 5px; }
        .nav-link:hover, .nav-link.active { background: #1e293b; color: #38bdf8; }
        
        .table-container { background: white; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden; flex-grow: 1; }
        .table thead th { background: #f8fafc; border-bottom: 2px solid #e2e8f0; white-space: nowrap; padding: 15px 12px; }
        .table tbody td { padding: 12px; font-size: 0.8rem; vertical-align: middle; white-space: nowrap; }
        .text-wrap-column { white-space: normal !important; min-width: 180px; max-width: 220px; }
        
        .footer-copy { font-size: 0.75rem; color: #64748b; padding: 20px; text-align: center; margin-top: auto; border-top: 1px solid #1e293b; }
        .main-page-footer { text-align: center; padding: 20px; color: #94a3b8; font-size: 0.8rem; }
    </style>
</head>
<body>

<nav id="sidebar">
    <div class="p-4">
        <div class="d-flex align-items-center mb-5 text-white">
            <i class="fas fa-gavel fa-2x text-primary me-3"></i>
            <span class="fs-5 fw-bold">LEGAL CMS</span>
        </div>
        <div class="nav flex-column">
            <a href="dashboard.php" class="nav-link active"><i class="fas fa-house me-2"></i> Dashboard</a>
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
    <div class="d-flex justify-content-between align-items-center mb-4">
        <button id="toggleBtn" class="btn btn-outline-dark d-lg-none"><i class="fas fa-bars"></i></button>
        <h4 class="fw-bold m-0 text-dark">Crime Files Repository</h4>
        <div class="badge bg-white text-dark border px-3 py-2 fw-medium shadow-sm">
            <i class="fas fa-tag text-primary me-2"></i>Category: <?= $cat ?>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-3 p-3 mb-4">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-8">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Search by subject, station, crime, or legal numbers..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                </div>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100 fw-bold">Search</button>
            </div>
            <div class="col-md-2">
                <a href="dashboard.php" class="btn btn-light border w-100">Reset</a>
            </div>
        </form>
    </div>

    <div class="table-container">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th><?= sortLink('subject_number','Subject No') ?></th>
                        <th><?= sortLink('division','Division') ?></th>
                        <th><?= sortLink('police_station','Station') ?></th>
                        <th><?= sortLink('crime','Crime') ?></th>
                        <th><?= sortLink('in_date','In Date') ?></th>
                        <th><?= sortLink('court_number','Court No') ?></th>
                        <th><?= sortLink('gcr_number','GCR No') ?></th>
                        <th><?= sortLink('in_word_no_date','In Word') ?></th>
                        <th><?= sortLink('division_station_out_word_date','Div Out') ?></th>
                        <th><?= sortLink('remember_date','Remember') ?></th>
                        <th><?= sortLink('dir_legal_out_word_date','Legal Out') ?></th>
                        <th><?= sortLink('dir_legal_subject_number','Legal Sub') ?></th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($q) > 0): ?>
                        <?php while($r = mysqli_fetch_assoc($q)): ?>
                        <tr>
                            <td class="fw-bold text-primary"><?= $r['subject_number'] ?></td>
                            <td><?= $r['division'] ?></td>
                            <td><?= $r['police_station'] ?></td>
                            <td class="text-wrap-column small"><?= $r['crime'] ?></td>
                            <td><?= date('d-m-Y', strtotime($r['in_date'])) ?></td>
                            <td><?= $r['court_number'] ?: '<span class="text-muted">---</span>' ?></td>
                            <td><?= $r['gcr_number'] ?: '<span class="text-muted">---</span>' ?></td>
                            <td><?= $r['in_word_no_date'] ?: '<span class="text-muted">---</span>' ?></td>
                            <td><?= $r['division_station_out_word_date'] ?: '<span class="text-muted">---</span>' ?></td>
                            <td>
                                <?php if($r['remember_date']): ?>
                                    <span class="text-danger fw-bold"><i class="fas fa-calendar-check me-1"></i><?= $r['remember_date'] ?></span>
                                <?php else: ?>
                                    <span class="text-muted">---</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $r['dir_legal_out_word_date'] ?: '<span class="text-muted">---</span>' ?></td>
                            <td class="fw-bold"><?= $r['dir_legal_subject_number'] ?: '<span class="text-muted">---</span>' ?></td>
                            <td class="text-center">
                                <a href="crime_show.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary px-3 rounded-pill">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="13" class="text-center py-5 text-muted">No files found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <footer class="main-page-footer">
        creativity by <strong>athavan ds</strong> &copy; <?= date('Y') ?>
    </footer>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $('#toggleBtn').click(function() { $('#sidebar').toggleClass('active'); });
</script>
</body>
</html>