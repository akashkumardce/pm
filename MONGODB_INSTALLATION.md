# MongoDB PHP Extension Installation Guide for XAMPP

## Your PHP Configuration
- **PHP Version**: 8.2.12
- **Build Type**: ZTS (Thread Safe)
- **Architecture**: x64
- **Compiler**: Visual Studio 2019 (VS16)

## Installation Steps

### Method 1: Download Pre-compiled DLL (Recommended)

1. **Download the MongoDB extension DLL:**
   - Go to: https://pecl.php.net/package/mongodb
   - Or use direct download for PHP 8.2 ZTS x64:
   - Visit: https://windows.php.net/downloads/pecl/releases/mongodb/
   - Download the latest version (e.g., `php_mongodb-1.19.0-8.2-ts-vs16-x64.zip`)

2. **Extract the DLL:**
   - Extract the zip file
   - Find `php_mongodb.dll`

3. **Copy to PHP extensions directory:**
   - Copy `php_mongodb.dll` to: `C:\xampp\php\ext\`

4. **Enable the extension:**
   - Open `C:\xampp\php\php.ini` in a text editor
   - Find the section with extensions (look for lines like `;extension=mysqli`)
   - Add this line:
     ```ini
     extension=mongodb
     ```
   - Save the file

5. **Restart Apache:**
   - Open XAMPP Control Panel
   - Stop Apache
   - Start Apache again

6. **Verify installation:**
   - Run: `C:\xampp\php\php.exe -m | findstr mongodb`
   - You should see `mongodb` in the output

### Method 2: Using PECL (If available)

If you have PECL installed:
```bash
pecl install mongodb
```

### Method 3: Manual Download Links

**Direct download for PHP 8.2.12 ZTS x64:**
- MongoDB extension: https://windows.php.net/downloads/pecl/releases/mongodb/1.19.0/
- Look for: `php_mongodb-1.19.0-8.2-ts-vs16-x64.zip`

**Alternative sources:**
- https://github.com/mongodb/mongo-php-driver/releases
- https://pecl.php.net/package/mongodb

### Troubleshooting

1. **Extension not loading:**
   - Check that `php_mongodb.dll` is in `C:\xampp\php\ext\`
   - Verify the line `extension=mongodb` is in `php.ini` (not commented with `;`)
   - Check for errors in Apache error log: `C:\xampp\apache\logs\error.log`

2. **Wrong DLL version:**
   - Make sure you download the ZTS (Thread Safe) version, not NTS
   - Make sure it's for PHP 8.2, not 8.1 or 8.3
   - Make sure it's x64, not x86

3. **Dependencies:**
   - MongoDB PHP extension requires the MongoDB C driver
   - Download from: https://github.com/mongodb/mongo-c-driver/releases
   - Place `libmongoc.dll` and `libbson.dll` in `C:\xampp\php\` or `C:\Windows\System32\`

### Quick Verification Script

Create a file `test_mongodb.php`:
```php
<?php
if (extension_loaded('mongodb')) {
    echo "MongoDB extension is loaded!\n";
    echo "Version: " . phpversion('mongodb') . "\n";
} else {
    echo "MongoDB extension is NOT loaded!\n";
}
?>
```

Run: `C:\xampp\php\php.exe test_mongodb.php`

