<?php
require_once 'auth_check.php';
require_once 'includes/permission_helper.php';
requirePermission('students');

function getSetting($conn, $key, $default = '') {
    $r = $conn->query("SELECT setting_value FROM school_settings WHERE setting_key='".mysqli_real_escape_string($conn,$key)."' LIMIT 1");
    $row = $r ? $r->fetch_assoc() : null;
    return $row ? $row['setting_value'] : $default;
}
$school_phone   = getSetting($conn, 'school_phone',   '09125128213');
$school_address = getSetting($conn, 'school_address', 'Ntezi, Ishielu LGA, Ebonyi State, Nigeria');

// Accepts ?ids=3,7,12 (bulk, from the checkboxes / class list), or the
// older ?id=3 / a single row's "ID Card" button.
$idsParam = $_GET['ids'] ?? $_GET['id'] ?? '';
$ids = array_values(array_filter(array_map('intval', explode(',', $idsParam))));

if (empty($ids)) { die('No students selected to print.'); }

$placeholders = implode(',', array_fill(0, count($ids), '?'));
$types = str_repeat('i', count($ids));
$stmt = $conn->prepare("
    SELECT s.*, c.class_name, c.arm, acs.session_name
    FROM students s
    JOIN classes c ON s.class_id = c.id
    LEFT JOIN academic_sessions acs ON s.session_id = acs.id
    WHERE s.id IN ($placeholders)
    ORDER BY c.class_name, c.arm, s.first_name
");
$stmt->bind_param($types, ...$ids);
$stmt->execute();
$cards = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (empty($cards)) { die('None of the selected students could be found.'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student ID Cards | G.O.L.A</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<style>
    * { box-sizing: border-box; }
    body { font-family: 'Inter', Arial, Helvetica, sans-serif; margin: 20px; color: #1a1a1a; background: #eef2f6; }
    .material-symbols-outlined { font-variation-settings: 'FILL' 1, 'wght' 500, 'GRAD' 0, 'opsz' 20; vertical-align: middle; }
    .no-print { text-align: center; margin-bottom: 20px; }
    .no-print button { padding: 10px 24px; background: #0A2E4D; color: #fff; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 14px; }
    .no-print p { font-size: 12px; color: #64748b; margin-top: 8px; }

    .sheet { display: flex; flex-wrap: wrap; gap: 14px; }

    /* Standard ID-1 card size: 85.6mm x 54mm */
    .card { width: 85.6mm; height: 54mm; border-radius: 3.2mm; overflow: hidden; position: relative; page-break-inside: avoid;
            box-shadow: 0 2px 8px rgba(10,46,77,0.18); background: #fff; }
    .accent-stripe { height: 1.4mm; background: linear-gradient(90deg, #C5A059, #0A2E4D 55%, #C5A059); }

    /* ── FRONT ────────────────────────────────────────────────────────── */
    .front { display: flex; flex-direction: column; }
    .front .band { background: #0A2E4D; color: #fff; padding: 2.2mm 3mm; display: flex; align-items: center; gap: 2mm; position: relative; }
    .front .crest { width: 7mm; height: 7mm; flex-shrink: 0; }
    .front .crest img { width: 100%; height: 100%; object-fit: contain; display: block; }
    .front .band .school { font-weight: 800; font-size: 8.5pt; letter-spacing: 0.02em; line-height: 1.1; }
    .front .band .tagline { font-size: 5pt; color: #C5A059; text-transform: uppercase; letter-spacing: 0.06em; margin-top: 0.4mm; }
    .front .session-tag { position: absolute; top: 0; right: 0; background: #C5A059; color: #0A2E4D; font-weight: 800; font-size: 5pt;
            padding: 1mm 2.2mm; border-bottom-left-radius: 2mm; letter-spacing: 0.02em; }

    .front .body { flex: 1; display: flex; gap: 2.8mm; padding: 3mm 3mm 1mm; position: relative; }
    .front .photo, .front .photo-placeholder { width: 17mm; height: 20mm; border-radius: 1.8mm; flex-shrink: 0; }
    .front .photo { object-fit: cover; border: 0.5mm solid #fff; outline: 0.3mm solid #C5A059; box-shadow: 0 1px 3px rgba(0,0,0,0.25); }
    .front .photo-placeholder { border: 0.5mm solid #fff; outline: 0.3mm solid #C5A059; background: #eef2f6;
            display: flex; align-items: center; justify-content: center; font-weight: 800; color: #94a3b8; font-size: 11pt; box-shadow: 0 1px 3px rgba(0,0,0,0.15); }

    .front .info { flex: 1; min-width: 0; padding-top: 0.5mm; }
    .front .name { font-weight: 800; font-size: 8.5pt; line-height: 1.2; color: #0A2E4D; }
    .front .reg-badge { display: inline-block; margin-top: 1mm; font-family: 'Courier New', monospace; font-size: 6.5pt; font-weight: 700;
            color: #0A2E4D; background: #C5A059/15; background: rgba(197,160,89,0.18); padding: 0.6mm 1.6mm; border-radius: 1mm; }
    .front dl { margin: 1.6mm 0 0; font-size: 5.8pt; color: #475569; display: grid; grid-template-columns: auto 1fr; gap: 0.6mm 1.5mm; }
    .front dl dt { font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.02em; }
    .front dl dd { margin: 0; color: #1e293b; font-weight: 600; }

    .front .wave { position: absolute; left: 0; right: 0; bottom: 0; height: 5mm; background: #0A2E4D; clip-path: polygon(0 60%, 100% 0, 100% 100%, 0% 100%); opacity: 0.05; pointer-events: none; }
    .front .foot { position: relative; border-top: 0.25mm solid #eef0f3; padding: 1mm 3mm; font-size: 5pt; color: #94a3b8; text-align: center; background: #fafbfc; }
    .front .foot .status-dot { display: inline-block; width: 1.3mm; height: 1.3mm; border-radius: 50%; background: #22c55e; margin-right: 1mm; }

    /* ── BACK ─────────────────────────────────────────────────────────── */
    .back { display: flex; flex-direction: column; }
    .back .back-head { background: #0A2E4D; color: #C5A059; font-weight: 800; font-size: 6.5pt; letter-spacing: 0.08em;
            text-align: center; padding: 1.6mm; text-transform: uppercase; }
    .back .back-body { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 2mm 3mm; text-align: center; }
    .back .barcode-panel { background: #f8fafc; border: 0.25mm solid #e2e8f0; border-radius: 1.5mm; padding: 1.8mm 3mm; }
    .back .barcode-svg { max-width: 100%; display: block; }
    .back .barcode-num { font-family: 'Courier New', monospace; font-size: 6.5pt; color: #334155; margin-top: 0.5mm; letter-spacing: 0.08em; font-weight: 700; }
    .back .rule { font-size: 5.3pt; color: #64748b; margin-top: 2.2mm; line-height: 1.55; }
    .back .rule .rule-title { color: #0A2E4D; font-weight: 800; display: flex; align-items: center; justify-content: center; gap: 1mm; margin-bottom: 0.8mm; }
    .back .rule .rule-title .material-symbols-outlined { font-size: 6.5pt; color: #C5A059; }
    .back .rule .contact-line { display: flex; align-items: center; justify-content: center; gap: 1mm; margin-top: 0.6mm; }
    .back .rule .contact-line .material-symbols-outlined { font-size: 6pt; color: #94a3b8; }
    .back .legal { font-size: 4.6pt; color: #cbd5e1; margin-top: 2mm; }

    @media print {
        .no-print { display: none; }
        body { margin: 0; background: #fff; }
        .card { box-shadow: none; border: 0.2mm solid #cbd5e1; }
    }
    /* Belt-and-suspenders so navy/gold backgrounds survive printing even if
       the browser's print dialog doesn't have "background graphics" on. */
    .card, .card * {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
        color-adjust: exact;
    }
</style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()">Print ID Cards</button>
        <p>Tip: in the print dialog, make sure "Background graphics" is turned on for the navy/gold colors to appear on paper.</p>
    </div>

    <div class="sheet">
        <?php foreach ($cards as $st):
            $full_name = trim($st['first_name'].' '.$st['last_name']);
            $initials  = strtoupper(substr($st['first_name'],0,1).substr($st['last_name'],0,1));
            $photo_src = !empty($st['passport_photo']) ? '../' . $st['passport_photo'] : '';
        ?>
        <!-- FRONT -->
        <div class="card front">
            <div class="accent-stripe"></div>
            <div class="band">
                <div class="session-tag"><?php echo htmlspecialchars($st['session_name'] ?: 'Session —'); ?></div>
                <div class="crest"><img src="../asset/favicon.png" alt="G.O.L.A Crest"></div>
                <div>
                    <div class="school">G.O.L.A</div>
                    <div class="tagline">Goodness Omogo Leadership Academy</div>
                </div>
            </div>
            <div class="body">
                <?php if ($photo_src): ?>
                <img class="photo" src="<?php echo htmlspecialchars($photo_src); ?>" alt="">
                <?php else: ?>
                <div class="photo-placeholder"><?php echo htmlspecialchars($initials); ?></div>
                <?php endif; ?>
                <div class="info">
                    <div class="name"><?php echo htmlspecialchars($full_name); ?></div>
                    <div class="reg-badge"><?php echo htmlspecialchars($st['student_id']); ?></div>
                    <dl>
                        <dt>Class</dt><dd><?php echo htmlspecialchars($st['class_name'].' '.$st['arm']); ?></dd>
                        <dt>Gender</dt><dd><?php echo htmlspecialchars($st['gender']); ?></dd>
                        <?php if (!empty($st['blood_group'])): ?><dt>Blood</dt><dd><?php echo htmlspecialchars($st['blood_group']); ?></dd><?php endif; ?>
                    </dl>
                </div>
                <div class="wave"></div>
            </div>
            <div class="foot"><span class="status-dot"></span>This card remains the property of G.O.L.A</div>
        </div>

        <!-- BACK -->
        <div class="card back">
            <div class="back-head">G.O.L.A &middot; Student Identification</div>
            <div class="back-body">
                <div class="barcode-panel">
                    <svg class="barcode-svg" data-code="<?php echo htmlspecialchars($st['student_id']); ?>"></svg>
                    <div class="barcode-num"><?php echo htmlspecialchars($st['student_id']); ?></div>
                </div>
                <div class="rule">
                    <div class="rule-title"><span class="material-symbols-outlined">info</span>If found, please return to</div>
                    <div>Goodness Omogo Leadership Academy</div>
                    <div class="contact-line"><span class="material-symbols-outlined">location_on</span><?php echo htmlspecialchars($school_address); ?></div>
                    <div class="contact-line"><span class="material-symbols-outlined">call</span><?php echo htmlspecialchars($school_phone); ?></div>
                </div>
                <div class="legal">This card is non-transferable and remains the property of G.O.L.A.</div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <script>
        document.querySelectorAll('.barcode-svg').forEach(function (svg) {
            JsBarcode(svg, svg.dataset.code, { format: 'CODE128', width: 1.4, height: 30, fontSize: 0, margin: 0, displayValue: false });
        });
    </script>
</body>
</html>