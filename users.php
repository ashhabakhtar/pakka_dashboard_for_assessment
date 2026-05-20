<?php
// users.php
require_once 'includes/db.php';
require_once 'includes/header.php';

// Enforce Sewak-only access
require_role('Sewak');

$system_role = $_SESSION['system_role'];

// Handle user CRUD submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_user') {
        $hashed_pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, system_role, designation_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['name'],
            $_POST['email'],
            $hashed_pass,
            $_POST['system_role'],
            $_POST['designation_id'] ?: null
        ]);
        header('Location: users.php?msg=added');
        exit();
    } elseif ($_POST['action'] === 'edit_user') {
        if (!empty($_POST['password'])) {
            $hashed_pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, password_hash = ?, system_role = ?, designation_id = ? WHERE id = ?");
            $stmt->execute([
                $_POST['name'],
                $_POST['email'],
                $hashed_pass,
                $_POST['system_role'],
                $_POST['designation_id'] ?: null,
                $_POST['user_id']
            ]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, system_role = ?, designation_id = ? WHERE id = ?");
            $stmt->execute([
                $_POST['name'],
                $_POST['email'],
                $_POST['system_role'],
                $_POST['designation_id'] ?: null,
                $_POST['user_id']
            ]);
        }
        header('Location: users.php?msg=updated');
        exit();
    } elseif ($_POST['action'] === 'delete_user') {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$_POST['user_id']]);
        header('Location: users.php?msg=deleted');
        exit();
    }
}

// Fetch all users with their designations
$stmt = $pdo->query("
    SELECT u.*, d.title as desig_title 
    FROM users u 
    LEFT JOIN designations d ON u.designation_id = d.id 
    ORDER BY u.id ASC
");
$users = $stmt->fetchAll();

// Fetch designations for dropdown
$stmt = $pdo->query("SELECT * FROM designations ORDER BY title ASC");
$designations = $stmt->fetchAll();
?>

<div class="page-header">
    <div>
        <h1 class="page-title">User Management</h1>
        <p style="margin: 0.25rem 0 0 0; color: var(--color-text-muted); font-size: 0.9rem;">Oversee application access, configure system roles (RBAC), and link designations.</p>
    </div>
    <button class="btn btn-primary" onclick="openModal('addUserModal')">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
        Register User
    </button>
</div>

<?php if (isset($_GET['msg'])): ?>
    <?php if ($_GET['msg'] === 'added'): ?>
        <div class="alert alert-success">New user has been registered successfully!</div>
    <?php elseif ($_GET['msg'] === 'updated'): ?>
        <div class="alert alert-success">User configurations updated and synchronized!</div>
    <?php elseif ($_GET['msg'] === 'deleted'): ?>
        <div class="alert alert-success">User has been successfully removed from the system.</div>
    <?php endif; ?>
<?php endif; ?>

<!-- Premium Search Box -->
<div class="card" style="margin-bottom: 2rem; padding: 1.25rem;">
    <div class="search-input-wrapper" style="max-width: 400px;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
        <input type="text" id="userSearch" class="form-control" placeholder="Search users by name, email or designation..." onkeyup="filterUsers()">
    </div>
</div>

<div class="table-wrapper">
    <table id="usersTable">
        <thead>
            <tr>
                <th style="width: 80px;">ID</th>
                <th>Full Name</th>
                <th>Email Address</th>
                <th>System Role (RBAC)</th>
                <th>Designation</th>
                <th style="text-align: right; padding-right: 2rem;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
            <tr class="user-row">
                <td style="font-weight: 700; color: var(--color-text-muted);">#<?= $u['id'] ?></td>
                <td><strong style="font-size: 0.95rem; color: var(--color-secondary);"><?= htmlspecialchars($u['name']) ?></strong></td>
                <td style="color: var(--color-text-muted); font-weight: 500;"><?= htmlspecialchars($u['email']) ?></td>
                <td><span class="badge badge-gray"><?= htmlspecialchars($u['system_role']) ?></span></td>
                <td style="font-weight: 600; color: var(--color-text);"><?= htmlspecialchars($u['desig_title'] ?? 'Not Assigned') ?></td>
                <td style="text-align: right; padding-right: 2rem;">
                    <div style="display: inline-flex; gap: 0.5rem; justify-content: flex-end;">
                        <button class="btn btn-outline" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;" 
                                onclick="editUser(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['name'])) ?>', '<?= htmlspecialchars(addslashes($u['email'])) ?>', '<?= $u['system_role'] ?>', '<?= $u['designation_id'] ?>')">
                            Edit
                        </button>
                        <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to completely delete this user? This cannot be undone.');">
                                <input type="hidden" name="action" value="delete_user">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <button type="submit" class="btn btn-danger" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;">Delete</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Add User Modal -->
<div class="modal-overlay" id="addUserModal">
    <div class="modal">
        <div class="modal-header">
            <h3>Register New System User</h3>
            <button class="modal-close" onclick="closeModal('addUserModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add_user">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. John Doe" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="e.g. johndoe@pakka.com" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Security Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Create strong access credential..." required>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;">
                    <div class="form-group">
                        <label class="form-label">RBAC System Role</label>
                        <select name="system_role" class="form-control" style="font-weight: 500;" required>
                            <option value="Utpadak" selected>Utpadak (Team Member)</option>
                            <option value="Sangrakshak">Sangrakshak (Team Lead)</option>
                            <option value="Sewak">Sewak (IT Head / Admin)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Linked Designation</label>
                        <select name="designation_id" class="form-control" style="font-weight: 500;">
                            <option value="">-- No Designation --</option>
                            <?php foreach ($designations as $d): ?>
                                <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addUserModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Register User</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal-overlay" id="editUserModal">
    <div class="modal">
        <div class="modal-header">
            <h3>Update User Configurations</h3>
            <button class="modal-close" onclick="closeModal('editUserModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="edit_user">
            <input type="hidden" name="user_id" id="edit_user_id">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" id="edit_user_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" id="edit_user_email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Change Password (leave blank to keep current)</label>
                    <input type="password" name="password" class="form-control" placeholder="Enter new password if modifying...">
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;">
                    <div class="form-group">
                        <label class="form-label">RBAC System Role</label>
                        <select name="system_role" id="edit_user_role" class="form-control" style="font-weight: 500;" required>
                            <option value="Utpadak">Utpadak (Team Member)</option>
                            <option value="Sangrakshak">Sangrakshak (Team Lead)</option>
                            <option value="Sewak">Sewak (IT Head / Admin)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Linked Designation</label>
                        <select name="designation_id" id="edit_user_desig" class="form-control" style="font-weight: 500;">
                            <option value="">-- No Designation --</option>
                            <?php foreach ($designations as $d): ?>
                                <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('editUserModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Details</button>
            </div>
        </form>
    </div>
</div>

<script>
function editUser(id, name, email, role, desigId) {
    document.getElementById('edit_user_id').value = id;
    document.getElementById('edit_user_name').value = name;
    document.getElementById('edit_user_email').value = email;
    document.getElementById('edit_user_role').value = role;
    document.getElementById('edit_user_desig').value = desigId || '';
    openModal('editUserModal');
}

function filterUsers() {
    const searchVal = document.getElementById('userSearch').value.toLowerCase();
    const rows = document.querySelectorAll('.user-row');
    
    rows.forEach(row => {
        const nameText = row.querySelector('strong').textContent.toLowerCase();
        const emailText = row.children[2].textContent.toLowerCase();
        const desigText = row.children[4].textContent.toLowerCase();
        
        if (nameText.includes(searchVal) || emailText.includes(searchVal) || desigText.includes(searchVal)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>
