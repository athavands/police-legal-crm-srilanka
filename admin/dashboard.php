<?php
include "../config/db.php";
include "../config/auth.php";

if (!isAdmin()) {
    header("Location: ../auth/login.php");
    exit;
}

/* -------------------- SORTING SETUP (All Columns) -------------------- */
$allowedSort = [
    'subject_number', 'division', 'police_station', 'crime', 'in_date', 
    'court_number', 'gcr_number', 'in_word_no_date', 'division_station_out_word_date', 
    'remember_date', 'dir_legal_out_word_date', 'dir_legal_subject_number'
];

$sort = $_GET['sort'] ?? 'in_date';
$order = $_GET['order'] ?? 'DESC';
if (!in_array($sort, $allowedSort)) { $sort = 'in_date'; }
$order = ($order === 'ASC') ? 'ASC' : 'DESC';

/* -------------------- FILTER & SEARCH LOGIC (FIXED) -------------------- */
$where = "deleted_at IS NULL";

if (!empty($_GET['year'])) {
    $year = (int)$_GET['year'];
    $where .= " AND YEAR(in_date) = $year";
}

if (!empty($_GET['search'])) {
    $s = mysqli_real_escape_string($conn, $_GET['search']);
    // Expanded to include EVERY column in your list
    $where .= " AND (
        subject_number LIKE '%$s%' OR 
        division LIKE '%$s%' OR 
        police_station LIKE '%$s%' OR 
        crime LIKE '%$s%' OR 
        in_date LIKE '%$s%' OR
        court_number LIKE '%$s%' OR 
        gcr_number LIKE '%$s%' OR 
        in_word_no_date LIKE '%$s%' OR 
        division_station_out_word_date LIKE '%$s%' OR 
        remember_date LIKE '%$s%' OR
        dir_legal_out_word_date LIKE '%$s%' OR 
        dir_legal_subject_number LIKE '%$s%'
    )";
}

$q = mysqli_query($conn, "SELECT * FROM crime_files WHERE $where ORDER BY $sort $order");

/* -------------------- HELPER FUNCTIONS -------------------- */
$yearsRes = mysqli_query($conn, "SELECT DISTINCT YEAR(in_date) AS year FROM crime_files WHERE deleted_at IS NULL ORDER BY year DESC");

function sortLink($column, $label) {
    $currentSort = $_GET['sort'] ?? '';
    $currentOrder = $_GET['order'] ?? 'DESC';
    $newOrder = ($currentSort === $column && $currentOrder === 'ASC') ? 'DESC' : 'ASC';
    
    $icon = '<i class="fas fa-sort text-muted opacity-25 ms-1"></i>';
    if ($currentSort === $column) {
        $icon = ($currentOrder === 'ASC') ? '<i class="fas fa-sort-up ms-1 text-primary"></i>' : '<i class="fas fa-sort-down ms-1 text-primary"></i>';
    }

    $query = $_GET;
    $query['sort'] = $column;
    $query['order'] = $newOrder;

    return '<a href="?' . http_build_query($query) . '" class="text-nowrap text-dark text-decoration-none fw-bold small">' 
           . strtoupper($label) . $icon . '</a>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Legal CMS | Full Search</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        :root { --sidebar-width: 260px; }
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; color: #1e293b; overflow-x: hidden; }

        /* Sidebar - Desktop */
        #sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            left: 0; top: 0;
            background: #0f172a;
            z-index: 1050;
            transition: 0.3s;
        }
        
        /* Content Wrapper */
        #content {
            margin-left: var(--sidebar-width);
            width: calc(100% - var(--sidebar-width));
            padding: 25px;
            transition: 0.3s;
        }

        /* Responsive Sidebar for Tablet/Mobile */
        @media (max-width: 1024px) {
            #sidebar { left: -260px; }
            #sidebar.active { left: 0; }
            #content { margin-left: 0; width: 100%; }
        }

        /* Sidebar Styling */
        .nav-link { color: #94a3b8; padding: 12px 18px; border-radius: 8px; font-weight: 500; margin-bottom: 5px; }
        .nav-link:hover, .nav-link.active { background: #1e293b; color: #38bdf8; }
        .nav-link i { width: 25px; }

        /* Table Design */
        .table-card { background: white; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; }
        .table thead th { background: #f1f5f9; padding: 15px 10px; border-bottom: 2px solid #e2e8f0; }
        .table tbody td { padding: 12px 10px; font-size: 0.9rem; border-bottom: 1px solid #f1f5f9; }
        
        /* Mobile Specific Column Hiding (to avoid excessive scrolling) */
        @media (max-width: 768px) {
            .col-optional { display: none; }
        }
    </style>
</head>
<body>

<nav id="sidebar" class="p-4">
    <div class="d-flex align-items-center mb-5 text-white">
        <i class="fas fa-gavel fa-2x text-primary me-3"></i>
        <span class="fs-5 fw-bold">LEGAL CMS</span>
    </div>
    
    <div class="nav flex-column">
        <a href="dashboard.php" class="nav-link active"><i class="fas fa-th-large"></i> Dashboard</a>
        <a href="crime_files_create.php" class="nav-link"><i class="fas fa-folder-plus"></i> New File</a>
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
        <h4 class="fw-bold m-0 d-none d-sm-block">Crime File Records</h4>
        <div class="d-flex align-items-center bg-white p-2 rounded-pill shadow-sm px-3 border">
            <span class="small fw-semibold me-2"><?= auth()['username'] ?></span>
            <img src="https://ui-avatars.com/api/?name=<?= auth()['username'] ?>&background=0284c7&color=fff" width="30" class="rounded-circle">
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form action="" method="GET" class="row g-2">
                <div class="col-lg-7 col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="fas fa-search"></i></span>
                        <input type="text" name="search" class="form-control border-start-0 ps-0" value="<?= $_GET['search'] ?? '' ?>" placeholder="Search Subject, Court No, GCR, Dates, or DL info...">
                    </div>
                </div>
                <div class="col-lg-3 col-md-3">
                    <select name="year" class="form-select">
                        <option value="">All Years</option>
                        <?php while ($y = mysqli_fetch_assoc($yearsRes)): ?>
                            <option value="<?= $y['year'] ?>" <?= (($_GET['year'] ?? '') == $y['year']) ? 'selected' : '' ?>><?= $y['year'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-lg-2 col-md-3">
                    <button type="submit" class="btn btn-primary w-100 fw-bold">SEARCH</button>
                </div>
            </form>
        </div>
    </div>

    <div class="table-card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="ps-3"><?= sortLink('subject_number', 'Subject #') ?></th>
                        <th><?= sortLink('division', 'Division') ?></th>
                        <th class="col-optional"><?= sortLink('police_station', 'Station') ?></th>
                        <th><?= sortLink('crime', 'Crime') ?></th>
                        <th class="col-optional"><?= sortLink('in_date', 'In Date') ?></th>
                        <th><?= sortLink('court_number', 'Court #') ?></th>
                        <th class="col-optional"><?= sortLink('gcr_number', 'GCR') ?></th>
                        <th class="col-optional"><?= sortLink('in_word_no_date', 'In Word') ?></th>
                        <th class="col-optional text-nowrap"><?= sortLink('division_station_out_word_date', 'Div OW') ?></th>
                        <th class="col-optional"><?= sortLink('remember_date', 'Reminder') ?></th>
                        <th class="col-optional"><?= sortLink('dir_legal_out_word_date', 'DL OW') ?></th>
                        <th class="col-optional"><?= sortLink('dir_legal_subject_number', 'DL Sub') ?></th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($q) > 0): ?>
                        <?php while ($r = mysqli_fetch_assoc($q)): ?>
                        <tr>
                            <td class="ps-3 fw-bold text-primary"><?= $r['subject_number'] ?></td>
                            <td><?= $r['division'] ?></td>
                            <td class="col-optional small"><?= $r['police_station'] ?></td>
                            <td><span class="badge bg-secondary-subtle text-secondary border px-2"><?= $r['crime'] ?></span></td>
                            <td class="col-optional small"><?= $r['in_date'] ?></td>
                            <td class="fw-medium"><?= $r['court_number'] ?? '-' ?></td>
                            <td class="col-optional small"><?= $r['gcr_number'] ?></td>
                            <td class="col-optional small"><?= $r['in_word_no_date'] ?></td>
                            <td class="col-optional small"><?= $r['division_station_out_word_date'] ?></td>
                            <td class="col-optional">
                                <?php if($r['remember_date']): ?>
                                    <span class="text-danger small fw-bold"><i class="fas fa-clock me-1"></i><?= $r['remember_date'] ?></span>
                                <?php else: ?> - <?php endif; ?>
                            </td>
                            <td class="col-optional small"><?= $r['dir_legal_out_word_date'] ?></td>
                            <td class="col-optional small"><?= $r['dir_legal_subject_number'] ?></td>
                            <td class="text-center">
                                <a href="crime_show.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="13" class="text-center py-5 text-muted">
                                <i class="fas fa-search fa-2x mb-3 d-block opacity-25"></i>
                                No records found for your search.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <p class="text-center mt-4 text-muted small">&copy; <?= date('Y') ?> Police Legal Division | Software Developed by Athavan DS</p>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Sidebar Toggle for Mobile/Tablet
    $('#toggleBtn').click(function() {
        $('#sidebar').toggleClass('active');
    });

    // Close sidebar when clicking outside on small screens
    $(document).click(function(e) {
        if (!$(e.target).closest('#sidebar, #toggleBtn').length && $(window).width() <= 1024) {
            $('#sidebar').removeClass('active');
        }
    });
</script>

</body>
</html>