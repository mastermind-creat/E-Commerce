# Environment Configuration Guide

## Overview
The M-Pesa API credentials and other sensitive configuration are now stored in a `.env` file for better security.

## Setup Instructions

### 1. Copy the Example File
```bash
cp .env.example .env
```

### 2. Edit the .env File
Open `.env` and update with your actual credentials:

```bash
nano .env
# or
vim .env
```

### 3. Configure M-Pesa Credentials

#### For Sandbox (Testing)
```env
MPESA_CONSUMER_KEY=xck8DVIsQpA2O32uRoNkezK5AsNhUG4cqmQE4HePRIgxALC2
MPESA_CONSUMER_SECRET=yOhA43rpjvKcAIyOGZytlpazek38Ay0cEGO2TAo6N9BQ9cmwPWGokTNnG0WF5Ajk
MPESA_ENV=sandbox
MPESA_SHORTCODE=174379
MPESA_PASSKEY=bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919
```

#### For Production (Live)
1. Register at https://developer.safaricom.co.ke
2. Create a production app
3. Get your production credentials
4. Update `.env`:

```env
MPESA_CONSUMER_KEY=your_production_consumer_key
MPESA_CONSUMER_SECRET=your_production_consumer_secret
MPESA_ENV=production
MPESA_SHORTCODE=your_paybill_or_till_number
MPESA_PASSKEY=your_production_passkey
MPESA_ACCOUNT_REFERENCE=Your Business Name
```

## Security Best Practices

### ✅ DO:
- Keep `.env` file out of version control (already in `.gitignore`)
- Set restrictive file permissions: `chmod 600 .env`
- Use different credentials for development and production
- Rotate credentials regularly
- Keep `.env.example` updated (without actual credentials)

### ❌ DON'T:
- Never commit `.env` to Git
- Never share `.env` file publicly
- Never hardcode credentials in code
- Never use production credentials in development

## File Permissions

Set proper permissions for the `.env` file:

```bash
chmod 600 .env
chown www-data:www-data .env  # For Apache
# or
chown nginx:nginx .env  # For Nginx
```

## How It Works

### 1. Environment Loader (`includes/load_env.php`)
- Reads `.env` file
- Parses key=value pairs
- Makes variables available via `env()` function

### 2. M-Pesa Config (`includes/mpesa_config.php`)
- Loads environment variables
- Uses `env()` function with fallback defaults
- Defines constants for use throughout the application

### 3. Usage Example
```php
// In any PHP file after mpesa_config.php is loaded
$consumerKey = MPESA_CONSUMER_KEY;
$environment = MPESA_ENV;
```

## Environment Variables Reference

| Variable | Description | Example |
|----------|-------------|---------|
| `MPESA_CONSUMER_KEY` | M-Pesa API Consumer Key | `xck8DVIsQp...` |
| `MPESA_CONSUMER_SECRET` | M-Pesa API Consumer Secret | `yOhA43rpjv...` |
| `MPESA_ENV` | Environment (sandbox/production) | `sandbox` |
| `MPESA_SHORTCODE` | Paybill or Till Number | `174379` |
| `MPESA_PASSKEY` | M-Pesa Passkey | `bfb279f9aa...` |
| `MPESA_ACCOUNT_REFERENCE` | Business name for transactions | `E-Commerce` |
| `MPESA_TRANSACTION_DESC` | Transaction description | `Payment for Order` |

## Troubleshooting

### .env file not loading
**Check:**
1. File exists: `ls -la .env`
2. File is readable: `chmod 600 .env`
3. Path is correct in `load_env.php`

### Credentials not working
**Verify:**
1. No extra spaces in `.env` file
2. No quotes around values (unless needed)
3. Correct environment (sandbox vs production)
4. Credentials are valid and active

### Testing Configuration
Run the test page to verify:
```
http://localhost/E-Commerce/test_mpesa.php
```

## Migration from Hardcoded Credentials

If you're upgrading from hardcoded credentials:

1. **Backup current config:**
   ```bash
   cp includes/mpesa_config.php includes/mpesa_config.php.backup
   ```

2. **Create .env file:**
   ```bash
   cp .env.example .env
   ```

3. **Copy credentials from old config to .env**

4. **Test thoroughly** before deploying

5. **Remove backup** once confirmed working

## Production Deployment Checklist

Before going live:

- [ ] Create `.env` file on production server
- [ ] Add production M-Pesa credentials
- [ ] Set `MPESA_ENV=production`
- [ ] Set file permissions: `chmod 600 .env`
- [ ] Verify `.env` is in `.gitignore`
- [ ] Test authentication with production credentials
- [ ] Set up monitoring for failed authentications
- [ ] Document credential rotation schedule

## Additional Security

### Use Environment Variables at Server Level
For even better security, set environment variables at the server level:

**Apache (.htaccess or VirtualHost):**
```apache
SetEnv MPESA_CONSUMER_KEY "your_key_here"
SetEnv MPESA_CONSUMER_SECRET "your_secret_here"
```

**Nginx:**
```nginx
fastcgi_param MPESA_CONSUMER_KEY "your_key_here";
fastcgi_param MPESA_CONSUMER_SECRET "your_secret_here";
```

Then remove these from `.env` file.

## Support

For issues with:
- **Environment setup**: Check this guide
- **M-Pesa credentials**: Contact Safaricom support
- **Integration issues**: See `MPESA_INTEGRATION_README.md`

## Files Created

- `.env` - Your actual credentials (gitignored)
- `.env.example` - Template file (safe to commit)
- `.gitignore` - Prevents `.env` from being committed
- `includes/load_env.php` - Environment loader
- `ENV_SETUP.md` - This guide

## Notes

- The `.env` file is already configured with your sandbox credentials
- Default values are provided as fallbacks in `mpesa_config.php`
- The system will work even if `.env` is missing (using defaults)
- For production, always use `.env` file with proper credentials
