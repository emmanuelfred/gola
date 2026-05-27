<?php 
require_once '../config/database.php';
$page_title = "Result Checker";
include '../includes/header.php';

// Fetch sessions from database
$sessions_query = $conn->query("SELECT id, session_name FROM academic_sessions ORDER BY id DESC");

// Check for error messages from view-result.php
$error = '';
if (isset($_GET['error'])) {
    switch($_GET['error']) {
        case 'invalid_pin':
            $error = 'Invalid scratch card PIN. Please check and try again.';
            break;
        case 'inactive_card':
            $error = 'This scratch card has not been activated. Please contact the school office.';
            break;
        case 'used_up':
            $error = 'This scratch card has been used up. Please purchase a new one.';
            break;
        case 'no_student':
            $error = 'Student ID not found. Please check your registration number and try again.';
            break;
        case 'no_results':
            $error = 'No results found for the selected session and term. Results may not have been published yet.';
            break;
        case 'missing_fields':
            $error = 'Please fill in all required fields.';
            break;
        default:
            $error = 'An error occurred. Please try again.';
    }
}
?>

<!-- Result Checker Hero Section -->
<section class="py-16 bg-gradient-to-br from-primary to-slate-800 text-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div class="inline-flex items-center gap-2 px-4 py-2 bg-gold/20 border border-gold/30 rounded-full text-gold font-semibold text-sm uppercase tracking-widest mb-6">
            <span class="material-symbols-outlined">school</span>
            Student Results Portal
        </div>
        <h1 class="text-4xl lg:text-6xl font-display font-black mb-4">Check Your Results</h1>
        <p class="text-xl text-slate-300">Enter your registration number and scratch card PIN to view your academic performance</p>
    </div>
</section>

<!-- Result Checker Form -->
<section class="py-16 bg-slate-50 dark:bg-slate-900">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl p-8 lg:p-12">
            <div class="text-center mb-8">
                <div class="inline-block p-4 bg-gold/10 rounded-full mb-4">
                    <span class="material-symbols-outlined text-5xl text-gold">assignment</span>
                </div>
                <h2 class="text-3xl font-display font-bold text-primary dark:text-gold mb-2">Enter Your Details</h2>
                <p class="text-slate-600 dark:text-slate-400">You need a valid scratch card PIN to access your results</p>
            </div>

            <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl flex gap-3">
                <span class="material-symbols-outlined text-red-500 flex-shrink-0">error</span>
                <p class="text-sm text-red-700 dark:text-red-400"><?php echo htmlspecialchars($error); ?></p>
            </div>
            <?php endif; ?>
            
            <form id="resultCheckerForm" action="view-result.php" method="POST" class="space-y-6">
                <!-- Registration Number -->
                <div>
                    <label for="student_id" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                        Registration Number <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 material-symbols-outlined text-slate-400">badge</span>
                        <input 
                            type="text" 
                            id="student_id" 
                            name="student_id" 
                            required 
                            placeholder="e.g., GOLA/2024/SS2/045"
                            value="<?php echo htmlspecialchars($_GET['student_id'] ?? ''); ?>"
                            class="w-full pl-12 pr-4 py-4 border-2 border-slate-200 dark:border-slate-700 rounded-xl focus:border-gold focus:ring-2 focus:ring-gold/20 dark:bg-slate-700 dark:text-white transition-all"
                        >
                    </div>
                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">Format: GOLA/YYYY/CLASS/XXX — found on your student ID card</p>
                </div>

                <!-- Scratch Card PIN -->
                <div>
                    <label for="pin_code" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                        Scratch Card PIN <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 material-symbols-outlined text-slate-400">confirmation_number</span>
                        <input 
                            type="text" 
                            id="pin_code" 
                            name="pin_code" 
                            required 
                            placeholder="e.g., GOLA-XXXX-XXXX-XXXX"
                            maxlength="19"
                            style="font-family: 'Courier New', Courier, monospace; letter-spacing: 0.05em;"
                            class="w-full pl-12 pr-4 py-4 border-2 border-slate-200 dark:border-slate-700 rounded-xl focus:border-gold focus:ring-2 focus:ring-gold/20 dark:bg-slate-700 dark:text-white transition-all uppercase font-bold text-lg"
                        >
                    </div>
                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">Scratch the silver panel on your card to reveal the PIN</p>
                </div>
                
                <!-- Academic Session -->
                <div>
                    <label for="session_id" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                        Academic Session <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 material-symbols-outlined text-slate-400">calendar_today</span>
                        <select 
                            id="session_id" 
                            name="session_id" 
                            required
                            class="w-full pl-12 pr-4 py-4 border-2 border-slate-200 dark:border-slate-700 rounded-xl focus:border-gold focus:ring-2 focus:ring-gold/20 dark:bg-slate-700 dark:text-white transition-all appearance-none bg-no-repeat"
                            style="background-image: url('data:image/svg+xml;charset=UTF-8,%3csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 24 24%27 fill=%27none%27 stroke=%27currentColor%27 stroke-width=%272%27 stroke-linecap=%27round%27 stroke-linejoin=%27round%27%3e%3cpolyline points=%276 9 12 15 18 9%27%3e%3c/polyline%3e%3c/svg%3e'); background-position: right 1rem center; background-size: 1.5em 1.5em;"
                        >
                            <option value="">Select Session</option>
                            <?php if ($sessions_query && $sessions_query->num_rows > 0): ?>
                                <?php while ($session = $sessions_query->fetch_assoc()): ?>
                                    <option value="<?php echo $session['id']; ?>" <?php echo (isset($_GET['session_id']) && $_GET['session_id'] == $session['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($session['session_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
                
                <!-- Term -->
                <div>
                    <label for="term_id" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                        Term <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 material-symbols-outlined text-slate-400">event_note</span>
                        <select 
                            id="term_id" 
                            name="term_id" 
                            required
                            class="w-full pl-12 pr-4 py-4 border-2 border-slate-200 dark:border-slate-700 rounded-xl focus:border-gold focus:ring-2 focus:ring-gold/20 dark:bg-slate-700 dark:text-white transition-all appearance-none"
                            style="background-image: url('data:image/svg+xml;charset=UTF-8,%3csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 24 24%27 fill=%27none%27 stroke=%27currentColor%27 stroke-width=%272%27 stroke-linecap=%27round%27 stroke-linejoin=%27round%27%3e%3cpolyline points=%276 9 12 15 18 9%27%3e%3c/polyline%3e%3c/svg%3e'); background-position: right 1rem center; background-size: 1.5em 1.5em;"
                        >
                            <option value="">Select Term</option>
                            <option value="1" <?php echo (isset($_GET['term_id']) && $_GET['term_id'] == '1') ? 'selected' : ''; ?>>First Term</option>
                            <option value="2" <?php echo (isset($_GET['term_id']) && $_GET['term_id'] == '2') ? 'selected' : ''; ?>>Second Term</option>
                            <option value="3" <?php echo (isset($_GET['term_id']) && $_GET['term_id'] == '3') ? 'selected' : ''; ?>>Third Term</option>
                        </select>
                    </div>
                </div>
                
                <!-- Submit Button -->
                <button 
                    type="submit" 
                    class="w-full bg-primary hover:bg-primary/90 text-white font-bold py-4 px-6 rounded-xl transition-all flex items-center justify-center gap-2 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5"
                >
                    <span class="material-symbols-outlined">search</span>
                    Check My Result
                </button>
            </form>
            
            <!-- Help Text -->
            <div class="mt-8 p-4 bg-gold/10 border border-gold/20 rounded-xl">
                <div class="flex gap-3">
                    <span class="material-symbols-outlined text-gold flex-shrink-0">info</span>
                    <div class="text-sm">
                        <p class="font-semibold text-primary dark:text-gold mb-1">How to Get a Scratch Card</p>
                        <p class="text-slate-600 dark:text-slate-400">Scratch cards can be purchased from the school office or authorized agents. Each card has a limited number of uses. Scratch the silver panel on the back to reveal your unique PIN code.</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Additional Information -->
        <div class="mt-8 grid md:grid-cols-3 gap-6">
            <div class="text-center p-6 bg-white dark:bg-slate-800 rounded-xl shadow-lg">
                <div class="inline-block p-3 bg-gold/10 rounded-full mb-3">
                    <span class="material-symbols-outlined text-3xl text-gold">verified</span>
                </div>
                <h3 class="font-bold text-primary dark:text-gold mb-2">Verified PINs</h3>
                <p class="text-sm text-slate-600 dark:text-slate-400">Only activated scratch card PINs can access results, ensuring secure verification</p>
            </div>
            
            <div class="text-center p-6 bg-white dark:bg-slate-800 rounded-xl shadow-lg">
                <div class="inline-block p-3 bg-gold/10 rounded-full mb-3">
                    <span class="material-symbols-outlined text-3xl text-gold">schedule</span>
                </div>
                <h3 class="font-bold text-primary dark:text-gold mb-2">24/7 Availability</h3>
                <p class="text-sm text-slate-600 dark:text-slate-400">Check your results anytime, anywhere, from any device</p>
            </div>
            
            <div class="text-center p-6 bg-white dark:bg-slate-800 rounded-xl shadow-lg">
                <div class="inline-block p-3 bg-gold/10 rounded-full mb-3">
                    <span class="material-symbols-outlined text-3xl text-gold">print</span>
                </div>
                <h3 class="font-bold text-primary dark:text-gold mb-2">Print & Download</h3>
                <p class="text-sm text-slate-600 dark:text-slate-400">Download your result as a PDF or print it directly from your browser</p>
            </div>
        </div>
    </div>
</section>

<script>
// Auto-format PIN input: GOLA-XXXX-XXXX-XXXX
document.getElementById('pin_code').addEventListener('input', function(e) {
    let val = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
    
    // Build formatted string
    let formatted = '';
    if (val.length > 0) formatted = val.substring(0, 4);
    if (val.length > 4) formatted += '-' + val.substring(4, 8);
    if (val.length > 8) formatted += '-' + val.substring(8, 12);
    if (val.length > 12) formatted += '-' + val.substring(12, 16);
    
    this.value = formatted;
});
</script>

<?php include '../includes/footer.php'; ?>