<?php
function auth() {
    return $_SESSION['auth'] ?? null;
}

function isAdmin() {
    return auth() && auth()['role'] === 'super_admin';
}
