<?php
$db_file = __DIR__ . '/database/pakka_dash.sqlite';
if (file_exists($db_file)) {
    unlink($db_file); // recreate fresh
}

try {
    $pdo = new PDO("sqlite:" . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "
    CREATE TABLE IF NOT EXISTS designations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL UNIQUE,
        description TEXT
    );

    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        password_hash TEXT NOT NULL,
        system_role TEXT NOT NULL DEFAULT 'Utpadak',
        designation_id INTEGER,
        FOREIGN KEY (designation_id) REFERENCES designations(id) ON DELETE SET NULL
    );

    CREATE TABLE IF NOT EXISTS tasks (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        description TEXT,
        status TEXT DEFAULT 'Not Started',
        priority TEXT DEFAULT 'Medium',
        due_date TEXT,
        assigned_to INTEGER,
        created_by INTEGER,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
    );

    CREATE TABLE IF NOT EXISTS role_profiles (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        designation_id INTEGER NOT NULL UNIQUE,
        profile_text TEXT,
        FOREIGN KEY (designation_id) REFERENCES designations(id) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS assessments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        designation_id INTEGER NOT NULL,
        fiscal_year TEXT NOT NULL,
        status TEXT DEFAULT 'Not Started',
        assessor_id INTEGER,
        assessment_data TEXT,
        FOREIGN KEY (designation_id) REFERENCES designations(id) ON DELETE CASCADE,
        FOREIGN KEY (assessor_id) REFERENCES users(id) ON DELETE SET NULL,
        UNIQUE (designation_id, fiscal_year)
    );

    CREATE TABLE IF NOT EXISTS personal_skills (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        designation_id INTEGER NOT NULL,
        skill_name TEXT NOT NULL,
        attribute_desc TEXT NOT NULL,
        status TEXT DEFAULT 'Pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (designation_id) REFERENCES designations(id) ON DELETE CASCADE
    );

    INSERT OR IGNORE INTO designations (id, title) VALUES 
    (1, 'IT Head'),
    (2, 'Paper Sales Head'),
    (3, 'Projects Head'),
    (6, 'Global Lead'),
    (7, 'Finance Head'),
    (8, 'Plant Head'),
    (9, 'Commercial Head'),
    (10, 'Brand and Marketing Head'),
    (11, 'Corporate Affairs Head'),
    (12, 'Food Services Lead'),
    (13, 'People and Culture Lead');

    INSERT OR IGNORE INTO users (id, name, email, password_hash, system_role, designation_id) VALUES 
    (1, 'Admin Sewak', 'sewak@pakka.com', '\$2y\$10\$wJZeGOPkge4xpu/kY4ClfObHQ9/k/LkWXflQzQK2sHUZ/OyxPfAVi', 'Sewak', 1),
    (2, 'Lead Sangrakshak', 'lead@pakka.com', '\$2y\$10\$wJZeGOPkge4xpu/kY4ClfObHQ9/k/LkWXflQzQK2sHUZ/OyxPfAVi', 'Sangrakshak', 10),
    (3, 'Member Utpadak', 'member@pakka.com', '\$2y\$10\$wJZeGOPkge4xpu/kY4ClfObHQ9/k/LkWXflQzQK2sHUZ/OyxPfAVi', 'Utpadak', 13);

    INSERT OR IGNORE INTO tasks (title, description, status, priority, due_date, assigned_to, created_by) VALUES 
    ('Setup Portal DB', 'Initialize the database.', 'Completed', 'High', '2026-06-01', 1, 1),
    ('Review Marketing Assets', 'Review new branding guidelines.', 'In Progress', 'Medium', '2026-06-15', 2, 1),
    ('Update Employee Handbook', 'Draft updates.', 'Not Started', 'Low', '2026-06-20', 3, 1);
    ";

    $pdo->exec($sql);

    // Read and seed structured profiles from parsed_profiles.json
    $profiles_json_file = __DIR__ . '/parsed_profiles.json';
    if (file_exists($profiles_json_file)) {
        $profiles_data = json_decode(file_get_contents($profiles_json_file), true);
        if ($profiles_data) {
            $stmt = $pdo->prepare("INSERT OR REPLACE INTO role_profiles (designation_id, profile_text) VALUES (?, ?)");
            foreach ($profiles_data as $desig_id => $profile) {
                // Ensure designation exists in designations table
                $check = $pdo->prepare("SELECT id FROM designations WHERE id = ?");
                $check->execute([$desig_id]);
                if (!$check->fetch()) {
                    // Extract designation name from title
                    $title = $profile['title'] ?? 'Designation ' . $desig_id;
                    $title = preg_replace('/^Role Profile\s*[—–-]\s*/i', '', $title);
                    $title = ucwords(strtolower(trim($title)));
                    $insert_desig = $pdo->prepare("INSERT OR IGNORE INTO designations (id, title) VALUES (?, ?)");
                    $insert_desig->execute([$desig_id, $title]);
                }
                
                // Insert JSON serialized profile
                $stmt->execute([$desig_id, json_encode($profile, JSON_UNESCAPED_UNICODE)]);
            }
            echo "Role profiles successfully seeded from parsed_profiles.json.\n";
        }
    } else {
        // Fallback simple profiles if json not found
        $pdo->exec("
            INSERT OR IGNORE INTO role_profiles (designation_id, profile_text) VALUES 
            (1, '{\"designation_id\":1,\"title\":\"Role Profile — IT HEAD\",\"mission\":\"Enable business and all stakeholders to have digital driven decision making and create transparency with the partners and customers.\",\"outcomes\":[\"End to end product & customer transparency\",\"Complete data driven automated decision making\"],\"skills\":[]}'),
            (8, '{\"designation_id\":8,\"title\":\"Role Profile — PLANT HEAD\",\"mission\":\"Establish Systems for sustainable improvement across Operations to achieve best in class performance.\",\"outcomes\":[],\"skills\":[]}'),
            (10, '{\"designation_id\":10,\"title\":\"Role Profile — BRAND AND MARKETING HEAD\",\"mission\":\"Maximize brand value, lead generation, and corporate presence through creative campaigns.\",\"outcomes\":[],\"skills\":[]}')
        ");
    }

    // Now insert assessments
    $pdo->exec("
        INSERT OR IGNORE INTO assessments (designation_id, fiscal_year, status, assessor_id) VALUES 
        (1, 'FY 2026-27', 'Not Started', 1),
        (8, 'FY 2026-27', 'In Progress', 1),
        (10, 'FY 2026-27', 'Completed', 1);
    ");

    echo "SQLite database and sample data created successfully.\n";


} catch(PDOException $e) {
    die("Setup failed: " . $e->getMessage());
}
?>
