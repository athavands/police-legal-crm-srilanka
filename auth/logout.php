<?php
session_start();
session_unset();   // Flush Auth cache
session_destroy();
header("Location: login.php");
