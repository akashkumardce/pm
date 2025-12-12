# Git Repository Setup Script
# Run this script after installing Git

Write-Host "Initializing Git repository..." -ForegroundColor Green
git init

Write-Host "Adding all files..." -ForegroundColor Green
git add .

Write-Host "Making initial commit..." -ForegroundColor Green
git commit -m "Initial commit: Property Management System with registration and login"

Write-Host "`nRepository initialized and committed!" -ForegroundColor Green
Write-Host "`nNext steps:" -ForegroundColor Yellow
Write-Host "1. Create a repository on GitHub/GitLab/Bitbucket" -ForegroundColor Cyan
Write-Host "2. Run one of these commands (replace with your repo URL):" -ForegroundColor Cyan
Write-Host "   git remote add origin https://github.com/YOUR_USERNAME/YOUR_REPO_NAME.git" -ForegroundColor White
Write-Host "   git branch -M main" -ForegroundColor White
Write-Host "   git push -u origin main" -ForegroundColor White
Write-Host "`nSee GIT_SETUP.md for detailed instructions." -ForegroundColor Yellow

