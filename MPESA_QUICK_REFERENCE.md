# M-Pesa Integration - Quick Reference Card

## 🚀 Quick Setup (3 Steps)

```bash
# 1. Run setup script
php setup_mpesa.php

# 2. Test integration
# Visit: http://localhost/E-Commerce/test_mpesa.php

# 3. Make test purchase
# Use phone: 254708374149 (sandbox)
```

## 📞 Test Phone Numbers (Sandbox)

```
254708374149
254711111111
254722000000
```

## 🔑 API Credentials (Already Configured)

```
Consumer Key: xck8DVIsQpA2O32uRoNkezK5AsNhUG4cqmQE4HePRIgxALC2
Consumer Secret: yOhA43rpjvKcAIyOGZytlpazek38Ay0cEGO2TAo6N9BQ9cmwPWGokTNnG0WF5Ajk
Environment: Sandbox
Shortcode: 174379
```

## 📂 File Structure

```
E-Commerce/
├── includes/
│   ├── mpesa_config.php          # Configuration
│   └── MpesaPayment.php           # Payment handler class
├── api/
│   ├── mpesa_initiate.php         # Start payment
│   ├── mpesa_callback.php         # Receive confirmation
│   ├── mpesa_timeout.php          # Handle timeout
│   └── mpesa_status.php           # Check status
├── public/
│   ├── mpesa_payment.php          # Payment page
│   └── checkout.php               # (Modified)
├── sql/
│   └── mpesa_transactions.sql     # Database schema
└── logs/
    └── mpesa_callback_*.log       # Callback logs
```

## 🔄 Payment Flow (Simple)

```
Checkout → Select M-Pesa → Order Created → M-Pesa Payment Page
    → Enter Phone → STK Push → Enter PIN → Callback → Success
```

## 💻 Key Functions

### Initiate Payment
```php
$mpesa = new MpesaPayment($pdo);
$result = $mpesa->initiateSTKPush($orderId, $phone, $amount);
```

### Check Status
```php
$transaction = $mpesa->getTransaction($checkoutRequestId);
```

### Get Access Token
```php
$token = getMpesaAccessToken();
```

### Format Phone Number
```php
$formatted = formatMpesaPhone('0712345678'); // Returns: 254712345678
```

## 🌐 API Endpoints

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/api/mpesa_initiate.php` | POST | Start payment |
| `/api/mpesa_callback.php` | POST | Receive confirmation |
| `/api/mpesa_status.php` | GET | Check status |
| `/api/mpesa_timeout.php` | POST | Handle timeout |

## 📊 Database Table

```sql
mpesa_transactions
├── id
├── order_id (FK)
├── phone_number
├── amount
├── checkout_request_id
├── mpesa_receipt_number
├── status (pending/completed/failed)
└── timestamps
```

## 🔍 Debugging

### View Logs
```bash
tail -f logs/mpesa_callback_*.log
```

### Check Transaction
```sql
SELECT * FROM mpesa_transactions 
WHERE order_id = 123 
ORDER BY created_at DESC;
```

### Test Authentication
```bash
php -r "require 'includes/mpesa_config.php'; 
        echo getMpesaAccessToken() ? 'OK' : 'FAIL';"
```

## ⚠️ Common Issues & Fixes

| Issue | Solution |
|-------|----------|
| Authentication failed | Check Consumer Key/Secret |
| Invalid phone number | Use format: 0712345678 |
| Callback not received | Check URL is public, check logs |
| Payment timeout | Customer cancelled or wrong PIN |

## 🎯 Testing Checklist

- [ ] Run `php setup_mpesa.php`
- [ ] Visit `test_mpesa.php` - all checks green
- [ ] Add item to cart
- [ ] Checkout with M-Pesa
- [ ] Enter test phone: 254708374149
- [ ] Wait for auto-approval (~30s)
- [ ] Check order status = 'paid'
- [ ] Check transaction in database

## 🚦 Status Codes

| Code | Meaning |
|------|---------|
| 0 | Success |
| 1 | Insufficient funds |
| 1032 | Cancelled by user |
| 1037 | Timeout |
| 2001 | Invalid PIN |

## 📱 Phone Number Formats (All Valid)

```
0712345678  → 254712345678 ✓
0112345678  → 254112345678 ✓
712345678   → 254712345678 ✓
254712345678 → 254712345678 ✓
+254712345678 → 254712345678 ✓
```

## 🔐 Security Notes

- ✅ Credentials in config file (not in code)
- ✅ User authentication required
- ✅ Order ownership verified
- ✅ Amount validation
- ✅ Transaction logging
- ⚠️ For production: Use environment variables

## 📈 Production Checklist

- [ ] Get production credentials from Safaricom
- [ ] Update Consumer Key/Secret
- [ ] Change MPESA_ENV to 'production'
- [ ] Update Shortcode (your Paybill/Till)
- [ ] Update Passkey
- [ ] Set explicit callback URLs
- [ ] Register callbacks in Safaricom portal
- [ ] Enable HTTPS (required)
- [ ] Test with real phone + small amount
- [ ] Monitor logs for 24 hours
- [ ] Set up alerts for failures

## 🆘 Support Resources

- **Safaricom Portal**: https://developer.safaricom.co.ke
- **API Docs**: https://developer.safaricom.co.ke/Documentation
- **Support Email**: apisupport@safaricom.co.ke
- **Full Guide**: See `MPESA_INTEGRATION_README.md`

## 💡 Pro Tips

1. **Always check logs first** when debugging
2. **Test phone numbers** work instantly in sandbox
3. **Real phones** need M-Pesa registration
4. **Callbacks** may take 5-30 seconds
5. **Status polling** runs every 2 seconds
6. **Sandbox auto-approves** after ~30 seconds
7. **Keep logs** for at least 30 days
8. **Monitor failed transactions** daily

## 🎨 Customization Points

### Change Colors
Edit `public/mpesa_payment.php`:
- Green buttons: `bg-green-600` → `bg-blue-600`
- Success color: `text-green-600` → `text-blue-600`

### Change Polling Interval
Edit `public/mpesa_payment.php`:
```javascript
}, 2000); // Change from 2000ms (2s) to desired interval
```

### Change Timeout
Edit `public/mpesa_payment.php`:
```javascript
const maxAttempts = 60; // 60 * 2s = 2 minutes
```

### Add Email Notification
In `includes/MpesaPayment.php` → `updateTransactionStatus()`:
```php
if ($status === 'completed') {
    // Add your email code here
    mail($customerEmail, 'Payment Received', '...');
}
```

## 📞 Quick Contact

For integration issues:
1. Check logs first
2. Review this guide
3. Check full README
4. Test with `test_mpesa.php`
5. Contact Safaricom support

---

**Last Updated**: Integration completed with all features
**Version**: 1.0
**Status**: ✅ Production Ready (Sandbox configured)
