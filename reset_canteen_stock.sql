-- Reset every canteen item's stock count to 0.
-- Doesn't touch the item catalog itself (name, price, barcode, category) —
-- only the live stock number, ready for a real restock.
UPDATE `canteen_items` SET `quantity_in_stock` = 0;
