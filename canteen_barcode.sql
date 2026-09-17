-- ============================================================================
-- CANTEEN BARCODE SUPPORT
-- Run this after canteen.sql / canteen_cost.sql
-- ============================================================================

-- Barcode identifies the product only — the price is always looked up fresh
-- from `price` at scan/sale time, never stored inside the barcode itself.
-- Nullable: existing items keep working with no barcode until an admin
-- generates one from the Items page. UNIQUE prevents two items ever sharing
-- a code.
ALTER TABLE `canteen_items` ADD COLUMN `barcode` varchar(20) DEFAULT NULL AFTER `category`;
ALTER TABLE `canteen_items` ADD UNIQUE KEY `barcode_unique` (`barcode`);
