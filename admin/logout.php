<?php
/**
 * Admin Logout
 */
require_once __DIR__ . '/../config.php';
initApp();

if (isLoggedIn()) {
    logout();
}

header('Location: ' . adminUrl('login.php'));
exit;
