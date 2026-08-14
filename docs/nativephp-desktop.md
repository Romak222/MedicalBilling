# NativePHP Desktop

## Desktop Status

NativePHP Desktop 2.2.1 is installed and registered with Laravel.

Generated files:

- `config/nativephp.php`
- `app/Providers/NativeAppServiceProvider.php`

The main desktop window opens the `dashboard` route with:

- Width: 1366
- Height: 768
- Minimum width: 1024
- Minimum height: 700
- New external windows suppressed
- Remembered window state enabled

Fresh installs redirect from the dashboard route to `/setup` until first-run setup is complete.

## Local PHP Runtime

The system PHP is XAMPP PHP 8.2.12. NativePHP requires PHP 8.3+, so Phase 2 added a project-local PHP 8.3.33 runtime under `.tools/php-8.3.33`.

Use:

```powershell
& .\.tools\php-8.3.33\php.exe artisan native:run
& .\.tools\php-8.3.33\php.exe C:\composer\composer.phar install
```

`.tools` is ignored by Git.

For build commands, prepend the project PHP folder to `PATH`. NativePHP internally runs `composer install --no-dev`, and the global Composer launcher otherwise resolves to XAMPP PHP 8.2.12.

```powershell
$env:Path = "$(Get-Location)\.tools\php-8.3.33;$env:Path"
& .\.tools\php-8.3.33\php.exe artisan native:build win x64 --no-interaction
```

## NativePHP Commands

```powershell
& .\.tools\php-8.3.33\php.exe artisan native:install
& .\.tools\php-8.3.33\php.exe artisan native:run
& .\.tools\php-8.3.33\php.exe artisan native:build win
```

## Packaging Notes

NativePHP installed `nativephp/php-bin`, a large package that contains PHP binaries used by NativePHP builds. The first install can be slow because the package archive is large.

Phase 2 build verification produced:

- Unpacked Windows app: `nativephp/electron/dist/win-unpacked/medstore-foundation.exe`
- NSIS staging archive: `nativephp/electron/dist/medstore-foundation-0.2.0-phase2-x64.nsis.7z`

The final installer `.exe` was not produced during this phase. A clean build/reset pass is still required.

NativePHP also warns that the app bundle is insecure until secure bundling is configured. Do not distribute Phase 2 builds commercially.

Production packaging still needs:

- App icon
- Signed Windows installer configuration
- Persistent app-data database path validation
- Backup-before-update workflow
- Update signing strategy
- Licence enforcement strategy
