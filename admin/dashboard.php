<?php
include "../config/db.php";
include "../config/auth.php";

if (!isAdmin()) {
    header("Location: ../auth/login.php");
    exit;
}

/* -------------------- NOTIFICATION LOGIC -------------------- */
$today = date('Y-m-d');
$notifQuery = mysqli_query($conn, "SELECT COUNT(*) as total FROM crime_files WHERE remember_date = '$today' AND deleted_at IS NULL");
$notifData = mysqli_fetch_assoc($notifQuery);
$notifCount = $notifData['total'] ?? 0;

// Fetch details for the dropdown list
$notifDetailsQuery = mysqli_query($conn, "SELECT id, subject_number, crime FROM crime_files WHERE remember_date = '$today' AND deleted_at IS NULL LIMIT 5");

/* -------------------- SORTING SETUP -------------------- */
$allowedSort = [
    'subject_number', 'division', 'police_station', 'crime', 'in_date', 
    'court_number', 'gcr_number', 'in_word_no_date', 'division_station_out_word_date', 
    'remember_date', 'dir_legal_out_word_date', 'dir_legal_subject_number'
];

$sort = $_GET['sort'] ?? 'in_date';
$order = $_GET['order'] ?? 'DESC';
if (!in_array($sort, $allowedSort)) { $sort = 'in_date'; }
$order = ($order === 'ASC') ? 'ASC' : 'DESC';

/* -------------------- FILTER & SEARCH LOGIC -------------------- */
$where = "deleted_at IS NULL";

if (!empty($_GET['year'])) {
    $year = (int)$_GET['year'];
    $where .= " AND YEAR(in_date) = $year";
}

if (!empty($_GET['search'])) {
    $s = mysqli_real_escape_string($conn, $_GET['search']);
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

        #sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            left: 0; top: 0;
            background: #0f172a;
            z-index: 1050;
            transition: 0.3s;
        }
        
        #content {
            margin-left: var(--sidebar-width);
            width: calc(100% - var(--sidebar-width));
            padding: 25px;
            transition: 0.3s;
        }

        @media (max-width: 1024px) {
            #sidebar { left: -260px; }
            #sidebar.active { left: 0; }
            #content { margin-left: 0; width: 100%; }
        }

        .nav-link { color: #94a3b8; padding: 12px 18px; border-radius: 8px; font-weight: 500; margin-bottom: 5px; position: relative; }
        .nav-link:hover, .nav-link.active { background: #1e293b; color: #38bdf8; }
        .nav-link i { width: 25px; }

        /* Notification Bubble Style */
        .notif-bubble {
            position: absolute;
            top: 50%;
            right: 15px;
            transform: translateY(-50%);
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 50px;
            box-shadow: 0 0 10px rgba(34, 197, 94, 0.5);
        }

        /* Topbar Dropdown Custom Styles */
        .notif-dropdown-menu {
            width: 320px;
            border: none;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            border-radius: 12px;
            padding: 0;
            overflow: hidden;
        }
        .notif-header { background: #f8fafc; padding: 12px 15px; border-bottom: 1px solid #e2e8f0; }
        .notif-body { max-height: 300px; overflow-y: auto; }
        .notif-item { padding: 12px 15px; border-bottom: 1px solid #f1f5f9; transition: 0.2s; display: block; text-decoration: none; color: inherit; }
        .notif-item:hover { background-color: #f0fdf4; }

        .table-card { background: white; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; }
        .table thead th { background: #f1f5f9; padding: 15px 10px; border-bottom: 2px solid #e2e8f0; }
        .table tbody td { padding: 12px 10px; font-size: 0.9rem; border-bottom: 1px solid #f1f5f9; }
        
        .row-today { background-color: #f0fdf4 !important; }
        
        .pulse-icon {
            display: inline-block;
            width: 8px;
            height: 8px;
            background-color: #22c55e;
            border-radius: 50%;
            margin-right: 5px;
            animation: pulse-green 2s infinite;
        }

        @keyframes pulse-green {
            0% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7); }
            70% { box-shadow: 0 0 0 8px rgba(34, 197, 94, 0); }
            100% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
        }

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
        <a href="dashboard.php" class="nav-link active">
            <i class="fas fa-th-large"></i> Dashboard
            <?php if($notifCount > 0): ?>
                <span class="badge bg-success notif-bubble"><?= $notifCount ?></span>
            <?php endif; ?>
        </a>
        <a href="crime_files_create.php" class="nav-link"><i class="fas fa-folder-plus"></i> New File</a>
        <a href="create_user.php" class="nav-link"><i class="fas fa-user-shield"></i> Users</a>
        <a href="categories.php" class="nav-link"><i class="fas fa-list-ul"></i> Categories</a>
        <hr class="text-secondary opacity-25">
        <a href="profile.php" class="nav-link"><i class="fas fa-key"></i> Change Password</a>
        <a href="../auth/logout.php" class="nav-link text-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</nav>

<div id="content">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <button id="toggleBtn" class="btn btn-outline-dark d-lg-none"><i class="fas fa-bars"></i></button>
        <h4 class="fw-bold m-0 d-none d-sm-block">Crime File Records</h4>
        
        <div class="d-flex align-items-center gap-3">
            <div class="dropdown">
                <button class="btn btn-white bg-white border rounded-circle shadow-sm p-2 position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-bell text-muted"></i>
                    <?php if($notifCount > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem;">
                            <?= $notifCount ?>
                        </span>
                    <?php endif; ?>
                </button>
                <div class="dropdown-menu dropdown-menu-end notif-dropdown-menu">
                    <div class="notif-header d-flex justify-content-between align-items-center">
                        <span class="fw-bold small">Reminders Today</span>
                        <span class="badge bg-success-subtle text-success rounded-pill"><?= $notifCount ?></span>
                    </div>
                    <div class="notif-body">
                        <?php if($notifCount > 0): ?>
                            <?php while($row_n = mysqli_fetch_assoc($notifDetailsQuery)): ?>
                                <a href="crime_show.php?id=<?= $row_n['id'] ?>" class="notif-item">
                                    <div class="fw-bold small text-primary">Sub: <?= $row_n['subject_number'] ?></div>
                                    <div class="text-muted" style="font-size: 0.75rem;"><?= $row_n['crime'] ?></div>
                                </a>
                            <?php endwhile; ?>
                            <div class="p-2 text-center border-top">
                                <a href="?search=<?= $today ?>" class="small text-decoration-none fw-bold">View All Reminders</a>
                            </div>
                        <?php else: ?>
                            <div class="p-4 text-center text-muted small">No reminders for today.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="d-flex align-items-center bg-white p-2 rounded-pill shadow-sm px-3 border">
                <span class="small fw-semibold me-2"><?= auth()['username'] ?></span>
                <img src="https://ui-avatars.com/api/?name=<?= auth()['username'] ?>&background=0284c7&color=fff" width="30" class="rounded-circle">
            </div>
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
                        <?php while ($r = mysqli_fetch_assoc($q)): 
                            $isToday = ($r['remember_date'] == $today);
                        ?>
                        <tr class="<?= $isToday ? 'row-today' : '' ?>">
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
                                    <span class="<?= $isToday ? 'text-success' : 'text-danger' ?> small fw-bold">
                                        <?php if($isToday): ?><span class="pulse-icon"></span><?php endif; ?>
                                        <i class="fas fa-clock me-1"></i><?= $r['remember_date'] ?>
                                    </span>
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
    $('#toggleBtn').click(function() {
        $('#sidebar').toggleClass('active');
    });

    $(document).click(function(e) {
        if (!$(e.target).closest('#sidebar, #toggleBtn').length && $(window).width() <= 1024) {
            $('#sidebar').removeClass('active');
        }
    });
</script>

</body>
</html>