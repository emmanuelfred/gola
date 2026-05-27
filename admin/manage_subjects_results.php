<?php
require_once 'auth_check.php';
$page_title = "Manage Result Subjects";

$success = '';
$error   = '';

// ── Handle actions ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    if ($_POST['action'] === 'add') {
        $code     = strtoupper(trim($_POST['subject_code'] ?? ''));
        $name     = trim($_POST['subject_name'] ?? '');
        $category = $_POST['category'] ?? 'Core';
        
        if (!$code || !$name) {
            $error = 'Subject code and name are required.';
        } else {
            $stmt = $conn->prepare("INSERT INTO subjects (subject_code, subject_name, category) VALUES (?,?,?)");
            $stmt->bind_param("sss", $code, $name, $category);
            if ($stmt->execute()) {
                logActivity('add_result_subject', "Added subject: $name ($code)");
                $success = "Subject <strong>$name</strong> added.";
            } else {
                $error = 'Error: '.$conn->error.' (Code may already exist.)';
            }
        }
    }
    
    if ($_POST['action'] === 'toggle') {
        $id = intval($_POST['id'] ?? 0);
        $conn->query("UPDATE subjects SET is_active = NOT is_active WHERE id=$id");
        $success = 'Subject status toggled.';
    }
    
    if ($_POST['action'] === 'delete' && hasPermission('admin')) {
        $id = intval($_POST['id'] ?? 0);
        $check = $conn->query("SELECT COUNT(*) as c FROM results WHERE subject_id=$id")->fetch_assoc();
        if ($check['c'] > 0) {
            $error = "Cannot delete — this subject has {$check['c']} result records.";
        } else {
            $conn->query("DELETE FROM subjects WHERE id=$id");
            $success = 'Subject deleted.';
        }
    }
    
    if ($_POST['action'] === 'edit') {
        $id       = intval($_POST['id'] ?? 0);
        $name     = trim($_POST['subject_name'] ?? '');
        $category = $_POST['category'] ?? 'Core';
        if ($name && $id) {
            $stmt = $conn->prepare("UPDATE subjects SET subject_name=?, category=? WHERE id=?");
            $stmt->bind_param("ssi", $name, $category, $id);
            $stmt->execute();
            $success = 'Subject updated.';
        }
    }
}

// ── Fetch subjects ─────────────────────────────────────────────────────────────
$filter_cat = $_GET['cat'] ?? '';
$where = $filter_cat ? "WHERE category = '".$conn->real_escape_string($filter_cat)."'" : '';
$subjects = $conn->query("SELECT s.*, 
    (SELECT COUNT(*) FROM results r WHERE r.subject_id=s.id) as result_count
    FROM subjects s $where ORDER BY category, subject_name")->fetch_all(MYSQLI_ASSOC);

$cat_counts = $conn->query("SELECT category, COUNT(*) as c FROM subjects GROUP BY category")->fetch_all(MYSQLI_ASSOC);
$counts = [];
foreach ($cat_counts as $cc) $counts[$cc['category']] = $cc['c'];
$total = array_sum($counts);
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $page_title; ?> | G.O.L.A Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
<script src="https://cdn.tailwindcss.com?plugins=forms"></script>
<script>tailwind.config={theme:{extend:{colors:{primary:"#0A2E4D",gold:"#C5A059"},fontFamily:{sans:["Inter","sans-serif"]}}}};</script>
<style>
.sidebar-link.active{background:linear-gradient(90deg,rgba(197,160,89,.1) 0%,transparent 100%);border-left:3px solid #C5A059;color:#C5A059;}
</style>
</head>
<body class="bg-slate-50 font-sans">
<div class="flex h-screen overflow-hidden">
<?php include 'admin_sidebar.php'; ?>
<div class="flex-1 flex flex-col overflow-hidden">
<?php include 'admin_topbar.php'; ?>
<main class="flex-1 overflow-y-auto p-6 lg:p-8">

<div class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Result Subjects</h1>
        <p class="text-slate-500 text-sm mt-1">Subjects available in the result/assessment system. These appear when entering scores.</p>
    </div>
    <button onclick="document.getElementById('addModal').classList.remove('hidden')"
        class="inline-flex items-center gap-2 bg-gold text-primary px-5 py-3 rounded-xl font-bold hover:bg-gold/90 shadow-sm">
        <span class="material-symbols-outlined">add_circle</span>Add Subject
    </button>
</div>

<?php if ($success): ?>
<div class="mb-5 p-4 bg-green-50 border border-green-200 rounded-xl flex gap-3 items-start">
    <span class="material-symbols-outlined text-green-600">check_circle</span>
    <p class="text-green-800 text-sm"><?php echo $success; ?></p>
</div>
<?php endif; ?>
<?php if ($error): ?>
<div class="mb-5 p-4 bg-red-50 border border-red-200 rounded-xl flex gap-3 items-start">
    <span class="material-symbols-outlined text-red-600">error</span>
    <p class="text-red-800 text-sm"><?php echo $error; ?></p>
</div>
<?php endif; ?>

<!-- Stats -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <?php
    $stat_cats = ['All'=>$total,'Core'=>$counts['Core']??0,'Elective'=>$counts['Elective']??0,'Vocational'=>$counts['Vocational']??0];
    $stat_colors = ['All'=>'text-primary','Core'=>'text-blue-600','Elective'=>'text-amber-600','Vocational'=>'text-green-600'];
    foreach ($stat_cats as $lbl => $num):
    ?>
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <p class="text-2xl font-black <?php echo $stat_colors[$lbl]; ?>"><?php echo $num; ?></p>
        <p class="text-xs text-slate-500 font-semibold uppercase mt-1"><?php echo $lbl; ?> Subjects</p>
    </div>
    <?php endforeach; ?>
</div>

<!-- Filter tabs -->
<div class="flex gap-2 mb-5 flex-wrap">
    <?php foreach ([''=>'All','Core'=>'Core','Elective'=>'Elective','Vocational'=>'Vocational'] as $k=>$v): ?>
    <a href="?cat=<?php echo $k; ?>" class="px-4 py-2 rounded-lg text-sm font-semibold transition-all
        <?php echo $filter_cat===$k ? 'bg-primary text-white' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50'; ?>">
        <?php echo $v; ?>
        <?php if ($k==='' && $total): ?>(<?php echo $total; ?>)<?php endif; ?>
        <?php if ($k!=='' && isset($counts[$k])): ?>(<?php echo $counts[$k]; ?>)<?php endif; ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- Subject Table -->
<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Code</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Subject Name</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 uppercase">Category</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 uppercase">Results</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 uppercase">Status</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($subjects)): ?>
                <tr><td colspan="6" class="px-4 py-12 text-center text-slate-400">
                    <span class="material-symbols-outlined text-4xl block mb-2">menu_book</span>
                    No subjects found. Click "Add Subject" to begin.
                </td></tr>
                <?php else: ?>
                <?php foreach ($subjects as $sub):
                    $cat_colors = [
                        'Core'      => 'bg-blue-100 text-blue-700',
                        'Elective'  => 'bg-amber-100 text-amber-700',
                        'Vocational'=> 'bg-green-100 text-green-700',
                    ];
                    $cat_color = $cat_colors[$sub['category']] ?? 'bg-slate-100 text-slate-700';
                ?>
                <tr class="hover:bg-slate-50 transition-colors" id="row-<?php echo $sub['id']; ?>">
                    <td class="px-4 py-3 font-mono font-bold text-primary text-xs">
                        <?php echo htmlspecialchars($sub['subject_code']); ?>
                    </td>
                    <td class="px-4 py-3">
                        <span class="view-name font-semibold text-slate-800"><?php echo htmlspecialchars($sub['subject_name']); ?></span>
                        <div class="edit-name hidden">
                            <form method="POST" class="flex gap-2 items-center">
                                <input type="hidden" name="action" value="edit">
                                <input type="hidden" name="id"     value="<?php echo $sub['id']; ?>">
                                <input type="text" name="subject_name" value="<?php echo htmlspecialchars($sub['subject_name']); ?>"
                                    class="border-slate-200 rounded-lg text-sm py-1 focus:ring-gold focus:border-gold w-48">
                                <select name="category" class="border-slate-200 rounded-lg text-sm py-1 focus:ring-gold focus:border-gold">
                                    <?php foreach (['Core','Elective','Vocational'] as $cat): ?>
                                    <option <?php echo $sub['category']===$cat?'selected':''; ?>><?php echo $cat; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="px-2 py-1 bg-green-600 text-white text-xs rounded font-semibold">Save</button>
                                <button type="button" onclick="cancelEdit(<?php echo $sub['id']; ?>)" class="px-2 py-1 bg-slate-200 text-slate-700 text-xs rounded">Cancel</button>
                            </form>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $cat_color; ?>">
                            <?php echo htmlspecialchars($sub['category']); ?>
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <?php if ($sub['result_count'] > 0): ?>
                        <span class="px-2 py-1 bg-primary/5 text-primary text-xs font-semibold rounded-full"><?php echo $sub['result_count']; ?></span>
                        <?php else: ?>
                        <span class="text-slate-300 text-xs">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <?php if (($sub['is_active'] ?? 1)): ?>
                        <span class="px-2 py-1 bg-green-100 text-green-700 text-xs font-semibold rounded-full">Active</span>
                        <?php else: ?>
                        <span class="px-2 py-1 bg-slate-100 text-slate-500 text-xs font-semibold rounded-full">Hidden</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-1">
                            <button onclick="startEdit(<?php echo $sub['id']; ?>)"
                                class="p-1.5 hover:bg-slate-100 text-slate-500 rounded-lg transition-all" title="Edit">
                                <span class="material-symbols-outlined text-sm">edit</span>
                            </button>
                            <form method="POST" class="inline">
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="id"     value="<?php echo $sub['id']; ?>">
                                <button type="submit" class="p-1.5 hover:bg-slate-100 text-slate-500 rounded-lg transition-all" title="Toggle Active">
                                    <span class="material-symbols-outlined text-sm"><?php echo ($sub['is_active']??1) ? 'visibility_off' : 'visibility'; ?></span>
                                </button>
                            </form>
                            <?php if ($sub['result_count'] == 0): ?>
                            <form method="POST" class="inline" onsubmit="return confirm('Delete subject <?php echo htmlspecialchars($sub['subject_name']); ?>?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id"     value="<?php echo $sub['id']; ?>">
                                <button type="submit" class="p-1.5 hover:bg-red-50 text-red-500 rounded-lg transition-all" title="Delete">
                                    <span class="material-symbols-outlined text-sm">delete</span>
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</main>
</div>
</div>

<!-- Add Subject Modal -->
<div id="addModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
<div class="bg-white rounded-2xl p-8 max-w-md w-full shadow-2xl">
    <h2 class="text-xl font-bold text-slate-900 mb-5 flex items-center gap-2">
        <span class="material-symbols-outlined text-gold">menu_book</span>
        Add Result Subject
    </h2>
    <form method="POST" class="space-y-4">
        <input type="hidden" name="action" value="add">
        <div>
            <label class="text-xs font-semibold text-slate-600 mb-1 block">
                Subject Code <span class="text-red-500">*</span>
            </label>
            <input type="text" name="subject_code" required maxlength="10" placeholder="e.g. MTH, ENG, PHY"
                class="w-full border-slate-200 rounded-xl text-sm uppercase font-mono focus:ring-gold focus:border-gold">
            <p class="text-xs text-slate-400 mt-1">Short unique code — max 10 characters, auto-uppercased.</p>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 mb-1 block">
                Subject Name <span class="text-red-500">*</span>
            </label>
            <input type="text" name="subject_name" required placeholder="e.g. Mathematics, English Language"
                class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 mb-1 block">Category</label>
            <select name="category" class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold">
                <option value="Core">Core (compulsory for all)</option>
                <option value="Elective">Elective (optional)</option>
                <option value="Vocational">Vocational (hands-on)</option>
            </select>
        </div>
        <div class="flex gap-3 pt-2">
            <button type="submit" class="flex-1 bg-gold text-primary py-3 rounded-xl font-bold hover:bg-gold/90">Add Subject</button>
            <button type="button" onclick="document.getElementById('addModal').classList.add('hidden')"
                class="flex-1 bg-slate-100 text-slate-700 py-3 rounded-xl font-semibold hover:bg-slate-200">Cancel</button>
        </div>
    </form>
</div>
</div>

<script>
function startEdit(id) {
    document.querySelector('#row-'+id+' .view-name').classList.add('hidden');
    document.querySelector('#row-'+id+' .edit-name').classList.remove('hidden');
}
function cancelEdit(id) {
    document.querySelector('#row-'+id+' .view-name').classList.remove('hidden');
    document.querySelector('#row-'+id+' .edit-name').classList.add('hidden');
}
document.getElementById('addModal').addEventListener('click', function(e) {
    if (e.target===this) this.classList.add('hidden');
});
</script>
</body>
</html>
