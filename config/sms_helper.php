<?php


// Twilio SMS Configuration
// Get these from: https://www.twilio.com/console

// TODO: Add your Twilio credentials here
const TWILIO_ACCOUNT_SID = 'AC7ded1ccb9966d09fa0d65e5b21a43510';
const TWILIO_AUTH_TOKEN = '2e05b43afbc6fca517cba9c6895c7701';
const TWILIO_PHONE_NUMBER = '+13612139909';  // Your Twilio phone number

function sendOrderConfirmationSMS($phoneNumber, $customerName, $orderId, $orderDetails) {
    // Validate phone number
    if (empty($phoneNumber)) {
        error_log("SMS Error: Phone number is empty");
        logSMSDebug("ERROR: Empty phone number");
        return false;
    }

    // Format phone number (remove spaces, hyphens, etc.)
    $phoneNumber = preg_replace('/[^0-9+]/', '', $phoneNumber);
    
    // Ensure phone number has country code
    if (substr($phoneNumber, 0, 1) !== '+') {
        // Check if it's already in +country format
        if (strlen($phoneNumber) == 10) {
            // 10 digits without country code
            // Check if it looks like a US number or Indian number
            if (in_array(substr($phoneNumber, 0, 3), ['361', '201', '212', '202', '404', '408', '415', '510', '602', '603', '610', '650', '702', '703', '707', '714', '720', '781', '801', '818', '831', '845', '858', '860', '865', '916', '925', '949'])) {
                // Known US area code - add +1
                $phoneNumber = '+1' . $phoneNumber;
            } else {
                // Default to Indian number +91
                $phoneNumber = '+91' . $phoneNumber;
            }
        } elseif (substr($phoneNumber, 0, 2) === '91') {
            // Already has country code 91 but no + sign
            $phoneNumber = '+' . $phoneNumber;
        } elseif (substr($phoneNumber, 0, 1) === '1' && strlen($phoneNumber) == 11) {
            // US number like 13612139909
            $phoneNumber = '+' . $phoneNumber;
        } else {
            // Default fallback
            $phoneNumber = '+91' . substr($phoneNumber, -10);
        }
    }

    // Build SMS message
    $message = buildOrderSMSMessage($customerName, $orderId, $orderDetails);
    
    logSMSDebug("SMS Attempt - Order: $orderId | Phone: $phoneNumber | Customer: $customerName | Length: " . strlen($message));

    try {
        // Make API call to Twilio
        return sendTwilioSMS($phoneNumber, $message, $orderId);
    } catch (Exception $e) {
        error_log("SMS Error: " . $e->getMessage());
        logSMSDebug("EXCEPTION: " . $e->getMessage());
        return false;
    }
}

function buildOrderSMSMessage($customerName, $orderId, $orderDetails) {
    // Extract data
    $paymentMethod = isset($orderDetails['payment_method']) 
        ? htmlspecialchars($orderDetails['payment_method']) 
        : 'Online Payment';
    
    $estimatedDelivery = isset($orderDetails['estimated_delivery']) 
        ? htmlspecialchars($orderDetails['estimated_delivery']) 
        : '2-3 business days';

    // Count items
    $itemCount = count($orderDetails['items']);
    $totalAmount = number_format($orderDetails['total'], 2);

    // Build message (Keep under 160 characters for single SMS, can be longer for multi-part)
    $message = sprintf(
        "Hi %s! 🎉 Your Grocify order #%s is confirmed! 💳Payment: %s | 🚚Delivery: %s | 📦Items: %d | 💰Total: ₹%s | Track: %s",
        $customerName,
        $orderId,
        $paymentMethod,
        $estimatedDelivery,
        $itemCount,
        $totalAmount,
        isset($orderDetails['order_link']) ? 'http://grocify.local/order_details.php?id=' . $orderId : 'Check email'
    );

    return $message;
}

function sendTwilioSMS($phoneNumber, $message, $orderId = 'UNKNOWN') {
    // Initialize cURL
    $curl = curl_init();

    $auth = base64_encode(TWILIO_ACCOUNT_SID . ':' . TWILIO_AUTH_TOKEN);
    $url = 'https://api.twilio.com/2010-04-01/Accounts/' . TWILIO_ACCOUNT_SID . '/Messages.json';

    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => http_build_query([
            'From' => TWILIO_PHONE_NUMBER,
            'To' => $phoneNumber,
            'Body' => $message,
        ]),
        CURLOPT_HTTPHEADER => [
            'Authorization: Basic ' . $auth,
            'Content-Type: application/x-www-form-urlencoded',
        ],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);

    logSMSDebug("=== TWILIO API CALL ===");
    logSMSDebug("Account: " . TWILIO_ACCOUNT_SID);
    logSMSDebug("From: " . TWILIO_PHONE_NUMBER);
    logSMSDebug("To: " . $phoneNumber);
    logSMSDebug("Message Length: " . strlen($message) . " chars");

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    curl_close($curl);

    // Log all SMS attempts for debugging
    $logMessage = "[SMS] OrderID: $orderId | To: $phoneNumber | HTTP: $httpCode | Time: " . date('Y-m-d H:i:s');
    error_log($logMessage);
    logSMSDebug($logMessage);

    if ($error) {
        logSMSDebug("cURL Error: $error");
        error_log("SMS cURL Error: $error");
        return false;
    }

    if ($httpCode == 201 || $httpCode == 200) {
        logSMSDebug("✓ SMS Sent Successfully to $phoneNumber");
        error_log("SMS Sent Successfully to $phoneNumber - Order #$orderId");
        return true;
    } else {
        $responseData = json_decode($response, true);
        $errorMsg = $responseData['message'] ?? 'Unknown error';
        $errorCode = $responseData['code'] ?? 'N/A';
        logSMSDebug("✗ SMS Error (HTTP $httpCode | Code $errorCode): $errorMsg");
        logSMSDebug("Full Response: " . $response);
        error_log("SMS Error (HTTP $httpCode | Code $errorCode): $errorMsg");
        error_log("Full Response: " . $response);
        return false;
    }
}

// Alternative: Log SMS locally (for testing without Twilio credentials)
function logOrderSMSLocally($phoneNumber, $customerName, $orderId, $orderDetails) {
    $message = buildOrderSMSMessage($customerName, $orderId, $orderDetails);
    
    $logEntry = sprintf(
        "[%s] SMS TO: %s | NAME: %s | ORDER: #%s | MESSAGE: %s\n",
        date('Y-m-d H:i:s'),
        $phoneNumber,
        $customerName,
        $orderId,
        $message
    );

    error_log($logEntry);
    file_put_contents(__DIR__ . '/../sms_log.txt', $logEntry, FILE_APPEND);
    
    return true;
}

// Debug logging function
function logSMSDebug($message) {
    $logEntry = "[" . date('Y-m-d H:i:s') . "] " . $message . "\n";
    error_log($logEntry);
    file_put_contents(__DIR__ . '/../sms_debug.log', $logEntry, FILE_APPEND);
}

?>
