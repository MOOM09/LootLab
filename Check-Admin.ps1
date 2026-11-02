$ErrorActionPreference = 'Stop'
Set-Location $PSScriptRoot
$php = 'C:\xampp\php\php.exe'
if (-not (Test-Path $php)) { throw 'PHP was not found at C:\xampp\php\php.exe.' }
$required = @('vendor/autoload.php','config/config.php','app/Core/Database.php','app/Core/Response.php','app/Models/User.php','app/Middleware/AuthMiddleware.php','app/Middleware/AdminMiddleware.php')
foreach ($file in $required) { if (-not (Test-Path $file)) { throw ('Project dependency is missing: ' + $file) } }
$files = @('public/admin.php','app/Controllers/Admin/PanelController.php','app/Models/AdminPanelStore.php','app/Services/AdminPanelFiles.php','app/Views/admin/panel/index.php')
foreach ($file in $files) {
    $result = & $php -l $file 2>&1
    if ($LASTEXITCODE -ne 0) { $result | Write-Output; throw ('Syntax error: ' + $file) }
}
$modules = @(& $php -m)
if ($LASTEXITCODE -ne 0) { throw 'Could not read PHP modules.' }
foreach ($extension in @('pdo_mysql','mbstring','fileinfo','json','session')) {
    if ($modules -notcontains $extension) { throw ('Enable PHP extension ' + $extension + ' in php.ini, then restart Apache.') }
}
Write-Host 'Required PHP extensions are available.'
Write-Host 'Admin files passed syntax and dependency checks. No database, Git or account changes were made.'
Write-Host 'Sign in with an existing admin account, then open http://localhost/projects/LootLab/public/admin.php'
