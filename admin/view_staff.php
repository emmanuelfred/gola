<?php
require_once 'auth_check.php';
require_once 'includes/permission_helper.php';
requirePermission('staff');
$page_title = "View Staff";

$id = intval($_GET['id'] ?? 0);
if (!$id) { header('Location: manage_staff.php'); exit; }

$stmt = $conn->prepare("SELECT st.*, r.role_name, r.requires_login, r.system_access, au.username, au.is_active as login_active, au.last_login
    FROM staff st
    JOIN staff_roles r ON st.role_id = r.id
    LEFT JOIN admin_users au ON st.admin_user_id = au.id
    WHERE st.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$s = $stmt->get_result()->fetch_assoc();
if (!$s) { header('Location: manage_staff.php'); exit; }

$status_colors = ['Active'=>'bg-green-100 text-green-700','On Leave'=>'bg-amber-100 text-amber-700','Suspended'=>'bg-orange-100 text-orange-700','Terminated'=>'bg-red-100 text-red-600'];
$sc = $status_colors[$s['status']] ?? 'bg-slate-100 text-slate-700';
?>
<!DOCTYPE html>
<html class="scroll-smooth" lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> | G.O.L.A</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <script>tailwind.config={theme:{extend:{colors:{primary:"#0A2E4D",gold:"#C5A059"},fontFamily:{sans:["Inter","sans-serif"]}}}};</script>
    <style>.sidebar-link.active{background:linear-gradient(90deg,rgba(197,160,89,0.1) 0%,transparent 100%);border-left:3px solid #C5A059;color:#C5A059;}.info-label{font-size:0.65rem;text-transform:uppercase;letter-spacing:0.05em;font-weight:600;color:#94a3b8;}.info-val{font-size:0.875rem;font-weight:500;color:#1e293b;margin-top:2px;}</style>
</head>
<body class="bg-slate-50 font-sans">
<div class="flex h-screen overflow-hidden">
    <?php include 'admin_sidebar.php'; ?>
    <div class="flex-1 flex flex-col overflow-hidden">
        <?php include 'admin_topbar.php'; ?>
        <main class="flex-1 overflow-y-auto p-8">

            <div class="flex items-center gap-4 mb-8">
                <a href="manage_staff.php" class="p-2 hover:bg-slate-100 rounded-lg"><span class="material-symbols-outlined">arrow_back</span></a>
                <div class="flex-1">
                    <h1 class="text-2xl font-bold text-slate-900">Staff Profile</h1>
                    <p class="text-slate-500 text-sm"><?php echo htmlspecialchars($s['staff_id']); ?></p>
                </div>
                <a href="edit_staff.php?id=<?php echo $s['id']; ?>" class="inline-flex items-center gap-2 px-5 py-2.5 bg-gold text-primary rounded-xl font-bold text-sm hover:bg-gold/90 shadow-sm">
                    <span class="material-symbols-outlined text-sm">edit</span>Edit
                </a>
            </div>

            <div class="grid lg:grid-cols-3 gap-6">
                <!-- Left: Photo + Quick Info -->
                <div class="lg:col-span-1 space-y-6">
                    <div class="bg-white rounded-xl border border-slate-200 p-6 text-center">
                        <?php if ($s['passport_photo']): ?>
                        <img src="../<?php echo htmlspecialchars($s['passport_photo']); ?>" class="w-28 h-28 rounded-full object-cover border-4 border-gold/20 mx-auto">
                        <?php else: ?>
                        <div class="w-28 h-28 rounded-full bg-slate-200 flex items-center justify-center text-slate-500 text-2xl font-bold mx-auto"><?php echo strtoupper(substr($s['first_name'],0,1).substr($s['last_name'],0,1)); ?></div>
                        <?php endif; ?>
                        <h2 class="font-bold text-lg text-slate-900 mt-4"><?php echo htmlspecialchars($s['first_name'].' '.$s['middle_name'].' '.$s['last_name']); ?></h2>
                        <p class="text-sm text-gold font-semibold"><?php echo htmlspecialchars($s['role_name']); ?></p>
                        <span class="inline-block mt-3 px-3 py-1 <?php echo $sc; ?> text-xs font-semibold rounded-full"><?php echo $s['status']; ?></span>
                    </div>

                    <div class="bg-white rounded-xl border border-slate-200 p-6">
                        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-4">Login Account</h3>
                        <?php if ($s['username']): ?>
                        <div class="space-y-3">
                            <div><p class="info-label">Username</p><p class="info-val"><?php echo htmlspecialchars($s['username']); ?></p></div>
                            <div><p class="info-label">Access Tier</p><p class="info-val capitalize"><?php echo str_replace('_',' ',$s['system_access']); ?></p></div>
                            <div><p class="info-label">Account Status</p><p class="info-val"><?php echo $s['login_active'] ? 'Active' : 'Deactivated'; ?></p></div>
                            <div><p class="info-label">Last Login</p><p class="info-val"><?php echo $s['last_login'] ? date('d M Y, g:i a', strtotime($s['last_login'])) : 'Never logged in'; ?></p></div>
                        </div>
                        <?php elseif ($s['requires_login']): ?>
                        <p class="text-sm text-amber-600 flex items-center gap-2"><span class="material-symbols-outlined text-sm">warning</span>This role needs a login, but none is linked yet. Edit this staff member to add one.</p>
                        <?php else: ?>
                        <p class="text-sm text-slate-400">This role doesn't require a system login.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Right: Details -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white rounded-xl border border-slate-200 p-6">
                        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-4">Personal Information</h3>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-5">
                            <div><p class="info-label">Gender</p><p class="info-val"><?php echo htmlspecialchars($s['gender']); ?></p></div>
                            <div><p class="info-label">Date of Birth</p><p class="info-val"><?php echo $s['date_of_birth'] ? date('d M Y', strtotime($s['date_of_birth'])) : '—'; ?></p></div>
                            <div><p class="info-label">Phone</p><p class="info-val"><?php echo htmlspecialchars($s['phone'] ?: '—'); ?></p></div>
                            <div><p class="info-label">Email</p><p class="info-val"><?php echo htmlspecialchars($s['email'] ?: '—'); ?></p></div>
                            <div class="col-span-2"><p class="info-label">Address</p><p class="info-val"><?php echo htmlspecialchars($s['address'] ?: '—'); ?></p></div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl border border-slate-200 p-6">
                        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-4">Employment</h3>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-5">
                            <div><p class="info-label">Staff ID</p><p class="info-val"><?php echo htmlspecialchars($s['staff_id']); ?></p></div>
                            <div><p class="info-label">Role</p><p class="info-val"><?php echo htmlspecialchars($s['role_name']); ?></p></div>
                            <div><p class="info-label">Date Employed</p><p class="info-val"><?php echo $s['date_employed'] ? date('d M Y', strtotime($s['date_employed'])) : '—'; ?></p></div>
                            <div><p class="info-label">Salary</p><p class="info-val">&#8358;<?php echo number_format($s['salary'], 2); ?></p></div>
                            <div><p class="info-label">Status</p><p class="info-val"><?php echo $s['status']; ?></p></div>
                        </div>
                        <?php if ($s['notes']): ?>
                        <div class="mt-5 pt-5 border-t border-slate-100">
                            <p class="info-label">Notes</p><p class="info-val font-normal"><?php echo nl2br(htmlspecialchars($s['notes'])); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>
</body>
</html>
