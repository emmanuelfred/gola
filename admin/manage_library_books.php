<?php
require_once 'auth_check.php';
require_once 'includes/permission_helper.php';
requirePermission('library');
require_once 'includes/library_helper.php';
$page_title = "Library Catalog";

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'add_book') {
        $title  = trim($_POST['title'] ?? '');
        $author = trim($_POST['author'] ?? '');
        $isbn   = trim($_POST['isbn'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $shelf  = trim($_POST['shelf_location'] ?? '');
        $copies = max(1, intval($_POST['total_copies'] ?? 1));

        if (!$title) {
            $error = 'Title is required.';
        } else {
            $stmt = $conn->prepare("INSERT INTO library_books (title, author, isbn, category, shelf_location, total_copies, available_copies) VALUES (?,?,?,?,?,?,?)");
            $stmt->bind_param("sssssii", $title, $author, $isbn, $category, $shelf, $copies, $copies);
            if ($stmt->execute()) {
                logActivity('add_book', "Added library book: $title ($copies copies)");
                $success = "<strong>" . htmlspecialchars($title) . "</strong> added to the catalog.";
            } else {
                $error = 'Failed to add book: ' . $conn->error;
            }
        }
    }

    if ($_POST['action'] === 'edit_book') {
        $id     = intval($_POST['book_id'] ?? 0);
        $title  = trim($_POST['title'] ?? '');
        $author = trim($_POST['author'] ?? '');
        $isbn   = trim($_POST['isbn'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $shelf  = trim($_POST['shelf_location'] ?? '');
        $new_total = max(1, intval($_POST['total_copies'] ?? 1));

        $book = getBook($conn, $id);
        if (!$book) {
            $error = 'Book not found.';
        } elseif (!$title) {
            $error = 'Title is required.';
        } else {
            // Adjust available_copies by the same delta as total_copies changed,
            // so copies currently on loan aren't silently lost from the count.
            $delta = $new_total - $book['total_copies'];
            $new_available = max(0, $book['available_copies'] + $delta);

            $stmt = $conn->prepare("UPDATE library_books SET title=?, author=?, isbn=?, category=?, shelf_location=?, total_copies=?, available_copies=? WHERE id=?");
            $stmt->bind_param("sssssiii", $title, $author, $isbn, $category, $shelf, $new_total, $new_available, $id);
            $stmt->execute();
            $success = "Book updated.";
        }
    }

    if ($_POST['action'] === 'delete_book' && hasPermission('admin')) {
        $id = intval($_POST['book_id'] ?? 0);
        $active = $conn->query("SELECT COUNT(*) as c FROM library_loans WHERE book_id=$id AND returned_date IS NULL")->fetch_assoc()['c'];
        if ($active > 0) {
            $error = "Cannot remove — $active cop(y/ies) currently on loan.";
        } else {
            $conn->query("UPDATE library_books SET is_active=0 WHERE id=$id");
            $success = "Book removed from catalog.";
        }
    }
}

$search = trim($_GET['search'] ?? '');
$books  = getAllBooks($conn, $search);
$total_books  = array_sum(array_column($books, 'total_copies'));
$available_books = array_sum(array_column($books, 'available_copies'));
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
        <div class="flex items-center gap-2 text-xs text-slate-500 mb-2">
            <a href="library_circulation.php" class="hover:text-gold">Library</a>
            <span class="material-symbols-outlined text-xs">chevron_right</span>
            <span class="text-slate-800">Catalog</span>
        </div>
        <h1 class="text-2xl font-bold text-slate-900">Library Catalog</h1>
        <p class="text-slate-500 text-sm mt-1">Manage the book collection. To lend or return a book, go to <a href="library_circulation.php" class="text-primary font-semibold hover:underline">Circulation</a>.</p>
    </div>
    <button onclick="document.getElementById('addModal').classList.remove('hidden')"
        class="inline-flex items-center gap-2 bg-gold text-primary px-5 py-3 rounded-xl font-bold hover:bg-gold/90 shadow-sm flex-shrink-0">
        <span class="material-symbols-outlined">add_circle</span>New Book
    </button>
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

<div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <p class="text-xs font-semibold text-slate-500 uppercase">Titles</p>
        <p class="text-2xl font-bold text-slate-900 mt-1"><?php echo count($books); ?></p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <p class="text-xs font-semibold text-slate-500 uppercase">Total Copies</p>
        <p class="text-2xl font-bold text-primary mt-1"><?php echo $total_books; ?></p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <p class="text-xs font-semibold text-slate-500 uppercase">Available Now</p>
        <p class="text-2xl font-bold text-green-600 mt-1"><?php echo $available_books; ?></p>
    </div>
</div>

<form method="GET" class="mb-5">
    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by title, author, or ISBN…"
        class="w-full max-w-md border-slate-200 rounded-lg text-sm focus:ring-gold focus:border-gold">
</form>

<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 border-b border-slate-200">
            <tr class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                <th class="px-5 py-3">Title</th>
                <th class="px-5 py-3">Author</th>
                <th class="px-5 py-3">Category</th>
                <th class="px-5 py-3">Shelf</th>
                <th class="px-5 py-3 text-center">Copies</th>
                <th class="px-5 py-3 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            <?php if (empty($books)): ?>
            <tr><td colspan="6" class="px-5 py-12 text-center text-slate-400">No books found.</td></tr>
            <?php endif; ?>
            <?php foreach ($books as $b): ?>
            <tr class="hover:bg-slate-50">
                <td class="px-5 py-3 font-semibold text-slate-800"><?php echo htmlspecialchars($b['title']); ?></td>
                <td class="px-5 py-3 text-slate-600"><?php echo htmlspecialchars($b['author'] ?: '—'); ?></td>
                <td class="px-5 py-3 text-slate-500"><?php echo htmlspecialchars($b['category'] ?: '—'); ?></td>
                <td class="px-5 py-3 text-slate-500 font-mono text-xs"><?php echo htmlspecialchars($b['shelf_location'] ?: '—'); ?></td>
                <td class="px-5 py-3 text-center">
                    <span class="px-2 py-0.5 <?php echo $b['available_copies']>0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600'; ?> text-xs font-semibold rounded-full">
                        <?php echo $b['available_copies']; ?> / <?php echo $b['total_copies']; ?>
                    </span>
                </td>
                <td class="px-5 py-3 text-right">
                    <button onclick='openEditModal(<?php echo json_encode($b); ?>)' class="text-primary hover:underline text-xs font-semibold mr-3">Edit</button>
                    <?php if (hasPermission('admin')): ?>
                    <form method="POST" class="inline" onsubmit="return confirm('Remove this book from the catalog?')">
                        <input type="hidden" name="action"  value="delete_book">
                        <input type="hidden" name="book_id" value="<?php echo $b['id']; ?>">
                        <button type="submit" class="text-red-500 hover:underline text-xs font-semibold">Remove</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

</main>
</div>
</div>

<!-- Add Book Modal -->
<div id="addModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
<div class="bg-white rounded-2xl p-8 max-w-md w-full shadow-2xl">
    <h2 class="text-xl font-bold text-slate-900 mb-5 flex items-center gap-2"><span class="material-symbols-outlined text-gold">add_circle</span>Add Book</h2>
    <form method="POST" class="space-y-4">
        <input type="hidden" name="action" value="add_book">
        <div><label class="text-xs font-semibold text-slate-600 mb-1 block">Title <span class="text-red-500">*</span></label>
            <input type="text" name="title" required class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold"></div>
        <div class="grid grid-cols-2 gap-3">
            <div><label class="text-xs font-semibold text-slate-600 mb-1 block">Author</label>
                <input type="text" name="author" class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold"></div>
            <div><label class="text-xs font-semibold text-slate-600 mb-1 block">ISBN</label>
                <input type="text" name="isbn" class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold"></div>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div><label class="text-xs font-semibold text-slate-600 mb-1 block">Category</label>
                <input type="text" name="category" placeholder="e.g. Fiction, Textbook" class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold"></div>
            <div><label class="text-xs font-semibold text-slate-600 mb-1 block">Shelf</label>
                <input type="text" name="shelf_location" placeholder="e.g. A-12" class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold"></div>
        </div>
        <div><label class="text-xs font-semibold text-slate-600 mb-1 block">Number of Copies</label>
            <input type="number" name="total_copies" min="1" value="1" class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold"></div>
        <div class="flex gap-3 pt-2">
            <button type="submit" class="flex-1 bg-gold text-primary py-3 rounded-xl font-bold hover:bg-gold/90">Add Book</button>
            <button type="button" onclick="document.getElementById('addModal').classList.add('hidden')" class="flex-1 bg-slate-100 text-slate-700 py-3 rounded-xl font-semibold hover:bg-slate-200">Cancel</button>
        </div>
    </form>
</div>
</div>

<!-- Edit Book Modal -->
<div id="editModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
<div class="bg-white rounded-2xl p-8 max-w-md w-full shadow-2xl">
    <h2 class="text-xl font-bold text-slate-900 mb-5 flex items-center gap-2"><span class="material-symbols-outlined text-gold">edit</span>Edit Book</h2>
    <form method="POST" class="space-y-4">
        <input type="hidden" name="action" value="edit_book">
        <input type="hidden" name="book_id" id="editId">
        <div><label class="text-xs font-semibold text-slate-600 mb-1 block">Title <span class="text-red-500">*</span></label>
            <input type="text" name="title" id="editTitle" required class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold"></div>
        <div class="grid grid-cols-2 gap-3">
            <div><label class="text-xs font-semibold text-slate-600 mb-1 block">Author</label>
                <input type="text" name="author" id="editAuthor" class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold"></div>
            <div><label class="text-xs font-semibold text-slate-600 mb-1 block">ISBN</label>
                <input type="text" name="isbn" id="editIsbn" class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold"></div>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div><label class="text-xs font-semibold text-slate-600 mb-1 block">Category</label>
                <input type="text" name="category" id="editCategory" class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold"></div>
            <div><label class="text-xs font-semibold text-slate-600 mb-1 block">Shelf</label>
                <input type="text" name="shelf_location" id="editShelf" class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold"></div>
        </div>
        <div><label class="text-xs font-semibold text-slate-600 mb-1 block">Number of Copies</label>
            <input type="number" name="total_copies" id="editCopies" min="1" class="w-full border-slate-200 rounded-xl text-sm focus:ring-gold focus:border-gold">
            <p class="text-xs text-slate-400 mt-1">Currently-loaned copies are tracked separately — increasing this adds new available copies.</p>
        </div>
        <div class="flex gap-3 pt-2">
            <button type="submit" class="flex-1 bg-gold text-primary py-3 rounded-xl font-bold hover:bg-gold/90">Save</button>
            <button type="button" onclick="document.getElementById('editModal').classList.add('hidden')" class="flex-1 bg-slate-100 text-slate-700 py-3 rounded-xl font-semibold hover:bg-slate-200">Cancel</button>
        </div>
    </form>
</div>
</div>

<script>
function openEditModal(b) {
    document.getElementById('editId').value = b.id;
    document.getElementById('editTitle').value = b.title;
    document.getElementById('editAuthor').value = b.author || '';
    document.getElementById('editIsbn').value = b.isbn || '';
    document.getElementById('editCategory').value = b.category || '';
    document.getElementById('editShelf').value = b.shelf_location || '';
    document.getElementById('editCopies').value = b.total_copies;
    document.getElementById('editModal').classList.remove('hidden');
}
['addModal','editModal'].forEach(id => {
    document.getElementById(id).addEventListener('click', function(e) { if (e.target === this) this.classList.add('hidden'); });
});
</script>
</body>
</html>
