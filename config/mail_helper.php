<?php


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';

function sendOrderConfirmationEmail($email, $customerName, $orderId, $orderDetails) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'grocify21854@gmail.com';
        $mail->Password   = 'jjor bsmx vfxf muvx';
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('grocify21854@gmail.com', 'Grocify');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Order Confirmation - Grocify #' . $orderId;

        $orderItemsHtml = '';
        $subtotal = 0;

        foreach ($orderDetails['items'] as $item) {
            $itemTotal = $item['quantity'] * $item['price'];
            $subtotal += $itemTotal;
            $orderItemsHtml .= '
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 12px; text-align: left;">' . htmlspecialchars($item['name']) . '</td>
                    <td style="padding: 12px; text-align: center;">' . $item['quantity'] . '</td>
                    <td style="padding: 12px; text-align: right;">₹' . number_format($item['price'], 2) . '</td>
                    <td style="padding: 12px; text-align: right;">₹' . number_format($itemTotal, 2) . '</td>
                </tr>
            ';
        }

        $paymentMethod = isset($orderDetails['payment_method']) ? htmlspecialchars($orderDetails['payment_method']) : 'Online Payment';
        $estimatedDelivery = isset($orderDetails['estimated_delivery']) ? htmlspecialchars($orderDetails['estimated_delivery']) : '2-3 business days';

        $htmlBody = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; color: #333; line-height: 1.6; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; background: #f9f9f9; }
                .header { background: #198754; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: white; padding: 20px; border-radius: 0 0 8px 8px; }
                .order-info { background: #f5f5f5; padding: 15px; border-radius: 5px; margin: 20px 0; }
                .order-info p { margin: 8px 0; }
                .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin: 15px 0; }
                .info-box { background: #f9f9f9; padding: 12px; border-radius: 5px; border-left: 4px solid #198754; }
                .info-box strong { color: #198754; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                .total-row { background: #198754; color: white; font-weight: bold; padding: 12px; }
                .button { background: #198754; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 20px; }
                .footer { text-align: center; font-size: 12px; color: #999; margin-top: 20px; border-top: 1px solid #eee; padding-top: 10px; }
                .section-header { border-bottom: 2px solid #198754; padding-bottom: 10px; margin-top: 30px; margin-bottom: 15px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1 style="margin: 0;">🛒 Order Confirmation</h1>
                </div>
                <div class="content">
                    <p>Hi <strong>' . htmlspecialchars($customerName) . '</strong>,</p>
                    <p>Thank you for your order! We have received your order and it is being processed.</p>
                    
                    <div class="order-info">
                        <p><strong>Order ID:</strong> #' . htmlspecialchars($orderId) . '</p>
                        <p><strong>Order Date:</strong> ' . date('d M Y, h:i A') . '</p>
                        <p><strong>Status:</strong> <span style="color: #198754; font-weight: bold;">Order Placed ✓</span></p>
                    </div>

                    <h3 class="section-header">Order Items</h3>
                    <table>
                        <thead>
                            <tr style="background: #f5f5f5;">
                                <th style="padding: 12px; text-align: left;">Product</th>
                                <th style="padding: 12px; text-align: center;">Quantity</th>
                                <th style="padding: 12px; text-align: right;">Price</th>
                                <th style="padding: 12px; text-align: right;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            ' . $orderItemsHtml . '
                        </tbody>
                    </table>

                    <div style="text-align: right; margin-top: 20px;">
                        <p style="font-size: 16px;"><strong>Subtotal:</strong> ₹' . number_format($subtotal, 2) . '</p>
                        <p style="font-size: 18px; color: #198754;"><strong>Total Amount:</strong> ₹' . number_format($orderDetails['total'], 2) . '</p>
                    </div>

                    <h3 class="section-header">Payment & Delivery Information</h3>
                    <div class="info-grid">
                        <div class="info-box">
                            <strong>💳 Payment Method</strong><br>
                            ' . $paymentMethod . '
                        </div>
                        <div class="info-box">
                            <strong>🚚 Estimated Delivery</strong><br>
                            ' . $estimatedDelivery . '
                        </div>
                    </div>

                    <h3 class="section-header">Delivery Address</h3>
                    <div style="background: #f9f9f9; padding: 12px; border-radius: 5px;">
                        <p style="margin: 0 0 8px 0;">
                            📍 ' . htmlspecialchars($orderDetails['address']) . '<br>
                        </p>
                        <p style="margin: 0;">
                            📞 <strong>Phone:</strong> ' . htmlspecialchars($orderDetails['phone']) . '
                        </p>
                    </div>

                    <p style="margin-top: 30px; text-align: center;">
                        <a href="' . htmlspecialchars($orderDetails['order_link']) . '" class="button">📦 Track Your Order</a>
                    </p>

                    <div style="background: #f0f8f5; padding: 15px; border-radius: 5px; margin-top: 20px; border-left: 4px solid #198754;">
                        <p style="margin: 0; color: #555;"><strong>💡 What\'s Next?</strong></p>
                        <p style="margin: 8px 0 0 0; color: #555; font-size: 14px;">
                            Your order has been confirmed and is being prepared. You\'ll receive a shipping notification once your items are on the way. You can track your order status anytime by clicking the button above.
                        </p>
                    </div>

                    <p style="margin-top: 20px; color: #666;">
                        If you have any questions about your order, please don\'t hesitate to contact us. We\'re here to help!
                    </p>

                    <div class="footer">
                        <p>© 2024 Grocify. All rights reserved.</p>
                        <p>This is an automated email. Please do not reply to this email.</p>
                    </div>
                </div>
            </div>
        </body>
        </html>
        ';

        $mail->Body = $htmlBody;

        return $mail->send();

    } catch (Exception $e) {
        error_log("Order Confirmation Email Error: " . $mail->ErrorInfo);
        return false;
    }
}
?>
