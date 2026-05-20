<?php
// includes/auth.php
session_start();

// Check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Ensure the user is logged in, otherwise redirect to login page
function require_login() {
    if (!is_logged_in()) {
        header('Location: index.php');
        exit();
    }
}

// Get the current user's role
function current_user_role() {
    return isset($_SESSION['system_role']) ? $_SESSION['system_role'] : null;
}

// Check if the current user has the required role(s)
// $roles can be a string or an array of allowed roles
function has_role($roles) {
    if (!is_logged_in()) return false;
    
    $current_role = current_user_role();
    if (is_array($roles)) {
        return in_array($current_role, $roles);
    }
    return $current_role === $roles;
}

// Ensure the user has the required role(s), otherwise deny access
function require_role($roles) {
    require_login();
    if (!has_role($roles)) {
        // You could redirect to a specific "Access Denied" page
        die("Access Denied: You do not have permission to view this page.");
    }
}
?>
