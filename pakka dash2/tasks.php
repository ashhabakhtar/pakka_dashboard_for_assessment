<?php
// tasks.php
require_once 'includes/db.php';
require_once 'includes/header.php';

$user_id = $_SESSION['user_id'];
$system_role = $_SESSION['system_role'];

// Handle form submission (Add/Edit Task)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_task' && $system_role === 'Sewak') {
        $stmt = $pdo->prepare("INSERT INTO tasks (title, description, status, priority, due_date, assigned_to, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['title'],
            $_POST['description'],
            $_POST['status'] ?? 'Not Started',
            $_POST['priority'] ?? 'Medium',
            $_POST['due_date'] ?: null,
            $_POST['assigned_to'] ?: null,
            $user_id
        ]);
        header('Location: tasks.php');
        exit();
    } elseif ($_POST['action'] === 'update_status' && ($system_role === 'Sewak' || $system_role === 'Sangrakshak')) {
        $stmt = $pdo->prepare("UPDATE tasks SET status = ? WHERE id = ?");
        $stmt->execute([$_POST['status'], $_POST['task_id']]);
        header('Location: tasks.php');
        exit();
    }
}

// Fetch users for dropdown (only Sewak needs this to assign tasks)
$users = [];
if ($system_role === 'Sewak') {
    $stmt = $pdo->query("SELECT id, name, system_role FROM users");
    $users = $stmt->fetchAll();
}

// Fetch tasks based on role
if ($system_role === 'Sewak') {
    $stmt = $pdo->query("SELECT t.*, u.name as assigned_name FROM tasks t LEFT JOIN users u ON t.assigned_to = u.id ORDER BY t.created_at DESC");
} elseif ($system_role === 'Sangrakshak') {
    $stmt = $pdo->prepare("SELECT t.*, u.name as assigned_name FROM tasks t LEFT JOIN users u ON t.assigned_to = u.id WHERE t.assigned_to = ? OR t.created_by = ? ORDER BY t.created_at DESC");
    $stmt->execute([$user_id, $user_id]);
} else {
    $stmt = $pdo->prepare("SELECT t.*, u.name as assigned_name FROM tasks t LEFT JOIN users u ON t.assigned_to = u.id WHERE t.assigned_to = ? ORDER BY t.created_at DESC");
    $stmt->execute([$user_id]);
}
$tasks = $stmt->fetchAll();

function getStatusBadge($status) {
    if ($status === 'Completed') return '<span class="badge badge-green">Completed</span>';
    if ($status === 'In Progress') return '<span class="badge badge-yellow">In Progress</span>';
    return '<span class="badge badge-gray">Not Started</span>';
}

function getPriorityBadgeClass($priority) {
    if ($priority === 'High') return 'color: var(--color-danger); font-weight: 700;';
    if ($priority === 'Medium') return 'color: var(--color-warning); font-weight: 700;';
    return 'color: var(--color-success); font-weight: 700;';
}
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Tasks Management</h1>
        <p style="margin: 0.25rem 0 0 0; color: var(--color-text-muted); font-size: 0.9rem;">Track, organize, and update current objectives.</p>
    </div>
    <?php if ($system_role === 'Sewak'): ?>
        <button class="btn btn-primary" onclick="openModal('addTaskModal')">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            Register Task
        </button>
    <?php endif; ?>
</div>

<!-- Instant Premium Search & Filters -->
<div class="card" style="margin-bottom: 2rem; padding: 1.25rem;">
    <div style="display: flex; gap: 1.5rem; justify-content: space-between; align-items: center; flex-wrap: wrap;">
        
        <!-- Search Input -->
        <div class="search-input-wrapper" style="max-width: 400px; flex-grow: 1;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input type="text" id="taskSearch" class="form-control" placeholder="Search tasks by title, description or assignee..." onkeyup="filterTasksTable()">
        </div>
        
        <!-- Quick Status Filter Tabs -->
        <div style="display: flex; gap: 0.5rem; background-color: #f1f5f9; padding: 0.25rem; border-radius: 8px;">
            <button class="btn btn-outline" style="border: none; padding: 0.375rem 0.85rem; font-size: 0.8rem; background-color: white; font-weight: 700; box-shadow: var(--shadow-sm);" onclick="filterByStatus('All', this)">All</button>
            <button class="btn btn-outline" style="border: none; padding: 0.375rem 0.85rem; font-size: 0.8rem; font-weight: 600;" onclick="filterByStatus('Not Started', this)">Not Started</button>
            <button class="btn btn-outline" style="border: none; padding: 0.375rem 0.85rem; font-size: 0.8rem; font-weight: 600;" onclick="filterByStatus('In Progress', this)">In Progress</button>
            <button class="btn btn-outline" style="border: none; padding: 0.375rem 0.85rem; font-size: 0.8rem; font-weight: 600;" onclick="filterByStatus('Completed', this)">Completed</button>
        </div>
    </div>
</div>

<div class="table-wrapper">
    <table id="tasksTable">
        <thead>
            <tr>
                <th>ID</th>
                <th>Task Details</th>
                <th>Assigned To</th>
                <th>Due Date</th>
                <th>Priority</th>
                <th>Status</th>
                <?php if ($system_role !== 'Utpadak'): ?>
                    <th style="text-align: right; padding-right: 2rem;">Actions</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($tasks)): ?>
                <tr class="no-tasks-row"><td colspan="7" style="text-align: center; padding: 3rem; color: var(--color-text-muted);">No tasks found.</td></tr>
            <?php endif; ?>
            <?php foreach ($tasks as $t): ?>
            <tr class="task-row" data-status="<?= htmlspecialchars($t['status']) ?>">
                <td><span style="font-weight: 700; color: var(--color-text-muted);">#<?= $t['id'] ?></span></td>
                <td>
                    <div style="font-size: 0.95rem; font-weight: 700; color: var(--color-secondary);"><?= htmlspecialchars($t['title']) ?></div>
                    <?php if (!empty($t['description'])): ?>
                        <div style="font-size: 0.8rem; color: var(--color-text-muted); margin-top: 0.25rem; font-weight: 500;">
                            <?= htmlspecialchars($t['description']) ?>
                        </div>
                    <?php endif; ?>
                </td>
                <td style="font-weight: 600; color: var(--color-text);"><?= htmlspecialchars($t['assigned_name'] ?? 'Unassigned') ?></td>
                <td style="font-weight: 500; color: var(--color-text-muted);"><?= $t['due_date'] ? date('M d, Y', strtotime($t['due_date'])) : '-' ?></td>
                <td><span style="<?= getPriorityBadgeClass($t['priority']) ?>"><?= htmlspecialchars($t['priority']) ?></span></td>
                <td><?= getStatusBadge($t['status']) ?></td>
                
                <?php if ($system_role !== 'Utpadak'): ?>
                <td style="text-align: right; padding-right: 2rem;">
                    <!-- Status Update Form inline with high-end selects -->
                    <form method="POST" style="display: inline-flex; gap: 0.5rem; align-items: center; justify-content: flex-end;">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="task_id" value="<?= $t['id'] ?>">
                        <select name="status" class="form-control" style="padding: 0.35rem 0.75rem; font-size: 0.8rem; width: auto; font-weight: 600;" onchange="this.form.submit()">
                            <option value="Not Started" <?= $t['status'] === 'Not Started' ? 'selected' : '' ?>>Not Started</option>
                            <option value="In Progress" <?= $t['status'] === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                            <option value="Completed" <?= $t['status'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
                        </select>
                    </form>
                </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if ($system_role === 'Sewak'): ?>
<!-- Add Task Modal -->
<div class="modal-overlay" id="addTaskModal">
    <div class="modal">
        <div class="modal-header">
            <h3>Register New Task</h3>
            <button class="modal-close" onclick="closeModal('addTaskModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add_task">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Task Title</label>
                    <input type="text" name="title" class="form-control" placeholder="Enter objective title..." required>
                </div>
                <div class="form-group">
                    <label class="form-label">Detailed Description</label>
                    <textarea name="description" class="form-control" placeholder="Provide extra task context..." rows="3"></textarea>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;">
                    <div class="form-group">
                        <label class="form-label">Assign To</label>
                        <select name="assigned_to" class="form-control" style="font-weight: 500;">
                            <option value="">-- Select Assignee --</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?> (<?= $u['system_role'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Due Date</label>
                        <input type="date" name="due_date" class="form-control">
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;">
                    <div class="form-group">
                        <label class="form-label">Priority</label>
                        <select name="priority" class="form-control" style="font-weight: 500;">
                            <option value="Low">Low</option>
                            <option value="Medium" selected>Medium</option>
                            <option value="High">High</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addTaskModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Task</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
let currentStatusFilter = 'All';

function filterTasksTable() {
    const searchVal = document.getElementById('taskSearch').value.toLowerCase();
    const rows = document.querySelectorAll('.task-row');
    
    rows.forEach(row => {
        const titleText = row.querySelector('strong, div').textContent.toLowerCase();
        const descText = row.querySelector('div[style*="font-size: 0.8rem"]') ? row.querySelector('div[style*="font-size: 0.8rem"]').textContent.toLowerCase() : '';
        const assigneeText = row.children[2].textContent.toLowerCase();
        const statusVal = row.getAttribute('data-status');
        
        const matchesSearch = titleText.includes(searchVal) || descText.includes(searchVal) || assigneeText.includes(searchVal);
        const matchesStatus = currentStatusFilter === 'All' || statusVal === currentStatusFilter;
        
        if (matchesSearch && matchesStatus) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function filterByStatus(status, buttonEl) {
    // Update active tab styles
    const buttons = buttonEl.parentNode.querySelectorAll('button');
    buttons.forEach(btn => {
        btn.style.backgroundColor = 'transparent';
        btn.style.fontWeight = '600';
        btn.style.boxShadow = 'none';
    });
    
    buttonEl.style.backgroundColor = 'white';
    buttonEl.style.fontWeight = '700';
    buttonEl.style.boxShadow = 'var(--shadow-sm)';
    
    currentStatusFilter = status;
    filterTasksTable();
}
</script>

<?php require_once 'includes/footer.php'; ?>
