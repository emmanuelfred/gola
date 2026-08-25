<?php
// Buffer everything from the very first line. This guarantees the AJAX
// branch below can return a perfectly clean JSON body no matter what stray
// output happens anywhere before it (a warning, an included file, even a
// stray HTML tag accidentally placed outside a <head> block) — we simply
// discard whatever's in the buffer right before writing the JSON.
ob_start();

require_once 'auth_check.php';
require_once 'includes/permission_helper.php';
requirePermission('canteen');
require_once 'includes/canteen_helper.php';
$page_title = "Canteen — Sell";

$authorized = isCanteenOperator($conn, $admin_id) || hasPermission('admin');

// ── AJAX: student lookup ────────────────────────────────────────────────────
if ($authorized && isset($_GET['ajax']) && $_GET['ajax'] === 'lookup_student') {
    ob_clean(); // discard anything printed before we got here
    header('Content-Type: application/json');
    $code = trim($_GET['code'] ?? '');
    $stmt = $conn->prepare("SELECT id, student_id, first_name, last_name FROM students WHERE student_id = ? AND status='Active'");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) {
        $student_db_id = (int) $row['id'];
        $balance = getStudentTabBalance($conn, $student_db_id);
        $limit   = getStudentCreditLimit($conn, $student_db_id);
        $response = [
            'found' => true, 'id' => $student_db_id, 'name' => $row['first_name'].' '.$row['last_name'],
            'reg_no' => $row['student_id'], 'balance' => $balance, 'limit' => $limit,
        ];
    } else {
        $response = ['found' => false];
    }
    echo json_encode($response);
    exit;
}

// ── Charge / complete sale ──────────────────────────────────────────────────
$success = null;
$error   = '';
if ($authorized && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'charge') {
    $cart = json_decode($_POST['cart'] ?? '[]', true) ?: [];
    $student_id   = intval($_POST['student_id'] ?? 0) ?: null;
    $payment_type = in_array($_POST['payment_type'] ?? '', ['Cash','Transfer','Tab']) ? $_POST['payment_type'] : '';
    $amount_paid  = floatval($_POST['amount_paid'] ?? 0);
    $override     = isset($_POST['override_limit']) && hasPermission('admin');

    if (!$payment_type) {
        $error = 'Select a payment method.';
    } else {
        $result = recordSale($conn, $student_id, $cart, $payment_type, $amount_paid, $admin_id, $override);
        if ($result['ok']) {
            logActivity('canteen_sale', "Sale {$result['receipt_no']} — $payment_type");
            $success = $result;
        } else {
            $error = $result['error'];
        }
    }
}

$search = trim($_GET['search'] ?? '');
$items  = $authorized ? getActiveItems($conn, $search) : [];
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
        <h1 class="text-2xl font-bold text-slate-900">Sell</h1>
        <p class="text-slate-500 text-sm mt-1">
            <a href="manage_canteen_items.php" class="text-primary font-semibold hover:underline">Manage Items</a> ·
            <a href="canteen_tabs.php" class="text-primary font-semibold hover:underline">Student Tabs</a>
        </p>
    </div>
</div>

<?php if ($success): ?>
<div class="mb-5 p-5 bg-green-50 border border-green-200 rounded-xl flex items-center justify-between gap-4">
    <div class="flex items-start gap-3">
        <span class="material-symbols-outlined text-green-600 flex-shrink-0">check_circle</span>
        <p class="text-green-800 text-sm">Sale <strong><?php echo htmlspecialchars($success['receipt_no']); ?></strong> completed.</p>
    </div>
    <a href="canteen_receipt.php?sale_id=<?php echo $success['sale_id']; ?>" target="_blank"
        class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white text-sm font-semibold rounded-lg hover:bg-primary/90 flex-shrink-0">
        <span class="material-symbols-outlined text-sm">print</span>Print Receipt
    </a>
</div>
<?php endif; ?>
<?php if ($error): ?>
<div class="mb-5 p-4 bg-red-50 border border-red-200 rounded-xl flex gap-3 items-start">
    <span class="material-symbols-outlined text-red-600 flex-shrink-0">error</span>
    <p class="text-red-800 text-sm"><?php echo htmlspecialchars($error); ?></p>
</div>
<?php endif; ?>

<div class="grid lg:grid-cols-3 gap-6">
    <!-- Item grid -->
    <div class="lg:col-span-2">
        <form method="GET" class="mb-4">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search items…"
                class="w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold">
        </form>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
            <?php foreach ($items as $it): ?>
            <button type="button"
                onclick='addToCart(<?php echo json_encode(["id"=>$it['id'],"name"=>$it['name'],"price"=>(float)$it['price'],"stock"=>$it['quantity_in_stock']]); ?>)'
                <?php echo $it['quantity_in_stock']<1 ? 'disabled' : ''; ?>
                class="text-left bg-white border border-slate-200 rounded-xl p-4 hover:border-gold hover:shadow-sm transition-all disabled:opacity-40 disabled:cursor-not-allowed">
                <p class="font-semibold text-slate-800 text-sm"><?php echo htmlspecialchars($it['name']); ?></p>
                <p class="text-gold font-bold text-sm mt-1">&#8358;<?php echo number_format($it['price'], 2); ?></p>
                <p class="text-xs <?php echo $it['quantity_in_stock']<1 ? 'text-red-500' : 'text-slate-400'; ?> mt-1">
                    <?php echo $it['quantity_in_stock']<1 ? 'Out of stock' : $it['quantity_in_stock'].' in stock'; ?>
                </p>
            </button>
            <?php endforeach; ?>
            <?php if (empty($items)): ?><p class="text-sm text-slate-400 col-span-full">No items found.</p><?php endif; ?>
        </div>
    </div>

    <!-- Cart / checkout -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-xl border border-slate-200 p-5 sticky top-6">
            <h4 class="font-semibold text-slate-800 text-sm mb-3">Cart</h4>
            <div id="cartItems" class="space-y-2 mb-3 max-h-56 overflow-y-auto"><p id="cartEmpty" class="text-xs text-slate-400">Cart is empty — click an item to add it.</p></div>
            <div class="flex items-center justify-between text-sm font-bold text-slate-800 pt-3 border-t border-slate-100 mb-4">
                <span>Subtotal</span><span id="cartSubtotal">&#8358;0.00</span>
            </div>

            <div class="mb-3">
                <label class="text-xs font-semibold text-slate-500 mb-1 block">Student (optional, required for Tab)</label>
                <input type="text" id="studentCode" placeholder="Reg No" class="w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold">
                <p id="studentResult" class="text-xs mt-1"></p>
            </div>

            <div class="mb-3">
                <label class="text-xs font-semibold text-slate-500 mb-1 block">Payment Method</label>
                <div class="grid grid-cols-3 gap-2">
                    <button type="button" onclick="setPayment('Cash')" id="pay-Cash" class="pay-btn px-2 py-2 border border-slate-200 rounded-lg text-xs font-semibold text-slate-600 hover:border-gold">Cash</button>
                    <button type="button" onclick="setPayment('Transfer')" id="pay-Transfer" class="pay-btn px-2 py-2 border border-slate-200 rounded-lg text-xs font-semibold text-slate-600 hover:border-gold">Transfer</button>
                    <button type="button" onclick="setPayment('Tab')" id="pay-Tab" class="pay-btn px-2 py-2 border border-slate-200 rounded-lg text-xs font-semibold text-slate-600 hover:border-gold">Add to Tab</button>
                </div>
            </div>

            <?php if (hasPermission('admin')): ?>
            <label class="flex items-center gap-2 mb-3 cursor-pointer">
                <input type="checkbox" id="overrideLimit" class="rounded text-gold focus:ring-gold">
                <span class="text-xs text-slate-500">Override credit limit (admin)</span>
            </label>
            <?php endif; ?>

            <form method="POST" id="chargeForm">
                <input type="hidden" name="action" value="charge">
                <input type="hidden" name="cart" id="cartInput">
                <input type="hidden" name="student_id" id="studentIdInput">
                <input type="hidden" name="payment_type" id="paymentTypeInput">
                <input type="hidden" name="amount_paid" id="amountPaidInput">
                <input type="hidden" name="override_limit" id="overrideInput">
                <button type="submit" id="chargeBtn" disabled
                    class="w-full px-5 py-3 bg-gold text-primary text-sm font-bold rounded-lg hover:bg-gold/90 transition-all disabled:opacity-40 disabled:cursor-not-allowed">
                    Complete Sale
                </button>
            </form>
        </div>
    </div>
</div>

<?php endif; ?>
</main>
</div>
</div>

<?php if ($authorized): ?>
<script>
let cart = [];
let selectedStudent = null;
let selectedPayment = null;

function fmt(n) { return '₦' + Number(n).toFixed(2); }

function addToCart(item) {
    const id = Number(item.id);
    const existing = cart.find(c => c.id === id);
    if (existing) {
        if (existing.qty < item.stock) existing.qty++;
    } else {
        cart.push({id: id, name: item.name, price: Number(item.price), qty: 1, stock: Number(item.stock)});
    }
    renderCart();
}
function changeQty(id, delta) {
    id = Number(id);
    const line = cart.find(c => c.id === id);
    if (!line) return;
    line.qty += delta;
    if (line.qty <= 0) cart = cart.filter(c => c.id !== id);
    renderCart();
}
function renderCart() {
    const container = document.getElementById('cartItems');
    if (cart.length === 0) {
        container.innerHTML = '<p id="cartEmpty" class="text-xs text-slate-400">Cart is empty — click an item to add it.</p>';
    } else {
        container.innerHTML = cart.map(c => `
            <div class="flex items-center justify-between text-xs">
                <div class="flex-1">
                    <p class="font-medium text-slate-700">${c.name}</p>
                    <p class="text-slate-400">${fmt(c.price)} × ${c.qty} = ${fmt(c.price * c.qty)}</p>
                </div>
                <div class="flex items-center gap-1 flex-shrink-0">
                    <button type="button" onclick="changeQty(${c.id},-1)" class="w-6 h-6 bg-slate-100 rounded hover:bg-slate-200">−</button>
                    <button type="button" onclick="changeQty(${c.id},1)" class="w-6 h-6 bg-slate-100 rounded hover:bg-slate-200">+</button>
                </div>
            </div>`).join('');
    }
    const subtotal = cart.reduce((s,c) => s + c.price*c.qty, 0);
    document.getElementById('cartSubtotal').textContent = fmt(subtotal);
    document.getElementById('amountPaidInput').value = subtotal.toFixed(2);
    updateChargeButton();
}

let lookupTimer;
document.getElementById('studentCode').addEventListener('input', function() {
    clearTimeout(lookupTimer);
    const code = this.value.trim();
    const resultEl = document.getElementById('studentResult');
    if (!code) { selectedStudent = null; resultEl.textContent=''; updateChargeButton(); return; }
    lookupTimer = setTimeout(() => {
        fetch('canteen_pos.php?ajax=lookup_student&code=' + encodeURIComponent(code))
        .then(async r => {
            const text = await r.text();
            if (r.redirected || !r.ok) {
                throw new Error('SESSION_EXPIRED');
            }
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Lookup endpoint returned non-JSON. Raw response:', text);
                throw new Error('BAD_RESPONSE');
            }
        })
        .then(data => {
            if (data.found) {
                selectedStudent = data;
                resultEl.innerHTML = `<span class="text-green-600 font-semibold">✓ ${data.name}</span> — balance ${fmt(data.balance)} / limit ${fmt(data.limit)}`;
            } else {
                selectedStudent = null;
                resultEl.innerHTML = '<span class="text-red-500">Not found</span>';
            }
            updateChargeButton();
        })
        .catch(err => {
            selectedStudent = null;
            if (err.message === 'SESSION_EXPIRED') {
                resultEl.innerHTML = '<span class="text-red-500">Your session has expired — please <a href="canteen_pos.php" class="underline font-semibold">refresh the page</a> and log in again.</span>';
            } else if (err.message === 'BAD_RESPONSE') {
                resultEl.innerHTML = '<span class="text-red-500">Server returned an unexpected response — open the browser console (F12) for details, and share that with support.</span>';
            } else {
                resultEl.innerHTML = '<span class="text-red-500">Lookup failed — check your connection and try again.</span>';
            }
            updateChargeButton();
        });
    }, 400);
});

function setPayment(type) {
    selectedPayment = type;
    document.querySelectorAll('.pay-btn').forEach(b => b.classList.remove('border-gold','bg-gold/10','text-primary'));
    document.getElementById('pay-' + type).classList.add('border-gold','bg-gold/10','text-primary');
    updateChargeButton();
}

function updateChargeButton() {
    const btn = document.getElementById('chargeBtn');
    let ok = cart.length > 0 && selectedPayment;
    if (selectedPayment === 'Tab' && !selectedStudent) ok = false;
    btn.disabled = !ok;
}

document.getElementById('chargeForm').addEventListener('submit', function() {
    document.getElementById('cartInput').value = JSON.stringify(cart.map(c => ({item_id: c.id, quantity: c.qty})));
    document.getElementById('studentIdInput').value = selectedStudent ? selectedStudent.id : '';
    document.getElementById('paymentTypeInput').value = selectedPayment;
    const overrideEl = document.getElementById('overrideLimit');
    document.getElementById('overrideInput').value = (overrideEl && overrideEl.checked) ? '1' : '';
});
</script>
<?php endif; ?>
</body>
</html>
