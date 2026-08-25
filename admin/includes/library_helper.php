<?php
/**
 * library_helper.php
 * ─────────────────────────────────────────────────────────────────────────
 * Books live in library_books (title-level, with a total/available copy
 * count). Borrowing is tracked in library_loans — a loan is "active" while
 * returned_date IS NULL. Overdue is computed on the fly, never stored.
 *
 * Requires $conn (mysqli connection) to already be available — include this
 * AFTER config/database.php.
 * ─────────────────────────────────────────────────────────────────────────
 */

const LIBRARY_LOAN_DAYS = 14;

/**
 * Get all active (visible) books, most recently added first.
 */
function getAllBooks(mysqli $conn, string $search = ''): array {
    if ($search) {
        $stmt = $conn->prepare("
            SELECT * FROM library_books
            WHERE is_active = 1 AND (title LIKE ? OR author LIKE ? OR isbn LIKE ?)
            ORDER BY title ASC
        ");
        $s = "%$search%";
        $stmt->bind_param("sss", $s, $s, $s);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    $result = $conn->query("SELECT * FROM library_books WHERE is_active = 1 ORDER BY title ASC");
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function getBook(mysqli $conn, int $book_id): ?array {
    $stmt = $conn->prepare("SELECT * FROM library_books WHERE id = ?");
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ?: null;
}

/**
 * Find a borrower (student or staff) by their reg no / staff ID.
 * Returns ['type'=>'Student'|'Staff', 'id'=>int, 'name'=>string, 'code'=>string] or null.
 */
function findBorrowerByCode(mysqli $conn, string $code): ?array {
    $code = trim($code);
    $stmt = $conn->prepare("SELECT id, student_id, first_name, last_name FROM students WHERE student_id = ? AND status = 'Active'");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    if ($row = $stmt->get_result()->fetch_assoc()) {
        return ['type' => 'Student', 'id' => $row['id'], 'name' => $row['first_name'].' '.$row['last_name'], 'code' => $row['student_id']];
    }

    $stmt = $conn->prepare("SELECT id, staff_id, first_name, last_name FROM staff WHERE staff_id = ? AND status = 'Active'");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    if ($row = $stmt->get_result()->fetch_assoc()) {
        return ['type' => 'Staff', 'id' => $row['id'], 'name' => $row['first_name'].' '.$row['last_name'], 'code' => $row['staff_id']];
    }

    return null;
}

/**
 * Lend a book. Returns ['ok'=>bool, 'error'=>string|null, 'loan_id'=>int|null].
 * Fails if there's no available copy.
 */
function borrowBook(mysqli $conn, int $book_id, string $borrower_type, int $borrower_id, string $due_date, ?int $issued_by): array {
    $book = getBook($conn, $book_id);
    if (!$book) return ['ok' => false, 'error' => 'Book not found.', 'loan_id' => null];
    if ($book['available_copies'] < 1) return ['ok' => false, 'error' => 'No copies available right now.', 'loan_id' => null];

    $conn->begin_transaction();
    try {
        $student_id = $borrower_type === 'Student' ? $borrower_id : null;
        $staff_id   = $borrower_type === 'Staff' ? $borrower_id : null;
        $today = date('Y-m-d');

        $stmt = $conn->prepare("
            INSERT INTO library_loans (book_id, borrower_type, student_id, staff_id, borrowed_date, due_date, issued_by)
            VALUES (?,?,?,?,?,?,?)
        ");
        $stmt->bind_param("isiissi", $book_id, $borrower_type, $student_id, $staff_id, $today, $due_date, $issued_by);
        $stmt->execute();
        $loan_id = $conn->insert_id;

        $conn->query("UPDATE library_books SET available_copies = available_copies - 1 WHERE id = $book_id");
        $conn->commit();
        return ['ok' => true, 'error' => null, 'loan_id' => $loan_id];
    } catch (Exception $e) {
        $conn->rollback();
        return ['ok' => false, 'error' => $e->getMessage(), 'loan_id' => null];
    }
}

/**
 * Return a book for a given loan. Marks it returned and restocks the copy.
 */
function returnBook(mysqli $conn, int $loan_id, ?int $returned_by, string $status = 'Returned'): array {
    $stmt = $conn->prepare("SELECT * FROM library_loans WHERE id = ? AND returned_date IS NULL");
    $stmt->bind_param("i", $loan_id);
    $stmt->execute();
    $loan = $stmt->get_result()->fetch_assoc();
    if (!$loan) return ['ok' => false, 'error' => 'Loan not found or already returned.'];

    $conn->begin_transaction();
    try {
        $today = date('Y-m-d');
        $stmt = $conn->prepare("UPDATE library_loans SET returned_date=?, status=?, returned_to=? WHERE id=?");
        $stmt->bind_param("ssii", $today, $status, $returned_by, $loan_id);
        $stmt->execute();

        // "Lost" books don't go back into circulation
        if ($status !== 'Lost') {
            $conn->query("UPDATE library_books SET available_copies = available_copies + 1 WHERE id = " . intval($loan['book_id']));
        }
        $conn->commit();
        return ['ok' => true, 'error' => null];
    } catch (Exception $e) {
        $conn->rollback();
        return ['ok' => false, 'error' => $e->getMessage()];
    }
}

/**
 * All currently active (not yet returned) loans, most overdue first.
 */
function getActiveLoans(mysqli $conn): array {
    $result = $conn->query("
        SELECT ll.*, lb.title, lb.author,
            CASE
                WHEN ll.borrower_type='Student' THEN CONCAT(s.first_name,' ',s.last_name)
                ELSE CONCAT(st.first_name,' ',st.last_name)
            END as borrower_name,
            CASE
                WHEN ll.borrower_type='Student' THEN s.student_id
                ELSE st.staff_id
            END as borrower_code,
            (ll.due_date < CURDATE()) as is_overdue,
            DATEDIFF(CURDATE(), ll.due_date) as days_overdue
        FROM library_loans ll
        JOIN library_books lb ON lb.id = ll.book_id
        LEFT JOIN students s ON s.id = ll.student_id AND ll.borrower_type='Student'
        LEFT JOIN staff st ON st.id = ll.staff_id AND ll.borrower_type='Staff'
        WHERE ll.returned_date IS NULL
        ORDER BY is_overdue DESC, ll.due_date ASC
    ");
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

/**
 * Loan history for a specific borrower (student or staff), most recent first.
 */
function getBorrowerLoanHistory(mysqli $conn, string $borrower_type, int $borrower_id): array {
    $col = $borrower_type === 'Student' ? 'student_id' : 'staff_id';
    $stmt = $conn->prepare("
        SELECT ll.*, lb.title, lb.author
        FROM library_loans ll JOIN library_books lb ON lb.id = ll.book_id
        WHERE ll.$col = ? ORDER BY ll.borrowed_date DESC
    ");
    $stmt->bind_param("i", $borrower_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
