
Stock Management Patch

SQL:
ALTER TABLE products ADD COLUMN stock_quantity INT NOT NULL DEFAULT 0;

Features:
- stock_quantity column
- Admin can store stock values (manual UI integration may be needed)
- Product page can display stock and disable add-to-cart when stock is 0.
