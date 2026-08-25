<?php
require_once 'auth_check.php';
require_once 'includes/permission_helper.php';
requirePermission('inventory');
require_once 'includes/inventory_helper.php';
$page_title = "Inventory";

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'add_item') {
        $name = trim($_POST['name'] ?? '');
        $category_id = intval($_POST['category_id'] ?? 0);
        $quantity = max(1, intval($_POST['quantity'] ?? 1));
        $unit_value = $_POST['unit_value'] !== '' ? floatval($_POST['unit_value']) : null;
        $location = trim($_POST['location'] ?? '') ?: null;
        $condition = in_array($_POST['condition_status'] ?? '', ['New','Good','Fair','Poor','Damaged']) ? $_POST['condition_status'] : 'Good';
        $serial = trim($_POST['serial_number'] ?? '') ?: null;
        $date_acquired = $_POST['date_acquired'] ?: null;
        $notes = trim($_POST['notes'] ?? '') ?: null;

        if (!$name || !$category_id) {
            $error = 'Item name and category are required.';
        } else {
            $stmt = $conn->prepare("INSERT INTO inventory_items (name, category_id, quantity, unit_value, location, condition_status, serial_number, date_acquired, notes) VALUES (?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("siidsssss", $name, $category_id, $quantity, $unit_value, $location, $condition, $serial, $date_acquired, $notes);
            if ($stmt->execute()) {
                logActivity('add_inventory_item', "Added inventory item: $name (qty $quantity)");
                $success = "<strong>" . htmlspecialchars($name) . "</strong> added to inventory.";
            } else {
                $error = 'Failed: ' . $conn->error;
            }
        }
    }

    if ($_POST['action'] === 'edit_item') {
        $id = intval($_POST['item_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $category_id = intval($_POST['category_id'] ?? 0);
        $quantity = max(0, intval($_POST['quantity'] ?? 0));
        $unit_value = $_POST['unit_value'] !== '' ? floatval($_POST['unit_value']) : null;
        $location = trim($_POST['location'] ?? '') ?: null;
        $condition = in_array($_POST['condition_status'] ?? '', ['New','Good','Fair','Poor','Damaged']) ? $_POST['condition_status'] : 'Good';
        $serial = trim($_POST['serial_number'] ?? '') ?: null;
        $notes = trim($_POST['notes'] ?? '') ?: null;

        if (!$id || !$name || !$category_id) {
            $error = 'Item name and category are required.';
        } else {
            $stmt = $conn->prepare("UPDATE inventory_items SET name=?, category_id=?, quantity=?, unit_value=?, location=?, condition_status=?, serial_number=?, notes=? WHERE id=?");
            $stmt->bind_param("siidssssi", $name, $category_id, $quantity, $unit_value, $location, $condition, $serial, $notes, $id);
            $stmt->execute();
            $success = "Item updated.";
        }
    }

    if ($_POST['action'] === 'dispose_item' && hasPermission('admin')) {
        $id = intval($_POST['item_id'] ?? 0);
        $conn->query("UPDATE inventory_items SET is_active=0 WHERE id=$id");
        logActivity('dispose_inventory_item', "Marked inventory item ID $id as disposed");
        $success = "Item marked as disposed.";
    }

    if ($_POST['action'] === 'add_category') {
        $name = trim($_POST['name'] ?? '');
        if ($name) {
            $stmt = $conn->prepare("INSERT INTO inventory_categories (name) VALUES (?)");
            $stmt->bind_param("s", $name);
            if ($stmt->execute()) { $success = "Category added."; } else { $error = "That category may already exist."; }
        }
    }
}

$search = trim($_GET['search'] ?? '');
$category_filter = intval($_GET['category_id'] ?? 0);
$categories = getInventoryCategories($conn);
$items = getInventoryItems($conn, $search, $category_filter);
$counts = getInventoryCounts($conn);
$total_value = getInventoryTotalValue($conn);
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
<style>.sidebar-link.active{background:linear-gradient(90deg,rgba(197,160,89,.1) 0%,transparent 100%);border-left:3px solid #C5A059;color:#C5A059;}</style>
</head>
<body class="bg-slate-50 font-sans">
<div class="flex h-screen overflow-hidden">
<?php include 'admin_sidebar.php'; ?>
<div class="flex-1 flex flex-col overflow-hidden">
<?php include 'admin_topbar.php'; ?>
<main class="flex-1 overflow-y-auto p-6 lg:p-8">

<div class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Inventory</h1>
        <p class="text-slate-500 text-sm mt-1">School property — furniture, equipment, and other assets.</p>
    </div>
    <div class="flex items-center gap-2">
        <button onclick="document.getElementById('addCatModal').classList.remove('hidden')"
            class="inline-flex items-center gap-2 bg-white border border-slate-200 px-4 py-2.5 rounded-xl font-semibold text-sm text-slate-700 hover:bg-slate-50">
            <span class="material-symbols-outlined text-sm">category</span>New Category
        </button>
        <button onclick="document.getElementById('addModal').classList.remove('hidden')"
            class="inline-flex items-center gap-2 bg-gold text-primary px-5 py-2.5 rounded-xl font-bold hover:bg-gold/90 shadow-sm">
            <span class="material-symbols-outlined text-sm">add_circle</span>Add Item
        </button>
    </div>
</div>

<?php if ($success): ?>
<div class="mb-5 p-4 bg-green-50 border border-green-200 rounded-xl flex gap-3 items-start">
    <span class="material-symbols-outlined text-green-600 flex-shrink-0">check_circle</span>
    <p class="text-green-800 text-sm"><?php echo $success; ?></p>
</div>
<?php endif; ?>
<?php if ($error): ?>
<div class="mb-5 p-4 bg-red-50 border border-red-200 rounded-xl flex gap-3 items-start">
    <span class="material-symbols-outlined text-red-600 flex-shrink-0">error</span>
    <p class="text-red-800 text-sm"><?php echo htmlspecialchars($error); ?></p>
</div>
<?php endif; ?>

<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-slate-200 p-4"><p class="text-xs font-semibold text-slate-500 uppercase">Item Types</p><p class="text-2xl font-bold text-slate-900 mt-1"><?php echo $counts['item_count']; ?></p></div>
    <div class="bg-white rounded-xl border border-slate-200 p-4"><p class="text-xs font-semibold text-slate-500 uppercase">Total Units</p><p class="text-2xl font-bold text-primary mt-1"><?php echo $counts['total_units']; ?></p></div>
    <div class="bg-white rounded-xl border border-slate-200 p-4"><p class="text-xs font-semibold text-slate-500 uppercase">Needs Attention</p><p class="text-2xl font-bold <?php echo $counts['needs_attention']>0?'text-amber-500':'text-slate-300'; ?> mt-1"><?php echo $counts['needs_attention']; ?></p></div>
    <div class="bg-white rounded-xl border border-slate-200 p-4"><p class="text-xs font-semibold text-slate-500 uppercase">Recorded Value</p><p class="text-2xl font-bold text-slate-700 mt-1">&#8358;<?php echo number_format($total_value,2); ?></p></div>
</div>

<form method="GET" class="flex flex-wrap gap-3 mb-5">
    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search items, location, serial…" class="flex-1 min-w-56 border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold">
    <select name="category_id" onchange="this.form.submit()" class="border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold">
        <option value="0">All Categories</option>
        <?php foreach ($categories as $c): ?>
        <option value="<?php echo $c['id']; ?>" <?php echo $category_filter==$c['id']?'selected':''; ?>><?php echo htmlspecialchars($c['name']); ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="px-4 py-2 bg-primary text-white text-sm font-semibold rounded-lg">Filter</button>
</form>

<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 border-b border-slate-200">
            <tr class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                <th class="px-5 py-3">Item</th>
                <th class="px-5 py-3">Category</th>
                <th class="px-5 py-3">Location</th>
                <th class="px-5 py-3 text-center">Qty</th>
                <th class="px-5 py-3 text-center">Condition</th>
                <th class="px-5 py-3 text-right">Value</th>
                <th class="px-5 py-3 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            <?php if (empty($items)): ?>
            <tr><td colspan="7" class="px-5 py-12 text-center text-slate-400">No items found.</td></tr>
            <?php endif; ?>
            <?php foreach ($items as $it):
                $cond_colors = ['New'=>'bg-green-100 text-green-700','Good'=>'bg-blue-100 text-blue-700','Fair'=>'bg-amber-100 text-amber-700','Poor'=>'bg-orange-100 text-orange-700','Damaged'=>'bg-red-100 text-red-600'];
            ?>
            <tr class="hover:bg-slate-50">
                <td class="px-5 py-3 font-semibold text-slate-800"><?php echo htmlspecialchars($it['name']); ?><?php echo $it['serial_number'] ? '<span class="text-xs text-slate-400 block font-mono">S/N: '.htmlspecialchars($it['serial_number']).'</span>' : ''; ?></td>
                <td class="px-5 py-3 text-slate-500"><?php echo htmlspecialchars($it['category_name']); ?></td>
                <td class="px-5 py-3 text-slate-500"><?php echo htmlspecialchars($it['location'] ?: '—'); ?></td>
                <td class="px-5 py-3 text-center text-slate-700 font-medium"><?php echo $it['quantity']; ?></td>
                <td class="px-5 py-3 text-center"><span class="px-2 py-0.5 <?php echo $cond_colors[$it['condition_status']]; ?> text-xs font-semibold rounded-full"><?php echo $it['condition_status']; ?></span></td>
                <td class="px-5 py-3 text-right text-slate-600"><?php echo $it['unit_value'] !== null ? '&#8358;'.number_format($it['unit_value']*$it['quantity'],2) : '—'; ?></td>
                <td class="px-5 py-3 text-right whitespace-nowrap">
                    <button onclick='openEditModal(<?php echo json_encode($it); ?>)' class="text-primary hover:underline text-xs font-semibold mr-3">Edit</button>
                    <?php if (hasPermission('admin')): ?>
                    <form method="POST" class="inline" onsubmit="return confirm('Mark this item as disposed? It will be removed from active inventory.')">
                        <input type="hidden" name="action" value="dispose_item">
                        <input type="hidden" name="item_id" value="<?php echo $it['id']; ?>">
                        <button type="submit" class="text-red-500 hover:underline text-xs font-semibold">Dispose</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

</main>
</div>
</div>

<!-- Add Item Modal -->
<div id="addModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
<div class="bg-white rounded-2xl p-8 max-w-md w-full shadow-2xl max-h-[90vh] overflow-y-auto">
    <h2 class="text-xl font-bold text-slate-900 mb-5 flex items-center gap-2"><span class="material-symbols-outlined text-gold">add_circle</span>Add Inventory Item</h2>
    <form method="POST" class="space-y-4">
        <input type="hidden" name="action" value="add_item">
        <div><label class="text-xs font-semibold text-slate-600 mb-1 block">Item Name <span class="text-red-500">*</span></label>
            <input type="text" name="name" required class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold"></div>
        <div><label class="text-xs font-semibold text-slate-600 mb-1 block">Category <span class="text-red-500">*</span></label>
            <select name="category_id" required class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold">
                <option value="">Select…</option>
                <?php foreach ($categories as $c): ?><option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option><?php endforeach; ?>
            </select></div>
        <div class="grid grid-cols-2 gap-3">
            <div><label class="text-xs font-semibold text-slate-600 mb-1 block">Quantity</label>
                <input type="number" min="1" name="quantity" value="1" class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold"></div>
            <div><label class="text-xs font-semibold text-slate-600 mb-1 block">Unit Value (&#8358;)</label>
                <input type="number" step="0.01" min="0" name="unit_value" placeholder="Optional" class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold"></div>
        </div>
        <div><label class="text-xs font-semibold text-slate-600 mb-1 block">Location</label>
            <input type="text" name="location" placeholder="e.g. JSS1-A classroom" class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold"></div>
        <div class="grid grid-cols-2 gap-3">
            <div><label class="text-xs font-semibold text-slate-600 mb-1 block">Condition</label>
                <select name="condition_status" class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold">
                    <?php foreach (['New','Good','Fair','Poor','Damaged'] as $cs): ?><option><?php echo $cs; ?></option><?php endforeach; ?>
                </select></div>
            <div><label class="text-xs font-semibold text-slate-600 mb-1 block">Date Acquired</label>
                <input type="date" name="date_acquired" class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold"></div>
        </div>
        <div><label class="text-xs font-semibold text-slate-600 mb-1 block">Serial Number (optional)</label>
            <input type="text" name="serial_number" class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold"></div>
        <div><label class="text-xs font-semibold text-slate-600 mb-1 block">Notes</label>
            <textarea name="notes" rows="2" class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold"></textarea></div>
        <div class="flex gap-3 pt-2">
            <button type="submit" class="flex-1 bg-gold text-primary py-3 rounded-xl font-bold hover:bg-gold/90">Add Item</button>
            <button type="button" onclick="document.getElementById('addModal').classList.add('hidden')" class="flex-1 bg-slate-100 text-slate-700 py-3 rounded-xl font-semibold hover:bg-slate-200">Cancel</button>
        </div>
    </form>
</div>
</div>

<!-- Edit Item Modal -->
<div id="editModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
<div class="bg-white rounded-2xl p-8 max-w-md w-full shadow-2xl max-h-[90vh] overflow-y-auto">
    <h2 class="text-xl font-bold text-slate-900 mb-5 flex items-center gap-2"><span class="material-symbols-outlined text-gold">edit</span>Edit Item</h2>
    <form method="POST" class="space-y-4">
        <input type="hidden" name="action" value="edit_item">
        <input type="hidden" name="item_id" id="editId">
        <div><label class="text-xs font-semibold text-slate-600 mb-1 block">Item Name</label>
            <input type="text" name="name" id="editName" required class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold"></div>
        <div><label class="text-xs font-semibold text-slate-600 mb-1 block">Category</label>
            <select name="category_id" id="editCategory" required class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold">
                <?php foreach ($categories as $c): ?><option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option><?php endforeach; ?>
            </select></div>
        <div class="grid grid-cols-2 gap-3">
            <div><label class="text-xs font-semibold text-slate-600 mb-1 block">Quantity</label>
                <input type="number" min="0" name="quantity" id="editQuantity" class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold"></div>
            <div><label class="text-xs font-semibold text-slate-600 mb-1 block">Unit Value (&#8358;)</label>
                <input type="number" step="0.01" min="0" name="unit_value" id="editValue" class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold"></div>
        </div>
        <div><label class="text-xs font-semibold text-slate-600 mb-1 block">Location</label>
            <input type="text" name="location" id="editLocation" class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold"></div>
        <div><label class="text-xs font-semibold text-slate-600 mb-1 block">Condition</label>
            <select name="condition_status" id="editCondition" class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold">
                <?php foreach (['New','Good','Fair','Poor','Damaged'] as $cs): ?><option><?php echo $cs; ?></option><?php endforeach; ?>
            </select></div>
        <div><label class="text-xs font-semibold text-slate-600 mb-1 block">Serial Number</label>
            <input type="text" name="serial_number" id="editSerial" class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold"></div>
        <div><label class="text-xs font-semibold text-slate-600 mb-1 block">Notes</label>
            <textarea name="notes" id="editNotes" rows="2" class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold"></textarea></div>
        <div class="flex gap-3 pt-2">
            <button type="submit" class="flex-1 bg-gold text-primary py-3 rounded-xl font-bold hover:bg-gold/90">Save</button>
            <button type="button" onclick="document.getElementById('editModal').classList.add('hidden')" class="flex-1 bg-slate-100 text-slate-700 py-3 rounded-xl font-semibold hover:bg-slate-200">Cancel</button>
        </div>
    </form>
</div>
</div>

<!-- Add Category Modal -->
<div id="addCatModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
<div class="bg-white rounded-2xl p-8 max-w-sm w-full shadow-2xl">
    <h2 class="text-xl font-bold text-slate-900 mb-5 flex items-center gap-2"><span class="material-symbols-outlined text-gold">category</span>New Inventory Category</h2>
    <form method="POST" class="space-y-4">
        <input type="hidden" name="action" value="add_category">
        <div><label class="text-xs font-semibold text-slate-600 mb-1 block">Category Name</label>
            <input type="text" name="name" required class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold"></div>
        <div class="flex gap-3 pt-2">
            <button type="submit" class="flex-1 bg-gold text-primary py-3 rounded-xl font-bold hover:bg-gold/90">Add</button>
            <button type="button" onclick="document.getElementById('addCatModal').classList.add('hidden')" class="flex-1 bg-slate-100 text-slate-700 py-3 rounded-xl font-semibold hover:bg-slate-200">Cancel</button>
        </div>
    </form>
</div>
</div>

<script>
function openEditModal(it) {
    document.getElementById('editId').value = it.id;
    document.getElementById('editName').value = it.name;
    document.getElementById('editCategory').value = it.category_id;
    document.getElementById('editQuantity').value = it.quantity;
    document.getElementById('editValue').value = it.unit_value || '';
    document.getElementById('editLocation').value = it.location || '';
    document.getElementById('editCondition').value = it.condition_status;
    document.getElementById('editSerial').value = it.serial_number || '';
    document.getElementById('editNotes').value = it.notes || '';
    document.getElementById('editModal').classList.remove('hidden');
}
['addModal','editModal','addCatModal'].forEach(id => {
    document.getElementById(id).addEventListener('click', function(e) { if (e.target === this) this.classList.add('hidden'); });
});
</script>
</body>
</html>
