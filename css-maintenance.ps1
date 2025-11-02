# CSS Maintenance Script for Haveli Restaurant Admin Dashboard
# PowerShell version for Windows systems

Write-Host "CSS Haveli Restaurant - CSS Maintenance Tool" -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan

# Function to minify CSS
function Minify-CSS {
    Write-Host "Minifying CSS..." -ForegroundColor Yellow
    
    try {
        $content = (Get-Content 'admin-dashboard.css' -Raw)
        $minified = $content -replace '/\*[\s\S]*?\*/', '' -replace '\s+', ' ' -replace ';\s*}', '}' -replace '{\s*', '{' -replace ':\s*', ':' -replace ';\s*', ';'
        $minified | Out-File 'admin-dashboard.min.css' -Encoding UTF8
        
        Write-Host "Minification complete!" -ForegroundColor Green
    }
    catch {
        Write-Host "Error during minification: $_" -ForegroundColor Red
    }
}

# Function to show file sizes
function Show-Sizes {
    Write-Host "File Sizes:" -ForegroundColor Blue
    Write-Host "--------------" -ForegroundColor Blue
    
    Get-ChildItem admin-dashboard*.css | Select-Object Name, @{Name="Size (KB)"; Expression={[math]::Round($_.Length/1KB, 2)}} | Format-Table -AutoSize
}

# Function to validate CSS
function Validate-CSS {
    Write-Host "CSS Validation:" -ForegroundColor Blue
    Write-Host "-----------------" -ForegroundColor Blue
    
    $cssContent = Get-Content 'admin-dashboard.css' -Raw
    
    if ($cssContent -match ":root") {
        Write-Host "Good: Using CSS custom properties" -ForegroundColor Green
    }
    
    if ($cssContent -match "@media") {
        Write-Host "Good: Responsive design implemented" -ForegroundColor Green
    }
    
    Write-Host "Basic validation complete" -ForegroundColor Green
}

# Check if we're in the right directory
if (-not (Test-Path "admin-dashboard.css")) {
    Write-Host "Error: admin-dashboard.css not found in current directory" -ForegroundColor Red
    Write-Host "Please run this script from the haveli project directory" -ForegroundColor Red
    exit
}

Write-Host ""
Write-Host "Choose an option:" -ForegroundColor White
Write-Host "1) Minify CSS" -ForegroundColor Gray
Write-Host "2) Show file sizes" -ForegroundColor Gray  
Write-Host "3) Validate CSS" -ForegroundColor Gray
Write-Host "4) Full maintenance" -ForegroundColor Gray
Write-Host ""

$choice = Read-Host "Enter choice [1-4]"

switch ($choice) {
    "1" {
        Minify-CSS
        Show-Sizes
    }
    "2" {
        Show-Sizes
    }
    "3" {
        Validate-CSS
    }
    "4" {
        Write-Host "Running full maintenance..." -ForegroundColor Cyan
        Minify-CSS
        Validate-CSS
        Show-Sizes
        Write-Host "Full maintenance complete!" -ForegroundColor Green
    }
    default {
        Write-Host "Invalid option. Please choose 1-4." -ForegroundColor Red
    }
}

Write-Host "CSS maintenance complete!" -ForegroundColor Green