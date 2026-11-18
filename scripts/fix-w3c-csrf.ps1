$root='C:\Work\iq-profil\resources\views'
Get-ChildItem -Path $root -Filter '*.blade.php' -Recurse | ForEach-Object {
  $raw=[IO.File]::ReadAllText($_.FullName)
  if ($raw.Contains('@csrf')) {
    $raw=$raw.Replace('@csrf','<input type="hidden" name="_token" value="{{ csrf_token() }}">')
    [IO.File]::WriteAllText($_.FullName,$raw,[System.Text.UTF8Encoding]::new($false))
    Write-Output ('UPDATED '+$_.FullName)
  }
}
