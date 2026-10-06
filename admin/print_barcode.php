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
    .size-toggle { margin-top: 12px; font-size: 13px; color: #334155; display: flex; gap: 18px; justify-content: center; }
    .size-toggle label { cursor: pointer; }

    :root {
        --label-w: 38mm; --label-h: 20mm;
        --name-fs: 6.5pt; --num-fs: 5.3pt; --price-fs: 7pt; --pad: 1.5mm;
    }
    body.size-medium {
        --label-w: 58mm; --label-h: 30mm;
        --name-fs: 9pt; --num-fs: 7pt; --price-fs: 10pt; --pad: 2.5mm;
    }
    body.size-tiny {
        --label-w: 28mm; --label-h: 15mm;
        --name-fs: 5.2pt; --num-fs: 4.4pt; --price-fs: 5.8pt; --pad: 1mm;
    }

    .label-sheet { display: flex; flex-wrap: wrap; gap: 2mm; }
    .label {
        width: var(--label-w); height: var(--label-h);
        border: 0.2mm dashed #999; border-radius: 1mm; padding: var(--pad);
        display: flex; flex-direction: column; align-items: center; justify-content: flex-start;
        text-align: center; overflow: hidden; page-break-inside: avoid; break-inside: avoid;
    }
    /* The text lines are never squeezed — if a label is tight, the barcode
       (below) is the part that gives up height, not the item name. */
    .label .name, .label .num, .label .price { flex: none; max-width: 100%; }
    /* Item name: wraps to up to 2 lines so the full name is readable.
       fitNames() in the script below shrinks the font slightly if a very
       long name still wouldn't fit in 2 lines. */
    .label .name {
        font-weight: bold; font-size: var(--name-fs); line-height: 1.1; margin-bottom: 0.4mm;
        white-space: normal; overflow-wrap: anywhere;
        display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 2; line-clamp: 2; overflow: hidden;
    }
    /* The barcode takes whatever height is left over. Its bars are stretched
       vertically only (see preserveAspectRatio in the script), so the bar
       widths — which is what a scanner actually reads — never change. */
    .label .barcode-svg { flex: 1 1 0; min-height: 0; width: 92%; display: block; }
    .label .num { font-size: var(--num-fs); color: #444; margin-top: 0.3mm; font-family: 'Courier New', monospace; letter-spacing: 0.02em; }
    .label .price { font-weight: bold; font-size: var(--price-fs); margin-top: 0.4mm; }
    @media print {
        .no-print { display: none; }
        body { margin: 0; }
        .label { border: 0.15mm solid #ccc; }
    }
</style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()">Print Labels</button>
        <div class="size-toggle">
            <label><input type="radio" name="labelSize" value="tiny" onchange="setSize(this.value)"> Tiny (28×15mm)</label>
            <label><input type="radio" name="labelSize" value="small" checked onchange="setSize(this.value)"> Small (38×20mm) — fits small items</label>
            <label><input type="radio" name="labelSize" value="medium" onchange="setSize(this.value)"> Medium (58×30mm)</label>
        </div>
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
        // Draw each barcode once, then strip its fixed px width/height in
        // favour of a viewBox — that turns it into a responsive vector, so
        // the size toggle below just resizes it via CSS with zero quality
        // loss, instead of needing to redraw the barcode for every size.
        document.querySelectorAll('.barcode-svg').forEach(function (svg) {
            JsBarcode(svg, svg.dataset.code, { format: 'CODE128', width: 1.6, height: 36, fontSize: 0, margin: 0, displayValue: false });
            const w = parseFloat(svg.getAttribute('width'));
            const h = parseFloat(svg.getAttribute('height'));
            svg.setAttribute('viewBox', '0 0 ' + w + ' ' + h);
            // 'none' = stretch to the space the layout gives it, height-wise
            svg.setAttribute('preserveAspectRatio', 'none');
            svg.removeAttribute('width');
            svg.removeAttribute('height');
        });

        // If an item name is too long to fit in 2 lines even after wrapping,
        // shrink its font a little (down to 70%) so it's never cut off.
        function fitNames() {
            document.querySelectorAll('.label .name').forEach(function (el) {
                el.style.fontSize = '';
                let fs = parseFloat(getComputedStyle(el).fontSize);
                const min = fs * 0.7;
                while (el.scrollHeight > el.clientHeight + 0.5 && fs > min) {
                    fs -= 0.25;
                    el.style.fontSize = fs + 'px';
                }
            });
        }

        function setSize(size) {
            document.body.classList.remove('size-tiny', 'size-medium');
            if (size === 'tiny') document.body.classList.add('size-tiny');
            if (size === 'medium') document.body.classList.add('size-medium');
            fitNames();
        }
        fitNames();
    </script>
</body>
</html>