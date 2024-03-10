@echo off
"%~dp0bin\php.exe" -c "%~dp0bin\php.ini" -n "%~dp0php\console.php" "fetch-history"
"%~dp0bin\php.exe" -c "%~dp0bin\php.ini" -n "%~dp0php\console.php" "fetch-files"
"%~dp0bin\php.exe" -c "%~dp0bin\php.ini" -n "%~dp0php\console.php" "compile-html"
@pause
