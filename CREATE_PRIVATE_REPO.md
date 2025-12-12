# Create Private Git Repository - Step by Step Guide

## Quick Setup (After Git Installation)

### Step 1: Complete Git Installation
If Git installation was interrupted:
1. Restart your terminal/PowerShell
2. Or manually install from: https://git-scm.com/download/win
3. Verify: `git --version`

### Step 2: Run Setup Script
```powershell
.\create-private-repo.ps1
```

This will:
- ✓ Initialize Git repository
- ✓ Add all files
- ✓ Create initial commit
- ✓ Guide you through creating private repo

---

## Manual Setup

### Step 1: Initialize Repository
```bash
git init
git add .
git commit -m "Initial commit: Property Management System with registration and login"
```

### Step 2: Create Private Repository on GitHub

**Option A: Using GitHub Website**
1. Go to: https://github.com/new
2. Repository name: `property-management` (or your choice)
3. **Select: Private** ⚠️ Important!
4. **DO NOT** check:
   - ❌ Add a README file
   - ❌ Add .gitignore
   - ❌ Choose a license
5. Click "Create repository"

**Option B: Using GitHub CLI** (if installed)
```bash
# First authenticate
gh auth login

# Create private repo and push
gh repo create property-management --private --source=. --remote=origin --push
```

### Step 3: Connect and Push
After creating the repository on GitHub, run:

```bash
# Replace YOUR_USERNAME and YOUR_REPO_NAME with your actual values
git remote add origin https://github.com/YOUR_USERNAME/YOUR_REPO_NAME.git
git branch -M main
git push -u origin main
```

You'll be prompted for your GitHub username and password (use Personal Access Token for password).

---

## Using Personal Access Token

GitHub no longer accepts passwords. Use a Personal Access Token:

1. Go to: https://github.com/settings/tokens
2. Click "Generate new token" → "Generate new token (classic)"
3. Give it a name: "Property Management Repo"
4. Select scopes: `repo` (full control of private repositories)
5. Click "Generate token"
6. Copy the token (you won't see it again!)
7. When pushing, use the token as your password

---

## Verify Repository is Private

After pushing:
1. Go to your repository on GitHub
2. Check the repository settings
3. Under "Danger Zone" → "Change repository visibility"
4. Should show "This repository is private"

---

## What Gets Pushed

✅ All source code
✅ API endpoints
✅ Frontend pages
✅ Database schema
✅ Documentation files
✅ Installation wizard
✅ Configuration templates

❌ **NOT pushed** (excluded by .gitignore):
- `config/database.php` (contains credentials)
- Log files
- IDE files
- OS files

---

## Troubleshooting

**"Git is not recognized"**
- Restart terminal after installing Git
- Or add Git to PATH manually

**"Authentication failed"**
- Use Personal Access Token instead of password
- Or use SSH keys

**"Repository not found"**
- Check repository name matches
- Verify you have access to the repository
- Ensure repository exists on GitHub

**"Permission denied"**
- Check your GitHub credentials
- Verify Personal Access Token has `repo` scope

---

## Next Steps After Pushing

1. ✅ Repository is now backed up on GitHub
2. ✅ You can clone it on other machines
3. ✅ Collaborate with others (add collaborators in repo settings)
4. ✅ Set up CI/CD if needed
5. ✅ Continue development with version control

