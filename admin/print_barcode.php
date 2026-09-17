<?php
require_once 'auth_check.php';
require_once 'includes/permission_helper.php';
requirePermission('canteen');
require_once 'includes/canteen_helper.php';

if (!(isCanteenOperator($conn, $admin_id) || hasPermission('admin'))) {
    die('Access restricted to Canteen Operators.');
}

// Accepts ?ids=3,7,12 (bulk) or the older ?id=3 (single) for convenience.
$idsParam = $_GET['ids'] ?? $_GET['id'] ?? '';
$ids = array_values(array_filter(array_map('intval', explode(',', $idsParam))));

if (empty($ids)) { die('No items selected to print.'); }

$placeholders = implode(',', array_fill(0, count($ids), '?'));
$types = str_repeat('i', count($ids));
$stmt = $conn->prepare("SELECT id, name, price, barcode FROM canteen_items WHERE id IN ($placeholders) AND barcode IS NOT NULL");
$stmt->bind_param($types, ...$ids);
$stmt->execute();
$labels = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (empty($labels)) { die('None of the selected items have a barcode yet. Generate one first from the Items page.'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Print Barcodes | G.O.L.A Canteen</title>
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<style>
    * { box-sizing: border-box; }
    body { font-family: Arial, Helvetica, sans-serif; margin: 20px; color: #1a1a1a; }
    .no-print { text-align: center; margin-bottom: 20px; }
    .no-print button { padding: 10px 24px; background: #0A2E4D; color: #fff; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 14px; }
    .label-sheet { display: flex; flex-wrap: wrap; gap: 12px; }
    .label {
        width: 220px;
        border: 1px dashed #999;
        border-radius: 6px;
        padding: 10px;
        text-align: center;
        page-break-inside: avoid;
    }
    .label .name { font-weight: bold; font-size: 13px; margin-bottom: 4px; word-wrap: break-word; }
    .label svg { max-width: 100%; }
    .label .num { font-size: 11px; color: #444; margin-top: 2px; font-family: 'Courier New', monospace; }
    .label .price { font-weight: bold; font-size: 14px; margin-top: 4px; }
    @media print {
        .no-print { display: none; }
        body { margin: 0; }
        .label { border: 1px solid #ccc; }
    }
</style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()">Print Labels</button>
    </div>

    <div class="label-sheet">
        <?php foreach ($labels as $i => $l): ?>
        <div class="label">
            <div class="name"><?php echo htmlspecialchars($l['name']); ?></div>
            <svg class="barcode-svg" data-code="<?php echo htmlspecialchars($l['barcode']); ?>"></svg>
            <div class="num"><?php echo htmlspecialchars($l['barcode']); ?></div>
            <div class="price">&#8358;<?php echo number_format($l['price'], 2); ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <script>
        document.querySelectorAll('.barcode-svg').forEach(function (svg) {
            JsBarcode(svg, svg.dataset.code, { format: 'CODE128', width: 1.6, height: 45, fontSize: 11, margin: 0 });
        });
    </script>
</body>
</html>
