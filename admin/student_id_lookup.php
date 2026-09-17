<?php
require_once 'auth_check.php';
require_once 'includes/permission_helper.php';
requirePermission('students');
$page_title = "Student ID Lookup";

// ── AJAX: look up a student by reg number (from the barcode, or typed in) ──
if (isset($_GET['ajax']) && $_GET['ajax'] === 'lookup_reg') {
    header('Content-Type: application/json');
    $reg_no = trim($_GET['code'] ?? '');
    $response = ['found' => false];
    if ($reg_no) {
        $stmt = $conn->prepare("
            SELECT s.*, c.class_name, c.arm, acs.session_name
            FROM students s
            JOIN classes c ON s.class_id = c.id
            LEFT JOIN academic_sessions acs ON s.session_id = acs.id
            WHERE s.student_id = ?
        ");
        $stmt->bind_param("s", $reg_no);
        $stmt->execute();
        $st = $stmt->get_result()->fetch_assoc();
        if ($st) {
            $response = [
                'found'        => true,
                'name'         => trim($st['first_name'].' '.$st['last_name']),
                'reg_no'       => $st['student_id'],
                'class'        => trim($st['class_name'].' '.$st['arm']),
                'gender'       => $st['gender'],
                'status'       => $st['status'],
                'student_type' => $st['student_type'],
                'blood_group'  => $st['blood_group'],
                'genotype'     => $st['genotype'],
                'dob'          => $st['date_of_birth'],
                'phone'        => $st['phone'],
                'guardian_name'  => $st['guardian_name'] ?: $st['father_name'] ?: $st['mother_name'],
                'guardian_phone' => $st['guardian_phone'] ?: $st['father_phone'] ?: $st['mother_phone'],
                'session'      => $st['session_name'],
                'photo_url'    => !empty($st['passport_photo']) ? '../' . $st['passport_photo'] : '',
                'view_url'     => 'view_student.php?id=' . $st['id'],
            ];
        }
    }
    echo json_encode($response);
    exit;
}
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

<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-900">Student ID Lookup</h1>
    <p class="text-slate-500 text-sm mt-1">Scan the barcode on the back of a student ID card, or type the reg. number below.</p>
</div>

<div class="max-w-xl mx-auto">
    <div id="scanBox" class="mb-4 bg-primary/5 border-2 border-dashed border-primary/30 rounded-xl p-3 flex items-center gap-3">
        <span class="material-symbols-outlined text-primary flex-shrink-0">barcode_scanner</span>
        <input type="text" id="scanInput" autocomplete="off" readonly placeholder="Ready to scan…"
            class="flex-1 border-0 bg-transparent text-sm font-medium text-slate-800 focus:ring-0 placeholder:text-slate-400" style="caret-color:transparent">
    </div>

    <form id="manualForm" class="flex gap-2 mb-2" onsubmit="return false;">
        <input type="text" id="manualInput" placeholder="Or type reg. number, e.g. GOLA/2024/001"
            class="flex-1 border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold">
        <button type="button" onclick="submitManual()" class="px-5 py-2 bg-primary text-white text-sm font-semibold rounded-lg hover:bg-primary/90">Search</button>
    </form>
    <p id="lookupMessage" class="text-xs mb-6"></p>

    <div id="resultCard" class="hidden bg-white rounded-2xl border border-slate-200 p-6">
        <div class="flex gap-4 items-start">
            <div id="resultPhotoWrap" class="w-20 h-24 rounded-lg overflow-hidden flex-shrink-0 bg-slate-100 flex items-center justify-center">
                <img id="resultPhoto" class="w-full h-full object-cover hidden" alt="">
                <span id="resultInitials" class="text-xl font-bold text-slate-400"></span>
            </div>
            <div class="flex-1 min-w-0">
                <h2 id="resultName" class="text-lg font-bold text-slate-900"></h2>
                <p id="resultClass" class="text-sm text-slate-500"></p>
                <span id="resultStatus" class="inline-block mt-1 px-2 py-0.5 rounded-full text-xs font-semibold"></span>
            </div>
        </div>

        <div class="mt-5">
            <label class="text-xs font-semibold text-slate-500 mb-1 block">Reg. Number</label>
            <div class="flex gap-2">
                <input type="text" id="resultRegNo" readonly onclick="this.select()"
                    class="flex-1 border-slate-200 rounded-lg text-sm font-mono font-bold text-primary bg-slate-50 focus:ring-gold focus:border-gold">
                <button type="button" onclick="copyRegNo()" id="copyBtn"
                    class="px-4 py-2 bg-gold text-primary text-sm font-bold rounded-lg hover:bg-gold/90 inline-flex items-center gap-1.5 flex-shrink-0">
                    <span class="material-symbols-outlined text-base">content_copy</span><span id="copyBtnText">Copy</span>
                </button>
            </div>
        </div>

        <dl class="grid grid-cols-2 gap-x-4 gap-y-3 mt-5 text-sm">
            <div><dt class="text-xs font-semibold text-slate-400 uppercase">Gender</dt><dd id="resultGender" class="text-slate-700"></dd></div>
            <div><dt class="text-xs font-semibold text-slate-400 uppercase">Student Type</dt><dd id="resultType" class="text-slate-700"></dd></div>
            <div><dt class="text-xs font-semibold text-slate-400 uppercase">Date of Birth</dt><dd id="resultDob" class="text-slate-700"></dd></div>
            <div><dt class="text-xs font-semibold text-slate-400 uppercase">Blood Group / Genotype</dt><dd id="resultBlood" class="text-slate-700"></dd></div>
            <div><dt class="text-xs font-semibold text-slate-400 uppercase">Phone</dt><dd id="resultPhone" class="text-slate-700"></dd></div>
            <div><dt class="text-xs font-semibold text-slate-400 uppercase">Session</dt><dd id="resultSession" class="text-slate-700"></dd></div>
            <div class="col-span-2"><dt class="text-xs font-semibold text-slate-400 uppercase">Guardian Contact</dt><dd id="resultGuardian" class="text-slate-700"></dd></div>
        </dl>

        <a id="resultViewLink" href="#" class="inline-flex items-center gap-2 mt-5 text-sm font-semibold text-primary hover:underline">
            <span class="material-symbols-outlined text-base">open_in_new</span>Open full profile
        </a>
    </div>
</div>

</main>
</div>
</div>

<script>
// ── Scan capture (same technique as the canteen "Scan Product" box) ────────
// The scanner is a USB HID device — a burst of keystrokes ending in a short
// pause is treated as "scan finished," so it works whether or not the
// scanner is configured to send an Enter/Tab terminator.
let scanBuffer = '';
let scanIdleTimer = null;
const scanInput = document.getElementById('scanInput');

function isRealFieldFocused() {
    const active = document.activeElement;
    if (!active || active === scanInput) return false;
    return active.tagName === 'INPUT' || active.tagName === 'TEXTAREA' || active.tagName === 'SELECT';
}

document.addEventListener('keydown', function (e) {
    if (isRealFieldFocused()) return;

    if (e.key === 'Enter' || e.key === 'Tab') {
        if (!scanBuffer) return;
        e.preventDefault();
        flushScanBuffer();
        return;
    }
    if (e.key.length === 1) {
        scanBuffer += e.key;
        scanInput.value = scanBuffer;
        clearTimeout(scanIdleTimer);
        scanIdleTimer = setTimeout(flushScanBuffer, 150);
    }
});

function flushScanBuffer() {
    clearTimeout(scanIdleTimer);
    const code = scanBuffer.trim();
    scanBuffer = '';
    scanInput.value = '';
    if (code) lookupStudent(code);
}

function submitManual() {
    const val = document.getElementById('manualInput').value.trim();
    if (val) lookupStudent(val);
}
document.getElementById('manualInput').addEventListener('keydown', function (e) {
    if (e.key === 'Enter') { e.preventDefault(); submitManual(); }
});

// ── Lookup + render ─────────────────────────────────────────────────────
function lookupStudent(code) {
    const msgEl = document.getElementById('lookupMessage');
    msgEl.innerHTML = '<span class="text-slate-400">Looking up ' + code + '…</span>';

    fetch('student_id_lookup.php?ajax=lookup_reg&code=' + encodeURIComponent(code))
        .then(async r => {
            const text = await r.text();
            if (r.redirected || !r.ok) throw new Error('SESSION_EXPIRED');
            try { return JSON.parse(text); } catch (e) { throw new Error('BAD_RESPONSE'); }
        })
        .then(data => {
            if (!data.found) {
                msgEl.innerHTML = '<span class="text-red-500">No student found with that reg. number.</span>';
                document.getElementById('resultCard').classList.add('hidden');
                return;
            }
            msgEl.innerHTML = '';
            renderResult(data);
        })
        .catch(err => {
            const text = err.message === 'SESSION_EXPIRED'
                ? 'Your session has expired — refresh the page and log in again.'
                : 'Lookup failed — check your connection and try again.';
            msgEl.innerHTML = '<span class="text-red-500">' + text + '</span>';
        });
}

const statusColors = { Active: 'bg-green-100 text-green-700', Graduated: 'bg-blue-100 text-blue-700', Withdrawn: 'bg-yellow-100 text-yellow-700', Suspended: 'bg-red-100 text-red-700' };

function renderResult(d) {
    document.getElementById('resultName').textContent = d.name;
    document.getElementById('resultClass').textContent = d.class;
    document.getElementById('resultStatus').textContent = d.status;
    document.getElementById('resultStatus').className = 'inline-block mt-1 px-2 py-0.5 rounded-full text-xs font-semibold ' + (statusColors[d.status] || 'bg-slate-100 text-slate-700');
    document.getElementById('resultRegNo').value = d.reg_no;
    document.getElementById('resultGender').textContent = d.gender || '—';
    document.getElementById('resultType').textContent = d.student_type || '—';
    document.getElementById('resultDob').textContent = d.dob || '—';
    document.getElementById('resultBlood').textContent = (d.blood_group || '—') + ' / ' + (d.genotype || '—');
    document.getElementById('resultPhone').textContent = d.phone || '—';
    document.getElementById('resultSession').textContent = d.session || '—';
    document.getElementById('resultGuardian').textContent = (d.guardian_name || '—') + (d.guardian_phone ? ' — ' + d.guardian_phone : '');
    document.getElementById('resultViewLink').href = d.view_url;

    const img = document.getElementById('resultPhoto');
    const initials = document.getElementById('resultInitials');
    if (d.photo_url) {
        img.src = d.photo_url;
        img.classList.remove('hidden');
        initials.classList.add('hidden');
    } else {
        img.classList.add('hidden');
        initials.classList.remove('hidden');
        initials.textContent = d.name.split(' ').map(w => w[0]).slice(0,2).join('').toUpperCase();
    }

    document.getElementById('resultCard').classList.remove('hidden');
}

// ── Copy reg no — tries the modern Clipboard API, falls back to the older
// execCommand so it still works on plain http:// (Clipboard API needs a
// secure context, but this select+execCommand path doesn't). ────────────
function copyRegNo() {
    const input = document.getElementById('resultRegNo');
    input.select();
    input.setSelectionRange(0, 99999);

    function showCopied() {
        const label = document.getElementById('copyBtnText');
        label.textContent = 'Copied!';
        setTimeout(() => { label.textContent = 'Copy'; }, 1500);
    }

    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(input.value).then(showCopied).catch(() => {
            document.execCommand('copy');
            showCopied();
        });
    } else {
        document.execCommand('copy');
        showCopied();
    }
}
</script>
</body>
</html>
