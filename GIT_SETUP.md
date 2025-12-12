# Git Repository Setup Instructions

## Prerequisites

Make sure Git is installed on your system:
- Download from: https://git-scm.com/download/win
- Or install via: `winget install Git.Git`

## Setup Git Repository

### 1. Initialize Git Repository

Open PowerShell or Command Prompt in the project directory and run:

```bash
git init
```

### 2. Configure Git (if not already done)

```bash
git config --global user.name "Your Name"
git config --global user.email "your.email@example.com"
```

### 3. Add All Files

```bash
git add .
```

### 4. Make Initial Commit

```bash
git commit -m "Initial commit: Property Management System with registration and login"
```

### 5. Create Remote Repository

Create a new repository on:
- **GitHub**: https://github.com/new
- **GitLab**: https://gitlab.com/projects/new
- **Bitbucket**: https://bitbucket.org/repo/create

**Important**: Don't initialize with README, .gitignore, or license (we already have these)

### 6. Add Remote and Push

Replace `YOUR_USERNAME` and `YOUR_REPO_NAME` with your actual values:

**For GitHub:**
```bash
git remote add origin https://github.com/YOUR_USERNAME/YOUR_REPO_NAME.git
git branch -M main
git push -u origin main
```

**For GitLab:**
```bash
git remote add origin https://gitlab.com/YOUR_USERNAME/YOUR_REPO_NAME.git
git branch -M main
git push -u origin main
```

**For Bitbucket:**
```bash
git remote add origin https://bitbucket.org/YOUR_USERNAME/YOUR_REPO_NAME.git
git branch -M main
git push -u origin main
```

## What's Included

- ✅ All source code
- ✅ Installation wizard
- ✅ API endpoints
- ✅ Frontend pages
- ✅ Database schema
- ✅ Documentation files

## What's Excluded (.gitignore)

- ❌ `config/database.php` (contains sensitive credentials)
- ❌ Log files
- ❌ IDE configuration files
- ❌ OS-specific files
- ❌ Temporary files

**Note**: The `config/database.php.example` file is included as a template. Users should copy it to `database.php` and configure it, or use the installer.

## Future Commits

After making changes:

```bash
git add .
git commit -m "Description of changes"
git push
```

