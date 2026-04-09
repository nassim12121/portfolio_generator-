@echo off
cd /d %~dp0

set "PHP_BIN=php"
%PHP_BIN% -v >nul 2>&1
if errorlevel 1 (
  set "PHP_BIN=%LOCALAPPDATA%\Microsoft\WinGet\Packages\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe"
)

"%PHP_BIN%" -v >nul 2>&1
if errorlevel 1 (
  echo PHP n'est pas installe ou introuvable.
  echo Installe PHP via: winget install --id PHP.PHP.8.3 --source winget
  pause
  exit /b 1
)

echo Demarrage du serveur PHP sur http://localhost:8000
echo Pour arreter: ferme cette fenetre.
"%PHP_BIN%" -S localhost:8000
