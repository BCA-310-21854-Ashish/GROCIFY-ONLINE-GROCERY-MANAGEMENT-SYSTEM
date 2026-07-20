# 🚀 Quick Start Guide - Order Confirmation Emails

## What's New?
Customers now automatically receive order confirmation emails when they place an order. The email includes:
- Order details
- Product list with prices
- Delivery information
- Tracking link

## 📧 For Users

### Test Email Sending
Go to: **http://localhost/grocify/test_email.php**
- Enter your name and email
- Click "Send Test Email"
- Check your inbox or spam folder

### Resend Confirmation Email
Go to: **http://localhost/grocify/resend_order_email.php**
- See your recent orders
- Click "Resend" to send email again

### During Checkout
Just place your order normally:
1. Add items to cart
2. Go to checkout
3. Fill billing details and pay
4. ✅ Confirmation email sent automatically

---

## ⚙️ For Developers

### Files Overview

| File | Purpose |
|------|---------|
| `config/mail_helper.php` | Handles email sending |
| `test_email.php` | Test page for email functionality |
| `resend_order_email.php` | UI to manually resend emails |
| `checkout.php` | Modified to send email after order |
| `EMAIL_SETUP_GUIDE.md` | Detailed configuration guide |

### How to Use in Code

```php
require_once 'config/mail_helper.php';

$orderDetails = [
    'items' => [
        ['name' => 'Product 1', 'quantity' => 2, 'price' => 100.00],
        ['name' => 'Product 2', 'quantity' => 1, 'price' => 50.00],
    ],
    'total' => 250.00,
    'address' => '123 Main St, City',
    'phone' => '+91 9876543210',
    'order_link' => 'http://example.com/order_details.php?id=123'
];

sendOrderConfirmationEmail('customer@email.com', 'Customer Name', 123, $orderDetails);
```

### Current Email Configuration
```php
// in config/mail_helper.php
Host: smtp.gmail.com
Port: 587
Security: TLS
From: grocify21854@gmail.com
```

### To Change Email Provider

Edit `config/mail_helper.php` in the `sendOrderConfirmationEmail()` function:

```php
$mail->Host       = 'your-smtp-server.com';  // Change this
$mail->Port       = 587;                      // Change if needed
$mail->Username   = 'your-email@gmail.com';  // Change this
$mail->Password   = 'your-app-password';      // Change this
```

### To Customize Email Design

Edit the `$htmlBody` variable in `config/mail_helper.php`. It's pure HTML, so you can modify:
- Colors (currently `#198754` green)
- Fonts and sizing
- Layout
- Add company logo
- Add/remove sections

---

## ✅ Status Checklist

- ✅ Email sending function created
- ✅ Integrated with checkout process
- ✅ Test page created
- ✅ Resend functionality added
- ✅ Configuration guide included
- ✅ Professional HTML email template
- ✅ Error handling implemented
- ✅ Ready for production use

---

## 🆘 Common Issues

| Issue | Solution |
|-------|----------|
| Emails not sending | Test with test_email.php first |
| Going to spam | Check Gmail 2FA and app password |
| Wrong sender name | Edit `setFrom()` in mail_helper.php |
| Want custom email design | Edit `$htmlBody` in mail_helper.php |
| Different email provider | Update SMTP credentials in mail_helper.php |

---

## 📞 Support

For detailed setup, refer to: **EMAIL_SETUP_GUIDE.md**

For implementation details, check: **IMPLEMENTATION_SUMMARY.md**

---

**Last Updated:** 06 Jun 2026
**Feature Status:** ✅ Active and Working
