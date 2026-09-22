# Railway Environment Variables for SeedMIS

## Required Environment Variables

Update these in Railway Dashboard → Your Project → Variables tab:

### Application
```env
APP_NAME="SeedMIS"
APP_ENV="production"
APP_KEY="base64:p0oguv4LVaI2toXEwUAqUyvxuBmn/SRJ7N7XOGohdmk="
APP_DEBUG="false"
APP_URL="https://seedmis-server-production.up.railway.app"
```

### Logging
```env
LOG_CHANNEL="stack"
LOG_LEVEL="error"
```

### Database (MySQL from Railway)
```env
DB_CONNECTION="mysql"
DB_HOST="${{MySQL.MYSQLHOST}}"
DB_PORT="${{MySQL.MYSQLPORT}}"
DB_DATABASE="${{MySQL.MYSQLDATABASE}}"
DB_USERNAME="${{MySQL.MYSQLUSER}}"
DB_PASSWORD="${{MySQL.MYSQLPASSWORD}}"
```

### Session & Cache
```env
SESSION_DRIVER="database"
CACHE_STORE="database"
QUEUE_CONNECTION="database"
```

### Email Configuration (Resend SMTP)
```env
MAIL_MAILER="smtp"
MAIL_HOST="smtp.resend.com"
MAIL_PORT="587"
MAIL_USERNAME="resend"
MAIL_PASSWORD="[Your Resend API Key - re_xxxxx]"
MAIL_ENCRYPTION="tls"
MAIL_FROM_ADDRESS="noreply@transactlogs.pro"
MAIL_FROM_NAME="SeedMIS - NWSSU"
```

---

## Important Notes

### Email Configuration
- **Domain**: Using `transactlogs.pro` (already verified in Resend)
- **Sender**: `noreply@transactlogs.pro`
- **API Key**: Same Resend account, won't affect other projects
- **Alternative emails** you can use:
  - `seedmis@transactlogs.pro`
  - `admin-seedmis@transactlogs.pro`
  - Any email with `@transactlogs.pro` domain

### Database
- Railway automatically connects to MySQL service using `${{MySQL.*}}` variables
- Make sure MySQL service is added to your Railway project

### Auto-Seeding
- Admin account is automatically created on deployment
- **Email**: `admin@seedmis.com`
- **Password**: `admin123`
- Seeder is idempotent (won't create duplicates)

### Deployment
- Automatic deployment on git push
- Runs migrations and seeds automatically
- Command: `php artisan migrate --force && php artisan db:seed --force`

---

## Testing Email Configuration

After deployment, test the email functionality:

1. Go to Forgot Password page
2. Enter any registered email (e.g., `admin@seedmis.com`)
3. Check the recipient's inbox for OTP email
4. Check Railway logs for any email errors

---

## Troubleshooting

### Email not sending?
1. Verify `transactlogs.pro` is still verified in Resend
2. Check Railway logs for email errors
3. Verify Resend API key is correct
4. Test email sending locally first

### Database connection issues?
1. Ensure MySQL service is running in Railway
2. Check if database variables are correctly linked
3. Try redeploying the project

### Admin login not working?
1. Check if migrations ran successfully
2. Check if seeder created admin account
3. Use test endpoint: `/api/test-db`
4. Manually seed: `/api/seed-admin`

---

## Quick Commands

### Clear config cache (if needed)
```bash
php artisan config:clear
```

### Re-run migrations and seeds
```bash
php artisan migrate:fresh --seed --force
```

### Test database connection
```bash
curl https://seedmis-server-production.up.railway.app/api/test-db
```

---

Last Updated: September 22, 2026
