$ErrorActionPreference = 'Stop'
$root = 'C:\Work\iq-profil'
$landing = (Invoke-WebRequest -Uri 'http://127.0.0.1:8123' -UseBasicParsing).Content
$validator = 'https://validator.w3.org/nu/?out=json'
$result = Invoke-WebRequest -Uri $validator -Method Post -Body $landing -ContentType 'text/html; charset=utf-8' -UseBasicParsing -TimeoutSec 30
$result.Content | Set-Content -LiteralPath "$root\docs\w3c-landing.json" -Encoding UTF8
$report = $result.Content | ConvertFrom-Json
$errors = @($report.messages | Where-Object { $_.type -eq 'error' })
Write-Output ('W3C_MESSAGES=' + $report.messages.Count)
Write-Output ('W3C_ERRORS=' + $errors.Count)
$report.messages | Select-Object -First 12 type,subType,message | Format-List
