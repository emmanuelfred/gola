<?php
require_once 'auth_check.php';
$page_title = "Access Restricted";
$feature = $_GET['feature'] ?? '';
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $page_title; ?> | G.O.L.A Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
<script src="https://cdn.tailwindcss.com?plugins=forms"></script>
<script>tailwind.config={theme:{extend:{colors:{primary:"#0A2E4D",gold:"#C5A059"},fontFamily:{sans:["Inter","sans-serif"]}}}};</script>
<style>.sidebar-link.active{background:linear-gradient(90deg,rgba(197,160,89,.1) 0%,transparent 100%);border-left:3px solid #C5A059;color:#C5A059;}</style>
</head>
<body class="bg-slate-50 font-sans">
<div class="flex h-screen overflow-hidden">
<?php include 'admin_sidebar.php'; ?>
<div class="flex-1 flex flex-col overflow-hidden">
<?php include 'admin_topbar.php'; ?>
<main class="flex-1 overflow-y-auto p-6 lg:p-8 flex items-center justify-center">

<div class="bg-white rounded-2xl border border-slate-200 p-16 text-center max-w-lg">
    <span class="material-symbols-outlined text-6xl text-slate-200 block mb-3">lock</span>
    <h3 class="font-bold text-slate-800 text-lg mb-2">Access Restricted</h3>
    <p class="text-sm text-slate-500 mb-6">Your role doesn't have permission to view this page<?php echo $feature ? ' (<code class="bg-slate-100 px-1.5 py-0.5 rounded text-xs">'.htmlspecialchars($feature).'</code>)' : ''; ?>. If you believe this is a mistake, ask an administrator to check your role's permissions in Manage Staff Roles.</p>
    <a href="dashboard.php" class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary text-white text-sm font-semibold rounded-lg hover:bg-primary/90">
        <span class="material-symbols-outlined text-sm">arrow_back</span>Back to Overview
    </a>
</div>

</main>
</div>
</div>
</body>
</html>
