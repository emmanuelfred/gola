<?php 
$page_title = "Contact Us - Get In Touch";
require_once 'config/database.php';

// Handle form submission
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['contact_submit'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    $privacy_accepted = isset($_POST['privacy_policy']) ? 1 : 0;
    
    // Basic validation
    if (empty($full_name) || empty($email) || empty($subject) || empty($message)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (!$privacy_accepted) {
        $error = "Please accept the privacy policy to continue.";
    } else {
        // Store in database (optional - create contacts table if needed)
        // For now, we'll just send an email notification
        
        $to = "info@goodnessomogo.edu.ng";
        $email_subject = "Contact Form: $subject";
        $email_body = "Name: $full_name\n";
        $email_body .= "Email: $email\n";
        $email_body .= "Subject: $subject\n\n";
        $email_body .= "Message:\n$message\n";
        
        $headers = "From: $email\r\n";
        $headers .= "Reply-To: $email\r\n";
        
        // Uncomment to enable email sending
        // if (mail($to, $email_subject, $email_body, $headers)) {
        //     $success = "Thank you! Your message has been sent successfully. We'll respond within 24 hours.";
        // } else {
        //     $error = "Sorry, there was an error sending your message. Please try again or contact us directly.";
        // }
        
        // For demo purposes, show success
        $success = "Thank you! Your message has been sent successfully. We'll respond within 24 hours.";
    }
}

include 'includes/header.php'; 
?>

<!-- Page Header -->
<section class="py-16 bg-gradient-to-br from-primary via-[#0A3556] to-slate-800 text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl lg:text-6xl font-display font-black mb-4">Connect With Us</h1>
        <p class="text-xl text-slate-300 max-w-4xl mx-auto leading-relaxed">
            Have questions about admissions or our programs? Our dedicated<br>
            team is here to guide you toward leadership excellence.
        </p>
    </div>
</section>

<!-- Main Contact Section -->
<section class="py-20 bg-slate-50 dark:bg-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-3 gap-8">
            
            <!-- Left Sidebar - Contact Info -->
            <div class="lg:col-span-1 space-y-6">
                
                <!-- Contact Details Card -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-8 shadow-lg">
                    <h2 class="text-2xl font-bold text-primary dark:text-gold mb-6">Contact Details</h2>
                    
                    <!-- Campus Location -->
                    <div class="flex items-start gap-4 mb-6 pb-6 border-b border-slate-200 dark:border-slate-700">
                        <div class="w-12 h-12 bg-gold/10 rounded-xl flex items-center justify-center flex-shrink-0">
                            <span class="material-symbols-outlined text-2xl text-gold">location_on</span>
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-900 dark:text-white mb-1">Our Campus</h3>
                            <p class="text-slate-600 dark:text-slate-400 text-sm">Ebonyi State, Nigeria.</p>
                            <p class="text-slate-600 dark:text-slate-400 text-sm">Building Tomorrow's Leaders Today.</p>
                        </div>
                    </div>
                    
                    <!-- Phone -->
                    <div class="flex items-start gap-4 mb-6 pb-6 border-b border-slate-200 dark:border-slate-700">
                        <div class="w-12 h-12 bg-gold/10 rounded-xl flex items-center justify-center flex-shrink-0">
                            <span class="material-symbols-outlined text-2xl text-gold">call</span>
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-900 dark:text-white mb-1">Call Us Directly</h3>
                            <p class="text-slate-600 dark:text-slate-400 text-sm mb-1">+234 (0) 800 000 0000</p>
                            <p class="text-xs text-slate-500">Mon - Fri, 8:00 AM - 4:00 PM</p>
                        </div>
                    </div>
                    
                    <!-- Email -->
                    <div class="flex items-start gap-4 mb-6">
                        <div class="w-12 h-12 bg-gold/10 rounded-xl flex items-center justify-center flex-shrink-0">
                            <span class="material-symbols-outlined text-2xl text-gold">mail</span>
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-900 dark:text-white mb-1">Email Address</h3>
                            <a href="mailto:info@goodnessomogo.edu.ng" class="text-sm text-gold hover:underline">
                                info@goodnessomogo.edu.ng
                            </a>
                        </div>
                    </div>
                    
                    <!-- Social Media -->
                    <div class="pt-6 border-t border-slate-200 dark:border-slate-700">
                        <h3 class="font-bold text-slate-900 dark:text-white mb-4">Follow Us</h3>
                        <div class="flex gap-3">
                            <a href="#" class="w-10 h-10 bg-primary hover:bg-gold text-white rounded-lg flex items-center justify-center transition-colors">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                            </a>
                            <a href="#" class="w-10 h-10 bg-primary hover:bg-gold text-white rounded-lg flex items-center justify-center transition-colors">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                            </a>
                            <a href="#" class="w-10 h-10 bg-primary hover:bg-gold text-white rounded-lg flex items-center justify-center transition-colors">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Admissions CTA -->
                <div class="bg-gradient-to-br from-primary to-slate-800 text-white rounded-2xl p-8 shadow-lg">
                    <h2 class="text-2xl font-bold mb-3">Admissions Now Open</h2>
                    <p class="text-slate-300 mb-6">Join Nigeria's leading institute for character development and academic excellence.</p>
                    <a href="admissions.php" class="inline-flex items-center gap-2 bg-gold text-primary px-6 py-3 rounded-lg font-semibold hover:bg-opacity-90 transition-all">
                        Apply Now
                        <span class="material-symbols-outlined">arrow_forward</span>
                    </a>
                </div>
                
            </div>
            
            <!-- Right Side - Contact Form -->
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-8 shadow-lg">
                    <h2 class="text-3xl font-bold text-primary dark:text-gold mb-2">Send us a Message</h2>
                    <p class="text-slate-600 dark:text-slate-400 mb-8">Feel free to reach out with any inquiries. We typically respond within 24 hours.</p>
                    
                    <!-- Success Message -->
                    <?php if ($success): ?>
                    <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl flex items-start gap-3">
                        <span class="material-symbols-outlined text-green-600">check_circle</span>
                        <p class="text-green-800"><?php echo htmlspecialchars($success); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Error Message -->
                    <?php if ($error): ?>
                    <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl flex items-start gap-3">
                        <span class="material-symbols-outlined text-red-600">error</span>
                        <p class="text-red-800"><?php echo htmlspecialchars($error); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Contact Form -->
                    <form method="POST" action="contact.php" class="space-y-6">
                        <input type="hidden" name="contact_submit" value="1">
                        
                        <div class="grid md:grid-cols-2 gap-6">
                            <!-- Full Name -->
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                                    Full Name
                                </label>
                                <input 
                                    type="text" 
                                    name="full_name" 
                                    required
                                    class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-gold/20 focus:border-gold"
                                    placeholder="Enter your name"
                                    value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>"
                                >
                            </div>
                            
                            <!-- Email Address -->
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                                    Email Address
                                </label>
                                <input 
                                    type="email" 
                                    name="email" 
                                    required
                                    class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-gold/20 focus:border-gold"
                                    placeholder="name@example.com"
                                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                >
                            </div>
                        </div>
                        
                        <!-- Subject -->
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                                Subject
                            </label>
                            <select 
                                name="subject" 
                                required
                                class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-gold/20 focus:border-gold"
                            >
                                <option value="">Select a subject</option>
                                <option value="General Inquiry">General Inquiry</option>
                                <option value="Admissions">Admissions Information</option>
                                <option value="Academic Programs">Academic Programs</option>
                                <option value="Financial Aid">Financial Aid</option>
                                <option value="Campus Visit">Campus Visit Request</option>
                                <option value="Technical Support">Technical Support</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <!-- Message -->
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                                Your Message
                            </label>
                            <textarea 
                                name="message" 
                                required
                                rows="6"
                                class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-gold/20 focus:border-gold resize-none"
                                placeholder="How can we help you?"
                            ><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                        </div>
                        
                        <!-- Privacy Policy Checkbox -->
                        <div class="flex items-start gap-3">
                            <input 
                                type="checkbox" 
                                name="privacy_policy" 
                                id="privacy_policy"
                                required
                                class="w-5 h-5 text-gold focus:ring-gold rounded mt-1"
                            >
                            <label for="privacy_policy" class="text-sm text-slate-600 dark:text-slate-400">
                                I agree to the <a href="privacy-policy.php" class="text-gold hover:underline">Privacy Policy</a> and consent to being contacted by the Academy.
                            </label>
                        </div>
                        
                        <!-- Submit Button -->
                        <div>
                            <button 
                                type="submit"
                                class="px-8 py-4 bg-primary text-white font-semibold rounded-lg hover:bg-opacity-90 transition-all flex items-center gap-2 shadow-lg"
                            >
                                Send Message
                                <span class="material-symbols-outlined">send</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
        </div>
    </div>
</section>

<!-- Map Section -->
<section class="py-20 bg-white dark:bg-background-dark">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-4xl font-display font-black text-primary dark:text-gold mb-4">Find Us</h2>
            <p class="text-slate-600 dark:text-slate-400">Visit our serene campus in Ebonyi State</p>
        </div>
        
        <!-- Google Maps Embed -->
        <div class="rounded-2xl overflow-hidden shadow-2xl">
            <iframe 
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d253682.62803175103!2d8.009379699999999!3d6.2649482!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x1044505e5db1e7c1%3A0xb1c4f84b8fc8d4c7!2sEbonyi%2C%20Nigeria!5e0!3m2!1sen!2s!4v1234567890123!5m2!1sen!2s" 
                width="100%" 
                height="450" 
                style="border:0;" 
                allowfullscreen="" 
                loading="lazy" 
                referrerpolicy="no-referrer-when-downgrade"
                class="w-full"
            ></iframe>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
