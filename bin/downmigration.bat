@echo off
set /p ConfigName= Please enter name of configuration:
set /p MigrationVersion= Please enter number of migration:
php app.php update-to database-schema %MigrationVersion% %ConfigName%
pause