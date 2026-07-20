# 🛒 Grocify — Online Grocery Store

A complete PHP/MySQL grocery e-commerce platform with 9 advanced features.

## 🚀 Features Added

| Feature | Files |
|---------|-------|
| 📦 Stock Management | `admin/stock.php` — track stock, SKUs, low-stock alerts |
| ⭐ Reviews & Ratings | `admin/reviews.php`, `submit_review.php`, `product.php` |
| 🏷️ Coupon System | `admin/coupons.php`, `apply_coupon.php` |
| ❤️ Wishlist | `wishlist.php`, `wishlist_action.php` |
| 📊 Dashboard Analytics | `admin/analytics.php` — charts, KPIs, top products |
| 🛵 Delivery Boy Module | `admin/delivery_boys.php` — assign, track, mark delivered |
| 🧾 GST Invoice | `gst_invoice.php` — itemized tax invoice, print/PDF |
| 🖼️ Product Gallery | `admin/product_gallery.php`, `product.php` — carousel |
| 💳 Demo Payment | `payment/demo_payment.php` — UPI, Card, Net Banking, Wallet |

## ⚙️ Setup

1. Import `grocify.sql` in phpMyAdmin
2. Place folder at `C:/xampp/htdocs/grocify/`
3. Visit `http://localhost/grocify/`

**Admin Login:** admin@grocify.com / password123  
**User Login:** john@example.com / password123

## 🔑 Default Coupons

| Code | Discount |
|------|----------|
| WELCOME10 | 10% off |
| SAVE50 | ₹50 off (min ₹300) |
| FRESH20 | 20% off (min ₹500) |
| NEWUSER | ₹30 off |

## 📁 Structure

```
grocify/
├── admin/          — Admin panel (analytics, stock, reviews, coupons, delivery)
├── auth/           — Login, register, forgot password
├── assets/         — CSS, JS, images
├── config/         — DB, mail, SMS helpers
├── partials/       — Header & footer
├── payment/        — Demo payment + success page
├── tcpdf/          — PDF generation library
├── PHPMailer/      — Email library
├── grocify.sql     — Complete database schema
└── *.php           — Storefront pages
```
