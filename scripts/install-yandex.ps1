$ErrorActionPreference='Stop'
$root='C:\Work\iq-profil'
$env:PATH="$root\.tools\php;"+[Environment]::GetEnvironmentVariable('PATH','Process')
Set-Location $root
& "$root\.tools\php\php.exe" "$root\.tools\composer.phar" require socialiteproviders/yandex --no-interaction --no-progress
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
Write-Output 'SOCIALITE_INSTALLED'
