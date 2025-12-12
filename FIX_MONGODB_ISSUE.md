# Fix MongoDB Extension Loading Issue

## Problem
You're getting: **"%1 is not a valid Win32 application"** or extension not loading.

## Root Cause
The MongoDB PHP extension requires **3 DLL files**, not just one:
1. `php_mongodb.dll` ✅ (you have this)
2. `libmongoc.dll` ❌ (missing)
3. `libbson.dll` ❌ (missing)

## Solution

### Step 1: Extract ALL DLLs from the Zip File

When you downloaded `php_mongodb-1.19.0-8.2-ts-vs16-x64.zip`, it contains multiple files:

```
php_mongodb-1.19.0-8.2-ts-vs16-x64.zip
├── php_mongodb.dll      ← You copied this
├── libmongoc.dll        ← MISSING! Copy this too
├── libbson.dll          ← MISSING! Copy this too
└── (possibly other DLLs)
```

### Step 2: Copy ALL DLLs to Ext Directory

Copy **ALL DLLs** from the extracted zip folder to:
```
C:\xampp\php\ext\
```

**Important**: Don't just copy `php_mongodb.dll` - copy everything!

### Step 3: Verify All Files Are Present

Run this to check:
```powershell
Get-ChildItem C:\xampp\php\ext\*mongodb*
Get-ChildItem C:\xampp\php\ext\lib*.dll
```

You should see:
- `php_mongodb.dll`
- `libmongoc.dll`
- `libbson.dll`

### Step 4: Restart Apache

1. Open XAMPP Control Panel
2. Stop Apache
3. Wait 5 seconds
4. Start Apache again

### Step 5: Verify Installation

Run:
```powershell
C:\xampp\php\php.exe check_mongodb.php
```

Or:
```powershell
C:\xampp\php\php.exe -m | findstr mongodb
```

## Common Mistakes

### ❌ Wrong: Only copying php_mongodb.dll
```
C:\xampp\php\ext\
└── php_mongodb.dll  ← Only this (WON'T WORK)
```

### ✅ Correct: Copying all DLLs
```
C:\xampp\php\ext\
├── php_mongodb.dll  ← Main extension
├── libmongoc.dll    ← Required dependency
└── libbson.dll      ← Required dependency
```

## If Still Not Working

### Check DLL Version Compatibility

The error "%1 is not a valid Win32 application" can also mean:
- **Wrong build type**: You need **ZTS** (Thread Safe), not NTS
- **Wrong architecture**: You need **x64**, not x86
- **Wrong PHP version**: Must match PHP 8.2

Verify your download:
- ✅ `php_mongodb-1.19.0-8.2-ts-vs16-x64.zip` (CORRECT)
- ❌ `php_mongodb-1.19.0-8.2-nts-vs16-x64.zip` (WRONG - NTS instead of ZTS)
- ❌ `php_mongodb-1.19.0-8.1-ts-vs16-x64.zip` (WRONG - PHP 8.1 instead of 8.2)

### Run Diagnostic

```powershell
powershell -ExecutionPolicy Bypass -File diagnose_mongodb.ps1
```

This will show exactly what's missing.

## Quick Fix Script

After copying all DLLs, run:
```powershell
powershell -ExecutionPolicy Bypass -File fix_mongodb_dependencies.ps1
```

## Still Having Issues?

1. **Check Apache error log**: `C:\xampp\apache\logs\error.log`
2. **Verify php.ini**: Make sure `extension=mongodb` is NOT commented (no `;` at start)
3. **Try different version**: Download a different version from PECL
4. **Re-download**: The zip file might be corrupted

## Summary

**The key issue**: You only copied `php_mongodb.dll` but the extension needs `libmongoc.dll` and `libbson.dll` as well. Extract and copy **ALL DLLs** from the zip file!

