<?php
// designations.php
require_once 'includes/db.php';
require_once 'includes/header.php';

$system_role = $_SESSION['system_role'];

// Handle form submission (Add/Edit Designation)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $system_role === 'Sewak' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_designation') {
        $stmt = $pdo->prepare("INSERT INTO designations (title, description) VALUES (?, ?)");
        $stmt->execute([$_POST['title'], $_POST['description']]);
        header('Location: designations.php');
        exit();
    } elseif ($_POST['action'] === 'edit_designation') {
        $stmt = $pdo->prepare("UPDATE designations SET title = ?, description = ? WHERE id = ?");
        $stmt->execute([$_POST['title'], $_POST['description'], $_POST['designation_id']]);
        header('Location: designations.php');
        exit();
    }
}

// Fetch all designations
$stmt = $pdo->query("SELECT * FROM designations ORDER BY id ASC");
$designations = $stmt->fetchAll();
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Designations</h1>
        <p style="margin: 0.25rem 0 0 0; color: var(--color-text-muted); font-size: 0.9rem;">Organize corporate roles, link role profiles, and initiate review cycles.</p>
    </div>
    <?php if ($system_role === 'Sewak'): ?>
        <button class="btn btn-primary" onclick="openModal('addDesignationModal')">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            Add Designation
        </button>
    <?php endif; ?>
</div>

<!-- Premium Search Box -->
<div class="card" style="margin-bottom: 2rem; padding: 1.25rem;">
    <div class="search-input-wrapper" style="max-width: 400px;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
        <input type="text" id="desigSearch" class="form-control" placeholder="Search designations by title or description..." onkeyup="filterDesignations()">
    </div>
</div>

<div class="table-wrapper">
    <table id="designationsTable">
        <thead>
            <tr>
                <th style="width: 80px;">SN</th>
                <th>Designation Title</th>
                <th>Description</th>
                <th style="text-align: right; padding-right: 2rem;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($designations as $index => $d): ?>
            <tr class="desig-row">
                <td style="font-weight: 700; color: var(--color-text-muted);"><?= $index + 1 ?></td>
                <td><strong style="font-size: 0.95rem; color: var(--color-secondary);"><?= htmlspecialchars($d['title']) ?></strong></td>
                <td style="color: var(--color-text-muted); font-weight: 500;"><?= htmlspecialchars(substr($d['description'], 0, 80)) ?><?= strlen($d['description']) > 80 ? '...' : '' ?></td>
                <td style="text-align: right; padding-right: 2rem;">
                    <div style="display: inline-flex; gap: 0.5rem; justify-content: flex-end;">
                        <?php if ($system_role === 'Sewak'): ?>
                            <button class="btn btn-outline" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;" onclick="editDesignation(<?= $d['id'] ?>, '<?= htmlspecialchars(addslashes($d['title'])) ?>', '<?= htmlspecialchars(addslashes($d['description'])) ?>')">Edit Title</button>
                            <a href="role_profiles.php?id=<?= $d['id'] ?>" class="btn btn-outline" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;">Role Profile</a>
                        <?php else: ?>
                            <a href="role_profiles.php?id=<?= $d['id'] ?>" class="btn btn-outline" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;">View Profile</a>
                        <?php endif; ?>
                        
                        <?php if ($system_role !== 'Utpadak'): ?>
                            <a href="assessments.php?id=<?= $d['id'] ?>" class="btn btn-outline" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;">Review (FY)</a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if ($system_role === 'Sewak'): ?>
<!-- Add Designation Modal -->
<div class="modal-overlay" id="addDesignationModal">
    <div class="modal">
        <div class="modal-header">
            <h3>Add New Designation</h3>
            <button class="modal-close" onclick="closeModal('addDesignationModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add_designation">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Designation Title</label>
                    <input type="text" name="title" class="form-control" placeholder="e.g. Brand & Marketing Head" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" placeholder="Describe the core purpose of this role..." rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addDesignationModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Designation</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Designation Modal -->
<div class="modal-overlay" id="editDesignationModal">
    <div class="modal">
        <div class="modal-header">
            <h3>Edit Designation</h3>
            <button class="modal-close" onclick="closeModal('editDesignationModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="edit_designation">
            <input type="hidden" name="designation_id" id="edit_id">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Designation Title</label>
                    <input type="text" name="title" id="edit_title" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('editDesignationModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Update</button>
            </div>
        </form>
    </div>
</div>

<script>
function editDesignation(id, title, description) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_title').value = title;
    document.getElementById('edit_description').value = description;
    openModal('editDesignationModal');
}

function filterDesignations() {
    const searchVal = document.getElementById('desigSearch').value.toLowerCase();
    const rows = document.querySelectorAll('.desig-row');
    
    rows.forEach(row => {
        const titleText = row.querySelector('strong').textContent.toLowerCase();
        const descText = row.children[2].textContent.toLowerCase();
        
        if (titleText.includes(searchVal) || descText.includes(searchVal)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}
</script>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
