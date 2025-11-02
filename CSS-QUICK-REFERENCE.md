# 🎨 CSS Quick Reference Card

## Haveli Restaurant Admin Dashboard - CSS Implementation

### 📁 File Structure
```
admin-dashboard.css         29.35 KB (Development)
admin-dashboard.min.css     20.56 KB (Production)
CSS-ARCHITECTURE.md         Technical Documentation
README-CSS.md              Implementation Guide
css-maintenance.ps1        Windows Maintenance Tool
```

### 🎯 Quick Commands

#### Minify CSS
```powershell
.\css-maintenance.ps1
# Choose option 1
```

#### Check File Sizes
```powershell
Get-ChildItem admin-dashboard*.css | Select Name, @{N="Size (KB)"; E={[math]::Round($_.Length/1KB, 2)}}
```

#### Validate CSS
```powershell
.\css-maintenance.ps1
# Choose option 3
```

### 🎨 Design Tokens (Most Used)

#### Colors
```css
--primary-color: #667eea
--secondary-color: #764ba2  
--success-color: #10b981
--warning-color: #f59e0b
--error-color: #ef4444
--gray-900: #0f172a (dark text)
--gray-600: #475569 (medium text)
--gray-200: #e2e8f0 (borders)
```

#### Typography
```css
--font-size-sm: 0.875rem    (14px)
--font-size-base: 1rem      (16px)
--font-size-lg: 1.125rem    (18px)
--font-size-xl: 1.25rem     (20px)
--font-size-2xl: 1.5rem     (24px)
--font-weight-medium: 500
--font-weight-semibold: 600
--font-weight-bold: 700
```

#### Spacing
```css
--space-2: 0.5rem    (8px)
--space-4: 1rem      (16px)
--space-6: 1.5rem    (24px)
--space-8: 2rem      (32px)
```

### 🧩 Component Classes

#### Buttons
```css
.btn                 /* Base button */
.btn-primary         /* Primary action */
.btn-success         /* Success action */
.btn-warning         /* Warning action */
.btn-danger          /* Danger action */
```

#### Cards
```css
.card               /* Base card */
.card-header        /* Card header */
.card-body          /* Card content */
.stat-card          /* Statistics card */
```

#### Tables
```css
.table-container    /* Responsive wrapper */
.responsive-table   /* Mobile-friendly table */
```

#### Status Badges  
```css
.status-badge       /* Base badge */
.status-pending     /* Yellow - pending */
.status-confirmed   /* Green - confirmed */
.status-completed   /* Blue - completed */
.status-cancelled   /* Red - cancelled */
```

### 📱 Responsive Breakpoints
```css
/* Base: Mobile-first (320px+) */
@media (max-width: 768px)  /* Tablet & Mobile */
@media (max-width: 480px)  /* Small Mobile */
```

### 🔧 Utility Classes

#### Spacing
```css
.m-0, .mt-4, .mb-4, .ml-4, .mr-4  /* Margins */
.p-0, .pt-4, .pb-4, .pl-4, .pr-4  /* Padding */
```

#### Text
```css
.text-center, .text-left, .text-right
.text-xs, .text-sm, .text-base, .text-lg
.font-medium, .font-semibold, .font-bold
```

#### Display
```css
.d-none, .d-block, .d-flex, .d-grid
.justify-center, .align-center
```

### 🎭 Animations
```css
--transition-fast: 150ms ease-in-out
--transition-normal: 250ms ease-in-out
--transition-slow: 350ms ease-in-out
```

### 💡 Common Patterns

#### Glassmorphism Card
```css
background: rgba(255, 255, 255, 0.95);
backdrop-filter: blur(12px);
border-radius: var(--radius-2xl);
box-shadow: var(--shadow-lg);
```

#### Gradient Button
```css
background: var(--primary-gradient);
color: var(--white);
transition: all var(--transition-fast);
```

#### Mobile-First Media Query
```css
/* Mobile base styles */
.component { ... }

@media (max-width: 768px) {
  .component { /* Mobile adjustments */ }
}
```

### ⚡ Performance Tips

1. **Use CSS Custom Properties** for consistent theming
2. **Prefer transforms** over layout-changing properties
3. **Use the minified version** in production
4. **Leverage hardware acceleration** with transform3d
5. **Keep selectors shallow** (avoid deep nesting)

### 🔍 Debugging

#### Inspect Design Tokens
```javascript
// In browser console
getComputedStyle(document.documentElement).getPropertyValue('--primary-color')
```

#### Check Responsive Breakpoints
```css
/* Add temporary border to debug layouts */
* { border: 1px solid red !important; }
```

### 📚 Documentation Files

- **CSS-ARCHITECTURE.md**: Technical deep-dive
- **README-CSS.md**: Implementation guide  
- **CSS-IMPLEMENTATION-SUMMARY.md**: Complete overview

### 🚀 Production Checklist

- [ ] Use minified CSS version
- [ ] Add cache-busting version parameter
- [ ] Test on target devices/browsers
- [ ] Validate with maintenance script
- [ ] Check file size optimization (should be ~20KB)

---
*Quick Reference for Haveli Restaurant Admin Dashboard CSS v2.0*