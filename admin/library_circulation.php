<?php
require_once 'auth_check.php';
require_once 'includes/permission_helper.php';
requirePermission('library');
require_once 'includes/library_helper.php';
$page_title = "Library Circulation";

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'lend') {
        $book_id = intval($_POST['book_id'] ?? 0);
        $code    = trim($_POST['borrower_code'] ?? '');
        $days    = max(1, intval($_POST['loan_days'] ?? LIBRARY_LOAN_DAYS));
        $due_date = date('Y-m-d', strtotime("+$days days"));

        $borrower = $code ? findBorrowerByCode($conn, $code) : null;
        if (!$book_id) {
            $error = 'Select a book.';
        } elseif (!$borrower) {
            $error = "No active student or staff found with code \"" . htmlspecialchars($code) . "\".";
        } else {
            $result = borrowBook($conn, $book_id, $borrower['type'], $borrower['id'], $due_date, $admin_id);
            if ($result['ok']) {
                $book = getBook($conn, $book_id);
                logActivity('lend_book', "Lent \"{$book['title']}\" to {$borrower['name']} ({$borrower['code']})");
                $success = "<strong>" . htmlspecialchars($book['title']) . "</strong> lent to <strong>" . htmlspecialchars($borrower['name']) . "</strong>, due back " . date('d M Y', strtotime($due_date)) . ".";
            } else {
                $error = $result['error'];
            }
        }
    }

    if ($_POST['action'] === 'return') {
        $loan_id = intval($_POST['loan_id'] ?? 0);
        $status  = in_array($_POST['status'] ?? '', ['Returned','Lost']) ? $_POST['status'] : 'Returned';
        $result = returnBook($conn, $loan_id, $admin_id, $status);
        if ($result['ok']) {
            logActivity('return_book', "Marked loan #$loan_id as $status");
            $success = $status === 'Lost' ? "Book marked as lost." : "Book marked as returned.";
        } else {
            $error = $result['error'];
        }
    }
}

$search = trim($_GET['search'] ?? '');
$books  = getAllBooks($conn, $search);
$active_loans = getActiveLoans($conn);
$overdue_count = count(array_filter($active_loans, fn($l) => $l['is_overdue']));
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
<main class="flex-1 overflow-y-auto p-6 lg:p-8">

<div class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Library Circulation</h1>
        <p class="text-slate-500 text-sm mt-1">Lend and return books. Manage the collection itself in <a href="manage_library_books.php" class="text-primary font-semibold hover:underline">Catalog</a>.</p>
    </div>
</div>

<?php if ($success): ?>
<div class="mb-5 p-4 bg-green-50 border border-green-200 rounded-xl flex gap-3 items-start">
    <span class="material-symbols-outlined text-green-600 flex-shrink-0">check_circle</span>
    <p class="text-green-800 text-sm"><?php echo $success; ?></p>
</div>
<?php endif; ?>
<?php if ($error): ?>
<div class="mb-5 p-4 bg-red-50 border border-red-200 rounded-xl flex gap-3 items-start">
    <span class="material-symbols-outlined text-red-600 flex-shrink-0">error</span>
    <p class="text-red-800 text-sm"><?php echo htmlspecialchars($error); ?></p>
</div>
<?php endif; ?>

<div class="grid grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <p class="text-xs font-semibold text-slate-500 uppercase">Books Out</p>
        <p class="text-2xl font-bold text-primary mt-1"><?php echo count($active_loans); ?></p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <p class="text-xs font-semibold text-slate-500 uppercase">Overdue</p>
        <p class="text-2xl font-bold <?php echo $overdue_count>0?'text-red-600':'text-slate-300'; ?> mt-1"><?php echo $overdue_count; ?></p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <p class="text-xs font-semibold text-slate-500 uppercase">Default Loan Period</p>
        <p class="text-2xl font-bold text-slate-700 mt-1"><?php echo LIBRARY_LOAN_DAYS; ?> days</p>
    </div>
</div>

<div class="grid lg:grid-cols-2 gap-6">

    <!-- Lend a book -->
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <h4 class="font-semibold text-slate-800 text-sm mb-3">
            <span class="material-symbols-outlined text-sm align-middle text-gold mr-1">book</span>
            Lend a Book
        </h4>
        <form method="POST" class="space-y-3">
            <input type="hidden" name="action" value="lend">
            <div>
                <label class="text-xs font-semibold text-slate-500 mb-1 block">Book</label>
                <select name="book_id" required class="w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold">
                    <option value="">Select a book…</option>
                    <?php foreach ($books as $b): if ($b['available_copies'] < 1) continue; ?>
                    <option value="<?php echo $b['id']; ?>"><?php echo htmlspecialchars($b['title']); ?> (<?php echo $b['available_copies']; ?> available)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="text-xs font-semibold text-slate-500 mb-1 block">Borrower — Reg No or Staff ID</label>
                <input type="text" name="borrower_code" required placeholder="e.g. GOLA/2026/JSS1A/003 or GOLA/STAFF/2026/0001"
                    class="w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold">
            </div>
            <div>
                <label class="text-xs font-semibold text-slate-500 mb-1 block">Loan Period (days)</label>
                <input type="number" name="loan_days" min="1" value="<?php echo LIBRARY_LOAN_DAYS; ?>" class="w-32 border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold">
            </div>
            <button type="submit" class="w-full px-5 py-2.5 bg-gold text-primary text-sm font-bold rounded-lg hover:bg-gold/90 transition-all">
                Lend Book
            </button>
        </form>
    </div>

    <!-- Quick catalog search -->
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <h4 class="font-semibold text-slate-800 text-sm mb-3">
            <span class="material-symbols-outlined text-sm align-middle text-gold mr-1">search</span>
            Check Availability
        </h4>
        <form method="GET" class="mb-3">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by title, author, or ISBN…"
                class="w-full border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold">
        </form>
        <div class="space-y-1.5 max-h-48 overflow-y-auto">
            <?php foreach (array_slice($books, 0, 20) as $b): ?>
            <div class="flex items-center justify-between text-xs">
                <span class="text-slate-700 truncate"><?php echo htmlspecialchars($b['title']); ?></span>
                <span class="<?php echo $b['available_copies']>0?'text-green-600':'text-red-500'; ?> font-semibold flex-shrink-0 ml-2">
                    <?php echo $b['available_copies']; ?>/<?php echo $b['total_copies']; ?>
                </span>
            </div>
            <?php endforeach; ?>
            <?php if (empty($books)): ?><p class="text-xs text-slate-400">No books found.</p><?php endif; ?>
        </div>
    </div>
</div>

<!-- Active loans -->
<div class="mt-6 bg-white rounded-xl border border-slate-200 overflow-hidden">
    <div class="px-5 py-3 border-b border-slate-100 bg-slate-50">
        <h4 class="font-semibold text-slate-800 text-sm">Books Currently Out (<?php echo count($active_loans); ?>)</h4>
    </div>
    <?php if (empty($active_loans)): ?>
    <div class="px-5 py-10 text-center text-slate-400 text-sm">No books currently on loan.</div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b text-xs">
                <tr>
                    <th class="px-4 py-2.5 text-left font-semibold text-slate-500 uppercase">Book</th>
                    <th class="px-4 py-2.5 text-left font-semibold text-slate-500 uppercase">Borrower</th>
                    <th class="px-4 py-2.5 text-center font-semibold text-slate-500 uppercase">Borrowed</th>
                    <th class="px-4 py-2.5 text-center font-semibold text-slate-500 uppercase">Due</th>
                    <th class="px-4 py-2.5 text-center font-semibold text-slate-500 uppercase">Status</th>
                    <th class="px-4 py-2.5 text-center font-semibold text-slate-500 uppercase">Return</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach ($active_loans as $l): ?>
                <tr class="hover:bg-slate-50 <?php echo $l['is_overdue'] ? 'bg-red-50/40' : ''; ?>">
                    <td class="px-4 py-2.5 font-semibold text-slate-800"><?php echo htmlspecialchars($l['title']); ?></td>
                    <td class="px-4 py-2.5 text-slate-600">
                        <?php echo htmlspecialchars($l['borrower_name']); ?>
                        <span class="text-xs text-slate-400 block font-mono"><?php echo htmlspecialchars($l['borrower_code']); ?> · <?php echo $l['borrower_type']; ?></span>
                    </td>
                    <td class="px-4 py-2.5 text-center text-slate-500 text-xs"><?php echo date('d M', strtotime($l['borrowed_date'])); ?></td>
                    <td class="px-4 py-2.5 text-center text-xs <?php echo $l['is_overdue'] ? 'text-red-600 font-bold' : 'text-slate-500'; ?>"><?php echo date('d M Y', strtotime($l['due_date'])); ?></td>
                    <td class="px-4 py-2.5 text-center">
                        <?php if ($l['is_overdue']): ?>
                        <span class="px-2 py-0.5 bg-red-100 text-red-600 text-xs font-semibold rounded-full"><?php echo $l['days_overdue']; ?>d overdue</span>
                        <?php else: ?>
                        <span class="px-2 py-0.5 bg-green-100 text-green-700 text-xs font-semibold rounded-full">On time</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-2.5 text-center">
                        <div class="flex items-center justify-center gap-1.5">
                            <form method="POST">
                                <input type="hidden" name="action"  value="return">
                                <input type="hidden" name="loan_id" value="<?php echo $l['id']; ?>">
                                <input type="hidden" name="status"  value="Returned">
                                <button type="submit" class="px-2.5 py-1 bg-primary/10 hover:bg-primary/20 text-primary text-xs font-semibold rounded-lg">Returned</button>
                            </form>
                            <form method="POST" onsubmit="return confirm('Mark this book as lost? It will not be added back to available copies.')">
                                <input type="hidden" name="action"  value="return">
                                <input type="hidden" name="loan_id" value="<?php echo $l['id']; ?>">
                                <input type="hidden" name="status"  value="Lost">
                                <button type="submit" class="px-2.5 py-1 bg-red-50 hover:bg-red-100 text-red-500 text-xs font-semibold rounded-lg">Lost</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

</main>
</div>
</div>
</body>
</html>
