<?php 
$page_title = "Admissions - Join Our Leadership Community";
require_once 'config/database.php';

// Handle inquiry form submission
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['inquiry_submit'])) {
    $parent_name = trim($_POST['parent_name']);
    $email = trim($_POST['email']);
    $student_name = trim($_POST['student_name']);
    $grade_level = $_POST['grade_level'];
    $hear_about = trim($_POST['hear_about']);
    
    // Basic validation
    if (empty($parent_name) || empty($email) || empty($student_name) || empty($grade_level)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Store in database (create inquiries table if needed)
        // For now, send email notification
        
        $to = "admissions@goodnessomogo.edu.ng";
        $subject = "New Admission Inquiry - $grade_level";
        $message = "New admission inquiry received:\n\n";
        $message .= "Parent/Guardian: $parent_name\n";
        $message .= "Email: $email\n";
        $message .= "Student Name: $student_name\n";
        $message .= "Grade Level: $grade_level\n";
        $message .= "Heard About Us: $hear_about\n";
        
        $headers = "From: noreply@goodnessomogo.edu.ng\r\n";
        $headers .= "Reply-To: $email\r\n";
        
        // For demo purposes, show success
        $success = "Thank you! Your inquiry has been submitted successfully. Our admissions team will contact you within 24 hours.";
    }
}

include 'includes/header.php'; 
?>

<!-- Hero Section -->
<section class="relative py-24 lg:py-32 bg-slate-50 dark:bg-slate-900 overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto text-center">
            <h1 class="text-5xl lg:text-7xl font-display font-black text-slate-900 dark:text-white mb-6">
                Shape Your <span class="text-gold">Future</span>
            </h1>
            <p class="text-xl text-slate-600 dark:text-slate-400 mb-10 leading-relaxed">
                Join a community dedicated to intellectual rigor, leadership<br class="hidden md:block">
                development, and character building in the heart of modern Nigeria.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="#inquiry-form" class="bg-primary text-white px-8 py-4 rounded-lg font-semibold hover:bg-opacity-90 transition-all shadow-lg">
                    Start Online Inquiry
                </a>
                <a href="#" class="bg-white dark:bg-slate-800 border-2 border-slate-300 dark:border-slate-600 text-slate-900 dark:text-white px-8 py-4 rounded-lg font-semibold hover:bg-slate-50 dark:hover:bg-slate-700 transition-all flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined">download</span>
                    Download Prospectus
                </a>
            </div>
        </div>
    </div>
</section>

<!-- The Pathway to Excellence Section -->
<section class="py-20 bg-white dark:bg-background-dark">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-4xl lg:text-5xl font-display font-black text-slate-900 dark:text-white mb-4">
                The Pathway to Excellence
            </h2>
            <p class="text-slate-600 dark:text-slate-400 text-lg">
                Our simple 4-step admission process for the 2024/2025 academic session.
            </p>
        </div>
        
        <!-- 4 Steps -->
        <div class="grid md:grid-cols-4 gap-8">
            
            <!-- Step 1: Inquiry -->
            <div class="text-center">
                <div class="w-16 h-16 bg-primary text-white rounded-full flex items-center justify-center text-2xl font-bold mx-auto mb-6 shadow-lg">
                    1
                </div>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-3">Inquiry</h3>
                <p class="text-sm text-slate-600 dark:text-slate-400">
                    Complete our online inquiry form or schedule a personalized tour.
                </p>
            </div>
            
            <!-- Step 2: Application -->
            <div class="text-center">
                <div class="w-16 h-16 bg-primary text-white rounded-full flex items-center justify-center text-2xl font-bold mx-auto mb-6 shadow-lg">
                    2
                </div>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-3">Application</h3>
                <p class="text-sm text-slate-600 dark:text-slate-400">
                    Submit your completed form and the application fee .
                </p>
            </div>
            
            <!-- Step 3: Assessment -->
            <div class="text-center">
                <div class="w-16 h-16 bg-primary text-white rounded-full flex items-center justify-center text-2xl font-bold mx-auto mb-6 shadow-lg">
                    3
                </div>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-3">Assessment</h3>
                <p class="text-sm text-slate-600 dark:text-slate-400">
                    Participate in our entrance examination and interview.
                </p>
            </div>
            
            <!-- Step 4: Enrollment -->
            <div class="text-center">
                <div class="w-16 h-16 bg-primary text-white rounded-full flex items-center justify-center text-2xl font-bold mx-auto mb-6 shadow-lg">
                    4
                </div>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-3">Enrollment</h3>
                <p class="text-sm text-slate-600 dark:text-slate-400">
                    Receive your admission offer and finalize with the payment of acceptance fees.
                </p>
            </div>
            
        </div>
    </div>
</section>

<!-- Life at GOLA - World-Class Facilities -->
<section class="py-20 bg-slate-50 dark:bg-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-12 items-center">
            
            <!-- Left Side - Images Grid -->
            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-4">
                    <div class="bg-slate-200 rounded-2xl overflow-hidden aspect-[4/3]">
                        <img src="./asset/school/image6.jpeg" alt="Modern Classroom" class="w-full h-full object-cover">
                    </div>
                    <div class="bg-gradient-to-br from-green-400 to-green-600 rounded-2xl p-8 aspect-square flex items-center justify-center">
                        <div class="text-center text-white">
                            <span class="material-symbols-outlined text-6xl mb-3 block">diversity_3</span>
                            <p class="font-bold">Diverse Community</p>
                        </div>
                    </div>
                </div>
                <div class="space-y-4 pt-8">
                    <div class="bg-gradient-to-br from-primary to-slate-700 rounded-2xl p-8 aspect-square flex flex-col items-center justify-center text-white">
                        <span class="material-symbols-outlined text-6xl mb-3">school</span>
                        <p class="font-bold text-center">Campus Life</p>
                        <p class="text-sm text-slate-300 mt-2 text-center">Excellence in Education</p>
                    </div>
                    <div class="bg-gradient-to-br from-teal-600 to-teal-800 rounded-2xl p-8 aspect-[4/3] flex flex-col items-center justify-center text-white">
                        <span class="material-symbols-outlined text-6xl mb-3">sports_soccer</span>
                        <p class="font-bold text-center">Campus Sports</p>
                        <p class="text-sm text-teal-200 mt-2 text-center">On-site gym, pool, and field</p>
                    </div>
                </div>
            </div>
            
            <!-- Right Side - Content -->
            <div>
                <p class="text-gold font-semibold uppercase tracking-wider mb-3">LIFE AT GOLA</p>
                <h2 class="text-4xl lg:text-5xl font-display font-black text-slate-900 dark:text-white mb-8">
                    World-Class Facilities for the Next Generation of Leaders
                </h2>
                
                <div class="space-y-6">
                    <!-- Feature 1 -->
                    <div class="flex gap-4">
                        <div class="w-12 h-12 bg-primary/10 rounded-xl flex items-center justify-center flex-shrink-0">
                            <span class="material-symbols-outlined text-2xl text-primary">science</span>
                        </div>
                        <div>
                            <h3 class="font-bold text-lg text-slate-900 dark:text-white mb-1">Advanced STEM Labs</h3>
                            <p class="text-slate-600 dark:text-slate-400 text-sm">
                                State-of-the-art laboratories designed for hands-on scientific discovery and innovation.
                            </p>
                        </div>
                    </div>
                    
                    <!-- Feature 2 -->
                    <div class="flex gap-4">
                        <div class="w-12 h-12 bg-gold/10 rounded-xl flex items-center justify-center flex-shrink-0">
                            <span class="material-symbols-outlined text-2xl text-gold">palette</span>
                        </div>
                        <div>
                            <h3 class="font-bold text-lg text-slate-900 dark:text-white mb-1">Creative Arts Hub</h3>
                            <p class="text-slate-600 dark:text-slate-400 text-sm">
                                Nurturing artistic talent through music, visual arts, and performance theater.
                            </p>
                        </div>
                    </div>
                    
                    <!-- Feature 3 -->
                    <div class="flex gap-4">
                        <div class="w-12 h-12 bg-green-500/10 rounded-xl flex items-center justify-center flex-shrink-0">
                            <span class="material-symbols-outlined text-2xl text-green-600">sports_soccer</span>
                        </div>
                        <div>
                            <h3 class="font-bold text-lg text-slate-900 dark:text-white mb-1">Elite Sports Complex</h3>
                            <p class="text-slate-600 dark:text-slate-400 text-sm">
                                Comprehensive athletic facilities including Olympic-sized swimming pools and multi-purpose courts.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</section>

<!-- Online Inquiry Form Section -->
<section id="inquiry-form" class="py-20 bg-white dark:bg-background-dark scroll-mt-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-5 gap-8">
            
            <!-- Left Side - Contact Info Card -->
            <div class="lg:col-span-2">
                <div class="bg-gradient-to-br from-primary to-slate-800 text-white rounded-2xl p-8 h-full">
                    <h2 class="text-3xl font-bold mb-6">Online Inquiry</h2>
                    <p class="text-slate-300 mb-8">
                        Please fill out the form below and our admissions office will reach out within 24 hours to schedule a consultation.
                    </p>
                    
                    <!-- Contact Details -->
                    <div class="space-y-6">
                        <div class="flex items-start gap-4">
                            <span class="material-symbols-outlined text-gold text-2xl">call</span>
                            <div>
                                <p class="font-semibold mb-1">+234 800 GOLA EDU</p>
                                <p class="text-sm text-slate-300">Mon - Fri, 8:00 AM - 4:00 PM</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-4">
                            <span class="material-symbols-outlined text-gold text-2xl">mail</span>
                            <div>
                                <p class="font-semibold">admissions@gola.edu.ng</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-4">
                            <span class="material-symbols-outlined text-gold text-2xl">location_on</span>
                            <div>
                                <p class="font-semibold">Victoria Island Annex, Lagos, Nigeria</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Right Side - Inquiry Form -->
            <div class="lg:col-span-3">
                <div class="bg-slate-50 dark:bg-slate-800 rounded-2xl p-8">
                    
                    <!-- Success Message -->
                    <?php if ($success): ?>
                    <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl flex items-start gap-3">
                        <span class="material-symbols-outlined text-green-600">check_circle</span>
                        <p class="text-green-800 text-sm"><?php echo htmlspecialchars($success); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Error Message -->
                    <?php if ($error): ?>
                    <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl flex items-start gap-3">
                        <span class="material-symbols-outlined text-red-600">error</span>
                        <p class="text-red-800 text-sm"><?php echo htmlspecialchars($error); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="admissions.php#inquiry-form" class="space-y-6">
                        <input type="hidden" name="inquiry_submit" value="1">
                        
                        <div class="grid md:grid-cols-2 gap-6">
                            <!-- Parent/Guardian Name -->
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                                    PARENT/GUARDIAN NAME
                                </label>
                                <input 
                                    type="text" 
                                    name="parent_name" 
                                    required
                                    placeholder="John Doe"
                                    class="w-full px-4 py-3 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 text-slate-900 dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                    value="<?php echo isset($_POST['parent_name']) ? htmlspecialchars($_POST['parent_name']) : ''; ?>"
                                >
                            </div>
                            
                            <!-- Email Address -->
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                                    EMAIL ADDRESS
                                </label>
                                <input 
                                    type="email" 
                                    name="email" 
                                    required
                                    placeholder="johndoe@email.com"
                                    class="w-full px-4 py-3 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 text-slate-900 dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                >
                            </div>
                        </div>
                        
                        <div class="grid md:grid-cols-2 gap-6">
                            <!-- Student Name -->
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                                    STUDENT NAME
                                </label>
                                <input 
                                    type="text" 
                                    name="student_name" 
                                    required
                                    placeholder="Jane Doe"
                                    class="w-full px-4 py-3 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 text-slate-900 dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                    value="<?php echo isset($_POST['student_name']) ? htmlspecialchars($_POST['student_name']) : ''; ?>"
                                >
                            </div>
                            
                            <!-- Grade Level -->
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                                    GRADE LEVEL INTERESTED
                                </label>
                                <select 
                                    name="grade_level" 
                                    required
                                    class="w-full px-4 py-3 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 text-slate-900 dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                >
                                    <option value="">Select Grade</option>
                                    <option value="JSS 1">JSS 1 (Grade 7)</option>
                                    <option value="JSS 2">JSS 2 (Grade 8)</option>
                                    <option value="JSS 3">JSS 3 (Grade 9)</option>
                                    <option value="SSS 1">SSS 1 (Grade 10)</option>
                                    <option value="SSS 2">SSS 2 (Grade 11)</option>
                                    <option value="SSS 3">SSS 3 (Grade 12)</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- How did you hear about us -->
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                                HOW DID YOU HEAR ABOUT US?
                            </label>
                            <textarea 
                                name="hear_about" 
                                rows="4"
                                placeholder="Your message..."
                                class="w-full px-4 py-3 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 text-slate-900 dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary resize-none"
                            ><?php echo isset($_POST['hear_about']) ? htmlspecialchars($_POST['hear_about']) : ''; ?></textarea>
                        </div>
                        
                        <!-- Submit Button -->
                        <button 
                            type="submit"
                            class="w-full bg-primary text-white py-4 rounded-lg font-bold text-lg hover:bg-opacity-90 transition-all shadow-lg"
                        >
                            SUBMIT INQUIRY
                        </button>
                    </form>
                </div>
            </div>
            
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section class="py-20 bg-slate-50 dark:bg-slate-900">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-4xl font-display font-black text-center text-slate-900 dark:text-white mb-12">
            Frequently Asked Questions
        </h2>
        
        <div class="space-y-4">
            
            <!-- FAQ 1 -->
            <details class="group bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <summary class="flex items-center justify-between px-6 py-5 cursor-pointer font-semibold text-slate-900 dark:text-white hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                    <span>When is the next entrance examination?</span>
                    <span class="material-symbols-outlined group-open:rotate-180 transition-transform">expand_more</span>
                </summary>
                <div class="px-6 pb-5 text-slate-600 dark:text-slate-400">
                    <p>Our entrance examinations are held quarterly. The next examination is scheduled for March 15, 2025. Register early to secure your preferred date as spaces fill up quickly.</p>
                </div>
            </details>
            
            <!-- FAQ 2 -->
            <details class="group bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <summary class="flex items-center justify-between px-6 py-5 cursor-pointer font-semibold text-slate-900 dark:text-white hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                    <span>Are boarding facilities available?</span>
                    <span class="material-symbols-outlined group-open:rotate-180 transition-transform">expand_more</span>
                </summary>
                <div class="px-6 pb-5 text-slate-600 dark:text-slate-400">
                    <p>Yes! We offer world-class boarding facilities with 24/7 supervision, modern dormitories, nutritious meals, and comprehensive pastoral care. Our boarding program fosters independence, community, and academic excellence.</p>
                </div>
            </details>
            
            <!-- FAQ 3 -->
            <details class="group bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <summary class="flex items-center justify-between px-6 py-5 cursor-pointer font-semibold text-slate-900 dark:text-white hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                    <span>What curriculum does GOLA follow?</span>
                    <span class="material-symbols-outlined group-open:rotate-180 transition-transform">expand_more</span>
                </summary>
                <div class="px-6 pb-5 text-slate-600 dark:text-slate-400">
                    <p>We follow the Nigerian National Curriculum with international standards integration. Students prepare for WAEC, NECO, and JAMB examinations while developing 21st-century skills through our leadership development program.</p>
                </div>
            </details>
            
            <!-- FAQ 4 -->
            <details class="group bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <summary class="flex items-center justify-between px-6 py-5 cursor-pointer font-semibold text-slate-900 dark:text-white hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                    <span>What is the student-teacher ratio?</span>
                    <span class="material-symbols-outlined group-open:rotate-180 transition-transform">expand_more</span>
                </summary>
                <div class="px-6 pb-5 text-slate-600 dark:text-slate-400">
                    <p>We maintain a 15:1 student-teacher ratio to ensure personalized attention and academic excellence. Small class sizes allow our teachers to understand each student's unique learning style and provide targeted support.</p>
                </div>
            </details>
            
            <!-- FAQ 5 -->
            <details class="group bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <summary class="flex items-center justify-between px-6 py-5 cursor-pointer font-semibold text-slate-900 dark:text-white hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                    <span>Are scholarships or financial aid available?</span>
                    <span class="material-symbols-outlined group-open:rotate-180 transition-transform">expand_more</span>
                </summary>
                <div class="px-6 pb-5 text-slate-600 dark:text-slate-400">
                    <p>Yes, we offer merit-based scholarships and need-based financial aid. Outstanding students may qualify for partial or full tuition coverage. Contact our admissions office to learn more about scholarship opportunities and application requirements.</p>
                </div>
            </details>
            
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-20 bg-gradient-to-br from-primary to-slate-800 text-white">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-4xl lg:text-5xl font-display font-black mb-6">Ready to Begin Your Journey?</h2>
        <p class="text-xl text-slate-300 mb-10 max-w-3xl mx-auto">
            Take the first step toward academic excellence and leadership development. Our admissions team is ready to guide you through the process.
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="#inquiry-form" class="bg-gold text-primary font-bold px-10 py-4 rounded-xl hover:scale-105 transition-transform shadow-lg flex items-center justify-center gap-2">
                Start Your Application
                <span class="material-symbols-outlined">arrow_forward</span>
            </a>
            <a href="contact.php" class="bg-white/10 hover:bg-white/20 backdrop-blur-sm border border-white/20 font-bold px-10 py-4 rounded-xl transition-all flex items-center justify-center gap-2">
                Schedule a Tour
                <span class="material-symbols-outlined">event</span>
            </a>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
