<?php 
$page_title = "Academics & Curriculum";
require_once 'config/database.php';

// ── Helper ────────────────────────────────────────────────────────────────────
function getSetting($conn, $key, $default = '') {
    $r = $conn->query("SELECT setting_value FROM school_settings WHERE setting_key='".mysqli_real_escape_string($conn,$key)."' LIMIT 1");
    $row = $r ? $r->fetch_assoc() : null;
    return $row ? $row['setting_value'] : $default;
}

// ── Settings ──────────────────────────────────────────────────────────────────
$current_session  = getSetting($conn, 'admissions_session', '2024/2025');
$prospectus_file  = getSetting($conn, 'prospectus_file', '');

// ── Academic Calendar from DB ─────────────────────────────────────────────────
// Get current session id
$sess_row = $conn->query("SELECT id FROM academic_sessions WHERE is_current=1 LIMIT 1")->fetch_assoc();
$sess_id  = $sess_row ? $sess_row['id'] : 1;

$calendar_events = [];
$cal_q = $conn->query("SELECT * FROM academic_calendar WHERE session_id=$sess_id AND is_active=1 ORDER BY event_date ASC");
if ($cal_q) while ($r = $cal_q->fetch_assoc()) $calendar_events[] = $r;

// ── Upcoming events (from academic_events table) ──────────────────────────────
$all_events_result = $conn->query("SELECT * FROM academic_events WHERE is_active=TRUE AND event_date >= CURDATE() ORDER BY event_date ASC LIMIT 20");
$events_by_month = [];
if ($all_events_result && $all_events_result->num_rows > 0) {
    while ($event = $all_events_result->fetch_assoc()) {
        $key = date('F Y', strtotime($event['event_date']));
        $events_by_month[$key][] = $event;
    }
}

// ── Subjects ──────────────────────────────────────────────────────────────────
$subjects_result = $conn->query("SELECT * FROM curriculum_subjects WHERE is_active=TRUE ORDER BY display_order ASC, subject_name ASC");
$subjects_by_category = ['Core'=>[],'Vocational'=>[],'Languages'=>[],'Elective'=>[]];
if ($subjects_result) {
    while ($s = $subjects_result->fetch_assoc())
        $subjects_by_category[$s['category']][] = $s;
}

// ── Departments ───────────────────────────────────────────────────────────────
$departments_result = $conn->query("SELECT * FROM academic_departments WHERE is_active=TRUE ORDER BY display_order ASC");

// ── Category config ───────────────────────────────────────────────────────────
$cal_cat_colors = [
    'Term Start' => ['bg-green-100 text-green-700',  'bg-green-500'],
    'Term End'   => ['bg-red-100 text-red-700',      'bg-red-500'],
    'Holiday'    => ['bg-blue-100 text-blue-700',    'bg-blue-500'],
    'Exam'       => ['bg-amber-100 text-amber-700',  'bg-amber-500'],
    'Event'      => ['bg-purple-100 text-purple-700','bg-purple-500'],
    'Deadline'   => ['bg-orange-100 text-orange-700','bg-orange-500'],
    'Other'      => ['bg-slate-100 text-slate-600',  'bg-slate-400'],
];

include 'includes/header.php'; 
?>

<!-- Page Header -->
<section class="py-16 bg-gradient-to-br from-primary via-[#0A3556] to-slate-800 text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div class="inline-flex items-center gap-2 px-4 py-1.5 bg-gold/20 border border-gold/30 rounded-full text-gold font-semibold text-sm uppercase tracking-widest mb-6">
            <span class="material-symbols-outlined text-sm">school</span>
            <?php echo htmlspecialchars($current_session); ?> Academic Session
        </div>
        <h1 class="text-5xl lg:text-6xl font-display font-black mb-4">Academics & Curriculum</h1>
        <p class="text-xl text-slate-300 max-w-3xl mx-auto leading-relaxed">
            Empowering future leaders through academic excellence, critical thinking, and a holistic curriculum designed for the 21st-century global stage.
        </p>
    </div>
</section>

<!-- ══════════════════════════════════════════════
     ACADEMIC CALENDAR
══════════════════════════════════════════════ -->
<section id="calendar" class="py-16 bg-slate-50 dark:bg-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Header row -->
        <div class="flex flex-wrap items-center justify-between gap-4 mb-10">
            <div>
                <h2 class="text-3xl font-display font-black text-primary dark:text-gold">Academic Calendar</h2>
                <p class="text-slate-500 dark:text-slate-400 mt-1"><?php echo htmlspecialchars($current_session); ?> Session</p>
            </div>
            <?php if ($prospectus_file): ?>
            <a href="uploads/prospectus/<?php echo htmlspecialchars($prospectus_file); ?>" target="_blank"
               class="inline-flex items-center gap-2 bg-primary text-white px-6 py-3 rounded-xl font-semibold hover:bg-primary/90 transition-all shadow-md">
                <span class="material-symbols-outlined">download</span>Download Prospectus PDF
            </a>
            <?php endif; ?>
        </div>

        <?php if (empty($calendar_events)): ?>
        <!-- Fallback: no calendar events yet -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-12 text-center">
            <span class="material-symbols-outlined text-6xl text-slate-200 dark:text-slate-700 block mb-3">calendar_month</span>
            <p class="font-semibold text-slate-600 dark:text-slate-400">Calendar events will appear here once published by admin.</p>
        </div>
        <?php else: ?>

        <!-- Calendar Grid -->
        <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-4">
            <?php foreach ($calendar_events as $i => $ev):
                $cats   = $cal_cat_colors[$ev['category']] ?? $cal_cat_colors['Other'];
                $badge  = $cats[0];
                $dot    = $cats[1];
                $is_past = strtotime($ev['event_date']) < strtotime('today');
            ?>
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5 flex gap-4 hover:shadow-md transition-all <?php echo $is_past ? 'opacity-60' : ''; ?>">
                <!-- Date block -->
                <div class="flex-shrink-0 text-center bg-primary text-white rounded-xl w-14 h-14 flex flex-col items-center justify-center">
                    <span class="text-xl font-black leading-none"><?php echo date('d', strtotime($ev['event_date'])); ?></span>
                    <span class="text-xs font-semibold uppercase"><?php echo date('M', strtotime($ev['event_date'])); ?></span>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between gap-2 mb-1">
                        <p class="font-bold text-slate-900 dark:text-white text-sm leading-snug"><?php echo htmlspecialchars($ev['title']); ?></p>
                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold flex-shrink-0 <?php echo $badge; ?>"><?php echo $ev['category']; ?></span>
                    </div>
                    <?php if ($ev['end_date'] && $ev['end_date'] !== $ev['event_date']): ?>
                    <p class="text-xs text-slate-400">
                        Until <?php echo date('d M Y', strtotime($ev['end_date'])); ?>
                    </p>
                    <?php endif; ?>
                    <?php if ($ev['description']): ?>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 line-clamp-2"><?php echo htmlspecialchars($ev['description']); ?></p>
                    <?php endif; ?>
                    <?php if ($is_past): ?>
                    <span class="text-xs text-slate-400 italic">Completed</span>
                    <?php elseif (strtotime($ev['event_date']) <= strtotime('+7 days')): ?>
                    <span class="text-xs text-amber-600 font-semibold">Upcoming soon</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Legend -->
        <div class="flex flex-wrap gap-3 mt-6">
            <?php foreach ($cal_cat_colors as $cat => $cols): ?>
            <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $cols[0]; ?>"><?php echo $cat; ?></span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- ══════════════════════════════════════════════
     CURRICULUM STRUCTURE
══════════════════════════════════════════════ -->
<section class="py-20 bg-white dark:bg-background-dark">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-4xl font-display font-black text-primary dark:text-gold mb-4">Our Curriculum Structure</h2>
            <p class="text-slate-600 dark:text-slate-400 max-w-2xl mx-auto">Comprehensive education across all levels — Junior Secondary (JSS 1–3) and Senior Secondary (SSS 1–3)</p>
        </div>

        <!-- Filter Tabs -->
        <div class="flex justify-center gap-3 mb-12 flex-wrap">
            <button onclick="switchLevel('all')" class="level-tab active px-6 py-2.5 rounded-xl font-semibold text-sm transition-all bg-primary text-white shadow-md" data-level="all">All Subjects</button>
            <button onclick="switchLevel('jss')" class="level-tab px-6 py-2.5 rounded-xl font-semibold text-sm transition-all bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200" data-level="jss">Junior Secondary (JSS 1-3)</button>
            <button onclick="switchLevel('sss')" class="level-tab px-6 py-2.5 rounded-xl font-semibold text-sm transition-all bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200" data-level="sss">Senior Secondary (SSS 1-3)</button>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php
            $cat_config = [
                'Core'       => ['bookmark',     'blue-600',   'blue-500/10',   'Core Subjects'],
                'Vocational' => ['palette',       'gold',       'gold/10',       'Vocational & Creative'],
                'Languages'  => ['translate',     'purple-600', 'purple-500/10', 'Languages'],
                'Elective'   => ['auto_stories',  'green-600',  'green-500/10',  'Elective Subjects'],
            ];
            foreach ($cat_config as $cat => [$icon, $color, $bg, $label]):
                $items = $subjects_by_category[$cat] ?? [];
                if (empty($items)) continue;
            ?>
            <div class="bg-slate-50 dark:bg-slate-800 rounded-2xl p-6 hover:shadow-xl transition-all">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-11 h-11 bg-<?php echo $bg; ?> rounded-xl flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-xl text-<?php echo $color; ?>"><?php echo $icon; ?></span>
                    </div>
                    <h3 class="font-bold text-primary dark:text-gold text-base leading-tight"><?php echo $label; ?></h3>
                </div>
                <ul class="space-y-2.5">
                    <?php foreach ($items as $subject):
                        $lvl = strtolower(substr($subject['class_level'], 0, 3));
                    ?>
                    <li class="subject-item flex items-start gap-2 text-sm text-slate-700 dark:text-slate-300" data-level="<?php echo $lvl; ?>">
                        <span class="text-gold mt-1 flex-shrink-0">•</span>
                        <div>
                            <span class="font-medium"><?php echo htmlspecialchars($subject['subject_name']); ?></span>
                            <?php if ($subject['class_level'] !== 'All'): ?>
                            <span class="text-xs text-slate-400 ml-1">(<?php echo $subject['class_level']; ?>)</span>
                            <?php endif; ?>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════════
     DEPARTMENTS
══════════════════════════════════════════════ -->
<?php if ($departments_result && $departments_result->num_rows > 0): ?>
<section class="py-20 bg-slate-50 dark:bg-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-4xl font-display font-black text-primary dark:text-gold mb-4">Academic Departments</h2>
            <p class="text-slate-600 dark:text-slate-400">Specialised faculties driving excellence</p>
        </div>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php
            $dept_gradients = ['from-primary to-slate-700','from-gold to-yellow-700','from-purple-600 to-purple-900','from-green-600 to-green-900','from-red-600 to-red-900','from-blue-600 to-blue-900'];
            $di = 0;
            while ($dept = $departments_result->fetch_assoc()):
                $grad = $dept_gradients[$di % count($dept_gradients)];
            ?>
            <div class="group bg-gradient-to-br <?php echo $grad; ?> rounded-2xl p-8 text-white shadow-lg hover:shadow-2xl hover:-translate-y-1 transition-all relative overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-white/5 rounded-full -translate-y-1/2 translate-x-1/2"></div>
                <span class="text-xs font-semibold text-white/60 uppercase tracking-widest">Faculty</span>
                <h3 class="text-xl font-bold mt-2 mb-3"><?php echo htmlspecialchars($dept['department_name']); ?></h3>
                <p class="text-white/80 text-sm leading-relaxed"><?php echo htmlspecialchars($dept['subjects_covered']); ?></p>
            </div>
            <?php $di++; endwhile; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ══════════════════════════════════════════════
     UPCOMING EVENTS
══════════════════════════════════════════════ -->
<?php if (!empty($events_by_month)): ?>
<section id="events" class="py-20 bg-white dark:bg-background-dark">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-4xl font-display font-black text-primary dark:text-gold mb-4">Upcoming Events</h2>
            <p class="text-slate-600 dark:text-slate-400">What's happening at GOLA</p>
        </div>
        <div class="space-y-10">
            <?php foreach ($events_by_month as $month => $events): ?>
            <div>
                <h3 class="text-lg font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-4 flex items-center gap-3">
                    <span class="flex-1 h-px bg-slate-200 dark:bg-slate-700"></span>
                    <?php echo $month; ?>
                    <span class="flex-1 h-px bg-slate-200 dark:bg-slate-700"></span>
                </h3>
                <div class="space-y-3">
                    <?php foreach ($events as $i => $ev): ?>
                    <a href="event-detail.php?id=<?php echo $ev['id']; ?>"
                       class="flex items-start gap-5 p-5 bg-slate-50 dark:bg-slate-800 hover:bg-gold/5 dark:hover:bg-slate-700 rounded-2xl border border-slate-100 dark:border-slate-700 hover:border-gold/30 transition-all group">
                        <div class="<?php echo $i===0?'bg-primary':'bg-slate-200 dark:bg-slate-700'; ?> <?php echo $i===0?'text-white':'text-slate-600 dark:text-slate-300'; ?> rounded-xl w-14 h-14 flex flex-col items-center justify-center flex-shrink-0">
                            <span class="text-xl font-black leading-none"><?php echo date('d', strtotime($ev['event_date'])); ?></span>
                            <span class="text-xs font-semibold uppercase"><?php echo date('M', strtotime($ev['event_date'])); ?></span>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-bold text-slate-900 dark:text-white group-hover:text-primary dark:group-hover:text-gold transition-colors"><?php echo htmlspecialchars($ev['title']); ?></h4>
                            <?php if ($ev['event_time']): ?>
                            <p class="text-xs text-slate-500 mt-1 flex items-center gap-1">
                                <span class="material-symbols-outlined text-xs text-gold">schedule</span>
                                <?php echo htmlspecialchars($ev['event_time']); ?>
                            </p>
                            <?php endif; ?>
                            <p class="text-sm text-slate-600 dark:text-slate-400 mt-1 line-clamp-1"><?php echo htmlspecialchars($ev['description']); ?></p>
                        </div>
                        <span class="material-symbols-outlined text-slate-300 group-hover:text-gold transition-colors self-center">chevron_right</span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ══════════════════════════════════════════════
     EDUCATIONAL PHILOSOPHY
══════════════════════════════════════════════ -->
<section class="py-20 bg-slate-50 dark:bg-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-16 items-center">
            <div>
                <h2 class="text-4xl font-display font-black text-primary dark:text-gold mb-4">Our Educational Philosophy</h2>
                <p class="text-xl text-gold font-semibold mb-6 italic">"To Learn, To Grow, To Lead"</p>
                <div class="space-y-4 text-slate-600 dark:text-slate-400 leading-relaxed text-base">
                    <p>At Goodness Omogo Leadership Academy, we believe that education is more than just passing exams. It's about character formation, fostering curiosity, and preparing students to take up leadership roles in a complex world.</p>
                    <p>Our curriculum is benchmarked against international standards while remaining deeply rooted in the rich heritage of Nigeria. We combine rigorous academics with practical skills, creativity, and ethical values.</p>
                </div>
                <div class="mt-8 space-y-4">
                    <?php foreach ([
                        ['psychology',  'Holistic Development',             'Nurturing mind, body, and character'],
                        ['devices',     'Technology Integrated Learning',   '21st-century skills for digital natives'],
                        ['groups_2',    'Small Class Sizes',                '15:1 student-teacher ratio for personal attention'],
                    ] as [$icon, $title, $desc]): ?>
                    <div class="flex items-start gap-4">
                        <div class="w-11 h-11 bg-gold/10 rounded-xl flex items-center justify-center flex-shrink-0">
                            <span class="material-symbols-outlined text-gold"><?php echo $icon; ?></span>
                        </div>
                        <div>
                            <h4 class="font-bold text-primary dark:text-gold"><?php echo $title; ?></h4>
                            <p class="text-sm text-slate-500 dark:text-slate-400"><?php echo $desc; ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="relative">
                <div class="bg-gradient-to-br from-gold/20 to-primary/20 rounded-3xl p-3 transform rotate-2 shadow-2xl">
                    <img src="./asset/courses-5.jpg" alt="Classroom" class="w-full rounded-2xl shadow-xl transform -rotate-2">
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="py-20 bg-gradient-to-r from-primary to-slate-800 text-white">
    <div class="max-w-5xl mx-auto px-4 text-center">
        <h2 class="text-4xl lg:text-5xl font-display font-black mb-6">Ready to Join Our Academic Community?</h2>
        <p class="text-xl text-slate-300 mb-10 max-w-3xl mx-auto">Experience world-class education that prepares your child for success in Nigeria and beyond.</p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="admissions.php" class="bg-gold text-primary font-bold px-10 py-4 rounded-xl hover:scale-105 transition-transform shadow-lg flex items-center justify-center gap-2">
                <span class="material-symbols-outlined">description</span>Apply for Admission
            </a>
            <a href="contact.php" class="bg-white/10 hover:bg-white/20 border border-white/20 font-bold px-10 py-4 rounded-xl transition-all flex items-center justify-center gap-2">
                <span class="material-symbols-outlined">call</span>Contact Admissions
            </a>
        </div>
    </div>
</section>

<script>
function switchLevel(level) {
    document.querySelectorAll('.level-tab').forEach(tab => {
        const active = tab.dataset.level === level;
        tab.classList.toggle('bg-primary', active);
        tab.classList.toggle('text-white', active);
        tab.classList.toggle('shadow-md', active);
        tab.classList.toggle('bg-slate-100', !active);
        tab.classList.toggle('text-slate-700', !active);
    });
    document.querySelectorAll('.subject-item').forEach(item => {
        const l = item.dataset.level;
        item.style.display = (level === 'all' || l === 'all' || l === level) ? 'flex' : 'none';
    });
}
</script>

<?php include 'includes/footer.php'; ?>