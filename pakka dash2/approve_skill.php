<?php
// approve_skill.php
require_once 'includes/db.php';

// Secure the route: Only Admin (Sewak) role can moderate skill submissions
if (!isset($_SESSION['user_id']) || $_SESSION['system_role'] !== 'Sewak') {
    header("Location: dashboard.php");
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($id > 0 && ($action === 'approve' || $action === 'reject')) {
    $status = ($action === 'approve') ? 'Approved' : 'Rejected';
    
    $stmt = $pdo->prepare("UPDATE personal_skills SET status = ? WHERE id = ?");
    $stmt->execute([$status, $id]);
    
    header("Location: dashboard.php?msg=skill_" . ($action === 'approve' ? 'approved' : 'rejected'));
    exit();
}

header("Location: dashboard.php");
exit();
