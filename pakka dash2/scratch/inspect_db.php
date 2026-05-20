<?php
$pdo = new PDO('sqlite:database/pakka_dash.sqlite');
$stmt = $pdo->query('SELECT id, designation_id, fiscal_year, status, assessment_data FROM assessments');
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($results, JSON_PRETTY_PRINT);
?>
