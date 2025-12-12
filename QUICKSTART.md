# Quick Start Guide

## Getting Started in 5 Minutes

### Step 1: Upload to Server
Upload all files to your LAMP server's web directory (e.g., `/var/www/html/` or your domain's public_html).

### Step 2: Set Permissions
```bash
chmod 755 config/
chmod 644 config/database.php
```

### Step 3: Run Installer
1. Open browser: `http://yourdomain.com/install/`
2. Enter database credentials:
   - Host: `localhost` (usually)
   - Database: `property_management` (or your choice)
   - Username: Your MySQL username
   - Password: Your MySQL password
3. Click "Continue"
4. Click "Install Schema"
5. Done!

### Step 4: Register First User
1. Go to homepage: `http://yourdomain.com/`
2. Click "Register"
3. Fill in your details
4. Select roles (Property Owner, Tenant, etc.)
5. Submit

### Step 5: Login
1. Go to login page: `http://yourdomain.com/login.php`
2. Enter your email and password
3. Access dashboard

## API Endpoints

### Authentication
- `POST /api/auth/register.php` - Register new user
- `POST /api/auth/login.php` - Login user
- `POST /api/auth/logout.php` - Logout user
- `GET /api/auth/me.php` - Get current user (requires auth)

### Roles
- `GET /api/roles/list.php` - List all available roles

## Example API Usage

### Register User
```javascript
fetch('/api/auth/register.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        email: 'user@example.com',
        password: 'password123',
        first_name: 'John',
        last_name: 'Doe',
        phone: '1234567890',
        roles: ['property_owner', 'tenant']
    })
})
.then(res => res.json())
.then(data => console.log(data));
```

### Login
```javascript
fetch('/api/auth/login.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        email: 'user@example.com',
        password: 'password123'
    })
})
.then(res => res.json())
.then(data => console.log(data));
```

## Default Roles

- `property_owner` - Property Owner
- `tenant` - Tenant
- `property_manager` - Property Manager
- `admin` - Admin

## File Structure Overview

- `/api/` - All API endpoints
- `/public/` - Public-facing pages
- `/config/` - Configuration files
- `/includes/` - Shared PHP functions
- `/install/` - Installation wizard
- `/database/` - Database schema

## Troubleshooting

**Can't access installer?**
- Check if mod_rewrite is enabled
- Verify `.htaccess` file exists
- Check Apache AllowOverride settings

**Database connection fails?**
- Verify MySQL credentials
- Check if MySQL service is running
- Ensure user has CREATE DATABASE privilege

**API returns 503?**
- Run the installer first
- Check database connection in config

## Next Steps

Now that you have the basic structure:
1. ✅ Registration and login working
2. ✅ Multi-role system in place
3. 🔲 Add property management features
4. 🔲 Add tenant management
5. 🔲 Add payment tracking
6. 🔲 Add more features as needed

