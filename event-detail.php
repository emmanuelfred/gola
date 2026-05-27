<?php 
require_once 'config/database.php';

// Get event ID from URL
$event_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch event details
$stmt = $conn->prepare("SELECT * FROM academic_events WHERE id = ? AND is_active = TRUE");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header('Location: academics.php');
    exit();
}

$event = $result->fetch_assoc();
$page_title = htmlspecialchars($event['title']);

// Fetch related events (same type, upcoming)
$related_query = "SELECT * FROM academic_events WHERE event_type = ? AND id != ? AND is_active = TRUE AND event_date >= CURDATE() ORDER BY event_date ASC LIMIT 3";
$related_stmt = $conn->prepare($related_query);
$related_stmt->bind_param("si", $event['event_type'], $event_id);
$related_stmt->execute();
$related_result = $related_stmt->get_result();

include 'includes/header.php'; 
?>

<!-- Event Header -->
<section class="py-16 bg-gradient-to-br from-primary to-slate-800 text-white">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-2 text-sm text-slate-300 mb-6">
            <a href="index.php" class="hover:text-gold">Home</a>
            <span class="material-symbols-outlined text-sm">chevron_right</span>
            <a href="academics.php" class="hover:text-gold">Academics</a>
            <span class="material-symbols-outlined text-sm">chevron_right</span>
            <span class="text-white">Event Details</span>
        </div>
        
        <!-- Event Type Badge -->
        <div class="inline-flex items-center gap-2 px-4 py-2 bg-gold/20 border border-gold/30 rounded-full text-gold font-semibold text-sm uppercase tracking-widest mb-6">
            <span class="material-symbols-outlined text-sm">
                <?php 
                echo $event['event_type'] == 'Exam' ? 'quiz' : 
                    ($event['event_type'] == 'Sports' ? 'sports_soccer' : 
                    ($event['event_type'] == 'Cultural' ? 'theater_comedy' : 
                    ($event['event_type'] == 'Meeting' ? 'groups' : 'event'))); 
                ?>
            </span>
            <?php echo htmlspecialchars($event['event_type']); ?>
        </div>
        
        <h1 class="text-4xl lg:text-6xl font-display font-black mb-6"><?php echo htmlspecialchars($event['title']); ?></h1>
        
        <!-- Event Meta Info -->
        <div class="flex flex-wrap gap-6 text-lg">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-gold">calendar_month</span>
                <span><?php echo date('F d, Y', strtotime($event['event_date'])); ?></span>
            </div>
            <?php if ($event['event_time']): ?>
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-gold">schedule</span>
                <span><?php echo htmlspecialchars($event['event_time']); ?></span>
            </div>
            <?php endif; ?>
            <?php if ($event['location']): ?>
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-gold">location_on</span>
                <span><?php echo htmlspecialchars($event['location']); ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Event Details -->
<section class="py-20 bg-white dark:bg-background-dark">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-3 gap-12">
            
            <!-- Main Content -->
            <div class="lg:col-span-2">
                <div class="prose prose-lg max-w-none dark:prose-invert">
                    <h2 class="text-3xl font-display font-black text-primary dark:text-gold mb-6">Event Details</h2>
                    <div class="text-slate-600 dark:text-slate-400 leading-relaxed whitespace-pre-line">
                        <?php echo nl2br(htmlspecialchars($event['description'])); ?>
                    </div>
                </div>
                
                <!-- Additional Information -->
                <div class="mt-12 bg-slate-50 dark:bg-slate-800 rounded-2xl p-8">
                    <h3 class="text-xl font-bold text-primary dark:text-gold mb-6">Important Information</h3>
                    <div class="space-y-4">
                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-gold mt-1">info</span>
                            <div>
                                <p class="font-semibold text-slate-900 dark:text-white mb-1">Event Type</p>
                                <p class="text-sm text-slate-600 dark:text-slate-400"><?php echo htmlspecialchars($event['event_type']); ?></p>
                            </div>
                        </div>
                        <?php if ($event['location']): ?>
                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-gold mt-1">location_on</span>
                            <div>
                                <p class="font-semibold text-slate-900 dark:text-white mb-1">Location</p>
                                <p class="text-sm text-slate-600 dark:text-slate-400"><?php echo htmlspecialchars($event['location']); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-gold mt-1">calendar_month</span>
                            <div>
                                <p class="font-semibold text-slate-900 dark:text-white mb-1">Date</p>
                                <p class="text-sm text-slate-600 dark:text-slate-400"><?php echo date('l, F d, Y', strtotime($event['event_date'])); ?></p>
                            </div>
                        </div>
                        <?php if ($event['event_time']): ?>
                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-gold mt-1">schedule</span>
                            <div>
                                <p class="font-semibold text-slate-900 dark:text-white mb-1">Time</p>
                                <p class="text-sm text-slate-600 dark:text-slate-400"><?php echo htmlspecialchars($event['event_time']); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- PDF Download if available -->
                <?php if ($event['pdf_file']): ?>
                <div class="mt-8">
                    <a href="assets/documents/events/<?php echo htmlspecialchars($event['pdf_file']); ?>" 
                       target="_blank"
                       class="inline-flex items-center gap-2 bg-primary text-white px-8 py-4 rounded-xl font-semibold hover:bg-opacity-90 transition-all shadow-lg">
                        <span class="material-symbols-outlined">download</span>
                        Download Event Details (PDF)
                    </a>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Sidebar -->
            <div class="lg:col-span-1">
                
                <!-- Add to Calendar -->
                <div class="bg-gradient-to-br from-gold/10 to-primary/10 rounded-2xl p-6 mb-8">
                    <h3 class="text-xl font-bold text-primary dark:text-gold mb-4">Add to Calendar</h3>
                    <div class="space-y-3">
                        <a href="#" class="flex items-center gap-3 px-4 py-3 bg-white dark:bg-slate-800 rounded-lg hover:shadow-md transition-all">
                            <span class="material-symbols-outlined text-primary dark:text-gold">event</span>
                            <span class="font-medium text-slate-900 dark:text-white">Google Calendar</span>
                        </a>
                        <a href="#" class="flex items-center gap-3 px-4 py-3 bg-white dark:bg-slate-800 rounded-lg hover:shadow-md transition-all">
                            <span class="material-symbols-outlined text-primary dark:text-gold">calendar_month</span>
                            <span class="font-medium text-slate-900 dark:text-white">iCal / Outlook</span>
                        </a>
                    </div>
                </div>
                
                <!-- Share Event -->
                <div class="bg-slate-50 dark:bg-slate-800 rounded-2xl p-6 mb-8">
                    <h3 class="text-xl font-bold text-primary dark:text-gold mb-4">Share Event</h3>
                    <div class="flex gap-3">
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" 
                           target="_blank"
                           class="w-12 h-12 bg-blue-600 text-white rounded-lg flex items-center justify-center hover:bg-blue-700 transition-colors">
                            <span class="material-symbols-outlined">share</span>
                        </a>
                        <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>&text=<?php echo urlencode($event['title']); ?>" 
                           target="_blank"
                           class="w-12 h-12 bg-sky-500 text-white rounded-lg flex items-center justify-center hover:bg-sky-600 transition-colors">
                            <span class="material-symbols-outlined">share</span>
                        </a>
                        <a href="https://wa.me/?text=<?php echo urlencode($event['title'] . ' - ' . 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" 
                           target="_blank"
                           class="w-12 h-12 bg-green-600 text-white rounded-lg flex items-center justify-center hover:bg-green-700 transition-colors">
                            <span class="material-symbols-outlined">share</span>
                        </a>
                    </div>
                </div>
                
                <!-- Related Events -->
                <?php if ($related_result && $related_result->num_rows > 0): ?>
                <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
                    <h3 class="text-xl font-bold text-primary dark:text-gold mb-4">Related Events</h3>
                    <div class="space-y-4">
                        <?php while($related = $related_result->fetch_assoc()): ?>
                        <a href="event-detail.php?id=<?php echo $related['id']; ?>" 
                           class="block p-4 bg-slate-50 dark:bg-slate-700 rounded-lg hover:shadow-md transition-all">
                            <div class="flex gap-3">
                                <div class="text-center bg-primary text-white rounded-lg px-3 py-2 flex-shrink-0">
                                    <div class="text-xl font-display font-black"><?php echo date('d', strtotime($related['event_date'])); ?></div>
                                    <div class="text-xs uppercase font-semibold"><?php echo date('M', strtotime($related['event_date'])); ?></div>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-bold text-sm text-slate-900 dark:text-white mb-1 line-clamp-2">
                                        <?php echo htmlspecialchars($related['title']); ?>
                                    </h4>
                                    <p class="text-xs text-slate-600 dark:text-slate-400">
                                        <?php echo htmlspecialchars($related['event_type']); ?>
                                    </p>
                                </div>
                            </div>
                        </a>
                        <?php endwhile; ?>
                    </div>
                    <a href="events.php" class="block mt-4 text-center text-primary dark:text-gold font-semibold hover:underline">
                        View All Events →
                    </a>
                </div>
                <?php endif; ?>
                
            </div>
            
        </div>
    </div>
</section>

<!-- Back to Academics -->
<section class="py-12 bg-slate-50 dark:bg-slate-900">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <a href="academics.php" class="inline-flex items-center gap-2 bg-primary text-white px-8 py-4 rounded-xl font-semibold hover:bg-opacity-90 transition-all">
            <span class="material-symbols-outlined">arrow_back</span>
            Back to Academics
        </a>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
