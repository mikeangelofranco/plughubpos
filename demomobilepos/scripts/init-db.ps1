Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

function Get-EnvFromDotenv([string]$Path) {
  $map = @{}
  if (-not (Test-Path $Path)) { return $map }
  Get-Content $Path | ForEach-Object {
    $line = $_.Trim()
    if ($line.Length -eq 0) { return }
    if ($line.StartsWith("#")) { return }
    $eq = $line.IndexOf("=")
    if ($eq -lt 1) { return }
    $key = $line.Substring(0, $eq).Trim()
    $val = $line.Substring($eq + 1).Trim()
    if (($val.StartsWith("'") -and $val.EndsWith("'")) -or ($val.StartsWith('"') -and $val.EndsWith('"'))) {
      $val = $val.Substring(1, $val.Length - 2)
    }
    if (-not $map.ContainsKey($key)) { $map[$key] = $val }
  }
  return $map
}

$root = Split-Path -Parent $PSScriptRoot
$dotenv = Get-EnvFromDotenv (Join-Path $root ".env")

function Pick([string]$key, [string]$default) {
  $item = Get-Item -Path "Env:$key" -ErrorAction SilentlyContinue
  $envVal = if ($null -ne $item) { $item.Value } else { $null }
  if ($null -ne $envVal -and $envVal -ne "") { return $envVal }
  if ($dotenv.ContainsKey($key) -and $dotenv[$key]) { return $dotenv[$key] }
  return $default
}

$dbHost = Pick "DB_HOST" "127.0.0.1"
$dbPort = Pick "DB_PORT" "5432"
$dbName = Pick "DB_NAME" "plughub_possystem"
$dbUser = Pick "DB_USER" "plughub"
$dbPass = Pick "DB_PASSWORD" ""
$adminUser = Pick "DB_ADMIN_USER" "postgres"
$adminPass = Pick "DB_ADMIN_PASSWORD" ""

if (-not (Get-Command psql -ErrorAction SilentlyContinue)) {
  throw "psql not found in PATH. Install PostgreSQL client tools or add psql to PATH."
}

Write-Host "Target DB: $dbName (owner: $dbUser) at ${dbHost}:$dbPort"

Write-Host "Checking if database exists..."
$env:PGPASSWORD = $dbPass
$exists = $false
try {
  $out = psql -w -h $dbHost -p $dbPort -U $dbUser -d postgres -tAc "select 1 from pg_database where datname = '$dbName';"
  if ($out -match "1") { $exists = $true }
} catch {
  $exists = $false
}

if (-not $exists) {
  Write-Host "Database not found; creating it (requires admin)..."
  if (-not $adminPass) {
    $sec = Read-Host "Postgres admin password for '$adminUser'" -AsSecureString
    $adminPass = [Runtime.InteropServices.Marshal]::PtrToStringAuto([Runtime.InteropServices.Marshal]::SecureStringToBSTR($sec))
  }
  $env:PGPASSWORD = $adminPass
  psql -w -h $dbHost -p $dbPort -U $adminUser -d postgres -f (Join-Path $root "scripts/create_db.psql")
}

Write-Host "Applying schema..."
$env:PGPASSWORD = $dbPass
psql -w -h $dbHost -p $dbPort -U $dbUser -d $dbName -f (Join-Path $root "scripts/schema.sql")

Write-Host "Done."
