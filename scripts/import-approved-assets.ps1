# Imports only user-approved visual assets from the supplied archive.
# Run from Windows, after saving IQ_Profile_Approved_Assets.zip in Downloads.
$ErrorActionPreference = 'Stop'
$root = 'C:\Work\iq-profil'
$archive = Get-ChildItem (Join-Path $env:USERPROFILE 'Downloads') -File -Filter 'IQ_Profile_Approved_Assets*.zip' -ErrorAction SilentlyContinue |
    Sort-Object LastWriteTime -Descending | Select-Object -First 1
if (-not $archive) { throw 'IQ_Profile_Approved_Assets.zip not found in Downloads' }
Add-Type -AssemblyName System.IO.Compression.FileSystem
$allowedRoot = [IO.Path]::GetFullPath((Join-Path $root 'public')) + [IO.Path]::DirectorySeparatorChar
$zip = [IO.Compression.ZipFile]::OpenRead($archive.FullName)
$count=0
try {
    foreach ($entry in $zip.Entries) {
        if ([string]::IsNullOrEmpty($entry.Name)) { continue }
        $relative = $entry.FullName -replace '/', [IO.Path]::DirectorySeparatorChar
        if (-not $relative.StartsWith('public\')) { throw "Unexpected ZIP entry: $relative" }
        $target = [IO.Path]::GetFullPath((Join-Path $root $relative))
        if (-not $target.StartsWith($allowedRoot,[System.StringComparison]::OrdinalIgnoreCase)) {
            throw "Unsafe archive entry: $relative"
        }
        $directory = [IO.Path]::GetDirectoryName($target)
        [IO.Directory]::CreateDirectory($directory) | Out-Null
        [IO.Compression.ZipFileExtensions]::ExtractToFile($entry, $target, $true)
        $count++
    }
} finally { $zip.Dispose() }
Write-Output "Imported $count original assets from $($archive.Name)"
Write-Output 'Verify: http://127.0.0.1:8123/assets/brand-original.png'
