<?php
$conn = mysqli_connect("localhost", "root", "", "user_system", 3306);

if (!$conn) {
    die("Database Connection Failed: " . mysqli_connect_error());
}
session_start();
?>
