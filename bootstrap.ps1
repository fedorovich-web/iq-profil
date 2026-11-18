$ErrorActionPreference = 'Stop'
$root = 'C:\Work\iq-profil'
New-Item -ItemType Directory -Force "$root\.tools", "$root\docs", "$root\scripts" | Out-Null
$phpDir = "$root\.tools\php"
$phpExe = "$phpDir\php.exe"
if (-not (Test-Path $phpExe)) {
  $url = 'https://downloads.php.net/~windows/releases/archives/php-8.5.12-Win32-vs17-x64.zip'
  $zip = "$root\.tools\php-8.5.12.zip"
  Write-Output "Fetching PHP 8.5.12 from $url"
  Invoke-WebRequest -Uri $url -OutFile $zip -TimeoutSec 120
  New-Item -ItemType Directory -Force $phpDir | Out-Null
  Expand-Archive -LiteralPath $zip -DestinationPath $phpDir -Force
  Remove-Item $zip
}
$ini = "$phpDir\php.ini"
if (-not (Test-Path $ini)) {
  Copy-Item "$phpDir\php.ini-development" $ini
  (Get-Content $ini -Raw) -replace ';extension_dir = "ext"', 'extension_dir = "ext"' -replace ';extension=curl', 'extension=curl' -replace ';extension=fileinfo', 'extension=fileinfo' -replace ';extension=mbstring', 'extension=mbstring' -replace ';extension=openssl', 'extension=openssl' -replace ';extension=pdo_sqlite', 'extension=pdo_sqlite' -replace ';extension=sqlite3', 'extension=sqlite3' -replace ';extension=zip', 'extension=zip' -replace ';extension=intl', 'extension=intl' | Set-Content -LiteralPath $ini -Encoding ascii
}
$composer = "$root\.tools\composer.phar"
if (-not (Test-Path $composer)) {
  Invoke-WebRequest -Uri 'https://getcomposer.org/composer-stable.phar' -OutFile $composer -TimeoutSec 120
}
& $phpExe -v
& $phpExe $composer --version
Write-Output 'BOOTSTRAP_OK'

