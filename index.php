<?php 
$page_title = "Home - Nigeria's Premier Leadership School";
require_once 'config/database.php';

$news_query = "SELECT * FROM news_articles WHERE is_published = TRUE ORDER BY published_date DESC, created_at DESC LIMIT 3";
$news_result = $conn->query($news_query);

$events_query = "SELECT * FROM academic_events WHERE is_active = TRUE AND is_featured = TRUE AND event_date >= CURDATE() ORDER BY event_date ASC LIMIT 3";
$events_result = $conn->query($events_query);

include 'includes/header.php'; 
?>

<!-- ══════════════════════════════════════════════
     HERO SLIDER
══════════════════════════════════════════════ -->
<header class="relative text-white overflow-hidden" style="height:100vh;min-height:600px;max-height:900px;">

    <!-- ── Slides ── -->
    <div id="heroSlider" class="absolute inset-0">

        <!-- Slide 1 -->
        <div class="hero-slide absolute inset-0 transition-opacity duration-1000 opacity-100"
             style="background: linear-gradient(#002c47b0, #002c47c8), url('./asset/header.jpg') center/cover no-repeat;">
        </div>

        <!-- Slide 2 -->
        <div class="hero-slide absolute inset-0 transition-opacity duration-1000 opacity-0"
             style="background: linear-gradient(#002c47a8, #002c47c0), url('./asset/school/image1.jpeg') center/cover no-repeat;">
        </div>

        <!-- Slide 3 -->
        <div class="hero-slide absolute inset-0 transition-opacity duration-1000 opacity-0"
             style="background: linear-gradient(#002c47a8, #002c47c0), url('./asset/school/image2.jpeg') center/cover no-repeat;">
        </div>

        <!-- Slide 4 -->
        <div class="hero-slide absolute inset-0 transition-opacity duration-1000 opacity-0"
             style="background: linear-gradient(#002c47a8, #002c47c0), url('./asset/school/image3.jpeg') center/cover no-repeat;">
        </div>

        <!-- Slide 5 -->
        <div class="hero-slide absolute inset-0 transition-opacity duration-1000 opacity-0"
             style="background: linear-gradient(#002c47a8, #002c47c0), url('./asset/school/image4.jpeg') center/cover no-repeat;">
        </div>

    </div>

    <!-- ── Content ── -->
    <div class="relative z-10 h-full flex items-center">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
            <div class="grid lg:grid-cols-2 gap-16 items-center">

                <!-- Left: Text -->
                <div class="space-y-8 text-center lg:text-left">
                    <div class="inline-flex items-center gap-2 px-3 py-1 bg-gold/20 border border-gold/30 rounded-full text-gold font-semibold text-sm uppercase tracking-widest">
                        <span class="w-2 h-2 rounded-full bg-gold animate-pulse"></span>
                        Goodness Omogo Leadership Academy
                    </div>

                    <!-- Slide-synced headline -->
                    <div id="heroHeadlines" class="relative" style="min-height:13rem;">
                        <h1 class="hero-headline absolute inset-0 text-5xl lg:text-6xl font-display font-black leading-tight transition-all duration-700 opacity-100 translate-y-0">
                            Cultivating the <br><span class="text-gold">Next Generation</span><br> of Leaders
                        </h1>
                        <h1 class="hero-headline absolute inset-0 text-5xl lg:text-6xl font-display font-black leading-tight transition-all duration-700 opacity-0 translate-y-4">
                            Excellence in <br><span class="text-gold">Education</span><br> & Character
                        </h1>
                        <h1 class="hero-headline absolute inset-0 text-5xl lg:text-6xl font-display font-black leading-tight transition-all duration-700 opacity-0 translate-y-4">
                            A Campus Built<br> for <span class="text-gold">Champions</span><br> to Thrive
                        </h1>
                        <h1 class="hero-headline absolute inset-0 text-5xl lg:text-6xl font-display font-black leading-tight transition-all duration-700 opacity-0 translate-y-4">
                            Where Leaders<br> are <span class="text-gold">Shaped</span><br> Every Day
                        </h1>
                        <h1 class="hero-headline absolute inset-0 text-5xl lg:text-6xl font-display font-black leading-tight transition-all duration-700 opacity-0 translate-y-4">
                            Join a Legacy of<br><span class="text-gold">Greatness</span><br> & Service
                        </h1>
                    </div>

                    <p class="text-xl text-slate-300 max-w-xl mx-auto lg:mx-0 leading-relaxed">
                        Excellence in education, character building, and leadership development. Empowering students to excel globally and serve locally.
                    </p>

                    <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                        <a href="admissions.php" class="bg-gold text-primary font-bold px-8 py-4 rounded-lg hover:scale-105 transition-transform flex items-center justify-center gap-2 shadow-lg">
                            <span class="material-symbols-outlined">description</span>
                            Apply Now
                        </a>
                        <a href="result-checker/" class="bg-white/10 hover:bg-white/20 backdrop-blur-sm border border-white/20 font-bold px-8 py-4 rounded-lg transition-all flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined">school</span>
                            Result Checker
                        </a>
                    </div>

                    <p class="text-gold font-display italic text-2xl pt-2">"To Learn • To Grow • To Lead"</p>
                </div>

                <!-- Right: Crest card -->
                <div class="hidden lg:flex justify-center items-center">
                    <div class="relative w-full max-w-sm">
                        <div class="bg-gradient-to-br from-gold/30 to-primary/30 backdrop-blur-lg border border-gold/20 rounded-3xl p-8 shadow-2xl crest-hover">
                            <div class="bg-primary/50 backdrop-blur-sm rounded-2xl p-6">
                                <img src="./asset/favicon.png" alt="Academy Crest" class="w-full h-auto">
                            </div>
                            <div class="text-center mt-6">
                                <h3 class="text-gold font-display text-3xl font-bold mb-2">Goodness Omogo</h3>
                                <p class="text-slate-300 text-sm">Leadership Academy</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- ── Prev / Next arrows ── -->
    <button id="heroPrev" aria-label="Previous slide"
        class="absolute left-4 top-1/2 -translate-y-1/2 z-20 w-12 h-12 flex items-center justify-center bg-white/10 hover:bg-gold/80 border border-white/20 rounded-full backdrop-blur-sm transition-all group">
        <span class="material-symbols-outlined text-white group-hover:text-primary">chevron_left</span>
    </button>
    <button id="heroNext" aria-label="Next slide"
        class="absolute right-4 top-1/2 -translate-y-1/2 z-20 w-12 h-12 flex items-center justify-center bg-white/10 hover:bg-gold/80 border border-white/20 rounded-full backdrop-blur-sm transition-all group">
        <span class="material-symbols-outlined text-white group-hover:text-primary">chevron_right</span>
    </button>

    <!-- ── Dot indicators ── -->
    <div class="absolute bottom-8 left-1/2 -translate-x-1/2 z-20 flex gap-3" id="heroDots">
        <button class="hero-dot w-3 h-3 rounded-full bg-gold transition-all scale-125" data-index="0" aria-label="Slide 1"></button>
        <button class="hero-dot w-3 h-3 rounded-full bg-white/40 hover:bg-white/70 transition-all" data-index="1" aria-label="Slide 2"></button>
        <button class="hero-dot w-3 h-3 rounded-full bg-white/40 hover:bg-white/70 transition-all" data-index="2" aria-label="Slide 3"></button>
        <button class="hero-dot w-3 h-3 rounded-full bg-white/40 hover:bg-white/70 transition-all" data-index="3" aria-label="Slide 4"></button>
        <button class="hero-dot w-3 h-3 rounded-full bg-white/40 hover:bg-white/70 transition-all" data-index="4" aria-label="Slide 5"></button>
    </div>

    <!-- ── Progress bar ── -->
    <div class="absolute bottom-0 left-0 w-full h-1 bg-white/10 z-20">
        <div id="heroProgress" class="h-full bg-gold transition-none" style="width:0%"></div>
    </div>

</header>

<!-- Slider Script -->
<script>
(function(){
    const slides     = document.querySelectorAll('.hero-slide');
    const headlines  = document.querySelectorAll('.hero-headline');
    const dots       = document.querySelectorAll('.hero-dot');
    const progress   = document.getElementById('heroProgress');
    const DURATION   = 5000; // ms per slide
    let current      = 0;
    let timer        = null;
    let startTime    = null;
    let rafId        = null;
    const total      = slides.length;

    function goTo(idx) {
        // Hide current
        slides[current].style.opacity     = '0';
        headlines[current].style.opacity  = '0';
        headlines[current].style.transform = 'translateY(1rem)';
        dots[current].classList.remove('bg-gold','scale-125');
        dots[current].classList.add('bg-white/40');

        current = (idx + total) % total;

        // Show new
        slides[current].style.opacity     = '1';
        headlines[current].style.opacity  = '1';
        headlines[current].style.transform = 'translateY(0)';
        dots[current].classList.add('bg-gold','scale-125');
        dots[current].classList.remove('bg-white/40');

        resetTimer();
    }

    function resetTimer() {
        cancelAnimationFrame(rafId);
        clearTimeout(timer);
        progress.style.transition = 'none';
        progress.style.width      = '0%';

        startTime = performance.now();

        function tick(now) {
            const elapsed = now - startTime;
            const pct     = Math.min((elapsed / DURATION) * 100, 100);
            progress.style.transition = 'none';
            progress.style.width      = pct + '%';
            if (pct < 100) {
                rafId = requestAnimationFrame(tick);
            }
        }
        rafId = requestAnimationFrame(tick);

        timer = setTimeout(() => goTo(current + 1), DURATION);
    }

    // Arrows
    document.getElementById('heroPrev').addEventListener('click', () => goTo(current - 1));
    document.getElementById('heroNext').addEventListener('click', () => goTo(current + 1));

    // Dots
    dots.forEach(dot => {
        dot.addEventListener('click', () => goTo(parseInt(dot.dataset.index)));
    });

    // Pause on hover
    const hero = document.getElementById('heroSlider').closest('header');
    hero.addEventListener('mouseenter', () => { clearTimeout(timer); cancelAnimationFrame(rafId); });
    hero.addEventListener('mouseleave', resetTimer);

    // Touch swipe
    let touchX = 0;
    hero.addEventListener('touchstart', e => touchX = e.touches[0].clientX, {passive:true});
    hero.addEventListener('touchend',   e => {
        const diff = touchX - e.changedTouches[0].clientX;
        if (Math.abs(diff) > 50) goTo(diff > 0 ? current+1 : current-1);
    }, {passive:true});

    // Keyboard
    document.addEventListener('keydown', e => {
        if (e.key === 'ArrowLeft')  goTo(current - 1);
        if (e.key === 'ArrowRight') goTo(current + 1);
    });

    // Kick off
    resetTimer();
})();
</script>

<!-- Principal's Welcome Section -->
<section class="py-20 bg-white dark:bg-background-dark">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-16 items-center">
            <div class="relative">
                <div class="bg-gradient-to-br from-primary to-slate-700 rounded-3xl p-2 shadow-2xl">
                    <div class="bg-slate-200 dark:bg-slate-800 rounded-2xl overflow-hidden">
                        <img src="./asset/princpal.jpeg" alt="Principal" class="w-full h-auto">
                    </div>
                </div>
                <div class="absolute -bottom-6 -right-6 bg-gold text-primary px-6 py-4 rounded-2xl shadow-xl">
                    <p class="font-display font-bold text-sm uppercase tracking-wider">Visionary Leadership</p>
                </div>
            </div>
            <div class="space-y-6">
                <div class="inline-block">
                    <span class="text-gold text-sm font-semibold uppercase tracking-widest">Visionary Leadership</span>
                </div>
                <h2 class="text-4xl lg:text-5xl font-display font-black text-primary dark:text-gold leading-tight">
                    Brief Biography of Rev. Fr. Dr. Peter Okwoeze Omogo
                </h2>
                <p class="text-base text-slate-600 dark:text-slate-400 leading-relaxed">
                    Rev. Fr. Dr. Peter Okwueze Omogo is a Catholic priest, educator, humanitarian, and visionary leader from Umuogudu Oshia Ngbo in Ebonyi State, Nigeria. Ordained on July 10, 2004, he has dedicated his life to pastoral service, education, and community development.
                </p>
                <p class="text-base text-slate-600 dark:text-slate-400 leading-relaxed">
                    He studied at St. Augustine's Seminary, Ezzamgbo, Bigard Memorial Seminary, Enugu, the Pontifical Urban University in Rome, Franciscan University of Steubenville, Ohio, and Concordia University, Chicago, where he earned his Ph.D.
                </p>
                <p class="text-base text-slate-600 dark:text-slate-400 leading-relaxed">
                    Fr. Omogo is the founder of the Samuel Omogo Foundation (SOF) and Goodness Omogo Leadership Academy (GOLA), initiatives focused on education, leadership, youth empowerment, and humanitarian outreach. Through his charitable works, hundreds of boreholes have been drilled to provide clean water, while educational materials, laptops, and scholarships have been provided to students and communities in need.
                </p>
                <p class="text-base text-slate-600 dark:text-slate-400 leading-relaxed">
                    His life is guided by the belief that <span class="italic font-semibold text-primary dark:text-gold">"A life without legacy is a fruitless life."</span>
                </p>
                <div class="pt-4">
                    <p class="font-display text-2xl text-primary dark:text-gold font-bold">Rev. Fr. Dr. Peter Okwoeze Omogo</p>
                    <p class="text-slate-500 dark:text-slate-400 italic">Founder</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Statistics Section -->
<section class="py-16 bg-primary text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-8">
            <div class="text-center">
                <div class="text-6xl font-display font-black text-gold mb-2">100<span class="text-4xl">%</span></div>
                <p class="text-slate-300 uppercase tracking-wider text-sm font-semibold">WAEC Pass Rate</p>
            </div>
            <div class="text-center">
                <div class="text-6xl font-display font-black text-gold mb-2">15<span class="text-4xl">:1</span></div>
                <p class="text-slate-300 uppercase tracking-wider text-sm font-semibold">Student-Teacher Ratio</p>
            </div>
            <div class="text-center">
                <div class="text-6xl font-display font-black text-gold mb-2">20<span class="text-4xl">+</span></div>
                <p class="text-slate-300 uppercase tracking-wider text-sm font-semibold">Extracurricular Clubs</p>
            </div>
            <div class="text-center">
                <div class="text-6xl font-display font-black text-gold mb-2">500<span class="text-4xl">+</span></div>
                <p class="text-slate-300 uppercase tracking-wider text-sm font-semibold">Alumni Global Impact</p>
            </div>
        </div>
    </div>
</section>

<!-- News & Announcements Section -->
<section class="py-20 bg-slate-50 dark:bg-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-end mb-12">
            <div>
                <h2 class="text-4xl font-display font-black text-primary dark:text-gold mb-2">News & Announcements</h2>
                <p class="text-slate-600 dark:text-slate-400">Stay updated with the latest from our academy</p>
            </div>
            <a href="news.php" class="hidden md:flex items-center gap-2 text-primary dark:text-gold font-semibold hover:underline">
                View All News
                <span class="material-symbols-outlined text-sm">arrow_forward</span>
            </a>
        </div>

        <div class="grid md:grid-cols-3 gap-8">
            <?php 
            if ($news_result && $news_result->num_rows > 0):
                $gradients = [
                    'Academic'     => 'from-primary to-slate-700',
                    'Sports'       => 'from-green-500 to-green-700',
                    'Events'       => 'from-purple-500 to-purple-700',
                    'Facilities'   => 'from-gold to-yellow-700',
                    'Achievements' => 'from-red-500 to-red-700',
                    'General'      => 'from-slate-500 to-slate-700'
                ];
                while($article = $news_result->fetch_assoc()):
                    $gradient = $gradients[$article['category']] ?? 'from-primary to-slate-700';
            ?>
            <article class="bg-white dark:bg-slate-800 rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-shadow">
                <div class="h-56 bg-gradient-to-br <?php echo $gradient; ?> relative overflow-hidden">
                    <?php if (!empty($article['featured_image'])): ?>
                    <img src="assets/images/news/<?php echo htmlspecialchars($article['featured_image']); ?>"
                         alt="<?php echo htmlspecialchars($article['title']); ?>"
                         class="w-full h-full object-cover">
                    <?php endif; ?>
                </div>
                <div class="p-6">
                    <div class="text-xs text-gold font-semibold uppercase tracking-wider mb-2">
                        <?php echo htmlspecialchars($article['category']); ?> • <?php echo date('M d, Y', strtotime($article['published_date'])); ?>
                    </div>
                    <h3 class="text-xl font-bold text-primary dark:text-gold mb-3 line-clamp-2">
                        <?php echo htmlspecialchars($article['title']); ?>
                    </h3>
                    <p class="text-slate-600 dark:text-slate-400 text-sm mb-4 line-clamp-3">
                        <?php echo htmlspecialchars($article['excerpt']); ?>
                    </p>
                    <a href="news-detail.php?slug=<?php echo urlencode($article['slug']); ?>"
                       class="text-primary dark:text-gold font-semibold hover:underline flex items-center gap-1">
                        Read More
                        <span class="material-symbols-outlined text-sm">arrow_forward</span>
                    </a>
                </div>
            </article>
            <?php endwhile;
            else: ?>
            <div class="col-span-3 text-center py-12">
                <p class="text-slate-500 dark:text-slate-400">No news articles available at the moment.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Upcoming Events Section -->
<section class="py-20 bg-white dark:bg-background-dark">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-3 gap-12">
            <div class="lg:col-span-1">
                <h2 class="text-4xl font-display font-black text-primary dark:text-gold mb-4">Upcoming Events</h2>
                <p class="text-slate-600 dark:text-slate-400 mb-6">Mark your calendar for these exciting events</p>
                <a href="academics.php#events" class="inline-flex items-center gap-2 bg-primary text-white px-6 py-3 rounded-lg hover:bg-opacity-90 transition-all">
                    Full Calendar
                    <span class="material-symbols-outlined text-sm">calendar_month</span>
                </a>
            </div>
            <div class="lg:col-span-2 space-y-4">
                <?php if ($events_result && $events_result->num_rows > 0):
                    $index = 0;
                    while($event = $events_result->fetch_assoc()):
                        $is_primary = $index === 0;
                ?>
                <a href="event-detail.php?id=<?php echo $event['id']; ?>"
                   class="block <?php echo $is_primary ? 'bg-primary text-white' : 'bg-slate-100 dark:bg-slate-800'; ?> rounded-2xl p-6 flex gap-6 items-start hover:shadow-xl transition-all">
                    <div class="text-center <?php echo $is_primary ? 'bg-gold text-primary' : 'bg-primary text-white'; ?> rounded-xl p-4 flex-shrink-0">
                        <div class="text-3xl font-display font-black"><?php echo date('d', strtotime($event['event_date'])); ?></div>
                        <div class="text-xs uppercase font-semibold"><?php echo date('M', strtotime($event['event_date'])); ?></div>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-xl font-bold mb-2"><?php echo htmlspecialchars($event['title']); ?></h3>
                        <?php if ($event['event_time']): ?>
                        <p class="<?php echo $is_primary ? 'text-slate-300' : 'text-slate-600 dark:text-slate-400'; ?> text-sm mb-2">
                            <span class="material-symbols-outlined text-gold inline text-xs align-middle">schedule</span>
                            <?php echo htmlspecialchars($event['event_time']); ?>
                        </p>
                        <?php endif; ?>
                        <p class="<?php echo $is_primary ? 'text-slate-300' : 'text-slate-600 dark:text-slate-400'; ?> text-sm">
                            <?php echo htmlspecialchars($event['description']); ?>
                        </p>
                    </div>
                </a>
                <?php $index++; endwhile;
                else: ?>
                <div class="bg-slate-100 dark:bg-slate-800 rounded-2xl p-12 text-center">
                    <span class="material-symbols-outlined text-6xl text-slate-400 mb-4 block">event</span>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">No Upcoming Events</h3>
                    <p class="text-slate-600 dark:text-slate-400">Check back soon for exciting school events!</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action Section -->
<section class="py-20 bg-gradient-to-r from-primary to-slate-800 text-white" style="background: linear-gradient(rgba(0, 44, 71, 0.8), rgba(0, 44, 71, 0.8)), url(./asset/school/image1.jpeg) center/cover no-repeat;">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-4xl lg:text-5xl font-display font-black mb-6">Ready to Start Your Leadership Journey?</h2>
        <p class="text-xl text-slate-300 mb-10 max-w-3xl mx-auto">
            Join Nigeria's premier leadership school and become part of a community dedicated to excellence, character, and service.
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="admissions.php" class="bg-gold text-primary font-bold px-10 py-4 rounded-xl hover:scale-105 transition-transform shadow-lg flex items-center justify-center gap-2">
                Request a Prospectus
                <span class="material-symbols-outlined">arrow_forward</span>
            </a>
            <a href="contact.php" class="bg-white/10 hover:bg-white/20 backdrop-blur-sm border border-white/20 font-bold px-10 py-4 rounded-xl transition-all flex items-center justify-center gap-2">
                Book a Campus Tour
                <span class="material-symbols-outlined">event</span>
            </a>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>