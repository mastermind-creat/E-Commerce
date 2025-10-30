# M-Pesa Integration Guide

## Overview
This e-commerce system now includes full M-Pesa payment integration using Safaricom's Daraja API with STK Push functionality.

## Features
- ✅ STK Push payment initiation
- ✅ Real-time payment status tracking
- ✅ Automatic payment confirmation via callbacks
- ✅ Transaction history and logging
- ✅ Secure API credential management
- ✅ Phone number validation and formatting
- ✅ Payment retry functionality
- ✅ Comprehensive error handling

## Files Created

### Configuration
- **`includes/mpesa_config.php`** - M-Pesa API configuration and helper functions

### Core Classes
- **`includes/MpesaPayment.php`** - Main M-Pesa payment handler class

### API Endpoints
- **`api/mpesa_initiate.php`** - Initiates STK Push payment
- **`api/mpesa_callback.php`** - Receives payment confirmation from Safaricom
- **`api/mpesa_timeout.php`** - Handles payment timeout notifications
- **`api/mpesa_status.php`** - Checks payment status

### User Interface
- **`public/mpesa_payment.php`** - M-Pesa payment processing page
- **`public/checkout.php`** - Updated to support M-Pesa payment flow

### Database
- **`sql/mpesa_transactions.sql`** - M-Pesa transactions table schema

## Installation Steps

### 1. Database Setup
Run the SQL migration to create the M-Pesa transactions table:

```bash
mysql -u your_username -p ecommerce_db < sql/mpesa_transactions.sql
```

Or execute in phpMyAdmin:
```sql
-- Copy and paste the contents of sql/mpesa_transactions.sql
```

### 2. Configure API Credentials

**🔐 Credentials are now stored in `.env` file for security!**

The `.env` file is already created with your sandbox credentials. For production:

1. Edit the `.env` file:
```bash
nano .env
```

2. Update with your production credentials:
```env
MPESA_CONSUMER_KEY=your_production_key
MPESA_CONSUMER_SECRET=your_production_secret
MPESA_ENV=production
MPESA_SHORTCODE=your_paybill_number
MPESA_PASSKEY=your_production_passkey
```

3. Get production credentials from https://developer.safaricom.co.ke

**See `ENV_SETUP.md` for detailed environment configuration guide.**

### 3. Configure Callback URLs
Update the callback URLs in `includes/mpesa_config.php` to match your domain:

```php
// Current configuration uses dynamic URLs
// For production, set explicit URLs:
define('MPESA_CALLBACK_URL', 'https://yourdomain.com/E-Commerce/api/mpesa_callback.php');
define('MPESA_TIMEOUT_URL', 'https://yourdomain.com/E-Commerce/api/mpesa_timeout.php');
```

### 4. Set Up Logging Directory
Create a logs directory for M-Pesa callbacks:

```bash
mkdir -p /opt/lampp/htdocs/E-Commerce/logs
chmod 755 /opt/lampp/htdocs/E-Commerce/logs
```

### 5. Configure Business Details (Production Only)
For production, update these in `includes/mpesa_config.php`:

```php
define('MPESA_SHORTCODE', 'YOUR_PAYBILL_NUMBER');
define('MPESA_PASSKEY', 'YOUR_PRODUCTION_PASSKEY');
define('MPESA_ACCOUNT_REFERENCE', 'Your Business Name');
```

## How It Works

### Payment Flow

1. **Customer Checkout**
   - Customer selects M-Pesa as payment method
   - Completes checkout form
   - System creates order with 'pending' payment status

2. **M-Pesa Payment Page**
   - Customer is redirected to `mpesa_payment.php`
   - Enters M-Pesa phone number
   - Clicks "Pay Now"

3. **STK Push Initiation**
   - System calls `api/mpesa_initiate.php`
   - API authenticates with Safaricom
   - Sends STK Push to customer's phone
   - Transaction logged in database

4. **Customer Authorization**
   - Customer receives STK Push prompt
   - Enters M-Pesa PIN
   - Confirms payment

5. **Payment Confirmation**
   - Safaricom sends callback to `api/mpesa_callback.php`
   - System updates transaction status
   - Order payment status updated to 'paid'
   - Order status updated to 'confirmed'

6. **Status Checking**
   - Frontend polls `api/mpesa_status.php` every 2 seconds
   - Displays real-time payment status
   - Redirects to success page on completion

## Testing

### Sandbox Testing
Use these test credentials:

**Test Phone Numbers:**
- 254708374149
- 254711111111
- 254722000000

**Test Paybill:** 174379

**Test PIN:** Any 4-digit number in sandbox

### Testing Steps
1. Add items to cart
2. Proceed to checkout
3. Select "M-Pesa" as payment method
4. Complete checkout
5. Enter test phone number on M-Pesa payment page
6. Click "Pay Now"
7. In sandbox, payment is auto-approved after ~30 seconds

## API Endpoints Documentation

### POST /api/mpesa_initiate.php
Initiates M-Pesa STK Push

**Request:**
```json
{
  "order_id": 123,
  "phone_number": "0712345678",
  "amount": 1500.00
}
```

**Response (Success):**
```json
{
  "success": true,
  "message": "STK Push sent successfully",
  "checkout_request_id": "ws_CO_123456789",
  "merchant_request_id": "12345-67890-1"
}
```

### GET /api/mpesa_status.php
Checks payment status

**Parameters:**
- `checkout_request_id` - The checkout request ID from initiate

**Response:**
```json
{
  "success": true,
  "status": "completed",
  "result_code": "0",
  "result_description": "The service request is processed successfully",
  "mpesa_receipt_number": "OEI2AK4Q16",
  "amount": "1500.00"
}
```

### POST /api/mpesa_callback.php
Receives payment confirmation (Called by Safaricom)

### POST /api/mpesa_timeout.php
Receives timeout notification (Called by Safaricom)

## Database Schema

### mpesa_transactions Table
```sql
- id (Primary Key)
- order_id (Foreign Key to orders)
- phone_number
- amount
- merchant_request_id
- checkout_request_id
- response_code
- response_description
- result_code
- result_description
- mpesa_receipt_number
- status (pending, completed, failed, cancelled)
- created_at
- updated_at
```

## Security Considerations

1. **API Credentials**
   - Store credentials securely
   - Never commit production credentials to version control
   - Consider using environment variables

2. **Callback Validation**
   - Callbacks are logged for audit
   - Validate callback source in production
   - Implement IP whitelisting for Safaricom IPs

3. **Phone Number Validation**
   - System validates Kenyan phone numbers (254...)
   - Formats numbers automatically

4. **Amount Verification**
   - System verifies amount matches order total
   - Prevents payment manipulation

## Troubleshooting

### Common Issues

**1. "Failed to authenticate with M-Pesa API"**
- Check Consumer Key and Secret
- Verify internet connectivity
- Check if credentials are valid

**2. "Invalid phone number"**
- Use format: 0712345678 or 0112345678
- Must be registered with M-Pesa
- Must be Kenyan number (starts with 07 or 01)

**3. Callback not received**
- Check callback URL is publicly accessible
- Verify URL in Safaricom dashboard
- Check logs in `/logs/mpesa_callback_*.log`

**4. Payment timeout**
- Customer may have cancelled
- Network issues
- Customer entered wrong PIN
- Check transaction history on payment page

### Logs Location
- Callback logs: `/logs/mpesa_callback_YYYY-MM-DD.log`
- Timeout logs: `/logs/mpesa_timeout_YYYY-MM-DD.log`

## Production Checklist

Before going live:

- [ ] Register production app on Safaricom Developer Portal
- [ ] Update Consumer Key and Secret
- [ ] Update Paybill/Till Number
- [ ] Update Passkey
- [ ] Change MPESA_ENV to 'production'
- [ ] Set explicit callback URLs (not dynamic)
- [ ] Register callback URLs in Safaricom dashboard
- [ ] Test with real phone number and small amount
- [ ] Set up SSL certificate (HTTPS required)
- [ ] Configure proper error logging
- [ ] Set up monitoring and alerts
- [ ] Review security settings
- [ ] Test callback reception
- [ ] Document support procedures

## Support

For M-Pesa API issues:
- Safaricom Developer Portal: https://developer.safaricom.co.ke
- API Documentation: https://developer.safaricom.co.ke/Documentation
- Support Email: apisupport@safaricom.co.ke

## License
This integration is part of the E-Commerce system.
