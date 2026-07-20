# Order Confirmation Email Feature - Setup Guide

## Overview
This implementation adds automatic order confirmation email functionality to Grocify. When a customer places an order, they receive a beautifully formatted HTML email with:
- Order ID and date
- Itemized list of products with prices
- Order total and subtotal
- Delivery address and phone number
- Direct link to track the order

## Files Created/Modified

### New Files:
1. **config/mail_helper.php** - Contains `sendOrderConfirmationEmail()` function
2. **test_email.php** - Test page to verify email functionality

### Modified Files:
1. **checkout.php** - Updated to send email after successful order creation

## How It Works

### 1. Order Flow
- Customer fills billing details and completes payment in `checkout.php`
- After order is saved to database, `sendOrderConfirmationEmail()` is called
- Email is sent to customer's billing email address
- Customer is redirected to success page

### 2. Email Details Included
- **Order Information**: Order ID, date, status
- **Product Details**: Name, quantity, price per unit, total per item
- **Order Summary**: Subtotal and total amount
- **Delivery Information**: Address and phone number
- **Tracking Link**: Direct link to order details page
- **Professional Design**: HTML formatted email with Grocify branding

## Email Configuration

The email is sent using **Gmail SMTP** with the following settings (in `config/mail_helper.php`):

```php
$mail->Host       = 'smtp.gmail.com';
$mail->Port       = 587;
$mail->SMTPSecure = 'tls';
$mail->Username   = 'grocify21854@gmail.com';
$mail->Password   = 'jjor bsmx vfxf muvx';  // App-specific password
```

**Note**: This uses an app-specific password (not the main Gmail password) for security.

## Testing the Feature

### Option 1: Using the Test Page
1. Navigate to: `http://localhost/grocify/test_email.php`
2. Enter your name and email address
3. Click "Send Test Email"
4. Check your inbox (may appear in spam folder)

### Option 2: Placing an Actual Order
1. Log in to the application
2. Add items to cart
3. Proceed to checkout
4. Fill all billing details
5. Complete payment
6. You'll receive a confirmation email automatically

## Email Customization

To customize the email template, edit `config/mail_helper.php`:

### Modify Sender Name:
```php
$mail->setFrom('grocify21854@gmail.com', 'YOUR_COMPANY_NAME');
```

### Modify Email Subject:
```php
$mail->Subject = 'Your Custom Subject - Order #' . $orderId;
```

### Modify Email HTML Content:
The entire HTML template is in the `$htmlBody` variable. You can customize:
- Colors (currently uses `#198754` for green)
- Text content
- Layout and styling
- Add company logo or additional information

## Gmail App Password Setup

If you're using a different Gmail account:

1. Enable **2-Factor Authentication** on your Gmail account
2. Go to: https://myaccount.google.com/apppasswords
3. Create an app password for "Mail" and "Windows"
4. Copy the generated 16-character password
5. Update the credentials in `config/mail_helper.php`:
   ```php
   $mail->Username = 'your-email@gmail.com';
   $mail->Password = 'your-app-password';  // 16 characters
   ```

## Error Handling

If an email fails to send:
- The order is still created and saved successfully
- Error is logged to PHP error log (for debugging)
- User can still access their order and retry email later if needed

To view error logs:
```bash
# Check PHP error logs (location varies by system)
# On XAMPP: C:\xampp\apache\logs\error.log
```

## Troubleshooting

### Email not sending?
1. Check Gmail credentials are correct
2. Verify 2FA is enabled and app password is used (not main password)
3. Check that "Less secure app access" is disabled (if using non-app password)
4. Verify SMTP server connection (test with `test_email.php`)

### Email goes to spam?
1. Check email is sent from verified Gmail address
2. Add SPF/DKIM records to your domain if using custom domain
3. Ensure email content doesn't trigger spam filters

### Port connection issues?
- Port 587 with TLS is the most reliable
- Alternative: Port 465 with SSL
- Update in `config/mail_helper.php` if needed

## Future Enhancements

Possible improvements:
1. Add email templates for order status updates (Confirmed, Packed, Shipped, Delivered)
2. Add "Resend Email" button on order details page
3. Implement order cancellation emails
4. Add promotional offers in confirmation emails
5. Support for multiple email providers (SendGrid, Mailgun, etc.)
6. Email attachment with invoice/receipt PDF

## Technical Details

- **Framework**: PHPMailer (included in `PHPMailer/` directory)
- **Email Format**: HTML with inline CSS
- **Character Encoding**: UTF-8
- **Security**: Uses SMTP with TLS encryption
- **Error Logging**: PHP's error_log() function

---

**For questions or issues**, contact the development team or check the test page to verify setup.
