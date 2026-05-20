<?php
// assessments.php
require_once 'includes/db.php';
require_once 'includes/header.php';

$system_role = $_SESSION['system_role'];
$user_id = $_SESSION['user_id'];

// Default FY (matching live options)
$current_fy = $_GET['fy'] ?? '2026-27';
$current_fy = str_replace('FY ', '', $current_fy); // Normalize parameter

$fys = ['2024-25', '2025-26', '2026-27'];
if (!in_array($current_fy, $fys)) {
    $current_fy = '2026-27';
}

// Convert to database string format ('FY 2026-27', etc.)
$db_fy = 'FY ' . $current_fy;

// Handle action (for backward compatibility / direct operations)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save_assessment' && ($system_role === 'Sewak' || $system_role === 'Sangrakshak')) {
        $desig_id = (int)$_POST['designation_id'];
        $status = $_POST['status'] ?? 'Completed';
        
        $stmt = $pdo->prepare("SELECT id FROM assessments WHERE designation_id = ? AND fiscal_year = ?");
        $stmt->execute([$desig_id, $db_fy]);
        $exists = $stmt->fetch();
        
        if ($exists) {
            $stmt = $pdo->prepare("UPDATE assessments SET status = ?, assessor_id = ? WHERE id = ?");
            $stmt->execute([$status, $user_id, $exists['id']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO assessments (designation_id, fiscal_year, status, assessor_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$desig_id, $db_fy, $status, $user_id]);
        }
        
        header("Location: assessments.php?fy=" . urlencode($current_fy) . "&msg=saved");
        exit();
    }
}

// Fetch all designations in alphabetical order (matching live designation table) and their assessment status for the selected FY
$stmt = $pdo->prepare("
    SELECT d.id as desig_id, d.title, a.status, a.id as assessment_id 
    FROM designations d 
    LEFT JOIN assessments a ON d.id = a.designation_id AND a.fiscal_year = ?
    ORDER BY d.title ASC
");
$stmt->execute([$db_fy]);
$assessments_list = $stmt->fetchAll();
?>

<style>
/* ==========================================================================
   ✨ High-Fidelity Replication of Live Assessment List (UAT styling)
   ========================================================================== */

.designation-container {
    background: #ffffff;
    padding: 25px 30px;
    border-radius: 12px;
    border: 1px solid var(--color-border);
    margin-top: 1rem;
    margin-bottom: 2rem;
    box-shadow: var(--shadow-sm);
}

.designation-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}

.search-box {
    padding: 8px 16px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    width: 250px;
    font-size: 0.875rem;
    font-family: inherit;
    font-weight: 500;
    transition: var(--transition-smooth);
    background-color: #f8fafc;
}

.search-box:focus {
    border-color: #A3243C;
    background-color: #ffffff;
    box-shadow: 0 0 0 3px rgba(163, 36, 60, 0.15);
    outline: none;
}

/* FINANCIAL YEAR UI */
.fy-filter-bar {
    display: flex;
    align-items: center;
    gap: 12px;
    background: #f5e3c3;
    border: 1px solid #e0c89a;
    border-radius: 8px;
    padding: 10px 18px;
    margin-bottom: 18px;
}

.fy-filter-label {
    font-weight: 600;
    color: #5E6337;
    font-size: 14px;
    white-space: nowrap;
}

.fy-filter-select {
    padding: 7px 12px;
    border: 1px solid #A3243C;
    border-radius: 6px;
    font-size: 14px;
    background: #ffffff;
    color: #333;
    cursor: pointer;
    font-weight: 600;
    outline: none;
}

.fy-current-badge {
    background: #A3243C;
    color: #ffffff;
    font-size: 11px;
    padding: 3px 10px;
    border-radius: 12px;
    font-weight: 600;
    letter-spacing: 0.3px;
}

/* TABLE CLEAN STYLE */
.designation-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 10px; /* vertical gap between rows */
    margin-top: 15px;
}

.designation-table th {
    background: #A3243C;
    color: #ffffff;
    padding: 12px;
    font-family: 'Outfit', sans-serif;
    font-weight: 700;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.05em;
    border-right: 1px solid rgba(255, 255, 255, 0.15);
}

.designation-table th:last-child {
    border-right: none;
}

/* EACH ROW CARD STYLE */
.designation-table tbody tr {
    background: #f9f9f9;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
    transition: 0.2s ease;
}

/* HOVER EFFECT */
.designation-table tbody tr:hover {
    background: #f1f1f1;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.06);
}

/* CELLS STYLE */
.designation-table td {
    padding: 14px 12px;
    border-top: 1px solid #eee;
    border-bottom: 1px solid #eee;
    vertical-align: middle;
    border-right: 1px solid #e0e0e0;
}

.designation-table td:last-child {
    border-right: none;
}

/* LEFT ROUND */
.designation-table td:first-child {
    border-left: 1px solid #eee;
    border-radius: 6px 0 0 6px;
}

/* RIGHT ROUND */
.designation-table td:last-child {
    border-right: 1px solid #eee;
    border-radius: 0 6px 6px 0;
}

/* ALIGNMENT AND WIDTHS */
.designation-table th:nth-child(1),
.designation-table td:nth-child(1) {
    width: 80px;
    text-align: center;
}

.designation-table th:nth-child(3),
.designation-table td:nth-child(3) {
    width: 250px;
    text-align: center;
}

.designation-table th {
    text-align: center;
}

.designation-table td:nth-child(2) {
    text-align: left;
    padding-left: 20px;
}

.designation-table td:nth-child(4) {
    text-align: center;
}

/* BUTTONS AND BADGES */
.action-buttons {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
}

.btn-role {
    display: inline-block;
    background: #5E6337;
    color: #ffffff;
    padding: 6px 14px;
    border-radius: 5px;
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
    transition: background 0.2s ease;
}

.btn-role:hover {
    background: #4a4f2b;
}

.status-done {
    display: inline-block;
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
    padding: 3px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.status-pending {
    display: inline-block;
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffeeba;
    padding: 3px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.fy-not-reviewed {
    color: #aaa;
    font-size: 13px;
    font-style: italic;
    font-weight: 500;
}
</style>

<div class="designation-container">
    <div class="designation-header">
        <div>
            <h2 style="margin: 0; font-family: 'Outfit', sans-serif; font-weight: 700; color: var(--color-secondary); font-size: 1.5rem;">Role Profile Assessments</h2>
            <p style="margin: 0.25rem 0 0 0; color: var(--color-text-muted); font-size: 0.875rem;">Review performance indexes and operational alignment against role profiles.</p>
        </div>
        <input type="text" id="searchBox" placeholder="Search designation..." class="search-box">
    </div>

    <!-- FY FILTER BAR -->
    <div class="fy-filter-bar">
        <label class="fy-filter-label">Financial Year</label>
        <select id="fySelect" class="fy-filter-select" onchange="window.location='assessments.php?fy='+this.value">
            <option value="2024-25" <?= $current_fy === '2024-25' ? 'selected' : '' ?>>FY 2024-25</option>
            <option value="2025-26" <?= $current_fy === '2025-26' ? 'selected' : '' ?>>FY 2025-26</option>
            <option value="2026-27" <?= $current_fy === '2026-27' ? 'selected' : '' ?>>FY 2026-27</option>
        </select>
        <?php if ($current_fy === '2026-27'): ?>
            <span class="fy-current-badge">Current Year</span>
        <?php endif; ?>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'saved'): ?>
        <div class="alert alert-success" style="margin-bottom: 1.5rem; padding: 0.75rem 1.25rem; background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 8px; font-weight: 600; font-size: 0.9rem;">
            Assessment processed and synced successfully!
        </div>
    <?php endif; ?>

    <table class="designation-table" id="designationTable">
        <thead>
            <tr>
                <th>SN</th>
                <th>Designation</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($assessments_list as $index => $a): 
                $status = $a['status'] ?? 'Not Started';
                $is_current_year = ($current_fy === '2026-27');
                $is_completed = ($status === 'Completed');
                $is_in_progress = ($status === 'In Progress');
                $has_write_access = ($system_role === 'Sewak' || $system_role === 'Sangrakshak');
            ?>
            <tr>
                <td><?= $index + 1 ?></td>
                <td>
                    <strong style="font-size: 0.95rem; color: var(--color-secondary);">
                        <?= htmlspecialchars($a['title']) ?>
                    </strong>
                </td>
                <td style="text-align:center;">
                    <?php if ($status === 'Completed'): ?>
                        <span class="status-done">Completed</span>
                    <?php elseif ($status === 'In Progress'): ?>
                        <span class="status-pending" style="background: #fff3cd; color: #856404; border-color: #ffeeba;">In Progress</span>
                    <?php else: ?>
                        <span class="status-pending">Not Started</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="action-buttons">
                        <?php if ($is_current_year): ?>
                            <?php if ($has_write_access): ?>
                                <a href="role_profile_assessment.php?designation_id=<?= $a['desig_id'] ?>&financial_year=<?= urlencode($current_fy) ?>" class="btn-role">
                                    <?= $status === 'Not Started' ? 'Start Assessment' : 'Re-Evaluate' ?>
                                </a>
                            <?php else: ?>
                                <?php if ($is_completed): ?>
                                    <a href="role_profile_assessment.php?designation_id=<?= $a['desig_id'] ?>&financial_year=<?= urlencode($current_fy) ?>&view=1" class="btn-role">View</a>
                                <?php else: ?>
                                    <span class="fy-not-reviewed">Not Reviewed</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php else: /* Past years */ ?>
                            <?php if ($is_completed): ?>
                                <a href="role_profile_assessment.php?designation_id=<?= $a['desig_id'] ?>&financial_year=<?= urlencode($current_fy) ?>&view=1" class="btn-role">View</a>
                            <?php else: ?>
                                <span class="fy-not-reviewed">Not Reviewed</span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
document.getElementById("searchBox").addEventListener("keyup", function(){
    let value = this.value.toLowerCase();
    document.querySelectorAll("#designationTable tbody tr").forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(value) ? "" : "none";
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
