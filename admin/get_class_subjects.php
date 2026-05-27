<?php
/**
 * get_class_subjects.php
 * AJAX endpoint — returns JSON array of subjects assigned to a class.
 * Used by manage_results.php to populate the subject dropdown.
 */
require_once 'auth_check.php';

header('Content-Type: application/json');

$class_id = intval($_GET['class_id'] ?? 0);

if (!$class_id) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("
    SELECT s.id, s.subject_code, s.subject_name, s.category
    FROM class_subjects cs
    JOIN subjects s ON s.id = cs.subject_id
    WHERE cs.class_id = ?
      AND s.is_active = 1
    ORDER BY s.subject_name
");
$stmt->bind_param("i", $class_id);
$stmt->execute();
$result = $stmt->get_result();

$subjects = [];
while ($row = $result->fetch_assoc()) {
    $subjects[] = [
        'id'           => $row['id'],
        'subject_code' => $row['subject_code'],
        'subject_name' => $row['subject_name'],
        'category'     => $row['category'],
    ];
}

echo json_encode($subjects);
