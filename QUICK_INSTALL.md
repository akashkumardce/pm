# Quick MongoDB Extension Installation Guide

## Current Status
✅ **Validated**: MongoDB extension is NOT installed  
✅ **Ready**: Configuration script prepared

## Installation Steps

### Option 1: Manual Download (Recommended)

1. **Download the extension:**
   - Visit: https://windows.php.net/downloads/pecl/releases/mongodb/
   - Look for: `php_mongodb-1.19.0-8.2-ts-vs16-x64.zip`
   - Or try: https://pecl.php.net/package/mongodb

2. **Extract and copy:**
   - Extract the zip file
   - Copy `php_mongodb.dll` to: `C:\xampp\php\ext\`

3. **Run the setup script:**
   ```powershell
   powershell -ExecutionPolicy Bypass -File setup_mongodb.ps1
   ```
   This will automatically configure `php.ini`.

4. **Restart Apache:**
   - Open XAMPP Control Panel
   - Stop Apache
   - Start Apache again

5. **Verify:**
   ```powershell
   C:\xampp\php\php.exe check_mongodb.php
   ```

### Option 2: Automated Setup (After Manual Download)

If you've already downloaded and copied the DLL:

```powershell
powershell -ExecutionPolicy Bypass -File setup_mongodb.ps1 -SkipDownload
```

### Your PHP Configuration
- **Version**: 8.2.12
- **Build**: ZTS (Thread Safe)
- **Architecture**: x64 (64-bit)
- **Compiler**: VS16 (Visual Studio 2019)

**Required file**: `php_mongodb-1.19.0-8.2-ts-vs16-x64.zip`

## Troubleshooting

### If download links don't work:
1. Try different versions: 1.18.0, 1.17.0, etc.
2. Check: https://pecl.php.net/package/mongodb for alternative downloads
3. Search for "php mongodb extension windows 8.2 zts x64 download"

### If extension still not loading after installation:
1. Check `C:\xampp\apache\logs\error.log` for errors
2. Verify `extension=mongodb` is in `php.ini` (not commented with `;`)
3. Make sure you downloaded the **ZTS** version, not NTS
4. Restart Apache completely (stop, wait 5 seconds, start)

## Verification

After installation, run:
```powershell
C:\xampp\php\php.exe check_mongodb.php
```

You should see: `[OK] MongoDB extension is LOADED`

## Next Steps

Once installed:
1. Run the installer: `http://localhost/pm/install`
2. Enter MongoDB URI: `mongodb://localhost:27017`
3. Complete the installation

