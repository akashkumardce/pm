@echo off
echo Initializing Git repository...
git init

echo Adding all files...
git add .

echo Making initial commit...
git commit -m "Initial commit: Property Management System with registration and login"

echo.
echo Repository initialized and committed!
echo.
echo Next steps:
echo 1. Create a repository on GitHub/GitLab/Bitbucket
echo 2. Run one of these commands (replace with your repo URL):
echo    git remote add origin https://github.com/YOUR_USERNAME/YOUR_REPO_NAME.git
echo    git branch -M main
echo    git push -u origin main
echo.
echo See GIT_SETUP.md for detailed instructions.
pause

