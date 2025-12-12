# Installing MongoDB PHP Extension for Windows/XAMPP

According to the [official MongoDB PHP documentation](https://www.mongodb.com/docs/languages/php/#installation), you need two components:

1. **MongoDB PHP Extension** (C driver) - Required
2. **MongoDB PHP Library** (via Composer) - Already in composer.json ✅

## Your Configuration
- **PHP Version**: 8.2.12
- **Build**: ZTS (Thread Safe)
- **Architecture**: x64
- **Compiler**: VS16

## Installation Steps

### Step 1: Download MongoDB PHP Extension

**Option A: Direct Download (Recommended)**
1. Visit: https://windows.php.net/downloads/pecl/releases/mongodb/
2. Look for the latest version (e.g., 1.19.0 or newer)
3. Download: `php_mongodb-1.19.0-8.2-ts-vs16-x64.zip`
   - Make sure it's **ZTS** (Thread Safe), not NTS
   - Make sure it's for **PHP 8.2**
   - Make sure it's **x64** (64-bit)

**Option B: Alternative Sources**
- PECL: https://pecl.php.net/package/mongodb
- GitHub: https://github.com/mongodb/mongo-php-driver/releases

### Step 2: Extract and Install

1. **Extract the zip file**
   - Extract `php_mongodb.dll` from the zip

2. **Copy to PHP extensions directory**
   - Copy `php_mongodb.dll` to: `C:\xampp\php\ext\`

3. **Enable in php.ini**
   - Open: `C:\xampp\php\php.ini`
   - Find the `[ExtensionList]` or extension section
   - Add this line:
     ```ini
     extension=mongodb
     ```
   - Save the file

4. **Restart Apache**
   - Open XAMPP Control Panel
   - Stop Apache
   - Start Apache again

### Step 3: Verify Installation

Run:
```powershell
C:\xampp\php\php.exe check_mongodb.php
```

Or check directly:
```powershell
C:\xampp\php\php.exe -m | findstr mongodb
```

You should see `mongodb` in the output.

### Step 4: Install Composer Dependencies

The MongoDB PHP Library is already in `composer.json`. Install it:

```powershell
composer install
```

Or if composer is not in PATH:
```powershell
php composer.phar install
```

## Troubleshooting

### Extension Not Loading

1. **Check DLL exists:**
   ```powershell
   Test-Path C:\xampp\php\ext\php_mongodb.dll
   ```
   Should return `True`

2. **Check php.ini:**
   ```powershell
   Select-String -Path C:\xampp\php\php.ini -Pattern "extension.*mongodb"
   ```
   Should show `extension=mongodb` (without `;` at the start)

3. **Check Apache error log:**
   - `C:\xampp\apache\logs\error.log`
   - Look for MongoDB-related errors

4. **Wrong DLL version:**
   - Make sure you downloaded **ZTS** version (not NTS)
   - Make sure it's for **PHP 8.2** (not 8.1 or 8.3)
   - Make sure it's **x64** (not x86)

### Dependencies Missing

The MongoDB extension requires the MongoDB C driver libraries:
- `libmongoc.dll`
- `libbson.dll`

These should be included in the extension zip file. If you get errors about missing DLLs:
1. Extract all DLLs from the zip to `C:\xampp\php\ext\`
2. Or copy them to `C:\Windows\System32\`

## Connecting to MongoDB Atlas

Once the extension is installed, you can connect to MongoDB Atlas using:

```
mongodb+srv://username:password@cluster.mongodb.net/
```

The installer now supports both local and Atlas connection strings.

## Quick Setup Script

After downloading and copying the DLL, run:

```powershell
powershell -ExecutionPolicy Bypass -File setup_mongodb.ps1
```

This will automatically configure `php.ini`.

## References

- [Official MongoDB PHP Documentation](https://www.mongodb.com/docs/languages/php/#installation)
- [MongoDB PHP Extension on PECL](https://pecl.php.net/package/mongodb)
- [Windows PHP Downloads](https://windows.php.net/downloads/pecl/releases/mongodb/)

