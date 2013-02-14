@echo off
set /p ConfigName= Please enter name of configuration:
php app.php update database-schema -e %ConfigName%
pause