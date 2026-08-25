<?php
/**
 * get_class_subjects.php
 * AJAX endpoint — returns JSON array of subjects assigned to a class.
 * Used by manage_results.php to populate the subject dropdown.
 */
require_once 'auth_check.php';

require_once 'includes/permission_helper.php';
requirePermission('results');
header('Content-Type: application/json');

$class_id = intval($_GET['class_id'] ?? 0);

if (!$class_id) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("
    SELECT s.id, s.subject_code, s.subject_name, s.category,
           cs.teacher_id, st.first_name as teacher_first, st.last_name as teacher_last
    FROM class_subjects cs
    JOIN subjects s ON s.id = cs.subject_id
    LEFT JOIN staff st ON st.id = cs.teacher_id
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
        'teacher_name' => $row['teacher_id'] ? trim($row['teacher_first'].' '.$row['teacher_last']) : null,
    ];
}

echo json_encode($subjects);
