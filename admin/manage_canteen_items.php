<?php
require_once 'auth_check.php';
require_once 'includes/permission_helper.php';
requirePermission('canteen');
require_once 'includes/canteen_helper.php';
$page_title = "Canteen Items";

$authorized = isCanteenOperator($conn, $admin_id) || hasPermission('admin');
$success = '';
$error   = '';

if ($authorized && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'add_item') {
        $name  = trim($_POST['name'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $cost_price = $_POST['cost_price'] !== '' ? floatval($_POST['cost_price']) : null;
        $stock = max(0, intval($_POST['quantity_in_stock'] ?? 0));
        $threshold = max(0, intval($_POST['low_stock_threshold'] ?? 5));

        if (!$name) {
            $error = 'Item name is required.';
        } else {
            $stmt = $conn->prepare("INSERT INTO canteen_items (name, category, price, cost_price, quantity_in_stock, low_stock_threshold) VALUES (?,?,?,?,?,?)");
            $stmt->bind_param("ssddii", $name, $category, $price, $cost_price, $stock, $threshold);
            if ($stmt->execute()) {
                logActivity('add_canteen_item', "Added canteen item: $name");
                $success = "<strong>" . htmlspecialchars($name) . "</strong> added.";
            } else {
                $error = 'Failed: ' . $conn->error;
            }
        }
    }

    if ($_POST['action'] === 'edit_item') {
        $id    = intval($_POST['item_id'] ?? 0);
        $name  = trim($_POST['name'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $cost_price = $_POST['cost_price'] !== '' ? floatval($_POST['cost_price']) : null;
        $threshold = max(0, intval($_POST['low_stock_threshold'] ?? 5));

        if (!$id || !$name) {
            $error = 'Item name is required.';
        } else {
            $stmt = $conn->prepare("UPDATE canteen_items SET name=?, category=?, price=?, cost_price=?, low_stock_threshold=? WHERE id=?");
            $stmt->bind_param("ssddii", $name, $category, $price, $cost_price, $threshold, $id);
            $stmt->execute();
            $success = "Item updated.";
        }
    }

    if ($_POST['action'] === 'restock') {
        $id  = intval($_POST['item_id'] ?? 0);
        $qty = intval($_POST['restock_qty'] ?? 0);
        if ($id && $qty > 0) {
            $conn->query("UPDATE canteen_items SET quantity_in_stock = quantity_in_stock + $qty WHERE id=$id");
            logActivity('restock_canteen_item', "Restocked item ID $id by $qty");
            $success = "Stock updated (+$qty).";
        }
    }

    if ($_POST['action'] === 'delete_item' && hasPermission('admin')) {
        $id = intval($_POST['item_id'] ?? 0);
        $conn->query("UPDATE canteen_items SET is_active=0 WHERE id=$id");
        $success = "Item removed.";
    }
}

$search = trim($_GET['search'] ?? '');
$items  = $authorized ? getActiveItems($conn, $search) : [];
$low_stock = array_filter($items, fn($i) => $i['quantity_in_stock'] <= $i['low_stock_threshold']);
$out_of_stock = array_filter($items, fn($i) => $i['quantity_in_stock'] == 0);
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

<?php if (!$authorized): ?>
<div class="bg-white rounded-2xl border border-slate-200 p-16 text-center max-w-lg mx-auto mt-10">
    <span class="material-symbols-outlined text-6xl text-slate-200 block mb-3">lock</span>
    <h3 class="font-bold text-slate-800 mb-2">Canteen Operator access required</h3>
    <p class="text-sm text-slate-500">This area is restricted to staff with the Canteen Operator role. If this should be you, ask an administrator to check your role in Manage Staff.</p>
</div>
<?php else: ?>

<div class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div>
        <div class="flex items-center gap-2 text-xs text-slate-500 mb-2">
            <a href="canteen_pos.php" class="hover:text-gold">Canteen</a>
            <span class="material-symbols-outlined text-xs">chevron_right</span>
            <span class="text-slate-800">Items</span>
        </div>
        <h1 class="text-2xl font-bold text-slate-900">Canteen Items</h1>
        <p class="text-slate-500 text-sm mt-1">Manage stock. To sell items, go to <a href="canteen_pos.php" class="text-primary font-semibold hover:underline">Sell (POS)</a>.</p>
    </div>
    <button onclick="document.getElementById('addModal').classList.remove('hidden')"
        class="inline-flex items-center gap-2 bg-gold text-primary px-5 py-3 rounded-xl font-bold hover:bg-gold/90 shadow-sm flex-shrink-0">
        <span class="material-symbols-outlined">add_circle</span>New Item
    </button>
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

<div class="grid grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <p class="text-xs font-semibold text-slate-500 uppercase">Items</p>
        <p class="text-2xl font-bold text-slate-900 mt-1"><?php echo count($items); ?></p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <p class="text-xs font-semibold text-slate-500 uppercase">Low Stock</p>
        <p class="text-2xl font-bold <?php echo count($low_stock)>0?'text-amber-500':'text-slate-300'; ?> mt-1"><?php echo count($low_stock); ?></p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <p class="text-xs font-semibold text-slate-500 uppercase">Out of Stock</p>
        <p class="text-2xl font-bold <?php echo count($out_of_stock)>0?'text-red-600':'text-slate-300'; ?> mt-1"><?php echo count($out_of_stock); ?></p>
    </div>
</div>

<form method="GET" class="mb-5">
    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search items…"
        class="w-full max-w-md border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold">
</form>

<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 border-b border-slate-200">
            <tr class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                <th class="px-5 py-3">Item</th>
                <th class="px-5 py-3">Category</th>
                <th class="px-5 py-3 text-right">Price</th>
                <th class="px-5 py-3 text-center">Stock</th>
                <th class="px-5 py-3 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            <?php if (empty($items)): ?>
            <tr><td colspan="5" class="px-5 py-12 text-center text-slate-400">No items found.</td></tr>
            <?php endif; ?>
            <?php foreach ($items as $it): ?>
            <tr class="hover:bg-slate-50">
                <td class="px-5 py-3 font-semibold text-slate-800"><?php echo htmlspecialchars($it['name']); ?></td>
                <td class="px-5 py-3 text-slate-500"><?php echo htmlspecialchars($it['category'] ?: '—'); ?></td>
                <td class="px-5 py-3 text-right text-slate-700 font-medium">&#8358;<?php echo number_format($it['price'], 2); ?></td>
                <td class="px-5 py-3 text-center">
                    <?php if ($it['quantity_in_stock'] == 0): ?>
                    <span class="px-2 py-0.5 bg-red-100 text-red-600 text-xs font-semibold rounded-full">Out of stock</span>
                    <?php elseif ($it['quantity_in_stock'] <= $it['low_stock_threshold']): ?>
                    <span class="px-2 py-0.5 bg-amber-100 text-amber-700 text-xs font-semibold rounded-full"><?php echo $it['quantity_in_stock']; ?> left — low</span>
                    <?php else: ?>
                    <span class="px-2 py-0.5 bg-green-100 text-green-700 text-xs font-semibold rounded-full"><?php echo $it['quantity_in_stock']; ?> in stock</span>
                    <?php endif; ?>
                </td>
                <td class="px-5 py-3 text-right whitespace-nowrap">
                    <button onclick='openRestockModal(<?php echo json_encode(["id"=>$it['id'],"name"=>$it['name']]); ?>)' class="text-green-600 hover:underline text-xs font-semibold mr-3">Restock</button>
                    <button onclick='openEditModal(<?php echo json_encode($it); ?>)' class="text-primary hover:underline text-xs font-semibold mr-3">Edit</button>
                    <?php if (hasPermission('admin')): ?>
                    <form method="POST" class="inline" onsubmit="return confirm('Remove this item?')">
                        <input type="hidden" name="action"  value="delete_item">
                        <input type="hidden" name="item_id" value="<?php echo $it['id']; ?>">
                        <button type="submit" class="text-red-500 hover:underline text-xs font-semibold">Remove</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php endif; ?>
</main>
</div>
</div>

<?php if ($authorized): ?>
<!-- Add Item Modal -->
<div id="addModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
<div class="bg-white rounded-2xl p-8 max-w-md w-full shadow-2xl">
    <h2 class="text-xl font-bold text-slate-900 mb-5 flex items-center gap-2"><span class="material-symbols-outlined text-gold">add_circle</span>Add Item</h2>
    <form method="POST" class="space-y-4">
        <input type="hidden" name="action" value="add_item">
        <div><label class="text-xs font-semibold text-slate-600 mb-1 block">Item Name <span class="text-red-500">*</span></label>
            <input type="text" name="name" required class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold"></div>
        <div><label class="text-xs font-semibold text-slate-600 mb-1 block">Category</label>
            <input type="text" name="category" placeholder="e.g. Snacks, Drinks" class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold"></div>
        <div class="grid grid-cols-2 gap-3">
            <div><label class="text-xs font-semibold text-slate-600 mb-1 block">Selling Price (&#8358;)</label>
                <input type="number" step="0.01" min="0" name="price" required class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold"></div>
            <div><label class="text-xs font-semibold text-slate-600 mb-1 block">Cost Price (&#8358;)</label>
                <input type="number" step="0.01" min="0" name="cost_price" placeholder="Optional" class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold">
                <p class="text-xs text-slate-400 mt-1">Needed to show profit in Reports.</p></div>
        </div>
        <div><label class="text-xs font-semibold text-slate-600 mb-1 block">Starting Stock</label>
            <input type="number" min="0" name="quantity_in_stock" value="0" class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold"></div>
        <div><label class="text-xs font-semibold text-slate-600 mb-1 block">Low Stock Warning At</label>
            <input type="number" min="0" name="low_stock_threshold" value="5" class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold"></div>
        <div class="flex gap-3 pt-2">
            <button type="submit" class="flex-1 bg-gold text-primary py-3 rounded-xl font-bold hover:bg-gold/90">Add Item</button>
            <button type="button" onclick="document.getElementById('addModal').classList.add('hidden')" class="flex-1 bg-slate-100 text-slate-700 py-3 rounded-xl font-semibold hover:bg-slate-200">Cancel</button>
        </div>
    </form>
</div>
</div>

<!-- Edit Item Modal -->
<div id="editModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
<div class="bg-white rounded-2xl p-8 max-w-md w-full shadow-2xl">
    <h2 class="text-xl font-bold text-slate-900 mb-5 flex items-center gap-2"><span class="material-symbols-outlined text-gold">edit</span>Edit Item</h2>
    <form method="POST" class="space-y-4">
        <input type="hidden" name="action" value="edit_item">
        <input type="hidden" name="item_id" id="editId">
        <div><label class="text-xs font-semibold text-slate-600 mb-1 block">Item Name <span class="text-red-500">*</span></label>
            <input type="text" name="name" id="editName" required class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold"></div>
        <div><label class="text-xs font-semibold text-slate-600 mb-1 block">Category</label>
            <input type="text" name="category" id="editCategory" class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold"></div>
        <div class="grid grid-cols-2 gap-3">
            <div><label class="text-xs font-semibold text-slate-600 mb-1 block">Selling Price (&#8358;)</label>
                <input type="number" step="0.01" min="0" name="price" id="editPrice" required class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold"></div>
            <div><label class="text-xs font-semibold text-slate-600 mb-1 block">Cost Price (&#8358;)</label>
                <input type="number" step="0.01" min="0" name="cost_price" id="editCostPrice" placeholder="Optional" class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold"></div>
        </div>
        <div><label class="text-xs font-semibold text-slate-600 mb-1 block">Low Stock Warning At</label>
            <input type="number" min="0" name="low_stock_threshold" id="editThreshold" class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold"></div>
        <p class="text-xs text-slate-400">Use "Restock" from the table to add stock — editing here won't change the quantity on hand.</p>
        <div class="flex gap-3 pt-2">
            <button type="submit" class="flex-1 bg-gold text-primary py-3 rounded-xl font-bold hover:bg-gold/90">Save</button>
            <button type="button" onclick="document.getElementById('editModal').classList.add('hidden')" class="flex-1 bg-slate-100 text-slate-700 py-3 rounded-xl font-semibold hover:bg-slate-200">Cancel</button>
        </div>
    </form>
</div>
</div>

<!-- Restock Modal -->
<div id="restockModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
<div class="bg-white rounded-2xl p-8 max-w-sm w-full shadow-2xl">
    <h2 class="text-xl font-bold text-slate-900 mb-1 flex items-center gap-2"><span class="material-symbols-outlined text-green-600">add_box</span>Restock</h2>
    <p id="restockItemName" class="text-sm text-slate-500 mb-4"></p>
    <form method="POST" class="space-y-4">
        <input type="hidden" name="action" value="restock">
        <input type="hidden" name="item_id" id="restockId">
        <div><label class="text-xs font-semibold text-slate-600 mb-1 block">Add Quantity</label>
            <input type="number" min="1" name="restock_qty" required autofocus class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold"></div>
        <div class="flex gap-3 pt-2">
            <button type="submit" class="flex-1 bg-green-600 text-white py-3 rounded-xl font-bold hover:bg-green-700">Add Stock</button>
            <button type="button" onclick="document.getElementById('restockModal').classList.add('hidden')" class="flex-1 bg-slate-100 text-slate-700 py-3 rounded-xl font-semibold hover:bg-slate-200">Cancel</button>
        </div>
    </form>
</div>
</div>

<script>
function openEditModal(it) {
    document.getElementById('editId').value = it.id;
    document.getElementById('editName').value = it.name;
    document.getElementById('editCategory').value = it.category || '';
    document.getElementById('editPrice').value = it.price;
    document.getElementById('editCostPrice').value = it.cost_price || '';
    document.getElementById('editThreshold').value = it.low_stock_threshold;
    document.getElementById('editModal').classList.remove('hidden');
}
function openRestockModal(it) {
    document.getElementById('restockId').value = it.id;
    document.getElementById('restockItemName').textContent = it.name;
    document.getElementById('restockModal').classList.remove('hidden');
}
['addModal','editModal','restockModal'].forEach(id => {
    document.getElementById(id).addEventListener('click', function(e) { if (e.target === this) this.classList.add('hidden'); });
});
</script>
<?php endif; ?>
</body>
</html>
