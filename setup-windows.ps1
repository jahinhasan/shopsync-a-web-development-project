# ============================================================
# ShopSync - Windows One-Command Setup
# Run: double-click setup-windows.bat  (or run this file with PowerShell)
#
# Ki kore:
#   1. PHP / Composer / Node.js khuje (na thakle winget diye install)
#   2. PHP extension gulo on kore (pdo_sqlite, mbstring, openssl...)
#   3. composer install + npm install
#   4. Fresh .env (SQLite) + APP_KEY
#   5. migrate + demo data seed
#   6. npm run build
#   7. php artisan serve  ->  http://127.0.0.1:8000
# ============================================================

$ProjectRoot = $PSScriptRoot
Push-Location $ProjectRoot

function Write-Step($msg)   { Write-Host "`n==> $msg" -ForegroundColor Cyan }
function Write-Ok($msg)     { Write-Host "    [OK] $msg" -ForegroundColor Green }
function Write-Warn2($msg)  { Write-Host "    [!] $msg" -ForegroundColor Yellow }
function Write-Fail($msg)   { Write-Host "`n    [X] $msg" -ForegroundColor Red }

function Add-SessionPath($dir) {
    if ($dir -and (Test-Path $dir) -and (-not $env:Path.Contains($dir))) {
        $env:Path = "$dir;$env:Path"
    }
}

function Test-Command($name) {
    return [bool](Get-Command $name -ErrorAction SilentlyContinue)
}

Write-Host "==============================================" -ForegroundColor Magenta
Write-Host "  ShopSync Windows Setup"                       -ForegroundColor Magenta
Write-Host "  Project: $ProjectRoot"                        -ForegroundColor Magenta
Write-Host "==============================================" -ForegroundColor Magenta

# ------------------------------------------------------------
# STEP 1: PHP khuje
# ------------------------------------------------------------
Write-Step "Step 1/8: PHP khuja hocche..."

$phpExe = $null
if (Test-Command 'php') {
    $phpExe = (Get-Command 'php').Source
} else {
    # Common install location gulo check kori
    $candidates = @(
        'C:\xampp\php\php.exe',
        'C:\php\php.exe',
        'C:\php8\php.exe',
        "$env:LOCALAPPDATA\Programs\php\php.exe"
    )
    # Winget PHP install hole er moddhe-o thakte pare
    $wgDir = "$env:LOCALAPPDATA\Microsoft\WinGet\Packages"
    if (Test-Path $wgDir) {
        $found = Get-ChildItem $wgDir -Recurse -Filter 'php.exe' -ErrorAction SilentlyContinue | Select-Object -First 1
        if ($found) { $candidates += $found.FullName }
    }
    foreach ($c in $candidates) {
        if (Test-Path $c) { $phpExe = $c; break }
    }
}

if (-not $phpExe) {
    Write-Fail "PHP khuje pay nai."
    Write-Host @"

  PHP install korte ekta kaj koro:
    1. https://windows.php.net/download/ theke PHP 8.3/8.4 'Non Thread Safe' zip download koro
    2. Zip ta extract koro C:\php folder e (vitor e sob php.dll thakbe)
    3. Ei script ta abar run koro

  (BA alternative: XAMPP install koro - https://www.apachefriends.org)
"@ -ForegroundColor Yellow
    Pop-Location
    exit 1
}
$phpDir = Split-Path $phpExe -Parent
Add-SessionPath $phpDir
Write-Ok "PHP: $phpExe"

# PHP version check (8.3+ lagbe)
$phpVersion = [string](& $phpExe -r "echo PHP_VERSION;")
try {
    $versionOk = ([version]$phpVersion -ge [version]'8.3')
} catch {
    $versionOk = $true   # version parse kora na gele Composer-e-i thik korbe
}
if (-not $versionOk) {
    Write-Fail "PHP 8.3+ lagbe, apnar version: $phpVersion"
    Write-Host "      https://windows.php.net/download/ theke notun version nin." -ForegroundColor Yellow
    Pop-Location
    exit 1
}
Write-Ok "PHP version: $phpVersion"

# ------------------------------------------------------------
# STEP 2: PHP extension gulo on kori
# ------------------------------------------------------------
Write-Step "Step 2/8: PHP extension check hocche..."

$requiredModules = @('openssl','pdo_sqlite','sqlite3','mbstring','tokenizer','xml','fileinfo','ctype','curl','zip','filter','session','iconv','dom')

function Get-LoadedModules($php) {
    return (& $php -m) | ForEach-Object { $_.Trim().ToLower() }
}

# php.ini khuje - na thakle php.ini-development theke toiri
$iniOutput = [string[]](& $phpExe --ini)
$loadedLine = $iniOutput | Select-String -Pattern 'Loaded Configuration File:\s*(\S+)'
$iniPath = $null
if ($loadedLine) { $iniPath = $loadedLine.Matches[0].Groups[1].Value.Trim() }

if (-not $iniPath -or $iniPath -eq '(none)') {
    $defaultIni = Join-Path $phpDir 'php.ini-development'
    $iniPath = Join-Path $phpDir 'php.ini'
    if (Test-Path $defaultIni) {
        try {
            Copy-Item $defaultIni $iniPath -Force
            Write-Warn2 "php.ini chilo na - php.ini-development theke copy korlam: $iniPath"
        } catch {
            Write-Warn2 "php.ini copy kora gelo na ($($_.Exception.Message)) - default settings cholbe."
            $iniPath = $null
        }
    } else {
        Write-Warn2 "php.ini na thakar karone extension auto-fix skip korlam."
        $iniPath = $null
    }
}

if ($iniPath -and (Test-Path $iniPath)) {
    $loaded = Get-LoadedModules $phpExe
    $missing = @($requiredModules | Where-Object { $loaded -notcontains $_ })

    if ($missing.Count -gt 0) {
        Write-Warn2 "Extension missing: $($missing -join ', ') - php.ini te on korchi..."

        # extension_dir set ache kina dekhi
        $iniContent = Get-Content $iniPath -Raw
        if ($iniContent -notmatch '(?m)^extension_dir\s*=') {
            Add-Content -Path $iniPath -Value 'extension_dir = "ext"'
        }

        # Jei extension er dll ache sudhu sheigulo on kori
        foreach ($m in $missing) {
            $dll = Join-Path $phpDir "ext\php_$m.dll"
            if (Test-Path $dll) {
                Add-Content -Path $iniPath -Value "extension=$m"
            } else {
                Write-Warn2 "php_$m.dll pai nai - ei extension chara hobe na hole pore dekhbe."
            }
        }

        # Abar check
        $loaded2 = Get-LoadedModules $phpExe
        $stillMissing = @($requiredModules | Where-Object { $loaded2 -notcontains $_ })
        if ($stillMissing.Count -gt 0) {
            Write-Fail "Ei extension gulo ekhono missing: $($stillMissing -join ', ')"
            Write-Host "      Manually $iniPath file khule ei line gulo add/uncomment koro:" -ForegroundColor Yellow
            Write-Host "      $($stillMissing | ForEach-Object { "extension=$_" })" -ForegroundColor Yellow
            Pop-Location
            exit 1
        }
    }
    Write-Ok "Sob required PHP extension loaded."
} else {
    Write-Warn2 "php.ini na thakar karone extension check skip korlam - problem hole notun kore install korte hobe."
}

# ------------------------------------------------------------
# STEP 3: Composer khuje
# ------------------------------------------------------------
Write-Step "Step 3/8: Composer khuja hocche..."

$composerCmd = $null
if (Test-Command 'composer') {
    $composerCmd = 'composer'
} else {
    if (Test-Command 'winget') {
        Write-Warn2 "Composer nai - winget diye install korchi (koyek minute legete pare)..."
        try {
            & winget install -e --id Composer.Composer --accept-source-agreements --accept-package-agreements --silent | Out-Null
        } catch {
            Write-Warn2 "winget install kaj kore nai."
        }
    }
    Add-SessionPath "$env:ProgramData\ComposerSetup\bin"
    if (Test-Command 'composer') {
        $composerCmd = 'composer'
    } else {
        # winget install path theke composer.phar khuje dekhi
        $phar = Get-ChildItem "$env:LOCALAPPDATA\Microsoft\WinGet\Packages" -Recurse -Filter 'composer.phar' -ErrorAction SilentlyContinue | Select-Object -First 1
        if ($phar) {
            $composerCmd = "& `"$($phar.FullName)`""
        }
    }
}

if (-not $composerCmd) {
    Write-Fail "Composer install kora gelo na."
    Write-Host "      Manually install koro: https://getcomposer.org/download/" -ForegroundColor Yellow
    Pop-Location
    exit 1
}
Write-Ok "Composer peye gechi."

# ------------------------------------------------------------
# STEP 4: Node.js + npm khuje
# ------------------------------------------------------------
Write-Step "Step 4/8: Node.js / npm khuja hocche..."

if (-not (Test-Command 'npm')) {
    if (Test-Command 'winget') {
        Write-Warn2 "npm nai - winget diye Node.js LTS install korchi (koyek minute legete pare)..."
        try {
            & winget install -e --id OpenJS.NodeJS.LTS --accept-source-agreements --accept-package-agreements --silent | Out-Null
        } catch {
            Write-Warn2 "winget install kaj kore nai."
        }
    }
    Add-SessionPath "$env:ProgramFiles\nodejs"
}

if (-not (Test-Command 'npm')) {
    Write-Fail "Node.js/npm install kora gelo na."
    Write-Host "      Manually install koro: https://nodejs.org (LTS version), tarpor terminal RESTART kore abar run koro." -ForegroundColor Yellow
    Pop-Location
    exit 1
}
$nodeVer = [string](& node -v)
$npmVer  = [string](& npm -v)
Write-Ok "Node: $nodeVer  |  npm: $npmVer"

# ------------------------------------------------------------
# STEP 5: PHP dependency (composer install)
# ------------------------------------------------------------
Write-Step "Step 5/8: composer install cholche (internet lagbe, 3-10 minute)..."

if ($composerCmd -eq 'composer') {
    $composerOutput = & composer install --no-interaction --no-progress 2>&1
} else {
    # composer.phar winget package er moddhe thakle php diye directly chalai
    $pharPath = $composerCmd -replace '^&\s*"', '' -replace '"$', ''
    Write-Warn2 "composer.phar khuje peyechi: $pharPath"
    $composerOutput = & $phpExe $pharPath install --no-interaction --no-progress 2>&1
}
$composerOutput | ForEach-Object { Write-Host "    $_" -ForegroundColor DarkGray }
if ($LASTEXITCODE -ne 0) {
    Write-Fail "composer install fail korlo. Upar er error message ta dekho."
    Pop-Location
    exit 1
}
Write-Ok "PHP dependency install hoyeche (vendor/)."

# ------------------------------------------------------------
# STEP 6: .env toiri + database + demo data
# ------------------------------------------------------------
Write-Step "Step 6/8: .env + database setup hocche..."

if (Test-Path '.env') {
    $backup = ".env.backup-$(Get-Date -Format 'yyyyMMdd-HHmmss')"
    Copy-Item '.env' $backup
    Write-Warn2 "Ager .env ta backup korlam: $backup"
}

# Fresh local demo .env likhi (SQLite - database install lagbe na)
$envContent = @'
APP_NAME=ShopSync
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=sqlite

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=database

MAIL_MAILER=log
MAIL_SCHEME=null
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

VITE_APP_NAME="${APP_NAME}"

# ---- ShopSync local demo ----
# Real-time chat on korte: nicher BROADCAST_CONNECTION log theke reverb kore
# notun terminal e `php artisan reverb:start` chalao (WINDOWS_SETUP_BANGLA.md dekho)
BROADCAST_CONNECTION=log
REVERB_APP_ID=shopsync-local
REVERB_APP_KEY=shopsync-local-key
REVERB_APP_SECRET=shopsync-local-secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
VITE_REVERB_APP_KEY=shopsync-local-key
VITE_REVERB_HOST=localhost
VITE_REVERB_PORT=8080
VITE_REVERB_SCHEME=http

SHOPSYNC_BKASH_NUMBER=01700000000
SHOPSYNC_NAGAD_NUMBER=01800000000
'@
Set-Content -Path '.env' -Value $envContent -Encoding UTF8
Write-Ok ".env toiri holo (SQLite database, kono database install lagbe na)."

# SQLite file toiri
$sqliteFile = Join-Path $ProjectRoot 'database\database.sqlite'
if (-not (Test-Path $sqliteFile)) {
    New-Item -ItemType File -Path $sqliteFile | Out-Null
}

& $phpExe artisan key:generate --force | Out-Null
if ($LASTEXITCODE -ne 0) { Write-Fail "key:generate fail korlo."; Pop-Location; exit 1 }
Write-Ok "APP_KEY generate holo."

& $phpExe artisan migrate --force | Out-Null
if ($LASTEXITCODE -ne 0) { Write-Fail "migrate fail korlo."; Pop-Location; exit 1 }
Write-Ok "Database table gulo toiri holo."

Write-Host "    Demo data seed hocche (product image download o hoy - internet lagbe)..." -ForegroundColor Gray
$seedOutput = & $phpExe artisan shopsync:seed-demo --force 2>&1
if ($LASTEXITCODE -ne 0) {
    Write-Warn2 "Image soho seed fail korlo - image chara abar try korchi..."
    $seedOutput = & $phpExe artisan shopsync:seed-demo --force --skip-images 2>&1
    if ($LASTEXITCODE -ne 0) {
        Write-Warn2 "Seed holo na (internet na thakle hoy na). App to cholbe - tarpor chalate parba:"
        Write-Host "      php artisan shopsync:seed-demo --force" -ForegroundColor Yellow
    } else {
        Write-Ok "Demo data seed holo (image chara)."
    }
} else {
    Write-Ok "Demo data seed holo (sob account, product, order)."
}

# ------------------------------------------------------------
# STEP 7: Frontend build
# ------------------------------------------------------------
Write-Step "Step 7/8: npm install + build cholche..."

& npm install --no-fund --no-audit
if ($LASTEXITCODE -ne 0) { Write-Fail "npm install fail korlo."; Pop-Location; exit 1 }

& npm run build
if ($LASTEXITCODE -ne 0) { Write-Fail "npm run build fail korlo."; Pop-Location; exit 1 }
Write-Ok "Frontend assets build holo (public/build/)."

# ------------------------------------------------------------
# STEP 8: storage link + server start
# ------------------------------------------------------------
Write-Step "Step 8/8: Final setup + server start..."
if (-not (Test-Path (Join-Path $ProjectRoot 'public\storage'))) {
    & $phpExe artisan storage:link | Out-Null
}
& $phpExe artisan config:clear | Out-Null

Write-Host ""
Write-Host "==============================================" -ForegroundColor Green
Write-Host "  Setup SOMPURNO!"                              -ForegroundColor Green
Write-Host "==============================================" -ForegroundColor Green
Write-Host ""
Write-Host "  Browser e jan:  http://127.0.0.1:8000"        -ForegroundColor White
Write-Host ""
Write-Host "  Demo login (password sobai: password):"       -ForegroundColor White
Write-Host "    Admin:          admin@shopsync.test"        -ForegroundColor Gray
Write-Host "    Business Owner: dataset.owner@shopsync.test" -ForegroundColor Gray
Write-Host "    Customer:       customer01@shopsync.test"   -ForegroundColor Gray
Write-Host ""
Write-Host "  Server bandh korte: Ctrl + C"                 -ForegroundColor White
Write-Host ""

& $phpExe artisan serve
Pop-Location
