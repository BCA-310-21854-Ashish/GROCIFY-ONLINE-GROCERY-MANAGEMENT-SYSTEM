# Email Order Confirmation - System Architecture & Flow

## 📊 System Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                     GROCIFY APPLICATION                      │
├─────────────────────────────────────────────────────────────┤
│                                                               │
│  USER CHECKOUT FLOW:                                          │
│  ──────────────────                                           │
│                                                               │
│   1. User adds items to cart                                 │
│   2. User proceeds to checkout.php                           │
│   3. User fills billing details                              │
│   4. User completes payment                                  │
│   5. Order inserted into database                            │
│         │                                                     │
│         └──► [Order ID Generated]                            │
│              │                                                │
│              └──► Fetch product details                       │
│                   │                                           │
│                   └──► Call sendOrderConfirmationEmail()       │
│                        │                                       │
│                        ├──► Generate HTML template             │
│                        │    with order details                │
│                        │                                       │
│                        ├──► Connect to Gmail SMTP             │
│                        │    (smtp.gmail.com:587)              │
│                        │                                       │
│                        └──► Send email to billing_email       │
│                             │                                  │
│                             ├─ SUCCESS ──► Redirect to        │
│                             │              payment/success.php │
│                             │                                  │
│                             └─ FAILURE ──► Log error, but     │
│                                            redirect anyway     │
│                                            (order is saved)    │
│                                                               │
│                                                               │
│  OPTIONAL USER ACTIONS:                                       │
│  ──────────────────────                                       │
│                                                               │
│   • Test email: Visit test_email.php (anytime)               │
│   • Resend email: Visit resend_order_email.php               │
│                   (from orders page)                         │
│                                                               │
└─────────────────────────────────────────────────────────────┘
```

## 🔄 Detailed Process Flow

```
START (checkout.php)
  │
  ├─ Validate user is logged in
  │
  ├─ Validate cart is not empty
  │
  ├─ Calculate order total
  │
  ├─ FORM SUBMITTED (POST)
  │   │
  │   ├─ Validate billing details
  │   ├─ Validate payment ID
  │   │
  │   ├─ INSERT into orders table
  │   │   ├─ user_id
  │   │   ├─ total_amount
  │   │   ├─ billing_name
  │   │   ├─ billing_email ◄────┐
  │   │   ├─ billing_phone       │
  │   │   ├─ billing_address ◄──┤── Used for email
  │   │   └─ payment_method      │
  │   │                          │
  │   ├─ Get order_id from insert│
  │   │                          │
  │   ├─ INSERT order_items for each product
  │   │   ├─ order_id
  │   │   ├─ product_id
  │   │   ├─ quantity
  │   │   └─ price
  │   │
  │   ├─ Collect order details
  │   │   │
  │   │   ├─ FOR EACH item in cart:
  │   │   │   ├─ SELECT product name from products
  │   │   │   └─ Build item array [name, qty, price]
  │   │   │
  │   │   └─ Create orderDetailsForEmail array:
  │   │       ├─ items: [...product data...]
  │   │       ├─ total: calculated
  │   │       ├─ address: from billing_address
  │   │       ├─ phone: from billing_phone
  │   │       └─ order_link: URL to order details
  │   │
  │   ├─ SEND EMAIL ◄────────────────────┐
  │   │   │                               │
  │   │   ├─ Call sendOrderConfirmationEmail()
  │   │   │                               │
  │   │   └─── [EMAIL FUNCTION] ──────────┤
  │   │       │                           │
  │   │       ├─ Create PHPMailer instance
  │   │       ├─ Set SMTP config
  │   │       ├─ Generate HTML template
  │   │       ├─ Send via SMTP
  │   │       └─ Return success/failure
  │   │
  │   ├─ Clear cart session
  │   │
  │   └─ Redirect to success.php
  │
  └─ END
```

## 📧 Email Generation Process

```
sendOrderConfirmationEmail()
│
├─ Initialize PHPMailer
│
├─ Configure SMTP:
│  ├─ Host: smtp.gmail.com
│  ├─ Port: 587
│  ├─ Security: TLS
│  ├─ Username: grocify21854@gmail.com
│  └─ Password: [app-password]
│
├─ Set Email Headers:
│  ├─ From: grocify21854@gmail.com (Grocify)
│  └─ To: [billing_email]
│
├─ Generate Email Body (HTML):
│  │
│  ├─ Header Section:
│  │  └─ "🛒 Order Confirmation"
│  │
│  ├─ Greeting:
│  │  └─ "Hi [customer_name]"
│  │
│  ├─ Order Info Box:
│  │  ├─ Order ID: #[order_id]
│  │  ├─ Date: [current_date_time]
│  │  └─ Status: Order Placed
│  │
│  ├─ Order Items Table:
│  │  ├─ Header Row: Product | Quantity | Price | Total
│  │  └─ FOR EACH item:
│  │     └─ [name] | [qty] | ₹[price] | ₹[total]
│  │
│  ├─ Summary:
│  │  ├─ Subtotal: ₹[subtotal]
│  │  └─ Total: ₹[total]
│  │
│  ├─ Delivery Address:
│  │  ├─ [address]
│  │  └─ Phone: [phone]
│  │
│  ├─ Action Button:
│  │  └─ [Track Your Order] ──► order_details.php?id=[order_id]
│  │
│  ├─ Footer:
│  │  ├─ Contact information
│  │  └─ Automated message disclaimer
│  │
│  └─ Styling:
│     ├─ Colors: Green (#198754)
│     ├─ Fonts: Arial, sans-serif
│     ├─ Responsive layout
│     └─ Inline CSS
│
├─ Send Email
│
└─ Return true/false + log errors
```

## 📁 File Interaction Map

```
checkout.php (modified)
│
├─ require 'config/db.php'
├─ require 'config/mail_helper.php' ◄─── [NEW IMPORT]
│
└─ After order creation:
   └─ sendOrderConfirmationEmail(
      $billing_email,
      $billing_name,
      $order_id,
      $orderDetailsForEmail
   )

config/mail_helper.php (new)
│
├─ include PHPMailer classes
├─ define sendOrderConfirmationEmail()
└─ return boolean


test_email.php (new)
│
├─ require 'config/mail_helper.php'
└─ Call sendOrderConfirmationEmail() with test data


resend_order_email.php (new)
│
├─ require 'config/db.php'
├─ require 'config/mail_helper.php'
├─ Fetch user's orders from DB
└─ Resend email for selected order
```

## 🔌 Database Integration

```
ORDERS TABLE (existing)
│
├─ id ..................... Primary key (auto)
├─ user_id ................ Foreign key to users
├─ total_amount ........... Used for email
├─ billing_name ........... Used for email greeting
├─ billing_email .......... Used for sending email ◄── KEY
├─ billing_phone .......... Used in email body
├─ billing_address ........ Used in email body ◄── KEY
├─ payment_method ......... Shown in email
├─ order_date ............. Used for timestamp
└─ status ................. Shown in email

ORDER_ITEMS TABLE (existing)
│
├─ order_id ............... Links to order
├─ product_id ............ Links to product (for name lookup)
├─ quantity ............... Used in email
└─ price .................. Used in email

PRODUCTS TABLE (existing)
│
├─ id ..................... Matched with product_id
└─ name ................... Used in email ◄── KEY (fetched during email)
```

## 🔐 Data Flow Security

```
User Input (Checkout Form)
    ↓
Validate & Sanitize
    ↓
Prepare Statement (SQL Injection Safe)
    ↓
Insert to Database
    ↓
Collect Order Data
    ↓
Escape HTML (htmlspecialchars)
    ↓
Build Email HTML
    ↓
Send via Encrypted SMTP (TLS)
    ↓
Email Delivered to Gmail Server
    ↓
Customer Receives Email
```

## 🧪 Testing Interfaces

```
Test Page: test_email.php
└─ Purpose: Quick verification of email setup
   ├─ No DB queries needed
   ├─ Uses sample data
   └─ Sends to any email address


Resend Page: resend_order_email.php
└─ Purpose: Resend confirmation to customer
   ├─ Requires login
   ├─ Fetches actual order from DB
   ├─ Resends actual email
   └─ User-initiated action


Live Testing: During Checkout
└─ Purpose: Real-world verification
   ├─ Automatic sending
   ├─ Actual order data
   ├─ Customer email used
   └─ Full feature test
```

## 📈 Success Metrics

```
✅ Email Successfully Sent If:
   ├─ SMTP connection established
   ├─ Email formatting completed
   ├─ Recipient email valid
   ├─ PHPMailer returns true
   └─ No exceptions thrown

❌ Email Fails If:
   ├─ SMTP connection fails
   ├─ Invalid credentials
   ├─ Invalid recipient email
   ├─ Mail server rejects
   └─ Exception thrown

⚠️  Graceful Failure:
   ├─ Order created successfully
   ├─ Error logged
   ├─ User redirected to success page
   └─ No user-visible error
```

---

**Architecture Last Updated:** 06 Jun 2026
**Version:** 1.0
**Status:** ✅ Complete
