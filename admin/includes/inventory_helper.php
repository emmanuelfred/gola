<?php
/**
 * inventory_helper.php
 * School property (furniture, equipment) — not for sale, distinct from
 * canteen_items which tracks sellable stock.
 * Requires $conn (mysqli connection) to already be available.
 */

function getInventoryCategories(mysqli $conn): array {
    $result = $conn->query("SELECT * FROM inventory_categories WHERE is_active=1 ORDER BY name ASC");
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function getInventoryItems(mysqli $conn, string $search = '', int $category_id = 0, bool $include_disposed = false): array {
    $sql = "SELECT i.*, c.name as category_name FROM inventory_items i JOIN inventory_categories c ON c.id = i.category_id WHERE 1=1";
    $types = ''; $params = [];
    if (!$include_disposed) { $sql .= " AND i.is_active=1"; }
    if ($search) { $sql .= " AND (i.name LIKE ? OR i.location LIKE ? OR i.serial_number LIKE ?)"; $s="%$search%"; $types.="sss"; $params=[...$params,$s,$s,$s]; }
    if ($category_id) { $sql .= " AND i.category_id = ?"; $types .= "i"; $params[] = $category_id; }
    $sql .= " ORDER BY i.name ASC";

    if ($types) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    return $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
}

function getInventoryItem(mysqli $conn, int $id): ?array {
    $stmt = $conn->prepare("SELECT * FROM inventory_items WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ?: null;
}

/**
 * Total recorded value of all active inventory (quantity × unit_value,
 * only counting items that have a unit_value on record).
 */
function getInventoryTotalValue(mysqli $conn): float {
    $result = $conn->query("SELECT COALESCE(SUM(quantity * unit_value),0) as t FROM inventory_items WHERE is_active=1 AND unit_value IS NOT NULL");
    return (float) $result->fetch_assoc()['t'];
}

function getInventoryCounts(mysqli $conn): array {
    $result = $conn->query("
        SELECT
            COUNT(*) as item_count,
            COALESCE(SUM(quantity),0) as total_units,
            SUM(CASE WHEN condition_status IN ('Poor','Damaged') THEN 1 ELSE 0 END) as needs_attention
        FROM inventory_items WHERE is_active=1
    ");
    return $result->fetch_assoc();
}
