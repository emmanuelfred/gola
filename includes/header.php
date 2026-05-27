<?php
// Detect the base path based on current directory
$current_dir = dirname($_SERVER['PHP_SELF']);
if (strpos($current_dir, 'result-checker') !== false || strpos($current_dir, 'admin') !== false) {
    $base_path = '../';
} else {
    $base_path = '';
}
?>
<!DOCTYPE html>
<html class="scroll-smooth" lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' | ' : ''; ?>Goodness Omogo Leadership Academy</title>
    <link rel="icon" type="image/png" href="<?php echo $base_path; ?>asset/favicon.png">
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        primary: "#002C47",
                        gold: "#C5A059",
                        "background-light": "#FDFCFB",
                        "background-dark": "#0A1924",
                    },
                    fontFamily: {
                        display: ["Playfair Display", "serif"],
                        sans: ["Inter", "sans-serif"],
                    },
                },
            },
        };
    </script>
    
    <style>
        .crest-hover { transition: transform 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        .crest-hover:hover { transform: scale(1.05); }
        .text-balance { text-wrap: balance; }
        .mobile-menu { display: none; }
        .mobile-menu.active { display: block; }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark text-slate-900 dark:text-slate-100 font-sans">

<!-- Navigation Bar -->
<nav class="sticky top-0 z-50 bg-white/90 dark:bg-background-dark/90 backdrop-blur-md border-b border-slate-200 dark:border-slate-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-20">
            <!-- Logo and School Name -->
            <a href="<?php echo $base_path; ?>index.php" class="flex items-center gap-3 hover:opacity-90 transition-opacity">
                <img src="<?php echo $base_path; ?>asset/favicon.png"
                     alt="Academy Logo" 
                     class="h-14 w-auto">
                <div class="hidden md:block">
                    <span class="block text-primary dark:text-gold font-display font-bold text-xl leading-none">GOODNESS OMOGO</span>
                    <span class="block text-slate-500 dark:text-slate-400 text-xs tracking-widest uppercase mt-1">Leadership Academy</span>
                </div>
            </a>
            
            <!-- Desktop Navigation Links -->
            <div class="hidden lg:flex items-center space-x-8 font-medium text-sm tracking-wide uppercase">
                <a href="<?php echo $base_path; ?>index.php" class="hover:text-gold transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'text-gold' : ''; ?>">Home</a>
                <a href="<?php echo $base_path; ?>about.php" class="hover:text-gold transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'about.php' ? 'text-gold' : ''; ?>">About Us</a>
                <a href="<?php echo $base_path; ?>academics.php" class="hover:text-gold transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'academics.php' ? 'text-gold' : ''; ?>">Academics</a>
                <a href="<?php echo $base_path; ?>admissions.php" class="hover:text-gold transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'admissions.php' ? 'text-gold' : ''; ?>">Admissions</a>
                <a href="<?php echo $base_path; ?>news.php" class="hover:text-gold transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'news.php' ? 'text-gold' : ''; ?>">News</a>
                <a href="<?php echo $base_path; ?>contact.php" class="hover:text-gold transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'contact.php' ? 'text-gold' : ''; ?>">Contact Us</a>
                
                <a href="<?php echo $base_path; ?>result-checker/" class="bg-primary text-white px-5 py-2.5 rounded hover:bg-opacity-90 transition-all flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">login</span>
                      Result Checker
                </a>
            </div>
            
            <!-- Mobile Menu Button -->
            <button id="mobile-menu-btn" class="lg:hidden p-2 text-slate-700 dark:text-slate-300 hover:text-gold transition-colors">
                <span class="material-symbols-outlined text-3xl">menu</span>
            </button>
        </div>
        
        <!-- Mobile Menu -->
        <div id="mobile-menu" class="mobile-menu lg:hidden pb-4">
            <div class="flex flex-col space-y-3 text-sm font-medium uppercase">
                <a href="<?php echo $base_path; ?>index.php" class="py-2 hover:text-gold transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'text-gold' : ''; ?>">Home</a>
                <a href="<?php echo $base_path; ?>about.php" class="py-2 hover:text-gold transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'about.php' ? 'text-gold' : ''; ?>">About Us</a>
                <a href="<?php echo $base_path; ?>academics.php" class="py-2 hover:text-gold transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'academics.php' ? 'text-gold' : ''; ?>">Academics</a>
                <a href="<?php echo $base_path; ?>admissions.php" class="py-2 hover:text-gold transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'admissions.php' ? 'text-gold' : ''; ?>">Admissions</a>
                <a href="<?php echo $base_path; ?>news.php" class="py-2 hover:text-gold transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'news.php' ? 'text-gold' : ''; ?>">News</a>
                <a href="<?php echo $base_path; ?>contact.php" class="py-2 hover:text-gold transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'contact.php' ? 'text-gold' : ''; ?>">Contact Us</a>
                
                <a  href="<?php echo $base_path; ?>result-checker/" class="bg-primary text-white px-5 py-3 rounded hover:bg-opacity-90 transition-all flex items-center justify-center gap-2 mt-2">
                    <span class="material-symbols-outlined text-sm">login</span>
                    Result Checker
                </a>
                <!--<a href="<?php //echo $base_path; ?>admin/login.php" class="bg-primary text-white px-5 py-3 rounded hover:bg-opacity-90 transition-all flex items-center justify-center gap-2 mt-2">
                    <span class="material-symbols-outlined text-sm">login</span>
                    School Portals
                </a>-->
            </div>
        </div>
    </div>
</nav>

<script>
    // Mobile menu toggle
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');
    
    mobileMenuBtn.addEventListener('click', () => {
        mobileMenu.classList.toggle('active');
    });
</script>