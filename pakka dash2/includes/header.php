<?php
// includes/header.php
require_once 'auth.php';
require_login();
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pakka Corporate Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="app-layout">
    <aside class="sidebar">
        <div class="sidebar-brand">
            <a href="dashboard.php" class="sidebar-brand-link">
                PAKKA
            </a>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="sidebar-link <?= $current_page == 'dashboard.php' ? 'active' : '' ?>">🏠 Home</a>
            <a href="tasks.php" class="sidebar-link <?= $current_page == 'tasks.php' ? 'active' : '' ?>">Tasks</a>
            <a href="designations.php" class="sidebar-link <?= $current_page == 'designations.php' ? 'active' : '' ?>">Create Designation</a>
            <a href="role_profiles.php" class="sidebar-link <?= $current_page == 'role_profiles.php' ? 'active' : '' ?>">All Role Profile</a>
            <a href="assessments.php" class="sidebar-link <?= $current_page == 'assessments.php' ? 'active' : '' ?>">Assessment List</a>
            <?php if ($_SESSION['system_role'] === 'Sewak'): ?>
                <a href="users.php" class="sidebar-link <?= $current_page == 'users.php' ? 'active' : '' ?>">Users</a>
            <?php endif; ?>
            <a href="logout.php" class="sidebar-link" style="margin-top: auto;">🚪 Logout</a>
        </nav>
        
        <div class="sidebar-user">
            <div style="font-weight: 700; font-size: 0.85rem; color: white;"><?= htmlspecialchars($_SESSION['user_name']) ?></div>
            <div class="user-role-badge"><?= htmlspecialchars($_SESSION['system_role']) ?></div>
        </div>
    </aside>
    
    <main class="main-content">
        <div class="container">
