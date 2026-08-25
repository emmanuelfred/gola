-- ============================================================================
-- CANTEEN COST TRACKING
-- Run this after canteen.sql
-- ============================================================================

-- Optional cost price per item — needed to calculate actual profit (revenue
-- minus cost), not just revenue. Nullable: items without a cost set are
-- flagged separately in reports rather than silently treated as zero-cost
-- (which would make profit look artificially high).
ALTER TABLE `canteen_items` ADD COLUMN `cost_price` decimal(10,2) DEFAULT NULL AFTER `price`;
