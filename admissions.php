<?php
$page_title = "Admissions - Goodness Omogo Leadership Academy";
require_once 'config/database.php';
require_once 'includes/email.php';   // ← PHPMailer helper

function getSetting($conn, $key, $default = '') {
    $r = $conn->query("SELECT setting_value FROM school_settings WHERE setting_key='".mysqli_real_escape_string($conn,$key)."' LIMIT 1");
    $row = $r ? $r->fetch_assoc() : null;
    return $row ? $row['setting_value'] : $default;
}

$admissions_open  = getSetting($conn, 'admissions_open', '1');
$current_session  = getSetting($conn, 'admissions_session', '2025/2026');
$app_fee_amount   = getSetting($conn, 'application_fee_amount', '5000');
$exam_date        = getSetting($conn, 'entrance_exam_date', '');
$school_email     = getSetting($conn, 'school_email', 'golaedu2026@gmail.com');
$prospectus_file  = getSetting($conn, 'prospectus_file', '');

$req_success = '';
$req_error   = '';

// ── Handle Prospectus Request ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['prospectus_request'])) {
    $parent_name  = trim($_POST['parent_name'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $phone        = trim($_POST['phone'] ?? '');
    $student_name = trim($_POST['student_name'] ?? '');
    $grade_level  = trim($_POST['grade_level'] ?? '');
    $how_heard    = trim($_POST['how_heard'] ?? '');

    if (!$parent_name || !$email || !$student_name || !$grade_level) {
        $req_error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $req_error = 'Please enter a valid email address.';
    } else {
        $pn = mysqli_real_escape_string($conn, $parent_name);
        $em = mysqli_real_escape_string($conn, $email);
        $ph = mysqli_real_escape_string($conn, $phone);
        $sn = mysqli_real_escape_string($conn, $student_name);
        $gl = mysqli_real_escape_string($conn, $grade_level);
        $hw = mysqli_real_escape_string($conn, $how_heard);

        $saved = $conn->query("INSERT INTO prospectus_requests
            (parent_name, email, phone, student_name, grade_level, how_heard)
            VALUES ('$pn','$em','$ph','$sn','$gl','$hw')");

        if ($saved) {
            // Find the prospectus PDF path
            $prospectus_path = __DIR__ . '/uploads/prospectus/' . $prospectus_file;

            // Send via PHPMailer — attaches the PDF and notifies admin
            $mail_result = sendProspectus([
                'parent_name'  => $parent_name,
                'email'        => $email,
                'phone'        => $phone,
                'student_name' => $student_name,
                'grade_level'  => $grade_level,
                'how_heard'    => $how_heard,
            ], $prospectus_path);

            if (!$mail_result['ok']) {
                // Mail failed — log it and update status so admin can follow up
                error_log('Prospectus mail failed for ' . $email . ': ' . $mail_result['error']);
                $conn->query("UPDATE prospectus_requests SET status='Failed',
                    admin_notes='Auto-send failed: " . mysqli_real_escape_string($conn, $mail_result['error']) . "'
                    WHERE email='$em' ORDER BY id DESC LIMIT 1");
            } else {
                // Mark as sent automatically
                $conn->query("UPDATE prospectus_requests SET status='Sent', sent_at=NOW()
                    WHERE email='$em' ORDER BY id DESC LIMIT 1");
            }

            $req_success = $email;
        } else {
            $req_error = 'Something went wrong. Please try again.';
        }
    }
}

include 'includes/header.php';
?>

<!-- ══════════════════════════════════════════════
     HERO
══════════════════════════════════════════════ -->
<section class="relative py-24 lg:py-32 bg-gradient-to-br from-primary via-[#0A3556] to-slate-800 text-white overflow-hidden">
    <div class="absolute inset-0 opacity-10" style="background:url('./asset/header.jpg') center/cover no-repeat;"></div>
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto text-center">
            <div class="inline-flex items-center gap-2 px-4 py-1.5 bg-gold/20 border border-gold/30 rounded-full text-gold font-semibold text-sm uppercase tracking-widest mb-6">
                <span class="w-2 h-2 rounded-full bg-gold <?php echo $admissions_open ? 'animate-pulse' : ''; ?>"></span>
                <?php echo $admissions_open ? 'Admissions Open' : 'Admissions Closed'; ?> — <?php echo htmlspecialchars($current_session); ?>
            </div>
            <h1 class="text-5xl lg:text-7xl font-display font-black mb-6">Shape Your <span class="text-gold">Future</span></h1>
            <p class="text-xl text-slate-300 mb-10 max-w-3xl mx-auto leading-relaxed">
                Join a community dedicated to intellectual rigour, leadership development, and character building in the heart of Nigeria.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <?php if ($admissions_open): ?>
                <a href="admissions_form.php"
                    class="bg-gold text-primary font-bold px-8 py-4 rounded-xl hover:scale-105 transition-transform shadow-xl flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined">edit_note</span>Start Online Application
                </a>
                <?php endif; ?>
                <a href="#prospectus"
                    class="bg-white/10 hover:bg-white/20 backdrop-blur-sm border border-white/20 font-bold px-8 py-4 rounded-xl transition-all flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined">description</span>Request Prospectus
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════════
     4 STEPS
══════════════════════════════════════════════ -->
<section class="py-20 bg-white dark:bg-background-dark">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-14">
            <h2 class="text-4xl lg:text-5xl font-display font-black text-primary dark:text-gold mb-4">The Pathway to Excellence</h2>
            <p class="text-slate-500 dark:text-slate-400 text-lg">Our <?php echo $admissions_open ? htmlspecialchars($current_session) : 'upcoming'; ?> admission process</p>
        </div>
        <div class="grid md:grid-cols-4 gap-6 relative">
            <div class="hidden md:block absolute top-8 left-0 right-0 h-0.5 bg-slate-200 dark:bg-slate-700" style="margin:0 12.5%;"></div>
            <?php foreach ([
                ['description','Request Prospectus','Request our school prospectus to learn everything about GOLA — programmes, fees, facilities and values.'],
                ['edit_note','Fill Application','Complete the full online boarding entrance application form with your details and supporting documents.'],
                ['payments','Pay Application Fee','Pay the ₦'.number_format(floatval($app_fee_amount)).' application fee and upload your bank teller with the form.'],
                ['quiz','Entrance Exam & Interview', $exam_date ? 'Sit the entrance exam on '.date('d M Y', strtotime($exam_date)).'. Top candidates proceed to interview.' : 'Sit our entrance examination on campus. Top candidates proceed to interview.'],
            ] as $i => [$icon, $title, $desc]): ?>
            <div class="relative z-10 text-center group">
                <div class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg transition-all duration-300
                    <?php echo $i===0?'bg-gold text-primary':'bg-primary text-white group-hover:bg-gold group-hover:text-primary'; ?>">
                    <span class="material-symbols-outlined text-2xl"><?php echo $icon; ?></span>
                </div>
                <p class="text-xs font-bold text-gold uppercase tracking-widest mb-1">Step <?php echo $i+1; ?></p>
                <h3 class="font-bold text-primary dark:text-white mb-2"><?php echo $title; ?></h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed"><?php echo $desc; ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════════
     LIFE AT GOLA
══════════════════════════════════════════════ -->
<section class="py-20 bg-slate-50 dark:bg-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-12 items-center">
            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-4">
                    <div class="bg-slate-200 dark:bg-slate-700 rounded-2xl overflow-hidden aspect-[4/3]">
                        <img src="./asset/school/image1.jpeg" alt="GOLA Campus" class="w-full h-full object-cover">
                    </div>
                    <div class="bg-gradient-to-br from-green-500 to-green-700 rounded-2xl overflow-hidden aspect-square flex flex-col items-center justify-center text-white">
                       
                         <img src="./asset/gola.jpeg" alt="GOLA Students" class="w-full h-full object-cover">
                    </div>
                </div>
                <div class="space-y-4 pt-8">
                    <div class="bg-gradient-to-br from-primary to-slate-700 rounded-2xl overflow-hidden aspect-square flex flex-col items-center justify-center text-white">
                      
                        <img src="./asset/com.jpeg" alt="GOLA Students" class="w-full h-full object-cover">
                    </div>
                    <div class="bg-slate-200 dark:bg-slate-700 rounded-2xl overflow-hidden aspect-[4/3]">
                        <img src="./asset/school/image2.jpeg" alt="GOLA Students" class="w-full h-full object-cover">
                    </div>
                </div>
            </div>
            <div>
                <p class="text-gold font-bold uppercase tracking-wider text-sm mb-3">Life at GOLA</p>
                <h2 class="text-4xl lg:text-5xl font-display font-black text-primary dark:text-white mb-6">
                    World-Class Facilities for the Next Generation of Leaders
                </h2>
                <div class="space-y-5">
                    <?php foreach ([
                        ['science',    'Advanced STEM Labs',       'State-of-the-art laboratories designed for hands-on scientific discovery and innovation.'],
                        ['sports_soccer','Full Sports Complex',    'Football pitch, volleyball courts, and athletics track — complete sports development.'],
                        ['menu_book',  'Modern Library & ICT Hub', 'Thousands of books and full internet access to support academic and research excellence.'],
                        ['bed',        'Comfortable Boarding',     'Supervised dormitories with 24/7 care, nutritious meals, and a safe home environment.'],
                    ] as [$icon, $title, $desc]): ?>
                    <div class="flex gap-4">
                        <div class="w-12 h-12 bg-primary/10 dark:bg-primary/20 rounded-xl flex items-center justify-center flex-shrink-0">
                            <span class="material-symbols-outlined text-primary dark:text-gold"><?php echo $icon; ?></span>
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-900 dark:text-white mb-0.5"><?php echo $title; ?></h3>
                            <p class="text-slate-500 dark:text-slate-400 text-sm"><?php echo $desc; ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════════
     PROSPECTUS REQUEST FORM
══════════════════════════════════════════════ -->
<section id="prospectus" class="py-20 bg-white dark:bg-background-dark">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-16 items-start">

            <!-- Left: Info -->
            <div class="lg:sticky lg:top-24">
                <p class="text-gold font-bold uppercase tracking-wider text-sm mb-3">Our Prospectus</p>
                <h2 class="text-4xl font-display font-black text-primary dark:text-gold mb-5">
                    Request Our School Prospectus
                </h2>
                <p class="text-slate-600 dark:text-slate-400 text-base leading-relaxed mb-6">
                    Our prospectus gives you a complete picture of life at Goodness Omogo Leadership Academy — our vision, academic programmes, boarding facilities, fees, and what we look for in our students.
                </p>
                <p class="text-slate-600 dark:text-slate-400 text-base leading-relaxed mb-8">
                    Fill in the short form and our admissions team will personally send the prospectus to your email address within <strong class="text-primary dark:text-gold">24 hours</strong>.
                </p>
                <div class="space-y-4">
                    <?php foreach ([
                        ['description',    'Full curriculum details for JSS and SSS levels'],
                        ['payments',       'Complete fee structure for the academic session'],
                        ['bed',            'Boarding facilities, daily schedule and house rules'],
                        ['emoji_events',   'Leadership programmes and extra-curricular activities'],
                        ['how_to_reg',     'Step-by-step admissions process and requirements'],
                    ] as [$icon, $text]): ?>
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 bg-gold/10 rounded-xl flex items-center justify-center flex-shrink-0">
                            <span class="material-symbols-outlined text-gold text-lg"><?php echo $icon; ?></span>
                        </div>
                        <p class="text-sm font-semibold text-slate-700 dark:text-slate-300"><?php echo $text; ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if ($admissions_open): ?>
                <div class="mt-8 p-5 bg-primary/5 dark:bg-primary/10 border border-primary/20 rounded-2xl">
                    <p class="font-bold text-primary dark:text-gold text-sm mb-1">Ready to apply?</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-3">You can start your application without waiting for the prospectus.</p>
                    <a href="admissions_form.php" class="inline-flex items-center gap-2 bg-primary text-white text-sm font-bold px-5 py-2.5 rounded-xl hover:bg-primary/90 transition-all">
                        <span class="material-symbols-outlined text-sm">edit_note</span>Apply Now
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <!-- Right: Form -->
            <div>
                <?php if ($req_success): ?>
                <!-- Request Success -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl border-2 border-green-200 dark:border-green-800 shadow-xl p-10 text-center">
                    <div class="w-20 h-20 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-5">
                        <span class="material-symbols-outlined text-5xl text-green-600">mark_email_read</span>
                    </div>
                    <h3 class="text-2xl font-black text-slate-900 dark:text-white mb-3">Request Received!</h3>
                    <p class="text-slate-600 dark:text-slate-400 mb-2">
                        Thank you for your interest in GOLA. We will send the prospectus to
                    </p>
                    <p class="font-bold text-primary dark:text-gold text-lg mb-5"><?php echo htmlspecialchars($req_success); ?></p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        Expect it within <strong>24 hours</strong>. Please check your spam folder if you don't see it.
                    </p>
                    <?php if ($admissions_open): ?>
                    <div class="mt-8 pt-6 border-t border-slate-100 dark:border-slate-700">
                        <p class="text-sm text-slate-500 mb-3">While you wait, you can start your application:</p>
                        <a href="admissions_form.php" class="inline-flex items-center gap-2 bg-primary text-white font-bold px-6 py-3 rounded-xl hover:bg-primary/90 transition-all">
                            <span class="material-symbols-outlined text-sm">edit_note</span>Apply Now
                        </a>
                    </div>
                    <?php endif; ?>
                </div>

                <?php else: ?>
                <!-- Request Form -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-xl overflow-hidden">
                    <div class="bg-gradient-to-r from-primary to-[#0A3556] px-7 py-6">
                        <h3 class="text-white font-black text-xl">Request the GOLA Prospectus</h3>
                        <p class="text-slate-300 text-sm mt-1">We'll email it directly to you within 24 hours</p>
                    </div>
                    <div class="p-7">
                        <?php if ($req_error): ?>
                        <div class="mb-5 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 rounded-xl flex gap-3 items-start">
                            <span class="material-symbols-outlined text-red-500 flex-shrink-0">error</span>
                            <p class="text-red-700 dark:text-red-400 text-sm"><?php echo htmlspecialchars($req_error); ?></p>
                        </div>
                        <?php endif; ?>

                        <form method="POST" class="space-y-4">
                            <input type="hidden" name="prospectus_request" value="1">

                            <div class="grid md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Parent / Guardian Name <span class="text-red-500">*</span></label>
                                    <input type="text" name="parent_name" required
                                        placeholder="Your full name"
                                        value="<?php echo htmlspecialchars($_POST['parent_name'] ?? ''); ?>"
                                        class="w-full px-4 py-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl bg-slate-50 dark:bg-slate-700 text-slate-900 dark:text-white text-sm focus:border-gold focus:ring-2 focus:ring-gold/20 outline-none transition-all">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Email Address <span class="text-red-500">*</span></label>
                                    <input type="email" name="email" required
                                        placeholder="your@email.com"
                                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                        class="w-full px-4 py-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl bg-slate-50 dark:bg-slate-700 text-slate-900 dark:text-white text-sm focus:border-gold focus:ring-2 focus:ring-gold/20 outline-none transition-all">
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Phone Number</label>
                                <input type="tel" name="phone"
                                    placeholder="e.g. 08012345678"
                                    value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                                    class="w-full px-4 py-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl bg-slate-50 dark:bg-slate-700 text-slate-900 dark:text-white text-sm focus:border-gold focus:ring-2 focus:ring-gold/20 outline-none transition-all">
                            </div>

                            <div class="grid md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Student's Name <span class="text-red-500">*</span></label>
                                    <input type="text" name="student_name" required
                                        placeholder="Student's full name"
                                        value="<?php echo htmlspecialchars($_POST['student_name'] ?? ''); ?>"
                                        class="w-full px-4 py-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl bg-slate-50 dark:bg-slate-700 text-slate-900 dark:text-white text-sm focus:border-gold focus:ring-2 focus:ring-gold/20 outline-none transition-all">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Grade Interested In <span class="text-red-500">*</span></label>
                                    <select name="grade_level" required
                                        class="w-full px-4 py-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl bg-slate-50 dark:bg-slate-700 text-slate-900 dark:text-white text-sm focus:border-gold focus:ring-2 focus:ring-gold/20 outline-none transition-all">
                                        <option value="">Select Grade</option>
                                        <?php foreach(['JSS 1','JSS 2','JSS 3','SS 1 (Science)','SS 1 (Arts)','SS 1 (Commercial)','SS 2 (Science)','SS 2 (Arts)','SS 2 (Commercial)','SS 3 (Science)','SS 3 (Arts)','SS 3 (Commercial)'] as $g): ?>
                                        <option <?php echo ($_POST['grade_level']??'')===$g?'selected':''; ?>><?php echo $g; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">How did you hear about GOLA?</label>
                                <textarea name="how_heard" rows="2"
                                    placeholder="e.g. Friend recommendation, social media, church announcement…"
                                    class="w-full px-4 py-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl bg-slate-50 dark:bg-slate-700 text-slate-900 dark:text-white text-sm focus:border-gold focus:ring-2 focus:ring-gold/20 outline-none transition-all resize-none"
                                ><?php echo htmlspecialchars($_POST['how_heard'] ?? ''); ?></textarea>
                            </div>

                            <button type="submit"
                                class="w-full bg-primary hover:bg-primary/90 text-white font-black text-base py-4 rounded-xl transition-all flex items-center justify-center gap-2 shadow-lg hover:shadow-xl hover:-translate-y-0.5">
                                <span class="material-symbols-outlined">send</span>
                                Send Me the Prospectus
                            </button>
                            <p class="text-center text-xs text-slate-400">We'll email it to you within 24 hours. No spam, ever.</p>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════════
     FAQ
══════════════════════════════════════════════ -->
<section class="py-20 bg-slate-50 dark:bg-slate-900">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-4xl font-display font-black text-primary dark:text-gold mb-3">Frequently Asked Questions</h2>
            <p class="text-slate-500 dark:text-slate-400">Everything you need to know about joining GOLA</p>
        </div>
        <div class="space-y-3">
            <?php foreach ([
                ['When is the next entrance examination?', $exam_date ? 'Our next entrance examination is scheduled for '.date('l, F j, Y', strtotime($exam_date)).'. '.($exam_venue?'It will be held at: '.$exam_venue.'. ':'').'Register early as spaces fill up quickly.' : 'Entrance examination dates are announced each term. Complete the inquiry form above and our admissions team will notify you of the next available date.'],
                ['Are boarding facilities available?', 'Yes — GOLA is a full boarding institution. We offer supervised dormitories with 24/7 care, nutritious meals three times daily, a structured daily timetable, and comprehensive pastoral support. Boarding is compulsory for all students.'],
                ['What curriculum does GOLA follow?', 'We follow the Nigerian National Curriculum (NERDC) with international standards integration. Students prepare for WAEC and NECO examinations while developing 21st-century skills through our leadership development programme.'],
                ['What is the application fee?', 'The non-refundable application fee is ₦'.number_format(floatval($app_fee_amount)).'. This covers processing, entrance examination materials, and administrative costs. Separate fees apply for tuition and boarding.'],
                ['What documents do I need to apply?', 'You will need: a copy of your last school result, a birth certificate or declaration of age, two recent passport photographs, and proof of payment of the application fee.'],
                ['Are scholarships or bursaries available?', 'Yes. We offer merit-based scholarships for academically exceptional students and need-based bursaries. Please contact the admissions office directly to discuss eligibility and requirements.'],
            ] as [$q, $a]): ?>
            <details class="group bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <summary class="flex items-center justify-between px-6 py-5 cursor-pointer font-bold text-slate-900 dark:text-white hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors text-sm">
                    <?php echo $q; ?>
                    <span class="material-symbols-outlined text-gold flex-shrink-0 group-open:rotate-180 transition-transform">expand_more</span>
                </summary>
                <div class="px-6 pb-5 text-slate-600 dark:text-slate-400 text-sm leading-relaxed border-t border-slate-100 dark:border-slate-700 pt-4">
                    <?php echo $a; ?>
                </div>
            </details>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="py-20 bg-gradient-to-br from-primary to-slate-800 text-white">
    <div class="max-w-5xl mx-auto px-4 text-center">
        <h2 class="text-4xl lg:text-5xl font-display font-black mb-5">Ready to Begin Your Journey?</h2>
        <p class="text-xl text-slate-300 mb-10 max-w-3xl mx-auto">Take the first step toward academic excellence and leadership development.</p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <?php if ($admissions_open): ?>
            <a href="admissions_form.php" class="bg-gold text-primary font-bold px-10 py-4 rounded-xl hover:scale-105 transition-transform shadow-xl flex items-center justify-center gap-2">
                <span class="material-symbols-outlined">edit_note</span>Start Your Application
            </a>
            <?php endif; ?>
            <a href="#prospectus" class="bg-white/10 hover:bg-white/20 border border-white/20 font-bold px-10 py-4 rounded-xl transition-all flex items-center justify-center gap-2 backdrop-blur-sm">
                <span class="material-symbols-outlined">description</span>Request Prospectus
            </a>
            <a href="contact.php" class="bg-white/10 hover:bg-white/20 border border-white/20 font-bold px-10 py-4 rounded-xl transition-all flex items-center justify-center gap-2 backdrop-blur-sm">
                <span class="material-symbols-outlined">call</span>Contact Admissions
            </a>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>