#!/bin/bash
# CSS Maintenance Script for Haveli Restaurant Admin Dashboard
# This script handles CSS updates, minification, and deployment

echo "🎨 Haveli Restaurant - CSS Maintenance Tool"
echo "=========================================="

# Function to minify CSS
minify_css() {
    echo "📦 Minifying CSS..."
    
    # PowerShell command for Windows
    if command -v powershell &> /dev/null; then
        powershell -Command "(Get-Content 'admin-dashboard.css' -Raw) -replace '/\*[\s\S]*?\*/', '' -replace '\s+', ' ' -replace ';\s*}', '}' -replace '{\s*', '{' -replace ':\s*', ':' -replace ';\s*', ';' | Out-File 'admin-dashboard.min.css' -Encoding UTF8"
    else
        # Fallback for Unix/Linux systems
        sed 's/\/\*.*\*\///g' admin-dashboard.css | tr -d '\n' | sed 's/  */ /g' > admin-dashboard.min.css
    fi
    
    echo "✅ Minification complete!"
}

# Function to show file sizes
show_sizes() {
    echo "📊 File Sizes:"
    echo "--------------"
    
    if command -v powershell &> /dev/null; then
        powershell -Command "Get-ChildItem admin-dashboard*.css | Select-Object Name, @{Name='Size (KB)'; Expression={[math]::Round(\$_.Length/1KB, 2)}} | Format-Table -AutoSize"
    else
        ls -lh admin-dashboard*.css | awk '{print $9 " - " $5}'
    fi
}

# Function to validate CSS
validate_css() {
    echo "🔍 CSS Validation:"
    echo "-----------------"
    
    # Check for common issues
    if grep -q "color: #[0-9a-fA-F]\{3\}" admin-dashboard.css; then
        echo "⚠️  Warning: Found 3-digit hex colors (consider 6-digit for consistency)"
    fi
    
    if grep -q "!important" admin-dashboard.css; then
        echo "⚠️  Warning: Found !important declarations (use sparingly)"
    fi
    
    if grep -q "margin-top\|padding-top" admin-dashboard.css | head -1; then
        echo "✅ Good: Using consistent spacing properties"
    fi
    
    echo "✅ Basic validation complete"
}

# Function to backup current CSS
backup_css() {
    timestamp=$(date +"%Y%m%d_%H%M%S")
    cp admin-dashboard.css "backups/admin-dashboard_${timestamp}.css"
    echo "💾 Backup created: backups/admin-dashboard_${timestamp}.css"
}

# Function to create backup directory
setup_backups() {
    if [ ! -d "backups" ]; then
        mkdir backups
        echo "📁 Created backups directory"
    fi
}

# Main menu
main_menu() {
    echo ""
    echo "Choose an option:"
    echo "1) Minify CSS"
    echo "2) Show file sizes"
    echo "3) Validate CSS"
    echo "4) Create backup"
    echo "5) Full update (backup + minify + validate)"
    echo "6) Exit"
    echo ""
    read -p "Enter choice [1-6]: " choice
    
    case $choice in
        1)
            minify_css
            show_sizes
            ;;
        2)
            show_sizes
            ;;
        3)
            validate_css
            ;;
        4)
            setup_backups
            backup_css
            ;;
        5)
            setup_backups
            backup_css
            minify_css
            validate_css
            show_sizes
            echo "🎉 Full update complete!"
            ;;
        6)
            echo "👋 Goodbye!"
            exit 0
            ;;
        *)
            echo "❌ Invalid option. Please choose 1-6."
            main_menu
            ;;
    esac
}

# Check if we're in the right directory
if [ ! -f "admin-dashboard.css" ]; then
    echo "❌ Error: admin-dashboard.css not found in current directory"
    echo "Please run this script from the haveli project directory"
    exit 1
fi

# Run main menu
main_menu

# Ask if user wants to continue
echo ""
read -p "Do you want to perform another action? (y/n): " continue_choice
if [[ $continue_choice =~ ^[Yy]$ ]]; then
    main_menu
else
    echo "✨ CSS maintenance complete!"
fi