<?php
// add_role_skill.php
require_once 'includes/db.php';
require_once 'includes/header.php';

$system_role = $_SESSION['system_role'];
$user_id = $_SESSION['user_id'];

// Strict Access Control: Only Sewak (Admin) is authorized
if ($system_role !== 'Sewak') {
    http_response_code(403);
    echo "<div class='alert alert-danger' style='background-color: #f8d7da; color: #842029; border: 1px solid #f5c2c7; padding: 1.5rem; border-radius: 8px; margin: 2rem 0; font-weight: 700; font-family: \"Outfit\", sans-serif;'>
            🚫 ACCESS DENIED: Only Sewak (Admin) accounts can perform designation role modifications.
          </div>";
    require_once 'includes/footer.php';
    exit();
}

$msg = '';
$msg_type = 'success';

// Handle skill addition POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_skill') {
    $desig_id = (int)($_POST['designation_id'] ?? 0);
    $category_name = trim($_POST['category_name'] ?? '');
    $category_weight = (int)($_POST['category_weight'] ?? 0);
    $skill_desc = trim($_POST['skill_desc'] ?? '');
    $skill_weight = (int)($_POST['skill_weight'] ?? 0);
    $assessment_desc = trim($_POST['assessment_desc'] ?? '');

    // Basic Input Validations
    if ($desig_id <= 0 || empty($category_name) || empty($skill_desc) || $category_weight <= 0 || $skill_weight <= 0) {
        $msg = "Please fill in all required fields. Weights must be positive integers.";
        $msg_type = "danger";
    } else {
        // Fetch designation title
        $d_stmt = $pdo->prepare("SELECT title FROM designations WHERE id = ?");
        $d_stmt->execute([$desig_id]);
        $designation = $d_stmt->fetch();

        if (!$designation) {
            $msg = "Selected designation does not exist.";
            $msg_type = "danger";
        } else {
            $desig_title = $designation['title'];

            // Fetch current profile from role_profiles
            $p_stmt = $pdo->prepare("SELECT profile_text FROM role_profiles WHERE designation_id = ?");
            $p_stmt->execute([$desig_id]);
            $profile_record = $p_stmt->fetch();

            $profile_data = null;
            if ($profile_record && !empty($profile_record['profile_text'])) {
                $profile_data = json_decode($profile_record['profile_text'], true);
            }

            // Initialize profile data template if not exists
            if (!$profile_data || !isset($profile_data['mission'])) {
                $profile_data = [
                    'designation_id' => $desig_id,
                    'title' => 'Role Profile — ' . strtoupper($desig_title),
                    'mission' => 'Build operational excellence and strategic growth capabilities in line with organizational goals.',
                    'outcomes' => [
                        'Achieve 100% of annual key performance indicators (KPIs).',
                        'Maintain high standard of quality assurance and system consistency.',
                        'Foster peer alignment and capability development.'
                    ],
                    'skills' => []
                ];
            }

            // Search if category already exists in profile's skills array
            $cat_found_index = -1;
            foreach ($profile_data['skills'] as $index => $s) {
                if (strcasecmp(trim($s['category']), trim($category_name)) === 0) {
                    $cat_found_index = $index;
                    break;
                }
            }

            if ($cat_found_index !== -1) {
                // Category exists, append technical skill inside it
                if (!isset($profile_data['skills'][$cat_found_index]['attributes'])) {
                    $profile_data['skills'][$cat_found_index]['attributes'] = [];
                }
                
                $profile_data['skills'][$cat_found_index]['attributes'][] = [
                    'desc' => $skill_desc,
                    'weight' => $skill_weight
                ];
                
                // Keep assessment description if updated or originally empty
                if (!empty($assessment_desc)) {
                    $profile_data['skills'][$cat_found_index]['assessment'] = $assessment_desc;
                }
            } else {
                // Category does not exist, append a new category object
                $next_sn = count($profile_data['skills']) + 1;
                $profile_data['skills'][] = [
                    'sn' => $next_sn,
                    'category' => $category_name,
                    'weightage' => $category_weight,
                    'assessment' => $assessment_desc,
                    'attributes' => [
                        [
                            'desc' => $skill_desc,
                            'weight' => $skill_weight
                        ]
                    ]
                ];
            }

            // Serialize and upsert into database
            $serialized_json = json_encode($profile_data, JSON_UNESCAPED_UNICODE);
            
            $check_stmt = $pdo->prepare("SELECT id FROM role_profiles WHERE designation_id = ?");
            $check_stmt->execute([$desig_id]);
            if ($check_stmt->fetch()) {
                $stmt = $pdo->prepare("UPDATE role_profiles SET profile_text = ? WHERE designation_id = ?");
                $stmt->execute([$serialized_json, $desig_id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO role_profiles (designation_id, profile_text) VALUES (?, ?)");
                $stmt->execute([$desig_id, $serialized_json]);
            }

            // Redirect back to profile page to show the new skill
            header("Location: role_profiles.php?designation_id=" . $desig_id . "&msg=skill_added");
            exit();
        }
    }
}

// Fetch all designations for select menu
$desig_stmt = $pdo->query("SELECT id, title FROM designations ORDER BY title ASC");
$designations = $desig_stmt->fetchAll();

$selected_desig_id = isset($_GET['designation_id']) ? (int)$_GET['designation_id'] : 0;
?>

<style>
.add-skill-container {
    background-color: var(--color-surface);
    border-radius: 12px;
    box-shadow: var(--shadow-md);
    border: 1px solid var(--color-border);
    padding: 2.5rem;
    max-width: 750px;
    margin: 1rem auto 3rem auto;
}

.add-skill-title {
    font-family: 'Outfit', sans-serif;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--color-secondary);
    margin-bottom: 0.5rem;
}

.form-group-skill {
    margin-bottom: 1.5rem;
}

.form-label-skill {
    display: block;
    font-weight: 700;
    font-size: 0.85rem;
    color: #475569;
    margin-bottom: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

.form-input-skill {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-size: 0.9rem;
    font-family: inherit;
    font-weight: 500;
    box-sizing: border-box;
    transition: var(--transition-smooth);
    background-color: #f8fafc;
}

.form-input-skill:focus {
    border-color: var(--color-primary);
    background-color: #ffffff;
    box-shadow: 0 0 0 3px var(--color-primary-glow);
    outline: none;
}

.weight-group {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.btn-submit-skill {
    background-color: var(--color-primary);
    color: white;
    font-family: 'Outfit', sans-serif;
    font-weight: 700;
    font-size: 0.95rem;
    padding: 0.85rem 2rem;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    width: 100%;
    transition: var(--transition-smooth);
    box-shadow: var(--shadow-sm);
}

.btn-submit-skill:hover {
    background-color: #155a25;
}
</style>

<div class="add-skill-container">
    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.5rem;">
        <span style="background-color: var(--color-primary-light); color: var(--color-primary); width: 36px; height: 36px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.25rem;">+</span>
        <div>
            <h2 class="add-skill-title">Add Skill / Role Component</h2>
            <p style="margin: 0; color: var(--color-text-muted); font-size: 0.85rem;">Append custom competency attributes and technical weights to any designation profile.</p>
        </div>
    </div>

    <?php if (!empty($msg)): ?>
        <div class="alert alert-<?= $msg_type ?>" style="padding: 0.75rem 1.25rem; border-radius: 8px; font-weight: 600; font-size: 0.875rem; margin-bottom: 1.5rem; background-color: <?= $msg_type === 'success' ? '#d4edda' : '#f8d7da' ?>; color: <?= $msg_type === 'success' ? '#155724' : '#842029' ?>; border: 1px solid <?= $msg_type === 'success' ? '#c3e6cb' : '#f5c2c7' ?>;">
            <?= htmlspecialchars($msg) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="add_role_skill.php">
        <input type="hidden" name="action" value="add_skill">

        <!-- Designation Dropdown -->
        <div class="form-group-skill">
            <label class="form-label-skill" for="designation_id">Select Target Designation</label>
            <select name="designation_id" id="designation_id" class="form-input-skill" required>
                <option value="" disabled <?= $selected_desig_id === 0 ? 'selected' : '' ?>>-- Choose Designation --</option>
                <?php foreach ($designations as $d): ?>
                    <option value="<?= $d['id'] ?>" <?= $d['id'] === $selected_desig_id ? 'selected' : '' ?>><?= htmlspecialchars($d['title']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Category Information -->
        <div class="weight-group">
            <div class="form-group-skill">
                <label class="form-label-skill" for="category_name">Attribute Category Name</label>
                <input type="text" name="category_name" id="category_name" class="form-input-skill" placeholder="e.g. Strategic Alignment" required>
            </div>
            
            <div class="form-group-skill">
                <label class="form-label-skill" for="category_weight">Category Weightage (%)</label>
                <input type="number" name="category_weight" id="category_weight" class="form-input-skill" min="1" max="100" placeholder="e.g. 20" required>
            </div>
        </div>

        <!-- Technical Skill Information -->
        <div class="weight-group">
            <div class="form-group-skill" style="grid-column: span 1;">
                <label class="form-label-skill" for="skill_desc">Technical Skill / Attribute Description</label>
                <input type="text" name="skill_desc" id="skill_desc" class="form-input-skill" placeholder="e.g. Review software licenses monthly" required>
            </div>
            
            <div class="form-group-skill" style="grid-column: span 1;">
                <label class="form-label-skill" for="skill_weight">Skill Weightage (%)</label>
                <input type="number" name="skill_weight" id="skill_weight" class="form-input-skill" min="1" max="100" placeholder="e.g. 5" required>
            </div>
        </div>

        <!-- Target Assessment Metric -->
        <div class="form-group-skill">
            <label class="form-label-skill" for="assessment_desc">Target Assessment Metric (Optional)</label>
            <input type="text" name="assessment_desc" id="assessment_desc" class="form-input-skill" placeholder="e.g. Software Cost Reduction ≥ 10%">
        </div>

        <button type="submit" class="btn-submit-skill">⚡ Add Skill Component to Profile</button>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>
