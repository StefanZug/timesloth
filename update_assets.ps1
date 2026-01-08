Write-Host "🦥 TimeSloth Asset Updater (Smart Edition)" -ForegroundColor Green

# 1. Package.json lesen
if (-not (Test-Path "package.json")) {
    Write-Host "❌ Keine package.json gefunden!" -ForegroundColor Red
    exit 1
}
$pkg = Get-Content "package.json" -Raw | ConvertFrom-Json
$deps = $pkg.dependencies

Write-Host "Lese Versionen aus package.json..." -ForegroundColor Gray
Write-Host " - Vue: $($deps.vue)"
Write-Host " - Bootstrap: $($deps.bootstrap)"

# 2. Ziel-Verzeichnisse
$VendorDir = "app/public/static/vendor"
$JsDir = "$VendorDir/js"
$CssDir = "$VendorDir/css"
$FontDir = "$VendorDir/fonts"

New-Item -ItemType Directory -Force -Path $JsDir | Out-Null
New-Item -ItemType Directory -Force -Path $CssDir | Out-Null
New-Item -ItemType Directory -Force -Path $FontDir | Out-Null

# 3. Download Funktion
function Download-File {
    param ($Url, $Dest)
    $FileName = Split-Path $Dest -Leaf
    Write-Host "Downloading $FileName..." -NoNewline
    try {
        # Versionsnummern bereinigen (z.B. "^3.4.0" -> "3.4.0")
        $CleanUrl = $Url -replace '\^', '' 
        Invoke-WebRequest -Uri $CleanUrl -OutFile $Dest
        Write-Host " OK" -ForegroundColor Green
    } catch {
        Write-Host " FEHLER" -ForegroundColor Red
        Write-Host $_
        exit 1
    }
}

# 4. Downloads (Dynamisch basierend auf package.json)

# Vue
Download-File "https://cdn.jsdelivr.net/npm/vue@$($deps.vue)/dist/vue.global.prod.js" "$JsDir/vue.js"

# Axios
Download-File "https://cdn.jsdelivr.net/npm/axios@$($deps.axios)/dist/axios.min.js" "$JsDir/axios.js"

# Bootstrap
Download-File "https://cdn.jsdelivr.net/npm/bootstrap@$($deps.bootstrap)/dist/js/bootstrap.bundle.min.js" "$JsDir/bootstrap.js"
Download-File "https://cdn.jsdelivr.net/npm/bootstrap@$($deps.bootstrap)/dist/css/bootstrap.min.css" "$CssDir/bootstrap.css"

# Bootstrap Icons (Achtung: Eigener Key in package.json nötig, json key darf kein Bindestrich haben, wir mappen es)
# Zugriff auf Property mit Bindestrich in PowerShell:
$bsIconVer = $deps.'bootstrap-icons'
Download-File "https://cdn.jsdelivr.net/npm/bootstrap-icons@$bsIconVer/font/bootstrap-icons.min.css" "$CssDir/bootstrap-icons.css"
Download-File "https://cdn.jsdelivr.net/npm/bootstrap-icons@$bsIconVer/font/fonts/bootstrap-icons.woff2" "$FontDir/bootstrap-icons.woff2"
Download-File "https://cdn.jsdelivr.net/npm/bootstrap-icons@$bsIconVer/font/fonts/bootstrap-icons.woff" "$FontDir/bootstrap-icons.woff"

# Marked & Purify
Download-File "https://cdn.jsdelivr.net/npm/marked@$($deps.marked)/marked.min.js" "$JsDir/marked.min.js"
Download-File "https://cdn.jsdelivr.net/npm/dompurify@$($deps.dompurify)/dist/purify.min.js" "$JsDir/purify.min.js"

Write-Host "`n✅ Assets aktualisiert. Jetzt commiten & pushen!" -ForegroundColor Cyan