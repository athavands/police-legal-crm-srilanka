<?php
include "../config/db.php";
include "../config/auth.php";

if (!isAdmin()) {
    header("Location: ../auth/login.php");
    exit;
}

/* -------------------- SORTING SETUP -------------------- */
$allowedSort = [
    'subject_number',
    'division',
    'police_station',
    'crime',
    'in_date',
    'court_number',
    'gcr_number',
    'in_word_no_date',
    'division_station_out_word_date',
    'remember_date',
    'dir_legal_out_word_date',
    'dir_legal_subject_number'
];

$sort = $_GET['sort'] ?? 'in_date';
$order = $_GET['order'] ?? 'DESC';

if (!in_array($sort, $allowedSort)) {
    $sort = 'in_date';
}

$order = ($order === 'ASC') ? 'ASC' : 'DESC';

/* -------------------- YEAR LIST -------------------- */
$yearsRes = mysqli_query($conn, "
    SELECT DISTINCT YEAR(in_date) AS year
    FROM crime_files
    WHERE deleted_at IS NULL
    ORDER BY year DESC
");

/* -------------------- FILTER LOGIC -------------------- */
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
        court_number LIKE '%$s%' OR
        gcr_number LIKE '%$s%' OR
        in_word_no_date LIKE '%$s%' OR
        division_station_out_word_date LIKE '%$s%' OR
        dir_legal_out_word_date LIKE '%$s%' OR
        dir_legal_subject_number LIKE '%$s%'
    )";
}

/* -------------------- DATA QUERY -------------------- */
$q = mysqli_query($conn, "
    SELECT *
    FROM crime_files
    WHERE $where
    ORDER BY $sort $order
");

function sortLink($column, $label) {
    $currentSort = $_GET['sort'] ?? '';
    $currentOrder = $_GET['order'] ?? 'DESC';

    $newOrder = ($currentSort === $column && $currentOrder === 'ASC') ? 'DESC' : 'ASC';
    $arrow = '';

    if ($currentSort === $column) {
        $arrow = ($currentOrder === 'ASC') ? ' ▲' : ' ▼';
    }

    $query = $_GET;
    $query['sort'] = $column;
    $query['order'] = $newOrder;

    return '<a href="?' . http_build_query($query) . '" class="text-white text-decoration-none">'
        . $label . $arrow . '</a>';
}

include "../assets/header.php";
?>

<div class="container-fluid">
<div class="row">

<!-- SIDEBAR -->
<div class="col-md-3 col-lg-2 bg-dark text-white min-vh-100 p-3">
    <h5 class="mb-4">Legal Division</h5>
    <ul class="nav nav-pills flex-column gap-2">
        <li class="nav-item"><span class="nav-link active">Dashboard</span></li>
        <li class="nav-item"><a class="nav-link text-white" href="crime_files_create.php">Create New</a></li>
        <li class="nav-item"><a class="nav-link text-white" href="create_user.php">Create User</a></li>
        <li class="nav-item"><a class="nav-link text-white" href="categories.php">Categories</a></li>
        <li class="nav-item"><a class="nav-link text-white" href="profile.php">Change Credentials</a></li>
        <li class="nav-item mt-4"><a class="nav-link text-danger" href="../auth/logout.php">Logout</a></li>
    </ul>
</div>

<!-- MAIN -->
<div class="col-md-9 col-lg-10 p-4">

<div class="d-flex justify-content-between mb-3">
    <h4>Crime Files</h4>
    <span>Welcome, <b><?= auth()['username'] ?></b></span>
</div>

<form class="row g-2 mb-3" method="GET">
    <div class="col-md-4">
        <input name="search" value="<?= $_GET['search'] ?? '' ?>" class="form-control" placeholder="Search all fields">
    </div>

    <div class="col-md-2">
        <select name="year" class="form-select">
            <option value="">All Years</option>
            <?php while ($y = mysqli_fetch_assoc($yearsRes)): ?>
                <option value="<?= $y['year'] ?>" <?= (($_GET['year'] ?? '') == $y['year']) ? 'selected' : '' ?>>
                    <?= $y['year'] ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>

    <div class="col-md-2">
        <button class="btn btn-primary w-100">Filter</button>
    </div>
</form>

<div class="table-responsive">
<table class="table table-bordered table-hover">
<thead class="table-dark">
<tr>
    <th><?= sortLink('subject_number', 'Subject No') ?></th>
    <th><?= sortLink('division', 'Division') ?></th>
    <th><?= sortLink('police_station', 'Station') ?></th>
    <th><?= sortLink('crime', 'Crime') ?></th>
    <th><?= sortLink('in_date', 'In Date') ?></th>
    <th><?= sortLink('court_number', 'Court No') ?></th>
    <th><?= sortLink('gcr_number', 'GCR No') ?></th>
    <th><?= sortLink('in_word_no_date', 'IN Word No / Date') ?></th>
    <th><?= sortLink('division_station_out_word_date', 'Division OW / Date') ?></th>
    <th><?= sortLink('remember_date', 'Remember Date') ?></th>
    <th><?= sortLink('dir_legal_out_word_date', 'Dir Legal OW / Date') ?></th>
    <th><?= sortLink('dir_legal_subject_number', 'Dir Legal Subject') ?></th>
    <th>View</th>
</tr>
</thead>

<tbody>
<?php while ($r = mysqli_fetch_assoc($q)): ?>
<tr>
    <td><?= $r['subject_number'] ?></td>
    <td><?= $r['division'] ?></td>
    <td><?= $r['police_station'] ?></td>
    <td><?= $r['crime'] ?></td>
    <td><?= $r['in_date'] ?></td>
    <td><?= $r['court_number'] ?></td>
    <td><?= $r['gcr_number'] ?></td>
    <td><?= $r['in_word_no_date'] ?></td>
    <td><?= $r['division_station_out_word_date'] ?></td>
    <td><?= $r['remember_date'] ?></td>
    <td><?= $r['dir_legal_out_word_date'] ?></td>
    <td><?= $r['dir_legal_subject_number'] ?></td>
    <td>
        <a href="crime_show.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary">👁</a>
    </td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>

</div>
</div>
</div>

</body>
</html>
