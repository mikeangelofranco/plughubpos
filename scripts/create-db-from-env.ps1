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

if (-not (Get-Command psql -ErrorAction SilentlyContinue)) {
  throw "psql not found in PATH. Install PostgreSQL client tools or add psql to PATH."
}

if (-not $dbPass) {
  $sec = Read-Host "Password for DB user '$dbUser'" -AsSecureString
  $dbPass = [Runtime.InteropServices.Marshal]::PtrToStringAuto([Runtime.InteropServices.Marshal]::SecureStringToBSTR($sec))
}

Write-Host "Creating database '$dbName' as '$dbUser' (requires CREATEDB privilege)."
$env:PGPASSWORD = $dbPass

$hasCreatedb = psql -w -h $dbHost -p $dbPort -U $dbUser -d postgres -tAc "select rolcreatedb from pg_roles where rolname = current_user;"
if ($hasCreatedb -notmatch "t") {
  throw "Role '$dbUser' does not have CREATEDB. Run scripts/init-db.ps1 (admin) or grant CREATEDB to '$dbUser'."
}

$exists = psql -w -h $dbHost -p $dbPort -U $dbUser -d postgres -tAc "select 1 from pg_database where datname = '$dbName';"
if ($exists -match "1") {
  Write-Host "Database already exists; skipping create."
} else {
  psql -w -h $dbHost -p $dbPort -U $dbUser -d postgres -v ON_ERROR_STOP=1 -c "create database \"$dbName\" owner \"$dbUser\";"
}
psql -w -h $dbHost -p $dbPort -U $dbUser -d $dbName -f (Join-Path $root "scripts/schema.sql")

Write-Host "Done."
