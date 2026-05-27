<?php
require_once 'auth_check.php';
$page_title = "Manage Students";

// Handle delete
if (isset($_GET['delete']) && hasPermission('admin')) {
    $del_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
    $stmt->bind_param("i", $del_id);
    if ($stmt->execute()) {
        logActivity('delete_student', 'Deleted student ID: ' . $del_id);
        $success = "Student deleted successfully.";
    }
}

// Filters
$class_filter = isset($_GET['class']) ? intval($_GET['class']) : 0;
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$where = "1=1";
$params = [];
$types = '';

if ($class_filter) { $where .= " AND s.class_id = ?"; $params[] = $class_filter; $types .= 'i'; }
if ($status_filter) { $where .= " AND s.status = ?"; $params[] = $status_filter; $types .= 's'; }
if ($search) { $where .= " AND (s.first_name LIKE ? OR s.last_name LIKE ? OR s.student_id LIKE ?)"; $s = "%$search%"; $params[] = $s; $params[] = $s; $params[] = $s; $types .= 'sss'; }

$sql = "SELECT s.*, c.class_name, c.arm FROM students s JOIN classes c ON s.class_id = c.id WHERE $where ORDER BY s.created_at DESC";
$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$students = $stmt->get_result();

$classes = $conn->query("SELECT id, class_name, arm FROM classes ORDER BY class_name, arm");
$total_students = $conn->query("SELECT COUNT(*) as c FROM students")->fetch_assoc()['c'];
$active_students = $conn->query("SELECT COUNT(*) as c FROM students WHERE status='Active'")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html class="scroll-smooth" lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> | G.O.L.A</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { primary: "#0A2E4D", gold: "#C5A059" }, fontFamily: { sans: ["Inter", "sans-serif"] } } } };
    </script>
    <style>.sidebar-link.active{background:linear-gradient(90deg,rgba(197,160,89,0.1) 0%,transparent 100%);border-left:3px solid #C5A059;color:#C5A059;}</style>
</head>
<body class="bg-slate-50 font-sans">
<div class="flex h-screen overflow-hidden">
    <?php include 'admin_sidebar.php'; ?>
    <div class="flex-1 flex flex-col overflow-hidden">
        <?php include 'admin_topbar.php'; ?>
        <main class="flex-1 overflow-y-auto p-8">
            
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-slate-900 mb-1">Student Management</h1>
                    <p class="text-slate-600"><?php echo $total_students; ?> total students &middot; <?php echo $active_students; ?> active</p>
                </div>
                <a href="add_student.php" class="inline-flex items-center gap-2 bg-gold text-primary px-5 py-3 rounded-lg font-semibold hover:bg-gold/90 transition-all">
                    <span class="material-symbols-outlined">person_add</span>
                    Register Student
                </a>
            </div>

            <?php if (isset($success)): ?>
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm"><?php echo $success; ?></div>
            <?php endif; ?>

            <!-- Filters -->
            <form method="GET" class="bg-white rounded-xl p-4 border border-slate-200 mb-6">
                <div class="flex flex-wrap gap-4 items-end">
                    <div class="flex-1 min-w-[200px]">
                        <label class="text-xs font-semibold text-slate-600 mb-1 block">Search</label>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Name or ID..." class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-gold focus:border-gold">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-600 mb-1 block">Class</label>
                        <select name="class" class="border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-gold focus:border-gold">
                            <option value="">All Classes</option>
                            <?php $classes->data_seek(0); while ($c = $classes->fetch_assoc()): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo $class_filter == $c['id'] ? 'selected' : ''; ?>><?php echo $c['class_name'] . ' ' . $c['arm']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-600 mb-1 block">Status</label>
                        <select name="status" class="border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-gold focus:border-gold">
                            <option value="">All</option>
                            <option value="Active" <?php echo $status_filter=='Active'?'selected':''; ?>>Active</option>
                            <option value="Graduated" <?php echo $status_filter=='Graduated'?'selected':''; ?>>Graduated</option>
                            <option value="Withdrawn" <?php echo $status_filter=='Withdrawn'?'selected':''; ?>>Withdrawn</option>
                            <option value="Suspended" <?php echo $status_filter=='Suspended'?'selected':''; ?>>Suspended</option>
                        </select>
                    </div>
                    <button type="submit" class="bg-primary text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-primary/90">Filter</button>
                    <a href="manage_students.php" class="text-sm text-slate-500 hover:text-gold">Reset</a>
                </div>
            </form>

            <!-- Students Table -->
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Student</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Reg. Number</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Class</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Gender</th>
                                <th class="px-4 py-3 text-center font-semibold text-slate-600">Status</th>
                                <th class="px-4 py-3 text-center font-semibold text-slate-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if ($students->num_rows == 0): ?>
                            <tr><td colspan="6" class="px-4 py-12 text-center text-slate-500">No students found. <a href="add_student.php" class="text-gold font-semibold">Register one now</a>.</td></tr>
                            <?php endif; ?>
                            <?php while ($st = $students->fetch_assoc()):
                                $status_colors = ['Active'=>'bg-green-100 text-green-700','Graduated'=>'bg-blue-100 text-blue-700','Withdrawn'=>'bg-yellow-100 text-yellow-700','Suspended'=>'bg-red-100 text-red-700'];
                                $sc = $status_colors[$st['status']] ?? 'bg-slate-100 text-slate-700';
                            ?>
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 bg-gold/10 rounded-full flex items-center justify-center text-gold font-bold text-xs">
                                            <?php echo strtoupper(substr($st['first_name'],0,1).substr($st['last_name'],0,1)); ?>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-slate-900"><?php echo htmlspecialchars($st['first_name'].' '.$st['last_name']); ?></p>
                                            <p class="text-xs text-slate-500"><?php echo $st['email'] ?? ''; ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-slate-700"><?php echo htmlspecialchars($st['student_id']); ?></td>
                                <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars($st['class_name'].' '.$st['arm']); ?></td>
                                <td class="px-4 py-3 text-slate-700"><?php echo $st['gender']; ?></td>
                                <td class="px-4 py-3 text-center"><span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $sc; ?>"><?php echo $st['status']; ?></span></td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex justify-center gap-1">
                                        <a href="edit_student.php?id=<?php echo $st['id']; ?>" class="p-1.5 hover:bg-blue-50 rounded text-blue-600" title="Edit"><span class="material-symbols-outlined text-lg">edit</span></a>
                                        <a href="view_student.php?id=<?php echo $st['id']; ?>" class="p-1.5 hover:bg-slate-50 rounded text-slate-600" title="View"><span class="material-symbols-outlined text-lg">visibility</span></a>
                                        <?php if (hasPermission('admin')): ?>
                                        <a href="manage_students.php?delete=<?php echo $st['id']; ?>" class="p-1.5 hover:bg-red-50 rounded text-red-600" title="Delete" onclick="return confirm('Are you sure? This cannot be undone.')"><span class="material-symbols-outlined text-lg">delete</span></a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>
</div>
</body>
</html>
