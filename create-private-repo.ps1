# Script to create private Git repository and push to GitHub
# Prerequisites: Git must be installed and GitHub CLI (gh) or manual repo creation

Write-Host "=== Property Management System - Git Setup ===" -ForegroundColor Cyan
Write-Host ""

# Check if Git is installed
try {
    $gitVersion = git --version 2>&1
    Write-Host "✓ Git found: $gitVersion" -ForegroundColor Green
} catch {
    Write-Host "✗ Git is not installed or not in PATH" -ForegroundColor Red
    Write-Host "Please install Git from https://git-scm.com/download/win" -ForegroundColor Yellow
    Write-Host "Or run: winget install Git.Git" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "After installing, restart your terminal and run this script again." -ForegroundColor Yellow
    exit 1
}

# Initialize repository if not already initialized
if (Test-Path ".git") {
    Write-Host "✓ Git repository already initialized" -ForegroundColor Green
} else {
    Write-Host "Initializing Git repository..." -ForegroundColor Yellow
    git init
    Write-Host "✓ Repository initialized" -ForegroundColor Green
}

# Add all files
Write-Host ""
Write-Host "Adding files to repository..." -ForegroundColor Yellow
git add .

# Check if there are changes to commit
$status = git status --porcelain
if ($status) {
    Write-Host "Making initial commit..." -ForegroundColor Yellow
    git commit -m "Initial commit: Property Management System with registration and login"
    Write-Host "✓ Initial commit created" -ForegroundColor Green
} else {
    Write-Host "✓ No changes to commit (already committed)" -ForegroundColor Green
}

Write-Host ""
Write-Host "=== Next Steps ===" -ForegroundColor Cyan
Write-Host ""
Write-Host "To create a PRIVATE repository on GitHub:" -ForegroundColor Yellow
Write-Host ""
Write-Host "Option 1: Using GitHub CLI (if installed)" -ForegroundColor Cyan
Write-Host "  1. Run: gh auth login" -ForegroundColor White
Write-Host "  2. Run: gh repo create property-management --private --source=. --remote=origin --push" -ForegroundColor White
Write-Host ""
Write-Host "Option 2: Manual (Recommended)" -ForegroundColor Cyan
Write-Host "  1. Go to: https://github.com/new" -ForegroundColor White
Write-Host "  2. Repository name: property-management (or your choice)" -ForegroundColor White
Write-Host "  3. Select: Private" -ForegroundColor White
Write-Host "  4. DO NOT initialize with README, .gitignore, or license" -ForegroundColor White
Write-Host "  5. Click 'Create repository'" -ForegroundColor White
Write-Host "  6. Then run these commands:" -ForegroundColor White
Write-Host ""
Write-Host "     git remote add origin https://github.com/YOUR_USERNAME/YOUR_REPO_NAME.git" -ForegroundColor Gray
Write-Host "     git branch -M main" -ForegroundColor Gray
Write-Host "     git push -u origin main" -ForegroundColor Gray
Write-Host ""

# Check if GitHub CLI is available
try {
    $ghVersion = gh --version 2>&1
    Write-Host "✓ GitHub CLI found: $ghVersion" -ForegroundColor Green
    Write-Host ""
    Write-Host "Would you like to create the repository using GitHub CLI? (y/n)" -ForegroundColor Yellow
    $response = Read-Host
    if ($response -eq 'y' -or $response -eq 'Y') {
        Write-Host ""
        Write-Host "Creating private repository on GitHub..." -ForegroundColor Yellow
        gh repo create property-management --private --source=. --remote=origin --push
        if ($LASTEXITCODE -eq 0) {
            Write-Host ""
            Write-Host "✓ Repository created and pushed successfully!" -ForegroundColor Green
        } else {
            Write-Host ""
            Write-Host "✗ Failed to create repository. You may need to authenticate first:" -ForegroundColor Red
            Write-Host "  Run: gh auth login" -ForegroundColor Yellow
        }
    }
} catch {
    Write-Host "GitHub CLI not found. Use Option 2 (Manual) above." -ForegroundColor Yellow
}

Write-Host ""
Write-Host "Done! Your code is ready to push." -ForegroundColor Green

