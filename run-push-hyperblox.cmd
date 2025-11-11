@echo off
setlocal

REM Launch the sync script from the repo root, no admin required.
REM If you have a GitHub token, set it before running:
REM    set GITHUB_TOKEN=ghp_your_token_here

set SCRIPT=%~dp0push-hyperblox.ps1
if not exist "%SCRIPT%" (
  echo Could not find push-hyperblox.ps1 next to this file.
  exit /b 1
)

REM Run PowerShell with ExecutionPolicy bypass and keep window open on exit.
powershell -NoProfile -NoExit -ExecutionPolicy Bypass -File "%SCRIPT%"

echo.
echo (A log is saved as push-hyperblox.log in this folder if something failed.)
pause

endlocal
exit /b %errorlevel%

