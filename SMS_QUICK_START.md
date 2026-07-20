# 📱 SMS Feature - Quick Start

## 🎯 What's New?

Customers now get **SMS notifications** when they place an order with:
- 🎉 Order confirmation
- 💳 Payment method
- 🚚 Estimated delivery
- 📦 Item count
- 💰 Total amount
- 📍 Tracking link

---

## 🚀 Get Started in 3 Steps

### Step 1: Create Twilio Account (5 min)
```
1. Go to: https://www.twilio.com
2. Sign up (free trial = $15 credit)
3. Get Account SID
4. Get Auth Token
5. Get Phone Number
```

### Step 2: Configure Grocify (2 min)
```
Edit: config/sms_helper.php

Add your credentials:
- TWILIO_ACCOUNT_SID
- TWILIO_AUTH_TOKEN
- TWILIO_PHONE_NUMBER
```

### Step 3: Test It (2 min)
```
Go to: http://localhost/grocify/test_sms.php
Send test SMS to yourself
Check your phone!
```

---

## 📱 Test SMS Page

**URL:** `http://localhost/grocify/test_sms.php`

**What it does:**
- Send test SMS to your phone
- Test with sample order data
- Verify SMS delivery
- Check message format

---

## 💬 What SMS Says

**Example message:**
```
Hi John! 🎉 Your Grocify order #5432 is confirmed! 
💳Payment: Credit Card | 🚚Delivery: 2-3 business days | 
📦Items: 3 | 💰Total: ₹425 | 
Track: http://grocify.local/order_details.php?id=5432
```

---

## ✅ Included in SMS

| Info | Example |
|------|---------|
| Name | John |
| Order ID | #5432 |
| Payment | Credit Card |
| Delivery | 2-3 business days |
| Items | 3 |
| Total | ₹425 |
| Tracking | Link to order page |

---

## 🔧 Setup (Detailed)

### 1. Get Twilio Credentials
```
https://www.twilio.com/console

Account Info:
├─ Account SID: AC...
├─ Auth Token: ...... (Keep secret!)
└─ Phone: +1234567890
```

### 2. Edit config/sms_helper.php
```php
const TWILIO_ACCOUNT_SID = 'ACxxxxx';        // ← Your Account SID
const TWILIO_AUTH_TOKEN = 'token';            // ← Your Auth Token
const TWILIO_PHONE_NUMBER = '+1234567890';   // ← Your Twilio Number
```

### 3. Verify in Twilio Console
```
Verified Caller IDs:
├─ Add your phone number
└─ Verify (get SMS code, enter it)
```

### 4. Test with test_sms.php
```
1. Go to: test_sms.php
2. Enter name & phone
3. Click "Send Test SMS"
4. Check phone for SMS
```

---

## 🧪 Testing

### Can I Test Without Twilio?

**Yes!** The system logs SMS locally:
```
Check: sms_log.txt (in grocify folder)
View SMS messages that would be sent
Later add Twilio credentials to go live
```

---

## 💰 Cost

**Twilio SMS Pricing:**
- Trial: $15 free (100+ SMS)
- Per SMS: ~₹0.50-1.00
- Monthly (1000 orders): ~₹750

**Very affordable for customer satisfaction!**

---

## 📋 Phone Number Formats

Works with:
- `9876543210` (10 digits)
- `+919876543210` (country code)
- `+91 98765 43210` (formatted)
- `+1 234 567 8900` (USA)

System auto-formats to: `+919876543210`

---

## ✨ Auto-Features

✅ Phone validation
✅ Format conversion
✅ Error handling
✅ Logging
✅ Graceful failures
✅ SMS sent even if email fails
✅ Order created even if SMS fails

---

## 🐛 Troubleshoot

| Issue | Solution |
|-------|----------|
| SMS not sent | Check Twilio credentials in sms_helper.php |
| Test SMS fails | Verify phone format: +91 9876543210 |
| Not receiving | Check SMS spam folder |
| Want to test first | Use local logging, add Twilio later |

---

## 📞 Links

**Twilio:** https://www.twilio.com
**Console:** https://www.twilio.com/console
**Test Page:** http://localhost/grocify/test_sms.php
**Full Guide:** SMS_SETUP_GUIDE.md

---

## 🎯 Quick Checklist

- [ ] Twilio account created
- [ ] Credentials copied
- [ ] sms_helper.php configured
- [ ] test_sms.php tested
- [ ] Test SMS received
- [ ] Ready for orders!

---

**Status:** ✅ READY
**Setup Time:** 10 minutes
**Cost:** ~₹0.50 per SMS

