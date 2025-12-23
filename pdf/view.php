<?php
include "../config/db.php";
include "../config/auth.php";

$id = (int)$_GET['id'];
$user = auth()['id'];

$pdf = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT * FROM pdf_stuff WHERE id=$id AND deleted_at IS NULL
"));

mysqli_query($conn,"
INSERT IGNORE INTO seen_info (pdf_id, user_id)
VALUES ($id, $user)
");

header("Content-Type: application/pdf");
readfile("../uploads/pdfs/".$pdf['file_path']);
