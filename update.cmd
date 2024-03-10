@echo off
cd %~dp0

.\bin\php.exe -c .\bin\php.ini -n .\php\console.php fetch-history

echo:
.\bin\php.exe -c .\bin\php.ini -n .\php\console.php fetch-files

echo:
.\bin\php.exe -c .\bin\php.ini -n .\php\console.php compile-html

pause
