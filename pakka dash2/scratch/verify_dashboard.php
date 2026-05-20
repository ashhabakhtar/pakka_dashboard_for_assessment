<?php
// scratch/verify_dashboard.php
require_once 'includes/db.php';

// Dynamic Competency Gap & Skill Analytics calculations
$rp_stmt = $pdo->query("SELECT profile_text FROM role_profiles");
$all_profiles = $rp_stmt->fetchAll(PDO::FETCH_COLUMN);

$categories_set = [];
$total_metrics_count = 0;
foreach ($all_profiles as $p_text) {
    if (empty($p_text)) continue;
    $profile_obj = json_decode($p_text, true);
    if (isset($profile_obj['skills'])) {
        foreach ($profile_obj['skills'] as $s) {
            if (isset($s['category'])) {
                $categories_set[trim(strtolower($s['category']))] = true;
            }
            if (isset($s['attributes'])) {
                $total_metrics_count += count($s['attributes']);
            }
        }
    }
}
$core_competencies_count = count($categories_set);

echo "Core Competencies Count: " . $core_competencies_count . "\n";
echo "Evaluated Metrics Count: " . $total_metrics_count . "\n";

// Fetch assessments for competency calculations
$ass_stmt = $pdo->query("SELECT a.*, d.title FROM assessments a JOIN designations d ON a.designation_id = d.id");
$assessments_list = $ass_stmt->fetchAll(PDO::FETCH_ASSOC);

$category_ratings_sum = [];
$category_ratings_count = [];
$default_categories = [
    'Process & Quality Excellence' => 3.8,
    'Manufacturing Excellence' => 3.5,
    'Strategic Alignment' => 3.2,
    'Resource & Budget Coordination' => 2.5
];

foreach ($assessments_list as $a) {
    $desig_id = (int)$a['designation_id'];
    
    $p_stmt = $pdo->prepare("SELECT profile_text FROM role_profiles WHERE designation_id = ?");
    $p_stmt->execute([$desig_id]);
    $profile_rec = $p_stmt->fetch();
    if (!$profile_rec || empty($profile_rec['profile_text'])) continue;
    
    $p_data = json_decode($profile_rec['profile_text'], true);
    if (empty($p_data['skills'])) continue;
    
    $ass_data = null;
    if (!empty($a['assessment_data'])) {
        $ass_data = json_decode($a['assessment_data'], true);
    }
    
    // Simulate scores for seeded Completed or In Progress assessments if data doesn't exist yet
    if (empty($ass_data) && ($a['status'] === 'Completed' || $a['status'] === 'In Progress')) {
        $ass_data = ['leader_rating' => []];
        foreach ($p_data['skills'] as $s) {
            $sn = $s['sn'];
            $ass_data['leader_rating'][$sn] = [];
            foreach ($s['attributes'] as $attr_index => $attr) {
                $seed = ($desig_id * 7 + $sn * 13 + $attr_index * 17) % 5;
                $simulated_rating = 2.5 + ($seed * 0.5);
                $ass_data['leader_rating'][$sn][$attr_index] = $simulated_rating;
            }
        }
    }
    
    if (!empty($ass_data['leader_rating'])) {
        foreach ($p_data['skills'] as $s) {
            $sn = $s['sn'];
            $cat_name = trim($s['category']);
            if (empty($cat_name)) continue;
            
            if (isset($ass_data['leader_rating'][$sn])) {
                foreach ($s['attributes'] as $attr_index => $attr) {
                    $rating_val = $ass_data['leader_rating'][$sn][$attr_index] ?? '-';
                    if ($rating_val !== '-' && $rating_val !== '') {
                        $r_num = (float)$rating_val;
                        if (!isset($category_ratings_sum[$cat_name])) {
                            $category_ratings_sum[$cat_name] = 0;
                            $category_ratings_count[$cat_name] = 0;
                        }
                        $category_ratings_sum[$cat_name] += $r_num;
                        $category_ratings_count[$cat_name]++;
                    }
                }
            }
        }
    }
}

$category_scores = [];
foreach ($category_ratings_sum as $cat_name => $sum) {
    $count = $category_ratings_count[$cat_name];
    if ($count > 0) {
        $category_scores[$cat_name] = round($sum / $count, 2);
    }
}

if (empty($category_scores)) {
    $category_scores = $default_categories;
}

$total_match_rate_sum = 0;
$total_match_rate_count = 0;
foreach ($category_scores as $cat_name => $score) {
    $total_match_rate_sum += ($score / 4.0) * 100;
    $total_match_rate_count++;
}
$overall_match_rate = $total_match_rate_count > 0 ? round($total_match_rate_sum / $total_match_rate_count, 1) : 80.0;
$overall_match_rate = min(100.0, max(0.0, $overall_match_rate));

echo "Overall Match Rate: " . $overall_match_rate . "%\n";
echo "Top Calculated Categories:\n";
foreach ($category_scores as $name => $score) {
    $rate = min(100, max(0, round(($score / 4.0) * 100)));
    echo " - " . $name . ": " . $score . " / 4.0 (" . $rate . "%)\n";
}
?>
