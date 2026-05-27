<?php 
$page_title = "Academics & Curriculum";
require_once 'config/database.php';

// Fetch featured upcoming events (homepage style)
$featured_events_query = "SELECT * FROM academic_events WHERE is_active = TRUE AND is_featured = TRUE AND event_date >= CURDATE() ORDER BY event_date ASC LIMIT 3";
$featured_events_result = $conn->query($featured_events_query);

// Fetch all upcoming events grouped by month
$all_events_query = "SELECT * FROM academic_events WHERE is_active = TRUE AND event_date >= CURDATE() ORDER BY event_date ASC LIMIT 20";
$all_events_result = $conn->query($all_events_query);

// Group events by month
$events_by_month = [];
if ($all_events_result && $all_events_result->num_rows > 0) {
    while ($event = $all_events_result->fetch_assoc()) {
        $month_year = date('F Y', strtotime($event['event_date']));
        if (!isset($events_by_month[$month_year])) {
            $events_by_month[$month_year] = [];
        }
        $events_by_month[$month_year][] = $event;
    }
}

// Fetch curriculum subjects grouped by category
$subjects_query = "SELECT * FROM curriculum_subjects WHERE is_active = TRUE ORDER BY display_order ASC, subject_name ASC";
$subjects_result = $conn->query($subjects_query);

// Group subjects by category
$subjects_by_category = [
    'Core' => [],
    'Vocational' => [],
    'Languages' => [],
    'Elective' => []
];

if ($subjects_result) {
    while ($subject = $subjects_result->fetch_assoc()) {
        $subjects_by_category[$subject['category']][] = $subject;
    }
}

// Fetch departments
$departments_query = "SELECT * FROM academic_departments WHERE is_active = TRUE ORDER BY display_order ASC";
$departments_result = $conn->query($departments_query);

include 'includes/header.php'; 
?>

<!-- Page Header -->
<section class="py-16 bg-gradient-to-br from-primary via-[#0A3556] to-slate-800 text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl lg:text-6xl font-display font-black mb-4">Academics & Curriculum</h1>
        <p class="text-xl text-slate-300 max-w-4xl mx-auto leading-relaxed">
            Empowering future leaders through academic excellence, critical thinking,<br>
            and a holistic curriculum designed for the 21st-century global stage.
        </p>
    </div>
</section>

<!-- Academic Calendar Section -->
<section class="py-16 bg-slate-50 dark:bg-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg p-8 flex items-center justify-between flex-wrap gap-6">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 bg-gold/10 rounded-xl flex items-center justify-center">
                    <span class="material-symbols-outlined text-4xl text-gold">calendar_month</span>
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-primary dark:text-gold mb-1">Academic Calendar 2024/2025</h2>
                    <p class="text-slate-600 dark:text-slate-400">Download our comprehensive schedule for the current session</p>
                </div>
            </div>
            <a href="assets/documents/academic-calendar.pdf" target="_blank" class="bg-primary hover:bg-opacity-90 text-white px-8 py-4 rounded-xl font-semibold transition-all flex items-center gap-2 shadow-lg">
                <span class="material-symbols-outlined">download</span>
                Download PDF
            </a>
        </div>
        
        
    </div>
</section>

<!-- Curriculum Structure Section -->
<section class="py-20 bg-white dark:bg-background-dark">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-4xl font-display font-black text-primary dark:text-gold mb-4">Our Curriculum Structure</h2>
            <p class="text-slate-600 dark:text-slate-400 max-w-3xl mx-auto">Comprehensive education across all levels</p>
        </div>
        
        <!-- Class Level Tabs -->
        <div class="flex justify-center gap-4 mb-12 flex-wrap">
            <button onclick="switchLevel('all')" class="level-tab active px-8 py-3 rounded-xl font-semibold transition-all bg-primary text-white" data-level="all">
                All Subjects
            </button>
            <button onclick="switchLevel('jss')" class="level-tab px-8 py-3 rounded-xl font-semibold transition-all bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200" data-level="jss">
                Junior Secondary (JSS 1-3)
            </button>
            <button onclick="switchLevel('sss')" class="level-tab px-8 py-3 rounded-xl font-semibold transition-all bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200" data-level="sss">
                Senior Secondary (SSS 1-3)
            </button>
        </div>
        
        <!-- Subject Categories -->
        <div class="grid md:grid-cols-3 gap-8">
            
            <!-- Core Subjects -->
            <div class="bg-slate-50 dark:bg-slate-800 rounded-2xl p-8 hover:shadow-xl transition-all">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-12 h-12 bg-blue-500/10 rounded-xl flex items-center justify-center">
                        <span class="material-symbols-outlined text-2xl text-blue-600 dark:text-blue-400">bookmark</span>
                    </div>
                    <h3 class="text-2xl font-bold text-primary dark:text-gold">Core Subjects</h3>
                </div>
                <ul class="space-y-3">
                    <?php foreach ($subjects_by_category['Core'] as $subject): ?>
                    <li class="subject-item flex items-start gap-2 text-slate-700 dark:text-slate-300" data-level="<?php echo strtolower(substr($subject['class_level'], 0, 3)); ?>">
                        <span class="text-gold mt-1">•</span>
                        <div>
                            <span class="font-medium"><?php echo htmlspecialchars($subject['subject_name']); ?></span>
                            <?php if ($subject['class_level'] != 'All'): ?>
                            <span class="text-xs text-slate-500 ml-2">(<?php echo $subject['class_level']; ?>)</span>
                            <?php endif; ?>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <!-- Vocational & Creative -->
            <div class="bg-slate-50 dark:bg-slate-800 rounded-2xl p-8 hover:shadow-xl transition-all">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-12 h-12 bg-gold/10 rounded-xl flex items-center justify-center">
                        <span class="material-symbols-outlined text-2xl text-gold">palette</span>
                    </div>
                    <h3 class="text-2xl font-bold text-primary dark:text-gold">Vocational & Creative</h3>
                </div>
                <ul class="space-y-3">
                    <?php foreach ($subjects_by_category['Vocational'] as $subject): ?>
                    <li class="subject-item flex items-start gap-2 text-slate-700 dark:text-slate-300" data-level="<?php echo strtolower(substr($subject['class_level'], 0, 3)); ?>">
                        <span class="text-gold mt-1">•</span>
                        <div>
                            <span class="font-medium"><?php echo htmlspecialchars($subject['subject_name']); ?></span>
                            <?php if ($subject['class_level'] != 'All'): ?>
                            <span class="text-xs text-slate-500 ml-2">(<?php echo $subject['class_level']; ?>)</span>
                            <?php endif; ?>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <!-- Leadership & Languages -->
            <div class="bg-slate-50 dark:bg-slate-800 rounded-2xl p-8 hover:shadow-xl transition-all">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-12 h-12 bg-purple-500/10 rounded-xl flex items-center justify-center">
                        <span class="material-symbols-outlined text-2xl text-purple-600 dark:text-purple-400">groups</span>
                    </div>
                    <h3 class="text-2xl font-bold text-primary dark:text-gold">Leadership & Languages</h3>
                </div>
                <ul class="space-y-3">
                    <?php foreach ($subjects_by_category['Languages'] as $subject): ?>
                    <li class="subject-item flex items-start gap-2 text-slate-700 dark:text-slate-300" data-level="<?php echo strtolower(substr($subject['class_level'], 0, 3)); ?>">
                        <span class="text-gold mt-1">•</span>
                        <div>
                            <span class="font-medium"><?php echo htmlspecialchars($subject['subject_name']); ?></span>
                            <?php if ($subject['class_level'] != 'All'): ?>
                            <span class="text-xs text-slate-500 ml-2">(<?php echo $subject['class_level']; ?>)</span>
                            <?php endif; ?>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
        </div>
        
        <!-- Elective Subjects (SSS Only) -->
        <?php if (!empty($subjects_by_category['Elective'])): ?>
        <div class="mt-8 bg-gradient-to-br from-gold/10 to-primary/10 rounded-2xl p-8">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-12 h-12 bg-gold rounded-xl flex items-center justify-center">
                    <span class="material-symbols-outlined text-2xl text-primary">star</span>
                </div>
                <div>
                    <h3 class="text-2xl font-bold text-primary dark:text-gold">Elective Subjects</h3>
                    <p class="text-sm text-slate-600 dark:text-slate-400">Available for Senior Secondary Students (SSS 1-3)</p>
                </div>
            </div>
            <div class="grid md:grid-cols-3 gap-4">
                <?php foreach ($subjects_by_category['Elective'] as $subject): ?>
                <div class="bg-white dark:bg-slate-800 rounded-xl p-4 subject-item" data-level="sss">
                    <p class="font-semibold text-primary dark:text-gold"><?php echo htmlspecialchars($subject['subject_name']); ?></p>
                    <?php if ($subject['description']): ?>
                    <p class="text-sm text-slate-600 dark:text-slate-400 mt-1"><?php echo htmlspecialchars($subject['description']); ?></p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Academic Events Calendar Section -->
<section id="events" class="py-20 bg-white dark:bg-background-dark scroll-mt-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-4xl font-display font-black text-primary dark:text-gold mb-4">Academic Events Calendar</h2>
            <p class="text-slate-600 dark:text-slate-400 max-w-3xl mx-auto">Stay updated with important dates, exams, holidays, and exciting school activities</p>
        </div>
        
        <?php if (!empty($events_by_month)): ?>
        
        <!-- Events Timeline -->
        <div class="space-y-12">
            <?php foreach ($events_by_month as $month_year => $month_events): ?>
            
            <!-- Month Header -->
            <div>
                <div class="flex items-center gap-4 mb-6">
                    <div class="h-1 flex-1 bg-gradient-to-r from-transparent via-gold to-transparent"></div>
                    <h3 class="text-2xl font-display font-black text-primary dark:text-gold px-6 py-2 bg-gold/10 rounded-full">
                        <?php echo $month_year; ?>
                    </h3>
                    <div class="h-1 flex-1 bg-gradient-to-r from-gold via-transparent to-transparent"></div>
                </div>
                
                <!-- Events for this month -->
                <div class="grid md:grid-cols-2 gap-6">
                    <?php foreach ($month_events as $event): ?>
                    
                    <a href="event-detail.php?id=<?php echo $event['id']; ?>" 
                       class="group bg-slate-50 dark:bg-slate-800 rounded-2xl p-6 border-l-4 <?php 
                           echo $event['event_type'] == 'Exam' ? 'border-red-500 hover:border-red-600' : 
                               ($event['event_type'] == 'Sports' ? 'border-green-500 hover:border-green-600' : 
                               ($event['event_type'] == 'Cultural' ? 'border-purple-500 hover:border-purple-600' : 
                               ($event['event_type'] == 'Holiday' ? 'border-blue-500 hover:border-blue-600' : 'border-gold hover:border-gold/80'))); 
                       ?> hover:shadow-xl transition-all">
                        
                        <div class="flex gap-4">
                            <!-- Date Box -->
                            <div class="flex-shrink-0">
                                <div class="w-16 h-16 bg-primary rounded-xl flex flex-col items-center justify-center text-white">
                                    <div class="text-2xl font-display font-black leading-none"><?php echo date('d', strtotime($event['event_date'])); ?></div>
                                    <div class="text-xs uppercase font-semibold mt-1"><?php echo date('M', strtotime($event['event_date'])); ?></div>
                                </div>
                            </div>
                            
                            <!-- Event Details -->
                            <div class="flex-1">
                                <div class="flex items-start justify-between gap-2 mb-2">
                                    <h4 class="font-bold text-slate-900 dark:text-white group-hover:text-primary dark:group-hover:text-gold transition-colors">
                                        <?php echo htmlspecialchars($event['title']); ?>
                                    </h4>
                                    <span class="px-3 py-1 bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold rounded-full flex-shrink-0">
                                        <?php echo htmlspecialchars($event['event_type']); ?>
                                    </span>
                                </div>
                                
                                <?php if ($event['event_time'] || $event['location']): ?>
                                <div class="flex flex-wrap gap-4 text-sm text-slate-600 dark:text-slate-400 mb-2">
                                    <?php if ($event['event_time']): ?>
                                    <div class="flex items-center gap-1">
                                        <span class="material-symbols-outlined text-gold text-sm">schedule</span>
                                        <span><?php echo htmlspecialchars($event['event_time']); ?></span>
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($event['location']): ?>
                                    <div class="flex items-center gap-1">
                                        <span class="material-symbols-outlined text-gold text-sm">location_on</span>
                                        <span><?php echo htmlspecialchars($event['location']); ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                                
                                <p class="text-sm text-slate-600 dark:text-slate-400 line-clamp-2">
                                    <?php echo htmlspecialchars($event['description']); ?>
                                </p>
                                
                                <?php if ($event['is_featured']): ?>
                                <div class="mt-3">
                                    <span class="inline-flex items-center gap-1 px-3 py-1 bg-gold/10 text-gold text-xs font-semibold rounded-full">
                                        <span class="material-symbols-outlined text-xs">star</span>
                                        Featured Event
                                    </span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                    
                    <?php endforeach; ?>
                </div>
            </div>
            
            <?php endforeach; ?>
        </div>
        
        <?php else: ?>
        
        <!-- No Events Message -->
        <div class="text-center py-16">
            <span class="material-symbols-outlined text-8xl text-slate-300 dark:text-slate-700 mb-6 block">event</span>
            <h3 class="text-2xl font-bold text-slate-900 dark:text-white mb-2">No Upcoming Events</h3>
            <p class="text-slate-600 dark:text-slate-400 mb-8">Check back soon for our academic calendar and exciting school events</p>
        </div>
        
        <?php endif; ?>
        
        <!-- Event Types Legend -->
        <div class="mt-16 bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-800 dark:to-slate-900 rounded-2xl p-8">
            <h3 class="text-xl font-bold text-primary dark:text-gold mb-6 text-center">Event Types</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4">
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Exams</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Sports</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 bg-purple-500 rounded-full"></div>
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Cultural</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Holidays</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 bg-gold rounded-full"></div>
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Activities</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 bg-gold rounded-full"></div>
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Meetings</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 bg-gold rounded-full"></div>
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Other</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Academic Departments -->
<section class="py-20 bg-slate-50 dark:bg-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-4xl font-display font-black text-primary dark:text-gold mb-4">Academic Departments</h2>
            <p class="text-slate-600 dark:text-slate-400 max-w-3xl mx-auto">Specialized faculties dedicated to nurturing talent and academic rigor</p>
        </div>
        
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php if ($departments_result && $departments_result->num_rows > 0): ?>
                <?php 
                $dept_colors = ['from-blue-600 to-blue-800', 'from-purple-600 to-purple-800', 'from-green-600 to-green-800', 'from-amber-600 to-amber-800'];
                $index = 0;
                while($dept = $departments_result->fetch_assoc()): 
                    $color = $dept_colors[$index % count($dept_colors)];
                ?>
                
                <div class="group cursor-pointer">
                    <div class="bg-gradient-to-br <?php echo $color; ?> rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all h-80 relative">
                        <!-- Background Pattern -->
                        <div class="absolute inset-0 opacity-10">
                            <?php if ($dept['featured_image']): ?>
                            <img src="assets/images/departments/<?php echo htmlspecialchars($dept['featured_image']); ?>" 
                                 alt="<?php echo htmlspecialchars($dept['department_name']); ?>"
                                 class="w-full h-full object-cover">
                            <?php else: ?>
                            <div class="w-full h-full bg-white/10"></div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Content -->
                        <div class="relative p-8 h-full flex flex-col justify-between">
                            <div>
                                <span class="text-xs font-semibold text-white/70 uppercase tracking-wider">Faculty</span>
                                <h3 class="text-2xl font-bold text-white mt-2 mb-3"><?php echo htmlspecialchars($dept['department_name']); ?></h3>
                                <p class="text-white/90 text-sm leading-relaxed"><?php echo htmlspecialchars($dept['subjects_covered']); ?></p>
                            </div>
                           
                        </div>
                    </div>
                </div>
                
                <?php 
                $index++;
                endwhile; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Educational Philosophy -->
<section class="py-20 bg-white dark:bg-background-dark">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-16 items-center">
            <div>
                <h2 class="text-4xl font-display font-black text-primary dark:text-gold mb-6">Our Educational Philosophy</h2>
                <p class="text-xl text-gold font-semibold mb-6 italic">"To Learn, To Grow, To Lead"</p>
                <div class="space-y-4 text-slate-600 dark:text-slate-400 leading-relaxed">
                    <p>
                        At Goodness Omogo Leadership Academy, we believe that education is more than just passing exams. 
                        It's about character formation, fostering curiosity, and preparing students to take up leadership 
                        roles in a complex world.
                    </p>
                    <p>
                        Our curriculum is benchmarked against international standards while remaining deeply rooted in the 
                        rich heritage of Nigeria. We combine rigorous academics with practical skills, creativity, and 
                        ethical values.
                    </p>
                </div>
                
                <!-- Features -->
                <div class="mt-8 space-y-4">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-gold/10 rounded-xl flex items-center justify-center flex-shrink-0">
                            <span class="material-symbols-outlined text-2xl text-gold">psychology</span>
                        </div>
                        <div>
                            <h4 class="font-bold text-primary dark:text-gold mb-1">Holistic Development Focused</h4>
                            <p class="text-sm text-slate-600 dark:text-slate-400">Nurturing mind, body, and character</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-gold/10 rounded-xl flex items-center justify-center flex-shrink-0">
                            <span class="material-symbols-outlined text-2xl text-gold">devices</span>
                        </div>
                        <div>
                            <h4 class="font-bold text-primary dark:text-gold mb-1">Technology Integrated Learning</h4>
                            <p class="text-sm text-slate-600 dark:text-slate-400">21st-century skills for digital natives</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-gold/10 rounded-xl flex items-center justify-center flex-shrink-0">
                            <span class="material-symbols-outlined text-2xl text-gold">groups_2</span>
                        </div>
                        <div>
                            <h4 class="font-bold text-primary dark:text-gold mb-1">Small Class Sizes for Personalized Attention</h4>
                            <p class="text-sm text-slate-600 dark:text-slate-400">15:1 student-teacher ratio</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Image -->
            <div class="relative">
                <div class="bg-gradient-to-br from-gold/20 to-primary/20 rounded-3xl p-4 transform rotate-3">
                    <img src="./asset/courses-5.jpg" 
                         alt="Classroom" 
                         class="w-full rounded-2xl shadow-2xl transform -rotate-3">
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="py-20 bg-gradient-to-r from-primary to-slate-800 text-white">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-4xl lg:text-5xl font-display font-black mb-6">Ready to Join Our Academic Community?</h2>
        <p class="text-xl text-slate-300 mb-10 max-w-3xl mx-auto">
            Experience world-class education that prepares your child for success in Nigeria and beyond.
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="admissions.php" class="bg-gold text-primary font-bold px-10 py-4 rounded-xl hover:scale-105 transition-transform shadow-lg flex items-center justify-center gap-2">
                <span class="material-symbols-outlined">description</span>
                Apply for Admission
            </a>
            <a href="contact.php" class="bg-white/10 hover:bg-white/20 backdrop-blur-sm border border-white/20 font-bold px-10 py-4 rounded-xl transition-all flex items-center justify-center gap-2">
                <span class="material-symbols-outlined">call</span>
                Contact Admissions
            </a>
        </div>
    </div>
</section>

<script>
// Level switching functionality
function switchLevel(level) {
    const tabs = document.querySelectorAll('.level-tab');
    const items = document.querySelectorAll('.subject-item');
    
    // Update tab styles
    tabs.forEach(tab => {
        if (tab.dataset.level === level) {
            tab.classList.remove('bg-slate-100', 'dark:bg-slate-800', 'text-slate-700', 'dark:text-slate-300');
            tab.classList.add('bg-primary', 'text-white', 'active');
        } else {
            tab.classList.add('bg-slate-100', 'dark:bg-slate-800', 'text-slate-700', 'dark:text-slate-300');
            tab.classList.remove('bg-primary', 'text-white', 'active');
        }
    });
    
    // Filter subjects
    items.forEach(item => {
        const itemLevel = item.dataset.level;
        if (level === 'all' || itemLevel === 'all' || itemLevel === level) {
            item.style.display = 'flex';
        } else {
            item.style.display = 'none';
        }
    });
}
</script>

<?php include 'includes/footer.php'; ?>