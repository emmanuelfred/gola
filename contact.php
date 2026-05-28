<?php 
$page_title = "Contact Us - Get In Touch";
require_once 'config/database.php';
require_once 'includes/email.php';   // ← PHPMailer helper

function getSetting($conn, $key, $default = '') {
    $r = $conn->query("SELECT setting_value FROM school_settings WHERE setting_key='".mysqli_real_escape_string($conn,$key)."' LIMIT 1");
    $row = $r ? $r->fetch_assoc() : null;
    return $row ? $row['setting_value'] : $default;
}

$school_phone   = getSetting($conn, 'school_phone',   '09125128213');
$school_email   = getSetting($conn, 'school_email',   'golaedu2026@gmail.com');
$school_address = getSetting($conn, 'school_address', 'Ntezi, Ishielu LGA, Ebonyi State, Nigeria');

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_submit'])) {
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email']     ?? '');
    $subject   = trim($_POST['subject']   ?? '');
    $message   = trim($_POST['message']   ?? '');

    if (!$full_name || !$email || !$subject || !$message) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!isset($_POST['privacy_policy'])) {
        $error = 'Please accept the privacy policy to continue.';
    } else {
        // Save to DB (optional — uncomment if you add contact_messages table)
        // $fn = mysqli_real_escape_string($conn,$full_name);
        // $em = mysqli_real_escape_string($conn,$email);
        // $su = mysqli_real_escape_string($conn,$subject);
        // $ms = mysqli_real_escape_string($conn,$message);
        // $conn->query("INSERT INTO contact_messages (full_name,email,subject,message) VALUES ('$fn','$em','$su','$ms')");

        // Send via PHPMailer (auto-reply + admin notification)
        $result = sendContactNotification([
            'full_name' => $full_name,
            'email'     => $email,
            'subject'   => $subject,
            'message'   => $message,
        ]);

        if ($result['ok']) {
            $success = "Thank you, $full_name! Your message has been received. We'll reply within 24 hours — check your inbox for a confirmation.";
        } else {
            // Mail failed but still acknowledge receipt
            $success = "Thank you! Your message has been received. We'll get back to you within 24 hours.";
            error_log('Contact mail failed: ' . $result['error']);
        }
    }
}

include 'includes/header.php';
?>

<!-- Page Header -->
<section class="py-16 bg-gradient-to-br from-primary via-[#0A3556] to-slate-800 text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-5xl lg:text-6xl font-display font-black mb-4">Connect With Us</h1>
        <p class="text-xl text-slate-300 max-w-3xl mx-auto leading-relaxed">
            Have questions about admissions or our programmes? Our dedicated team is here to guide you.
        </p>
    </div>
</section>

<!-- Main Section -->
<section class="py-20 bg-slate-50 dark:bg-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-3 gap-8">

            <!-- Left: Contact Info -->
            <div class="lg:col-span-1 space-y-5">
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg overflow-hidden">
                    <div class="bg-primary px-6 py-5">
                        <h2 class="text-white font-bold text-lg">Contact Details</h2>
                        <p class="text-slate-300 text-sm mt-0.5">We'd love to hear from you</p>
                    </div>
                    <div class="p-6 space-y-5">
                        <div class="flex items-start gap-4">
                            <div class="w-11 h-11 bg-gold/10 rounded-xl flex items-center justify-center flex-shrink-0">
                                <span class="material-symbols-outlined text-gold">location_on</span>
                            </div>
                            <div>
                                <p class="font-bold text-slate-900 dark:text-white text-sm mb-0.5">Our Campus</p>
                                <p class="text-slate-600 dark:text-slate-400 text-sm"><?php echo htmlspecialchars($school_address); ?></p>
                            </div>
                        </div>
                        <div class="w-full h-px bg-slate-100 dark:bg-slate-700"></div>
                        <div class="flex items-start gap-4">
                            <div class="w-11 h-11 bg-gold/10 rounded-xl flex items-center justify-center flex-shrink-0">
                                <span class="material-symbols-outlined text-gold">call</span>
                            </div>
                            <div>
                                <p class="font-bold text-slate-900 dark:text-white text-sm mb-0.5">Call Us</p>
                                <a href="tel:<?php echo preg_replace('/\s+/','', $school_phone); ?>"
                                   class="text-sm text-gold hover:underline font-medium">
                                    <?php echo htmlspecialchars($school_phone); ?>
                                </a>
                                <p class="text-xs text-slate-400 mt-0.5">Mon–Fri, 8:00 AM–4:00 PM</p>
                            </div>
                        </div>
                        <div class="w-full h-px bg-slate-100 dark:bg-slate-700"></div>
                        <div class="flex items-start gap-4">
                            <div class="w-11 h-11 bg-gold/10 rounded-xl flex items-center justify-center flex-shrink-0">
                                <span class="material-symbols-outlined text-gold">mail</span>
                            </div>
                            <div>
                                <p class="font-bold text-slate-900 dark:text-white text-sm mb-0.5">Email Us</p>
                                <a href="mailto:<?php echo htmlspecialchars($school_email); ?>"
                                   class="text-sm text-gold hover:underline font-medium break-all">
                                    <?php echo htmlspecialchars($school_email); ?>
                                </a>
                            </div>
                        </div>
                        <div class="w-full h-px bg-slate-100 dark:bg-slate-700"></div>
                        <div>
                            <p class="font-bold text-slate-900 dark:text-white text-sm mb-3">Follow Us</p>
                            <div class="flex gap-3">
                                <a href="#" class="w-10 h-10 bg-primary hover:bg-gold text-white rounded-xl flex items-center justify-center transition-colors" title="Facebook">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                </a>
                                <a href="#" class="w-10 h-10 bg-primary hover:bg-gold text-white rounded-xl flex items-center justify-center transition-colors" title="Instagram">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                                </a>
                                <a href="https://wa.me/234<?php echo ltrim(preg_replace('/\s+/','',$school_phone),'0'); ?>" target="_blank"
                                   class="w-10 h-10 bg-green-600 hover:bg-green-700 text-white rounded-xl flex items-center justify-center transition-colors" title="WhatsApp">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Office Hours -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg p-6">
                    <h3 class="font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-gold">schedule</span>Office Hours
                    </h3>
                    <div class="space-y-2 text-sm">
                        <?php foreach([['Monday – Friday','8:00 AM – 4:00 PM',true],['Saturday','9:00 AM – 12:00 PM',false],['Sunday','Closed',false]] as [$day,$hrs,$open]): ?>
                        <div class="flex justify-between items-center py-1.5 border-b border-slate-100 dark:border-slate-700 last:border-0">
                            <span class="text-slate-600 dark:text-slate-400"><?php echo $day; ?></span>
                            <span class="font-semibold <?php echo $open?'text-green-600':'text-slate-400'; ?>"><?php echo $hrs; ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Right: Form -->
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-primary to-slate-700 px-8 py-6">
                        <h2 class="text-white font-bold text-xl">Send Us a Message</h2>
                        <p class="text-slate-300 text-sm mt-1">We reply within 24 hours — you'll also get an automatic confirmation in your inbox</p>
                    </div>
                    <div class="p-8">

                        <?php if ($success): ?>
                        <div class="mb-6 p-5 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-2xl flex gap-3 items-start">
                            <span class="material-symbols-outlined text-green-600 flex-shrink-0 text-2xl">mark_email_read</span>
                            <div>
                                <p class="font-bold text-green-800 dark:text-green-300 text-base"><?php echo htmlspecialchars($success); ?></p>
                                <p class="text-green-700 dark:text-green-400 text-sm mt-1">Check your inbox (and spam folder just in case).</p>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($error): ?>
                        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-2xl flex gap-3 items-start">
                            <span class="material-symbols-outlined text-red-500 flex-shrink-0">error</span>
                            <p class="text-red-700 text-sm"><?php echo htmlspecialchars($error); ?></p>
                        </div>
                        <?php endif; ?>

                        <form method="POST" class="space-y-5">
                            <input type="hidden" name="contact_submit" value="1">
                            <div class="grid md:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Full Name <span class="text-red-500">*</span></label>
                                    <input type="text" name="full_name" required value="<?php echo htmlspecialchars($_POST['full_name']??''); ?>"
                                        placeholder="Your full name"
                                        class="w-full px-4 py-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl bg-slate-50 dark:bg-slate-700 text-slate-900 dark:text-white text-sm focus:border-gold focus:ring-2 focus:ring-gold/20 outline-none transition-all">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Email Address <span class="text-red-500">*</span></label>
                                    <input type="email" name="email" required value="<?php echo htmlspecialchars($_POST['email']??''); ?>"
                                        placeholder="your@email.com"
                                        class="w-full px-4 py-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl bg-slate-50 dark:bg-slate-700 text-slate-900 dark:text-white text-sm focus:border-gold focus:ring-2 focus:ring-gold/20 outline-none transition-all">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Subject <span class="text-red-500">*</span></label>
                                <select name="subject" required
                                    class="w-full px-4 py-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl bg-slate-50 dark:bg-slate-700 text-slate-900 dark:text-white text-sm focus:border-gold focus:ring-2 focus:ring-gold/20 outline-none transition-all">
                                    <option value="">Select a subject</option>
                                    <?php foreach(['General Inquiry','Admissions Information','Academic Programmes','Financial Aid & Scholarships','Campus Visit Request','Prospectus Request','Technical Support','Other'] as $opt): ?>
                                    <option <?php echo ($_POST['subject']??'')===$opt?'selected':''; ?>><?php echo $opt; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Your Message <span class="text-red-500">*</span></label>
                                <textarea name="message" required rows="6" placeholder="How can we help you?"
                                    class="w-full px-4 py-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl bg-slate-50 dark:bg-slate-700 text-slate-900 dark:text-white text-sm focus:border-gold focus:ring-2 focus:ring-gold/20 outline-none transition-all resize-none"
                                ><?php echo htmlspecialchars($_POST['message']??''); ?></textarea>
                            </div>
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input type="checkbox" name="privacy_policy" required class="mt-1 w-4 h-4 text-gold focus:ring-gold rounded border-2 border-slate-300">
                                <span class="text-sm text-slate-600 dark:text-slate-400">
                                    I agree to the <a href="privacy-policy.php" class="text-gold hover:underline">Privacy Policy</a> and consent to being contacted by the Academy.
                                </span>
                            </label>
                            <button type="submit"
                                class="w-full bg-primary hover:bg-primary/90 text-white font-bold py-4 rounded-xl transition-all flex items-center justify-center gap-2 shadow-lg hover:shadow-xl hover:-translate-y-0.5">
                                <span class="material-symbols-outlined">send</span>Send Message
                            </button>
                            <p class="text-center text-xs text-slate-400">You'll receive an automatic confirmation email immediately after sending.</p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Map -->
<section class="py-16 bg-white dark:bg-background-dark">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-10">
            <h2 class="text-3xl font-display font-black text-primary dark:text-gold mb-2">Find Us</h2>
            <p class="text-slate-500">Visit our campus in Ebonyi State, Nigeria</p>
        </div>
        <div class="rounded-2xl overflow-hidden shadow-2xl border border-slate-200 dark:border-slate-700">
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d253682.62803175103!2d8.009379699999999!3d6.2649482!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x1044505e5db1e7c1%3A0xb1c4f84b8fc8d4c7!2sEbonyi%2C%20Nigeria!5e0!3m2!1sen!2s!4v1234567890123!5m2!1sen!2s"
                width="100%" height="420" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
