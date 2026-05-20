<?php
// role_profiles.php
require_once 'includes/db.php';
require_once 'includes/header.php';

$system_role = $_SESSION['system_role'];
$user_id = $_SESSION['user_id'];

// Get designation_id if specified (detailed view/edit)
$designation_id = isset($_GET['designation_id']) ? (int)$_GET['designation_id'] : 0;
$view_mode = isset($_GET['view']) && $_GET['view'] == 1;

// Handle Form Submission (Save Profile Grid)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_profile' && $system_role === 'Sewak') {
    $desig_id = (int)$_POST['designation_id'];
    
    // Package POST data back into structured JSON
    $mission = trim($_POST['mission'] ?? '');
    
    $raw_outcomes = $_POST['outcomes'] ?? [];
    $outcomes = array_values(array_filter(array_map('trim', $raw_outcomes)));
    
    $skills = [];
    $attributes = $_POST['attribute'] ?? [];
    $assessments = $_POST['assessment'] ?? [];
    $attr_weights = $_POST['attr_weight'] ?? [];
    
    $sn = 1;
    foreach ($attributes as $index => $category_name) {
        $category_name = trim($category_name);
        if ($category_name === '') continue;
        
        $weightage = (int)($attr_weights[$index] ?? 0);
        $assessment = trim($assessments[$index] ?? '');
        
        $child_attributes = [];
        $techs = $_POST['tech'][$index] ?? [];
        $tech_weights = $_POST['tech_weight'][$index] ?? [];
        
        foreach ($techs as $t_index => $desc) {
            $desc = trim($desc);
            if ($desc === '') continue;
            $weight = (int)($tech_weights[$t_index] ?? 0);
            
            $child_attributes[] = [
                'desc' => $desc,
                'weight' => $weight
            ];
        }
        
        $skills[] = [
            'sn' => $sn++,
            'category' => $category_name,
            'weightage' => $weightage,
            'assessment' => $assessment,
            'attributes' => $child_attributes
        ];
    }
    
    // Fetch designation title
    $d_stmt = $pdo->prepare("SELECT title FROM designations WHERE id = ?");
    $d_stmt->execute([$desig_id]);
    $d_row = $d_stmt->fetch();
    $desig_title = $d_row ? $d_row['title'] : 'Role Profile';
    
    $profile_data = [
        'designation_id' => $desig_id,
        'title' => 'Role Profile — ' . strtoupper($desig_title),
        'mission' => $mission,
        'outcomes' => $outcomes,
        'skills' => $skills
    ];
    
    $serialized_json = json_encode($profile_data, JSON_UNESCAPED_UNICODE);
    
    // Upsert into role_profiles
    $check = $pdo->prepare("SELECT id FROM role_profiles WHERE designation_id = ?");
    $check->execute([$desig_id]);
    if ($check->fetch()) {
        $stmt = $pdo->prepare("UPDATE role_profiles SET profile_text = ? WHERE designation_id = ?");
        $stmt->execute([$serialized_json, $desig_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO role_profiles (designation_id, profile_text) VALUES (?, ?)");
        $stmt->execute([$desig_id, $serialized_json]);
    }
    
    header('Location: role_profiles.php?designation_id=' . $desig_id . '&msg=saved');
    exit();
}

// Handle Designation Title update (Sewak inline edit action matching live site's designation management)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_designation' && $system_role === 'Sewak') {
    $d_id = (int)$_POST['designation_id'];
    $d_title = trim($_POST['title'] ?? '');
    $d_desc = trim($_POST['description'] ?? '');
    if (!empty($d_title)) {
        $stmt = $pdo->prepare("UPDATE designations SET title = ?, description = ? WHERE id = ?");
        $stmt->execute([$d_title, $d_desc, $d_id]);
        header('Location: role_profiles.php?msg=desig_updated');
        exit();
    }
}
?>

<style>
/* Custom Interactive Profiles & Grid CSS styling */
.profile-view-container {
    background-color: var(--color-surface);
    border-radius: 12px;
    box-shadow: var(--shadow-md);
    border: 1px solid var(--color-border);
    padding: 2rem;
    margin-bottom: 3rem;
}

.table-rp-grid {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.85rem;
    margin-top: 1rem;
    margin-bottom: 1.5rem;
    background: #ffffff;
}

.table-rp-grid th, .table-rp-grid td {
    border: 1px solid var(--color-border);
    padding: 0.75rem;
    vertical-align: middle;
}

.table-rp-grid th {
    background-color: #fafafa;
    color: var(--color-secondary);
    font-family: 'Outfit', sans-serif;
    font-weight: 700;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.05em;
}

.table-rp-grid textarea {
    width: 100%;
    border: 1px solid transparent;
    background: transparent;
    padding: 0.35rem 0.5rem;
    resize: none;
    font-family: inherit;
    font-size: 0.85rem;
    font-weight: 500;
    color: var(--color-text);
    border-radius: 4px;
    box-sizing: border-box;
    transition: var(--transition-smooth);
}

.table-rp-grid textarea:focus:not([disabled]) {
    border-color: var(--color-primary);
    background: #ffffff;
    box-shadow: 0 0 0 3px var(--color-primary-glow);
    outline: none;
}

.table-rp-grid input[type="number"] {
    width: 70px;
    text-align: center;
    border: 1px solid var(--color-border);
    padding: 0.35rem;
    font-weight: 700;
    color: var(--color-secondary);
    border-radius: 4px;
    background-color: #f8fafc;
}

.table-rp-grid input[type="number"]:focus:not([disabled]) {
    border-color: var(--color-primary);
    background: #ffffff;
    outline: none;
}

.outcome-input-row {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
    align-items: center;
}

.outcome-input-row input {
    flex-grow: 1;
}

.invalid-weight {
    background-color: #ffe6e6 !important;
}

.invalid-weight input, .invalid-weight textarea {
    border-color: var(--color-danger) !important;
    background-color: #fff2f2 !important;
}

.validation-warning-alert {
    background-color: #fffbeb;
    border-left: 4px solid var(--color-warning);
    color: #92400e;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    font-size: 0.875rem;
    font-weight: 600;
}

.btn-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border-radius: 6px;
    border: 1px solid var(--color-border);
    background: #ffffff;
    cursor: pointer;
    transition: var(--transition-smooth);
}

.btn-icon:hover:not(:disabled) {
    background-color: #f1f5f9;
    border-color: #cbd5e1;
}

.btn-icon-danger:hover:not(:disabled) {
    background-color: #fee2e2;
    border-color: #fca5a5;
    color: var(--color-danger);
}

.search-box-profiles {
    padding: 0.75rem 1rem 0.75rem 2.5rem;
    font-size: 0.9rem;
    border-radius: 8px;
    border: 1.5px solid var(--color-border);
    width: 100%;
    box-sizing: border-box;
    background-color: #ffffff;
}

.search-box-profiles:focus {
    border-color: var(--color-primary);
    outline: none;
    box-shadow: 0 0 0 3px var(--color-primary-glow);
}
</style>

<?php if ($designation_id === 0): 
    // LIST VIEW: Show table of all designations and profiles (designation_list.php)
    $stmt = $pdo->query("
        SELECT d.id, d.title, d.description, rp.profile_text 
        FROM designations d 
        LEFT JOIN role_profiles rp ON d.id = rp.designation_id 
        ORDER BY d.id ASC
    ");
    $designations = $stmt->fetchAll();
?>

<div class="page-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 2.5rem;">
    <div>
        <h1 class="page-title">All Role Profiles</h1>
        <p style="margin: 0.25rem 0 0 0; color: var(--color-text-muted); font-size: 0.9rem;">View, configure, and review structured operational competencies for all corporate roles.</p>
    </div>
    <?php if ($system_role === 'Sewak'): ?>
        <a href="add_role_skill.php" class="btn" style="background-color: var(--color-primary); color: white; display: inline-flex; align-items: center; gap: 0.5rem; font-family: 'Outfit', sans-serif; font-weight: 700; font-size: 0.85rem; padding: 0.6rem 1.2rem; border-radius: 8px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"></path></svg>
            Add Role Skill Component
        </a>
    <?php endif; ?>
</div>

<?php if (isset($_GET['msg'])): ?>
    <?php if ($_GET['msg'] === 'desig_updated'): ?>
        <div class="alert alert-success">Designation details updated successfully.</div>
    <?php elseif ($_GET['msg'] === 'saved'): ?>
        <div class="alert alert-success">Role profile saved successfully.</div>
    <?php endif; ?>
<?php endif; ?>

<!-- Filter & Search Controls -->
<div class="card" style="margin-bottom: 2rem; padding: 1.25rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div class="search-input-wrapper" style="max-width: 450px; width: 100%; position: relative;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--color-text-muted);">
                <circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input type="text" id="searchBox" class="search-box-profiles" placeholder="Search designation title..." onkeyup="filterDesignations()">
        </div>
        <div style="font-size: 0.85rem; font-weight: 700; color: var(--color-text-muted);">
            Active Financial Year: <span class="badge badge-green">FY 2026-27</span>
        </div>
    </div>
</div>

<div class="table-wrapper">
    <table id="designationTable">
        <thead>
            <tr>
                <th style="width: 70px; text-align: center;">SN</th>
                <th>Designation</th>
                <th style="width: 160px; text-align: center;">Edit Designation</th>
                <th style="width: 220px; text-align: center;">Role Profile Grid</th>
                <th style="width: 200px; text-align: center;">FY 2026-27 Review</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($designations as $index => $d): 
                $profile_exists = !empty($d['profile_text']);
            ?>
            <tr class="desig-row">
                <td style="text-align: center; font-weight: 700; color: var(--color-text-muted);"><?= $index + 1 ?></td>
                <td>
                    <strong class="desig-title" style="font-size: 0.95rem; color: var(--color-secondary);"><?= htmlspecialchars($d['title']) ?></strong>
                    <?php if ($profile_exists): ?>
                        <span class="badge badge-green" style="font-size: 0.65rem; margin-left: 0.5rem; vertical-align: middle;">Active Profile</span>
                    <?php endif; ?>
                </td>
                <td style="text-align: center;">
                    <?php if ($system_role === 'Sewak'): ?>
                        <button class="btn btn-outline" style="padding: 0.4rem 0.8rem; font-size: 0.775rem;" 
                                onclick="openEditModal(<?= $d['id'] ?>, '<?= htmlspecialchars(addslashes($d['title'])) ?>', '<?= htmlspecialchars(addslashes($d['description'])) ?>')">
                            Edit Designation
                        </button>
                    <?php else: ?>
                        <span style="color: var(--color-text-muted); font-size: 0.775rem; font-weight: 500;">Read-Only</span>
                    <?php endif; ?>
                </td>
                <td style="text-align: center;">
                    <div style="display: inline-flex; gap: 0.35rem;">
                        <?php if ($profile_exists): ?>
                            <a href="role_profiles.php?designation_id=<?= $d['id'] ?>&view=1" class="btn btn-outline" style="padding: 0.4rem 0.8rem; font-size: 0.775rem; border-color: var(--color-primary); color: var(--color-primary);">View</a>
                            <?php if ($system_role === 'Sewak'): ?>
                                <a href="role_profiles.php?designation_id=<?= $d['id'] ?>" class="btn" style="padding: 0.4rem 0.8rem; font-size: 0.775rem; background-color: var(--color-primary); color: white;">Edit</a>
                            <?php endif; ?>
                        <?php else: ?>
                            <?php if ($system_role === 'Sewak'): ?>
                                <a href="role_profiles.php?designation_id=<?= $d['id'] ?>" class="btn" style="padding: 0.4rem 0.8rem; font-size: 0.775rem; background-color: var(--color-success); color: white; border: none;">Create</a>
                            <?php else: ?>
                                <span style="color: var(--color-text-muted); font-size: 0.75rem; font-weight: 600; font-style: italic;">No Profile</span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </td>
                <td style="text-align: center;">
                    <?php if ($profile_exists): ?>
                        <a href="role_profile_assessment.php?designation_id=<?= $d['id'] ?>&financial_year=FY+2026-27" class="btn btn-outline" style="padding: 0.4rem 0.8rem; font-size: 0.775rem; border-color: #2e503b; color: #2e503b;">Review (FY)</a>
                    <?php else: ?>
                        <span style="color: var(--color-text-muted); font-size: 0.775rem; font-weight: 500;">No Role Profile</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if ($system_role === 'Sewak'): ?>
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
                    <input type="text" name="title" id="edit_title" class="form-control" required style="border-width: 1.5px;">
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" id="edit_description" class="form-control" rows="3" style="border-width: 1.5px;"></textarea>
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
function openEditModal(id, title, description) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_title').value = title;
    document.getElementById('edit_description').value = description;
    openModal('editDesignationModal');
}
</script>
<?php endif; ?>

<script>
function filterDesignations() {
    const searchVal = document.getElementById('searchBox').value.toLowerCase();
    const rows = document.querySelectorAll('.desig-row');
    
    rows.forEach(row => {
        const titleText = row.querySelector('.desig-title').textContent.toLowerCase();
        if (titleText.includes(searchVal)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}
</script>

<?php else: 
    // DETAIL VIEW: Detailed grid editor or read-only view of a designation profile (role_profile.php)
    $stmt = $pdo->prepare("SELECT * FROM designations WHERE id = ?");
    $stmt->execute([$designation_id]);
    $designation = $stmt->fetch();
    
    if (!$designation) {
        echo "<div class='alert alert-danger'>Designation not found.</div>";
        require_once 'includes/footer.php';
        exit();
    }
    
    // Fetch profile
    $p_stmt = $pdo->prepare("SELECT * FROM role_profiles WHERE designation_id = ?");
    $p_stmt->execute([$designation_id]);
    $profile_record = $p_stmt->fetch();
    
    $profile = [];
    if ($profile_record && !empty($profile_record['profile_text'])) {
        $profile = json_decode($profile_record['profile_text'], true);
    }
    
    // If profile is empty, load Fallback Corporate Profile template
    if (empty($profile)) {
        $profile = [
            'designation_id' => $designation_id,
            'title' => 'Role Profile — ' . strtoupper($designation['title']),
            'mission' => 'Drive organizational alignment, optimize operational metrics, and champion capability building.',
            'outcomes' => [
                'Successfully achieve 100% of annual key performance indicators (KPIs).',
                'Streamline workflow efficiency with measurable time/cost reductions of ≥ 5%.',
                'Foster outstanding team synergy, aiming for peer collaboration scores ≥ 85%.',
                'Champion digital capability growth by adopting state-of-the-art office automations.',
                'Demonstrate high resource optimization, completing projects within assigned budgets.'
            ],
            'skills' => [
                [
                    'sn' => 1,
                    'category' => 'Strategic Alignment',
                    'weightage' => 25,
                    'assessment' => 'KPI Completion Rate ≥ 90%',
                    'attributes' => [
                        ['desc' => 'Align team activities with high-level corporate roadmap metrics.', 'weight' => 9],
                        ['desc' => 'Execute priority project objectives on schedule.', 'weight' => 8],
                        ['desc' => 'Conduct standard monthly operations reports.', 'weight' => 8]
                    ]
                ],
                [
                    'sn' => 2,
                    'category' => 'Process & Quality Excellence',
                    'weightage' => 25,
                    'assessment' => 'Process Optimization ≥ 5%',
                    'attributes' => [
                        ['desc' => 'Pinpoint and resolve operational bottlenecks systematically.', 'weight' => 9],
                        ['desc' => 'Maintain absolute documentation accuracy in corporate systems.', 'weight' => 8],
                        ['desc' => 'Optimize communication transparency within the business unit.', 'weight' => 8]
                    ]
                ],
                [
                    'sn' => 3,
                    'category' => 'Resource & Budget Coordination',
                    'weightage' => 20,
                    'assessment' => 'Spend Variance < 5%',
                    'attributes' => [
                        ['desc' => 'Track and manage department expenditures strictly inside budget parameters.', 'weight' => 7],
                        ['desc' => 'Optimize utilization metrics for software licenses and computing resources.', 'weight' => 7],
                        ['desc' => 'Identify cost-saving pathways through waste minimization.', 'weight' => 6]
                    ]
                ],
                [
                    'sn' => 4,
                    'category' => 'Team Collaboration & Mentoring',
                    'weightage' => 15,
                    'assessment' => 'Peer Collaboration Score ≥ 85%',
                    'attributes' => [
                        ['desc' => 'Collaborate actively with commercial, financial, and operational heads.', 'weight' => 5],
                        ['desc' => 'Upskill new joiners and junior coordinators in core operations.', 'weight' => 5],
                        ['desc' => 'Nurture a highly positive, zero-silo organizational workspace.', 'weight' => 5]
                    ]
                ],
                [
                    'sn' => 5,
                    'category' => 'Innovation & Upskilling',
                    'weightage' => 15,
                    'assessment' => 'Learning Goal Accomplishment',
                    'attributes' => [
                        ['desc' => 'Integrate AI automation models to accelerate weekly duties.', 'weight' => 5],
                        ['desc' => 'Formulate new frameworks to capture division knowledge assets.', 'weight' => 5],
                        ['desc' => 'Pursue continuous self-directed professional education courses.', 'weight' => 5]
                    ]
                ]
            ]
        ];
    }
    
    // Check editing permissions
    $can_edit = ($system_role === 'Sewak') && !$view_mode;
?>

<div style="margin-bottom: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 style="margin: 0; font-family: 'Outfit', sans-serif; font-size: 1.85rem; font-weight: 700; color: var(--color-secondary); letter-spacing: -0.01em;">
                <?= htmlspecialchars($profile['title'] ?? 'Role Profile') ?>
            </h1>
            <p style="margin: 0.25rem 0 0 0; color: var(--color-text-muted); font-size: 0.9rem;">
                <?= $can_edit ? 'Create, modify, and balance competency weights for this role profile.' : 'Detailed breakdown of core outcomes and capabilities for this designation.' ?>
            </p>
        </div>
        <div style="display: flex; gap: 0.5rem; align-items: center;">
            <?php if ($system_role === 'Sewak'): ?>
                <a href="add_role_skill.php?designation_id=<?= $designation_id ?>" class="btn btn-outline" style="padding: 0.6rem 1.2rem; font-size: 0.85rem; font-weight: 700; border-color: var(--color-primary); color: var(--color-primary); display: inline-flex; align-items: center; gap: 0.35rem;">
                    + Add Skill Component
                </a>
            <?php endif; ?>
            <a href="role_profiles.php" class="btn btn-outline" style="padding: 0.6rem 1.2rem; font-size: 0.85rem; font-weight: 700;">
                Back to Designation List
            </a>
        </div>
    </div>
</div>

<?php if (isset($_GET['msg'])): ?>
    <?php if ($_GET['msg'] === 'saved'): ?>
        <div class="alert alert-success" style="margin-bottom: 1.5rem;">Structured Role Profile successfully balanced and saved!</div>
    <?php elseif ($_GET['msg'] === 'skill_added'): ?>
        <div class="alert alert-success" style="margin-bottom: 1.5rem; background-color: #d1e7dd; color: #0f5132; border-color: #badbcc;">⚡ New skill component successfully added to this designation profile structure! Feel free to review or fine-tune weights below.</div>
    <?php endif; ?>
<?php endif; ?>

<!-- Validation warning display dynamically updated by JS -->
<?php if ($can_edit): ?>
    <div id="valWarningBlock" class="validation-warning-alert" style="display: none;">
        <strong>Validation Notice:</strong> Weight calculations must sum to exactly 100. Category attributes and child skills are currently unbalanced. Please balance them to enable saving.
    </div>
<?php endif; ?>

<form method="POST" id="profileForm">
    <input type="hidden" name="action" value="save_profile">
    <input type="hidden" name="designation_id" value="<?= $designation_id ?>">
    
    <div class="profile-view-container">
        
        <!-- Mission text field -->
        <h4 style="margin: 0 0 0.5rem 0; font-family: 'Outfit', sans-serif; color: #2e503b; font-size: 1.15rem; font-weight: 700;">Mission:</h4>
        <div style="margin-bottom: 2rem;">
            <textarea name="mission" class="form-control" rows="3" placeholder="Define the core mission or primary purpose of this designation..." style="border-width: 1.5px; font-weight: 500; font-size: 0.925rem; resize: vertical;" <?= !$can_edit ? 'disabled' : 'required' ?>><?= htmlspecialchars($profile['mission'] ?? '') ?></textarea>
        </div>
        
        <!-- Key Outcomes array editor -->
        <h4 style="margin: 0 0 0.75rem 0; font-family: 'Outfit', sans-serif; color: #2e503b; font-size: 1.15rem; font-weight: 700; display: flex; justify-content: space-between; align-items: center;">
            Key Outcomes:
            <?php if ($can_edit): ?>
                <button type="button" class="btn btn-outline" style="padding: 0.25rem 0.6rem; font-size: 0.725rem; font-weight: 700;" onclick="addOutcome()">+ Add Outcome</button>
            <?php endif; ?>
        </h4>
        <div id="outcomesContainer" style="margin-bottom: 2.5rem;">
            <?php 
            $outcomes_list = $profile['outcomes'] ?? [];
            if (empty($outcomes_list)) $outcomes_list = [''];
            foreach ($outcomes_list as $ocIdx => $oc): 
            ?>
                <div class="outcome-input-row" data-index="<?= $ocIdx ?>">
                    <span style="font-weight: 700; color: #2e503b; width: 25px;" class="outcome-sn"><?= $ocIdx + 1 ?>.</span>
                    <input type="text" name="outcomes[]" class="form-control" value="<?= htmlspecialchars($oc) ?>" placeholder="Define a primary business/operational outcome..." required style="border-width: 1.5px;" <?= !$can_edit ? 'disabled' : '' ?>>
                    <?php if ($can_edit): ?>
                        <button type="button" class="btn-icon btn-icon-danger" onclick="removeOutcome(this)" <?= count($outcomes_list) === 1 ? 'disabled' : '' ?>>&times;</button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Interactive Competency Grid -->
        <h4 style="margin: 0 0 0.75rem 0; font-family: 'Outfit', sans-serif; color: #2e503b; font-size: 1.15rem; font-weight: 700; display: flex; justify-content: space-between; align-items: center;">
            Competency and Weights Grid:
            <?php if ($can_edit): ?>
                <button type="button" class="btn btn-outline" style="padding: 0.25rem 0.6rem; font-size: 0.725rem; font-weight: 700;" onclick="addCategoryRow()">+ Add Category</button>
            <?php endif; ?>
        </h4>
        
        <div class="table-wrapper" style="overflow-x: auto; margin-bottom: 1.5rem; padding: 0;">
            <table class="table-rp-grid" id="gridTable">
                <thead>
                    <tr>
                        <th style="width: 50px; text-align: center;">SN</th>
                        <th style="width: 220px;">Skill Attribute Category</th>
                        <th style="width: 280px;">Individual Technical Skill Description</th>
                        <th style="width: 200px;">Target Assessment Metric</th>
                        <th style="width: 90px; text-align: center;">Category Weight</th>
                        <th style="width: 90px; text-align: center;">Skill Weight</th>
                        <?php if ($can_edit): ?>
                            <th style="width: 90px; text-align: center;">Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody id="gridTbody">
                    <?php 
                    $cat_index = 0;
                    foreach ($profile['skills'] as $s): 
                        $rowCount = count($s['attributes']);
                        if ($rowCount === 0) {
                            $s['attributes'][] = ['desc' => '', 'weight' => 0];
                            $rowCount = 1;
                        }
                    ?>
                        <!-- First Row of Category -->
                        <tr class="category-group" data-cat-id="<?= $cat_index ?>" data-row-idx="0">
                            <td rowspan="<?= $rowCount ?>" style="text-align: center; font-weight: 700; color: var(--color-text-muted); background: #fafafa;" class="cat-sn-cell"><?= $cat_index + 1 ?></td>
                            
                            <td rowspan="<?= $rowCount ?>" style="background: #fafafa; position: relative;">
                                <textarea name="attribute[<?= $cat_index ?>]" placeholder="Category Title..." required rows="2" <?= !$can_edit ? 'disabled' : '' ?>><?= htmlspecialchars($s['category']) ?></textarea>
                                <?php if ($can_edit): ?>
                                    <button type="button" class="btn btn-outline" style="padding: 0.15rem 0.4rem; font-size: 0.65rem; font-weight: 700; position: absolute; bottom: 5px; right: 5px;" onclick="addSkillRow(<?= $cat_index ?>)">+ Skill</button>
                                <?php endif; ?>
                            </td>
                            
                            <td>
                                <textarea name="tech[<?= $cat_index ?>][]" placeholder="Attribute outcome / capability..." required rows="2" <?= !$can_edit ? 'disabled' : '' ?>><?= htmlspecialchars($s['attributes'][0]['desc']) ?></textarea>
                            </td>
                            
                            <td rowspan="<?= $rowCount ?>">
                                <textarea name="assessment[<?= $cat_index ?>]" placeholder="Assessment Target Metric..." required rows="2" <?= !$can_edit ? 'disabled' : '' ?>><?= htmlspecialchars($s['assessment']) ?></textarea>
                            </td>
                            
                            <td rowspan="<?= $rowCount ?>" style="text-align: center; background: #fafafa;" class="cat-weight-cell">
                                <input type="number" name="attr_weight[<?= $cat_index ?>]" class="attr-weight-input" data-cat-id="<?= $cat_index ?>" value="<?= (int)$s['weightage'] ?>" min="1" max="100" required onchange="validateCalculations()" <?= !$can_edit ? 'disabled' : '' ?>>
                            </td>
                            
                            <td style="text-align: center;">
                                <input type="number" name="tech_weight[<?= $cat_index ?>][]" class="tech-weight-input" data-cat-id="<?= $cat_index ?>" value="<?= (int)$s['attributes'][0]['weight'] ?>" min="1" max="100" required onchange="validateCalculations()" <?= !$can_edit ? 'disabled' : '' ?>>
                            </td>
                            
                            <?php if ($can_edit): ?>
                                <td style="text-align: center;">
                                    <button type="button" class="btn-icon btn-icon-danger" onclick="deleteSkill(this, <?= $cat_index ?>)" style="display: none;" class="del-skill-btn">&times;</button>
                                    <button type="button" class="btn-icon btn-icon-danger" onclick="deleteCategory(<?= $cat_index ?>)" title="Delete full Category" style="margin-left: 0.25rem;">🗑️</button>
                                </td>
                            <?php endif; ?>
                        </tr>
                        
                        <!-- Subsequent Rows of Category -->
                        <?php for ($i = 1; $i < $rowCount; $i++): ?>
                            <tr class="category-subrow" data-cat-id="<?= $cat_index ?>" data-row-idx="<?= $i ?>">
                                <td>
                                    <textarea name="tech[<?= $cat_index ?>][]" placeholder="Attribute outcome / capability..." required rows="2" <?= !$can_edit ? 'disabled' : '' ?>><?= htmlspecialchars($s['attributes'][$i]['desc']) ?></textarea>
                                </td>
                                
                                <td style="text-align: center;">
                                    <input type="number" name="tech_weight[<?= $cat_index ?>][]" class="tech-weight-input" data-cat-id="<?= $cat_index ?>" value="<?= (int)$s['attributes'][$i]['weight'] ?>" min="1" max="100" required onchange="validateCalculations()" <?= !$can_edit ? 'disabled' : '' ?>>
                                </td>
                                
                                <?php if ($can_edit): ?>
                                    <td style="text-align: center;">
                                        <button type="button" class="btn-icon btn-icon-danger" onclick="deleteSkill(this, <?= $cat_index ?>)">&times;</button>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endfor; ?>
                    <?php 
                        $cat_index++;
                    endforeach; 
                    ?>
                </tbody>
                <tfoot>
                    <!-- Total Row matching fetched_profile_1.html exactly -->
                    <tr style="background:#f5f5f5; font-weight:bold; font-family: 'Outfit', sans-serif;">
                        <td colspan="4" align="right" style="padding: 1rem; text-align: right; font-weight: 700;">TOTAL</td>
                        <td id="totalAttr" style="text-align: center; font-weight: 800; color: var(--color-primary);">0</td>
                        <td id="totalTech" style="text-align: center; font-weight: 800; color: var(--color-primary);">0</td>
                        <?php if ($can_edit): ?>
                            <td></td>
                        <?php endif; ?>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <?php if ($can_edit): ?>
            <div style="display: flex; gap: 1rem; justify-content: flex-start; margin-top: 2rem;">
                <button type="submit" id="saveBtn" class="btn" style="background-color: var(--color-primary); color: white; padding: 0.75rem 2rem; font-size: 0.95rem; font-weight: 700; border-radius: 8px;">
                    Save Profile Grid
                </button>
                <a href="role_profiles.php" class="btn btn-outline" style="padding: 0.75rem 1.5rem; font-size: 0.95rem; font-weight: 700;">Cancel Changes</a>
            </div>
        <?php endif; ?>
        
    </div>
</form>

<script>
// Key Outcomes Management
function addOutcome() {
    const container = document.getElementById('outcomesContainer');
    const rows = container.querySelectorAll('.outcome-input-row');
    const newIdx = rows.length;
    
    const div = document.createElement('div');
    div.className = 'outcome-input-row';
    div.setAttribute('data-index', newIdx);
    div.innerHTML = `
        <span style="font-weight: 700; color: #2e503b; width: 25px;" class="outcome-sn">${newIdx + 1}.</span>
        <input type="text" name="outcomes[]" class="form-control" value="" placeholder="Define a primary business/operational outcome..." required style="border-width: 1.5px;">
        <button type="button" class="btn-icon btn-icon-danger" onclick="removeOutcome(this)">&times;</button>
    `;
    container.appendChild(div);
    updateOutcomeNumbers();
}

function removeOutcome(btn) {
    const container = document.getElementById('outcomesContainer');
    btn.parentNode.remove();
    updateOutcomeNumbers();
}

function updateOutcomeNumbers() {
    const container = document.getElementById('outcomesContainer');
    const rows = container.querySelectorAll('.outcome-input-row');
    const deleteButtons = container.querySelectorAll('.btn-icon-danger');
    
    rows.forEach((row, idx) => {
        row.querySelector('.outcome-sn').innerText = (idx + 1) + '.';
        row.setAttribute('data-index', idx);
    });
    
    // Disable delete if only 1 remains
    deleteButtons.forEach(btn => {
        btn.disabled = (rows.length === 1);
    });
}

// Competency and Weight calculations
let catCounter = <?= $cat_index ?>;

function addCategoryRow() {
    const tbody = document.getElementById('gridTbody');
    const newCatIdx = catCounter++;
    
    const tr = document.createElement('tr');
    tr.className = 'category-group';
    tr.setAttribute('data-cat-id', newCatIdx);
    tr.setAttribute('data-row-idx', '0');
    
    tr.innerHTML = `
        <td style="text-align: center; font-weight: 700; color: var(--color-text-muted); background: #fafafa;" class="cat-sn-cell"></td>
        <td style="background: #fafafa; position: relative;">
            <textarea name="attribute[${newCatIdx}]" placeholder="Category Title..." required rows="2"></textarea>
            <button type="button" class="btn btn-outline" style="padding: 0.15rem 0.4rem; font-size: 0.65rem; font-weight: 700; position: absolute; bottom: 5px; right: 5px;" onclick="addSkillRow(${newCatIdx})">+ Skill</button>
        </td>
        <td>
            <textarea name="tech[${newCatIdx}][]" placeholder="Attribute outcome / capability..." required rows="2"></textarea>
        </td>
        <td>
            <textarea name="assessment[${newCatIdx}]" placeholder="Assessment Target Metric..." required rows="2"></textarea>
        </td>
        <td style="text-align: center; background: #fafafa;" class="cat-weight-cell">
            <input type="number" name="attr_weight[${newCatIdx}]" class="attr-weight-input" data-cat-id="${newCatIdx}" value="20" min="1" max="100" required onchange="validateCalculations()">
        </td>
        <td style="text-align: center;">
            <input type="number" name="tech_weight[${newCatIdx}][]" class="tech-weight-input" data-cat-id="${newCatIdx}" value="20" min="1" max="100" required onchange="validateCalculations()">
        </td>
        <td style="text-align: center;">
            <button type="button" class="btn-icon btn-icon-danger" onclick="deleteSkill(this, ${newCatIdx})" style="display: none;">&times;</button>
            <button type="button" class="btn-icon btn-icon-danger" onclick="deleteCategory(${newCatIdx})" title="Delete full Category" style="margin-left: 0.25rem;">🗑️</button>
        </td>
    `;
    
    tbody.appendChild(tr);
    initAutoresize();
    validateCalculations();
}

function addSkillRow(catIdx) {
    const tbody = document.getElementById('gridTbody');
    
    // Find the category base row
    const baseRow = tbody.querySelector(`.category-group[data-cat-id="${catIdx}"]`);
    if (!baseRow) return;
    
    // Find all subrows for this category to determine the insert index and current rowspan
    const subrows = tbody.querySelectorAll(`tr[data-cat-id="${catIdx}"]`);
    const lastRowOfGroup = subrows[subrows.length - 1];
    
    // Create new subrow
    const tr = document.createElement('tr');
    tr.className = 'category-subrow';
    tr.setAttribute('data-cat-id', catIdx);
    tr.setAttribute('data-row-idx', subrows.length);
    
    tr.innerHTML = `
        <td>
            <textarea name="tech[${catIdx}][]" placeholder="Attribute outcome / capability..." required rows="2"></textarea>
        </td>
        <td style="text-align: center;">
            <input type="number" name="tech_weight[${catIdx}][]" class="tech-weight-input" data-cat-id="${catIdx}" value="0" min="1" max="100" required onchange="validateCalculations()">
        </td>
        <td style="text-align: center;">
            <button type="button" class="btn-icon btn-icon-danger" onclick="deleteSkill(this, ${catIdx})">&times;</button>
        </td>
    `;
    
    // Insert new row right after the last row in this category's group
    lastRowOfGroup.parentNode.insertBefore(tr, lastRowOfGroup.nextSibling);
    
    // Update rowspan values on the base row
    const newRowspan = subrows.length + 1;
    baseRow.querySelector('.cat-sn-cell').setAttribute('rowspan', newRowspan);
    baseRow.querySelector('td[rowspan]:nth-child(2)').setAttribute('rowspan', newRowspan);
    baseRow.querySelector('td[rowspan]:nth-child(4)').setAttribute('rowspan', newRowspan);
    baseRow.querySelector('.cat-weight-cell').setAttribute('rowspan', newRowspan);
    
    initAutoresize();
    validateCalculations();
}

function deleteSkill(btn, catIdx) {
    const tbody = document.getElementById('gridTbody');
    const tr = btn.closest('tr');
    
    // If it's a subrow, simply remove it and adjust rowspan
    if (tr.classList.contains('category-subrow')) {
        tr.remove();
        adjustRowspans(catIdx);
    } else {
        // Base row cannot delete individual skill directly unless there are subrows,
        // in which case we shift the first subrow's contents into the base row!
        const subrows = tbody.querySelectorAll(`.category-subrow[data-cat-id="${catIdx}"]`);
        if (subrows.length > 0) {
            const firstSubrow = subrows[0];
            // Shift values
            const subTechVal = firstSubrow.querySelector('textarea').value;
            const subWeightVal = firstSubrow.querySelector('input[type="number"]').value;
            
            tr.querySelector('textarea[name^="tech"]').value = subTechVal;
            tr.querySelector('.tech-weight-input').value = subWeightVal;
            
            firstSubrow.remove();
            adjustRowspans(catIdx);
        }
    }
    validateCalculations();
}

function adjustRowspans(catIdx) {
    const tbody = document.getElementById('gridTbody');
    const baseRow = tbody.querySelector(`.category-group[data-cat-id="${catIdx}"]`);
    if (!baseRow) return;
    
    const subrows = tbody.querySelectorAll(`tr[data-cat-id="${catIdx}"]`);
    const newRowspan = subrows.length;
    
    baseRow.querySelector('.cat-sn-cell').setAttribute('rowspan', newRowspan);
    baseRow.querySelector('td[rowspan]:nth-child(2)').setAttribute('rowspan', newRowspan);
    baseRow.querySelector('td[rowspan]:nth-child(4)').setAttribute('rowspan', newRowspan);
    baseRow.querySelector('.cat-weight-cell').setAttribute('rowspan', newRowspan);
    
    // Re-index remaining subrows
    tbody.querySelectorAll(`.category-subrow[data-cat-id="${catIdx}"]`).forEach((row, i) => {
        row.setAttribute('data-row-idx', i + 1);
    });
}

function deleteCategory(catIdx) {
    if (!confirm('Are you sure you want to delete this category and all its technical skills?')) return;
    const tbody = document.getElementById('gridTbody');
    tbody.querySelectorAll(`tr[data-cat-id="${catIdx}"]`).forEach(row => row.remove());
    validateCalculations();
}

function validateCalculations() {
    let totalAttrWeight = 0;
    let totalTechWeight = 0;
    let allGroupsBalanced = true;
    
    // 1. Gather all category IDs currently in table
    const categoryIds = [];
    document.querySelectorAll('.category-group').forEach(row => {
        const catId = parseInt(row.getAttribute('data-cat-id'));
        if (!categoryIds.includes(catId)) {
            categoryIds.push(catId);
        }
    });
    
    // 2. Validate each category group
    categoryIds.forEach((catId, snIndex) => {
        // Update visual serial number
        const baseRow = document.querySelector(`.category-group[data-cat-id="${catId}"]`);
        if (baseRow) {
            baseRow.querySelector('.cat-sn-cell').innerText = (snIndex + 1);
        }
        
        // Sum Attribute weight
        const attrInput = document.querySelector(`.attr-weight-input[data-cat-id="${catId}"]`);
        const attrWeight = attrInput ? parseInt(attrInput.value) || 0 : 0;
        totalAttrWeight += attrWeight;
        
        // Sum Child Technical Skill weights
        let techSum = 0;
        document.querySelectorAll(`.tech-weight-input[data-cat-id="${catId}"]`).forEach(input => {
            const w = parseInt(input.value) || 0;
            techSum += w;
            totalTechWeight += w;
        });
        
        // Check if group is balanced
        const allGroupRows = document.querySelectorAll(`tr[data-cat-id="${catId}"]`);
        if (techSum !== attrWeight || attrWeight === 0) {
            allGroupsBalanced = false;
            allGroupRows.forEach(row => row.classList.add('invalid-weight'));
        } else {
            allGroupRows.forEach(row => row.classList.remove('invalid-weight'));
        }
        
        // Show/hide sub-row delete buttons based on row count
        const skillDeleteBtns = document.querySelectorAll(`tr[data-cat-id="${catId}"] .btn-icon-danger:not([title])`);
        skillDeleteBtns.forEach(btn => {
            btn.style.display = (allGroupRows.length > 1) ? 'inline-block' : 'none';
        });
    });
    
    // Update footer totals
    document.getElementById('totalAttr').innerText = totalAttrWeight;
    document.getElementById('totalTech').innerText = totalTechWeight;
    
    // Evaluate full grid status
    const saveBtn = document.getElementById('saveBtn');
    const warningBlock = document.getElementById('valWarningBlock');
    
    const isFullyValid = allGroupsBalanced && (totalAttrWeight === 100) && (totalTechWeight === 100);
    
    if (saveBtn) {
        saveBtn.disabled = !isFullyValid;
    }
    
    if (warningBlock) {
        if (isFullyValid) {
            warningBlock.style.display = 'none';
        } else {
            warningBlock.style.display = 'block';
            let warningText = '<strong>Validation Notice:</strong> ';
            if (totalAttrWeight !== 100 || totalTechWeight !== 100) {
                warningText += `Weights are unbalanced (Category Total: <strong>${totalAttrWeight}/100</strong>, Skill Total: <strong>${totalTechWeight}/100</strong>). `;
            }
            if (!allGroupsBalanced) {
                warningText += 'Some technical skill sums do not match their category category weights (highlighted in red).';
            }
            warningBlock.innerHTML = warningText;
        }
    }
}

// Auto-resizing textareas
function initAutoresize() {
    document.querySelectorAll(".table-rp-grid textarea, .profile-view-container textarea").forEach(el => {
        function autoResize() {
            el.style.height = "auto";
            el.style.height = el.scrollHeight + "px";
        }
        el.removeEventListener("input", autoResize);
        el.addEventListener("input", autoResize);
        autoResize(); // trigger immediately
    });
}

window.addEventListener('DOMContentLoaded', () => {
    initAutoresize();
    validateCalculations();
});
</script>

<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
