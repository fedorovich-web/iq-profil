$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.IO.Compression.FileSystem
$root = 'C:\Work\iq-profil'
$in = Join-Path $root '.incoming'
$public = Join-Path $root 'public'
$dest = Join-Path $public 'assets'
New-Item -ItemType Directory -Force $in, $dest | Out-Null

function ExtractAllowed([string]$archivePath, [string[]]$names, [string]$destination) {
    if (-not (Test-Path -LiteralPath $archivePath)) {
        Write-Output "Not found: $archivePath"
        return
    }
    $zip = [System.IO.Compression.ZipFile]::OpenRead($archivePath)
    try {
        foreach ($entry in $zip.Entries) {
            if ($names -ccontains $entry.FullName) {
                $target = Join-Path $destination $entry.Name
                [System.IO.Compression.ZipFileExtensions]::ExtractToFile($entry, $target, $true)
                Write-Output "Imported: $($entry.Name)"
            }
        }
    } finally { $zip.Dispose() }
}

$avif = @(
    'benefit-science.avif', 'benefit-format.avif', 'benefit-results.avif',
    'test-iq.avif', 'test-logic.avif', 'test-memory.avif',
    'step-select.avif', 'step-complete.avif', 'step-results.avif',
    'iq_profile_hero_logic_matrix_desktop.avif',
    'iq_profile_hero_logic_matrix_mobile.avif'
)
ExtractAllowed (Join-Path $in 'IQ_Profil_Assets_256_AVIF85(2).zip') $avif $dest
ExtractAllowed (Join-Path $in 'favicon(5).zip') @(
    'favicon.ico','favicon-96x96.png','apple-touch-icon.png',
    'web-app-manifest-192x192.png','web-app-manifest-512x512.png','site.webmanifest'
) $public

$logo = Join-Path $in 'logo(3).jpg'
if (Test-Path $logo) { Copy-Item $logo (Join-Path $dest 'logo.jpg') -Force }

$have = @(Get-ChildItem -Path $dest -Filter '*.avif' -File -ErrorAction SilentlyContinue).Count
Write-Output "AVIF asset count: $have / 11"
if ($have -ne 11) { Write-Warning "Original AVIF assets are not all installed." }
