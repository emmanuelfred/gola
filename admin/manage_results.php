<?php
require_once 'auth_check.php';
$page_title = "Manage Results";

$classes  = $conn->query("SELECT id, class_name, arm FROM classes ORDER BY class_name, arm");
$sessions = $conn->query("SELECT id, session_name FROM academic_sessions ORDER BY id DESC");
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
select:disabled{opacity:0.5;cursor:not-allowed;}
.mode-card{cursor:pointer;transition:all .2s;}
.mode-card.selected{border-color:#C5A059;background:#fffbf2;box-shadow:0 0 0 3px rgba(197,160,89,.15);}
.mode-card:not(.selected):hover{border-color:#94a3b8;}
</style>
</head>
<body class="bg-slate-50 font-sans">
<div class="flex h-screen overflow-hidden">
<?php include 'admin_sidebar.php'; ?>
<div class="flex-1 flex flex-col overflow-hidden">
<?php include 'admin_topbar.php'; ?>
<main class="flex-1 overflow-y-auto p-6 lg:p-8">

<!-- Header -->
<div class="mb-8">
    <h1 class="text-2xl font-bold text-slate-900">Result Management</h1>
    <p class="text-slate-500 text-sm mt-1">Choose how you want to enter results, then fill in the details below.</p>
</div>

<!-- ── MODE SELECTOR ────────────────────────────────────────────── -->
<div class="mb-6">
    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Step 1 — Choose Entry Mode</p>
    <div class="grid md:grid-cols-2 gap-4">

        <!-- Mode A: Subject-first -->
        <div class="mode-card selected border-2 rounded-xl p-5 bg-white" id="modeCardA" onclick="setMode('A')">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 bg-gold/10 rounded-xl flex items-center justify-center flex-shrink-0">
                    <span class="material-symbols-outlined text-gold text-2xl">menu_book</span>
                </div>
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-1">
                        <h3 class="font-bold text-slate-900">By Subject</h3>
                        <span class="px-2 py-0.5 bg-gold/20 text-gold text-xs font-bold rounded-full">SUBJECT TEACHER</span>
                    </div>
                    <p class="text-sm text-slate-500">Pick a class + subject → see all students in that class → enter scores for that one subject.</p>
                    <p class="text-xs text-slate-400 mt-2 italic">Best for subject teachers entering marks for their subject.</p>
                </div>
                <div class="w-5 h-5 rounded-full border-2 border-gold flex items-center justify-center flex-shrink-0 mt-0.5" id="radioA">
                    <div class="w-2.5 h-2.5 rounded-full bg-gold"></div>
                </div>
            </div>
        </div>

        <!-- Mode B: Student-first -->
        <div class="mode-card border-2 border-slate-200 rounded-xl p-5 bg-white" id="modeCardB" onclick="setMode('B')">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 bg-primary/10 rounded-xl flex items-center justify-center flex-shrink-0">
                    <span class="material-symbols-outlined text-primary text-2xl">person_search</span>
                </div>
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-1">
                        <h3 class="font-bold text-slate-900">By Student</h3>
                        <span class="px-2 py-0.5 bg-primary/10 text-primary text-xs font-bold rounded-full">CLASS TEACHER</span>
                    </div>
                    <p class="text-sm text-slate-500">Pick a class → see all students → click a student → enter all their subjects at once.</p>
                    <p class="text-xs text-slate-400 mt-2 italic">Best for class teachers completing a full student report card.</p>
                </div>
                <div class="w-5 h-5 rounded-full border-2 border-slate-300 flex items-center justify-center flex-shrink-0 mt-0.5" id="radioB">
                    <div class="w-2.5 h-2.5 rounded-full bg-transparent"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── FORM A: Subject-first ─────────────────────────────────────── -->
<div id="formA" class="bg-white rounded-xl border border-slate-200 p-6 mb-6 shadow-sm">
    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-4">Step 2 — Select Parameters</p>
    <form method="GET" action="enter_results.php">
        <input type="hidden" name="mode" value="subject">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
            <div>
                <label class="text-xs font-semibold text-slate-600 mb-1.5 block">Session <span class="text-red-500">*</span></label>
                <select name="session_id" required class="w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold">
                    <option value="">Select Session</option>
                    <?php $sessions->data_seek(0); while ($s = $sessions->fetch_assoc()): ?>
                    <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['session_name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div>
                <label class="text-xs font-semibold text-slate-600 mb-1.5 block">Term <span class="text-red-500">*</span></label>
                <select name="term_id" required class="w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold">
                    <option value="">Select Term</option>
                    <option value="1">First Term</option>
                    <option value="2">Second Term</option>
                    <option value="3">Third Term</option>
                </select>
            </div>
            <div>
                <label class="text-xs font-semibold text-slate-600 mb-1.5 block">Class <span class="text-red-500">*</span></label>
                <select name="class_id" id="classSelectA" required class="w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold">
                    <option value="">Select Class</option>
                    <?php $classes->data_seek(0); while ($c = $classes->fetch_assoc()): ?>
                    <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['class_name'].' '.$c['arm']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div>
                <label class="text-xs font-semibold text-slate-600 mb-1.5 block">Subject <span class="text-red-500">*</span></label>
                <div class="relative">
                    <select name="subject_id" id="subjectSelectA" required disabled class="w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold">
                        <option value="">— Select class first —</option>
                    </select>
                    <div id="loaderA" class="hidden absolute right-3 top-1/2 -translate-y-1/2">
                        <svg class="animate-spin h-4 w-4 text-gold" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    </div>
                </div>
                <p id="hintA" class="text-xs text-slate-400 mt-1">Select a class first</p>
            </div>
        </div>
        <button type="submit" id="submitA" disabled
            class="inline-flex items-center gap-2 bg-gold text-primary px-6 py-3 rounded-xl font-bold hover:bg-gold/90 transition-all shadow-sm disabled:opacity-40 disabled:cursor-not-allowed">
            <span class="material-symbols-outlined">edit_note</span>Load Students & Enter Scores
        </button>
    </form>
</div>

<!-- ── FORM B: Student-first ─────────────────────────────────────── -->
<div id="formB" class="hidden bg-white rounded-xl border border-slate-200 p-6 mb-6 shadow-sm">
    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-4">Step 2 — Select Class & Session</p>
    <form method="GET" action="student_results.php">
        <input type="hidden" name="mode" value="student">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5">
            <div>
                <label class="text-xs font-semibold text-slate-600 mb-1.5 block">Session <span class="text-red-500">*</span></label>
                <select name="session_id" id="sessionB" required class="w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold">
                    <option value="">Select Session</option>
                    <?php $sessions->data_seek(0); while ($s = $sessions->fetch_assoc()): ?>
                    <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['session_name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div>
                <label class="text-xs font-semibold text-slate-600 mb-1.5 block">Term <span class="text-red-500">*</span></label>
                <select name="term_id" id="termB" required class="w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold">
                    <option value="">Select Term</option>
                    <option value="1">First Term</option>
                    <option value="2">Second Term</option>
                    <option value="3">Third Term</option>
                </select>
            </div>
            <div>
                <label class="text-xs font-semibold text-slate-600 mb-1.5 block">Class <span class="text-red-500">*</span></label>
                <select name="class_id" id="classSelectB" required class="w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold">
                    <option value="">Select Class</option>
                    <?php $classes->data_seek(0); while ($c = $classes->fetch_assoc()): ?>
                    <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['class_name'].' '.$c['arm']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>
        <div class="flex items-center gap-4">
            <button type="submit" id="submitB" disabled
                class="inline-flex items-center gap-2 bg-primary text-white px-6 py-3 rounded-xl font-bold hover:bg-primary/90 transition-all shadow-sm disabled:opacity-40 disabled:cursor-not-allowed">
                <span class="material-symbols-outlined">groups</span>View Students & Enter All Subjects
            </button>
            <p class="text-xs text-slate-400">You'll see a list of all students — click any one to enter their full result.</p>
        </div>
    </form>
</div>

<!-- Guide -->
<div class="bg-white rounded-xl border border-slate-200 p-6">
    <h2 class="text-sm font-bold text-slate-800 mb-4 flex items-center gap-2">
        <span class="material-symbols-outlined text-gold text-base">help_outline</span>Which mode should I use?
    </h2>
    <div class="grid md:grid-cols-2 gap-4 text-sm">
        <div class="flex gap-3 p-4 bg-gold/5 rounded-xl">
            <span class="material-symbols-outlined text-gold flex-shrink-0">menu_book</span>
            <div>
                <p class="font-semibold text-slate-800 mb-1">By Subject (Subject Teacher)</p>
                <p class="text-slate-500 text-xs">You teach Mathematics across SS2 Science, SS2 Arts, and SS2 Commercial. Use this mode — select each class + Mathematics and enter all their scores in one screen.</p>
            </div>
        </div>
        <div class="flex gap-3 p-4 bg-primary/5 rounded-xl">
            <span class="material-symbols-outlined text-primary flex-shrink-0">person_search</span>
            <div>
                <p class="font-semibold text-slate-800 mb-1">By Student (Class Teacher)</p>
                <p class="text-slate-500 text-xs">You are the class teacher for SS2 Science. Use this mode — select SS2 Science, see all 35 students listed, click each student and enter scores for all 13 of their subjects.</p>
            </div>
        </div>
    </div>
</div>

<!-- Shortcut -->
<div class="mt-5 p-4 bg-slate-100 rounded-xl flex items-center justify-between gap-4">
    <div class="flex items-center gap-2 text-sm text-slate-600">
        <span class="material-symbols-outlined text-slate-400 text-base">settings</span>
        Need to add or change which subjects a class offers?
    </div>
    <a href="manage_class_subjects.php" class="inline-flex items-center gap-1 px-4 py-2 bg-white border border-slate-200 rounded-lg text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-all whitespace-nowrap">
        <span class="material-symbols-outlined text-sm">tune</span>Manage Class Subjects
    </a>
</div>

</main>
</div>
</div>

<script>
// ── Mode toggle ────────────────────────────────────────────────────
function setMode(m) {
    ['A','B'].forEach(x => {
        document.getElementById('modeCard'+x).classList.toggle('selected', x===m);
        document.getElementById('form'+x).classList.toggle('hidden', x!==m);
        const dot = document.getElementById('radio'+x);
        dot.classList.toggle('border-gold', x===m);
        dot.classList.toggle('border-slate-300', x!==m);
        dot.querySelector('div').classList.toggle('bg-gold', x===m);
        dot.querySelector('div').classList.toggle('bg-transparent', x!==m);
    });
}

// ── Subject AJAX loader for Mode A ────────────────────────────────
function setupSubjectLoader(classSelectId, subjectSelectId, loaderId, hintId, submitId) {
    const cls  = document.getElementById(classSelectId);
    const sub  = document.getElementById(subjectSelectId);
    const load = document.getElementById(loaderId);
    const hint = document.getElementById(hintId);
    const btn  = document.getElementById(submitId);

    cls.addEventListener('change', function() {
        sub.innerHTML = '<option value="">Loading…</option>';
        sub.disabled = true;
        btn.disabled = true;
        if (!this.value) { sub.innerHTML='<option value="">— Select class first —</option>'; hint.textContent='Select a class first'; return; }
        load.classList.remove('hidden');
        hint.textContent = 'Loading…';
        fetch('get_class_subjects.php?class_id=' + this.value)
            .then(r => r.json())
            .then(subjects => {
                load.classList.add('hidden');
                sub.innerHTML = '<option value="">Select Subject</option>';
                if (!subjects.length) {
                    sub.innerHTML = '<option value="">No subjects assigned to this class</option>';
                    hint.textContent = 'No subjects assigned — go to Manage Class Subjects.';
                    return;
                }
                subjects.forEach(s => {
                    const o = document.createElement('option');
                    o.value = s.id;
                    o.textContent = s.subject_name + ' (' + s.subject_code + ')';
                    sub.appendChild(o);
                });
                sub.disabled = false;
                hint.textContent = subjects.length + ' subject(s) available';
                sub.addEventListener('change', () => checkReady(classSelectId, subjectSelectId, submitId));
            })
            .catch(() => { load.classList.add('hidden'); hint.textContent='Error loading — try again.'; });
    });
}

function checkReady(clsId, subId, btnId) {
    const cls = document.getElementById(clsId).value;
    const sub = document.getElementById(subId)?.value;
    document.getElementById(btnId).disabled = !(cls && sub);
}

setupSubjectLoader('classSelectA','subjectSelectA','loaderA','hintA','submitA');

// ── Mode B submit enabler ──────────────────────────────────────────
function checkB() {
    const ok = document.getElementById('sessionB').value &&
               document.getElementById('termB').value &&
               document.getElementById('classSelectB').value;
    document.getElementById('submitB').disabled = !ok;
}
['sessionB','termB','classSelectB'].forEach(id => document.getElementById(id).addEventListener('change', checkB));
</script>
</body>
</html>
