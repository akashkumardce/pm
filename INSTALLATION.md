# Installation Guide

## Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher (or MariaDB 10.2+)
- Apache web server with mod_rewrite enabled
- PDO MySQL extension enabled

## Installation Steps

### 1. Upload Files

Upload all files to your web server's document root or a subdirectory.

### 2. Set Permissions

Ensure the following directories are writable:
- `config/` (for database configuration file)

```bash
chmod 755 config/
chmod 644 config/database.php
```

### 3. Run Installation Wizard

1. Open your web browser
2. Navigate to: `http://yourdomain.com/install/`
3. Follow the installation wizard:

   **Step 1: Database Configuration**
   - Enter your MySQL host (usually `localhost`)
   - Enter database name (or create a new one)
   - Enter MySQL username
   - Enter MySQL password
   - Click "Continue"

   **Step 2: Install Schema**
   - Click "Install Schema" to create database tables
   - Wait for confirmation

   **Step 3: Complete**
   - Installation is complete!
   - You can now access the application

### 4. First User Registration

1. Navigate to the homepage
2. Click "Register" or go to `/register.php`
3. Fill in your details and select roles
4. Complete registration
5. Login with your credentials

## Default Roles

The system comes with these default roles:
- **Property Owner** - For property owners
- **Tenant** - For tenants/renters
- **Property Manager** - For property managers
- **Admin** - System administrator

Users can have multiple roles assigned to them.

## Troubleshooting

### Database Connection Errors

- Verify MySQL credentials are correct
- Ensure MySQL service is running
- Check if database user has CREATE DATABASE privileges
- Verify database name doesn't contain special characters

### Permission Errors

- Ensure `config/` directory is writable
- Check file ownership matches web server user

### mod_rewrite Not Working

- Ensure Apache mod_rewrite is enabled
- Check `.htaccess` file is present
- Verify Apache AllowOverride is set to All

### API Endpoints Not Working

- Check `.htaccess` file is in place
- Verify mod_rewrite is enabled
- Check file paths are correct

## Security Notes

- Change default database credentials after installation
- Set proper file permissions
- Keep PHP and MySQL updated
- Use HTTPS in production
- Regularly backup your database

