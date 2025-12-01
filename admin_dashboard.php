<?php
/**
 * SECURE ADMIN DASHBOARD
 * Authentication required to access this page
 */
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Not logged in, redirect to login page
    header("Location: admin_access.php");
    exit;
}

// Check session timeout (30 minutes)
$session_timeout = 1800; // 30 minutes in seconds
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_timeout)) {
    // Session expired, destroy and redirect
    session_destroy();
    header("Location: admin_access.php?timeout=1");
    exit;
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Regenerate session ID periodically for security
if (!isset($_SESSION['last_regeneration']) || (time() - $_SESSION['last_regeneration'] > 300)) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Haveli Restaurant - Admin Dashboard</title>
    <!-- Professional CSS Architecture -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="admin-dashboard.css" rel="stylesheet">
    <link href="admin-dashboard.modal.css" rel="stylesheet">
</head>
<body>
    <script>
        // Provide a lightweight fallback so inline onclicks don't throw if the main script
        // hasn't loaded yet. The full implementation (`window.showSection`) is defined
        // later in the main script — this stub simply updates the hash so navigation still works.
        window.showSection = window.showSection || function(sectionId, event) {
            if (event && event.preventDefault) event.preventDefault();
            try {
                // Try to activate the section if DOM is ready
                var el = document.getElementById(sectionId);
                if (el) {
                    document.querySelectorAll('.content-section').forEach(function(s){ s.classList.remove('active'); });
                    el.classList.add('active');
                } else {
                    // Fallback: set hash to allow manual navigation
                    location.hash = sectionId;
                }
            } catch (e) {
                // If anything fails, silently ignore — this is just a safe stub
            }
        };
    </script>
    <!-- Mobile Menu Toggle -->
    <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Mobile Overlay -->
    <div class="mobile-overlay" onclick="closeMobileMenu()"></div>
    
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="logo">
                <h1><i class="fas fa-building"></i> HAVELI</h1>
                <p>Admin Dashboard</p>
            </div>
            
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="#dashboard" class="nav-link active" onclick="showSection('dashboard')">
                        <i class="fas fa-tachometer-alt"></i>
                        Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#reservations" class="nav-link" onclick="showSection('reservations')">
                        <i class="fas fa-calendar-check"></i>
                        Reservations
                        <span id="pending-count" class="email-queue-count" style="display: none;">0</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#orders" class="nav-link" onclick="showSection('orders')">
                        <i class="fas fa-shopping-cart"></i>
                        Orders
                        <span id="orders-count" class="email-queue-count" style="display: none;">0</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#email-system" class="nav-link" onclick="showSection('email-system')">
                        <i class="fas fa-envelope"></i>
                        Email System
                        <span id="email-queue-count" class="email-queue-count" style="display: none;">0</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#analytics" class="nav-link" onclick="showSection('analytics')">
                        <i class="fas fa-chart-bar"></i>
                        Analytics
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#blog" class="nav-link" onclick="showSection('blog')">
                        <i class="fas fa-blog"></i>
                        Blog Admin
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#settings" class="nav-link" onclick="showSection('settings')">
                        <i class="fas fa-cog"></i>
                        Settings
                    </a>
                </li>
                <li class="nav-item" style="margin-top: 20px; border-top: 1px solid #eee; padding-top: 15px;">
                    <a href="#" class="nav-link" onclick="logout()" style="color: #dc3545;">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <h1>Admin Dashboard</h1>
                <p>Welcome back! Here's what's happening at Haveli Restaurant today.</p>
            </div>
            
            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-number" id="total-reservations">0</div>
                    <div class="stat-label">Total Reservations</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-number" id="pending-reservations">0</div>
                    <div class="stat-label">Pending Reservations</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-number" id="total-orders">0</div>
                    <div class="stat-label">Total Orders</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="stat-number" id="emails-sent">0</div>
                    <div class="stat-label">Emails Sent Today</div>
                </div>
            </div>
            
            <!-- Dashboard Section -->
            <div id="dashboard" class="content-section active">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-tachometer-alt"></i> Dashboard Overview
                    </h2>
                    <button class="btn btn-primary" onclick="refreshDashboard()">
                        <i class="fas fa-sync"></i> Refresh
                    </button>
                </div>
                
                <div class="email-status">
                    <h3><i class="fas fa-envelope"></i> Email System Status</h3>
                    <div id="email-status-content">
                        <p><i class="fas fa-spinner fa-spin"></i> Checking email system...</p>
                    </div>
                </div>
                
                <h3>Recent Activity</h3>
                <div id="recent-activity">
                    <p>Loading recent activity...</p>
                </div>
            </div>
            
            <!-- Reservations Section -->
            <div id="reservations" class="content-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-calendar-check"></i> Reservations
                    </h2>
                    <div>
                        <button class="btn btn-success" onclick="processEmailQueue()">
                            <i class="fas fa-paper-plane"></i> Send Pending Emails
                        </button>
                        <button class="btn btn-primary" onclick="loadReservations()">
                            <i class="fas fa-sync"></i> Refresh
                        </button>
                    </div>
                </div>
                
                <div id="reservations-content">
                    <p>Loading reservations...</p>
                </div>
            </div>
            
            <!-- Orders Section -->
            <div id="orders" class="content-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-shopping-cart"></i> Orders
                    </h2>
                    <button class="btn btn-primary" onclick="loadOrders()">
                        <i class="fas fa-sync"></i> Refresh
                    </button>
                </div>
                
                <div id="orders-content">
                    <p>Loading orders...</p>
                </div>
            </div>
            
            <!-- Email System Section -->
            <div id="email-system" class="content-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-envelope-open-text"></i> Email System Management
                    </h2>
                    <div class="email-system-buttons">
                        <button class="btn btn-primary" onclick="checkEmailStatus()">
                            <i class="fas fa-sync"></i> Check Status
                        </button>
                        <button class="btn btn-success" onclick="processEmailQueue()">
                            <i class="fas fa-paper-plane"></i> Process Queue
                        </button>
                        <button class="btn" onclick="testEmailSystem()" title="Send a test email">
                            <i class="fas fa-paper-plane"></i> Send Test Email
                        </button>
                    </div>
                </div>
                
                <div id="email-system-content">
                    <p>Loading email system status...</p>
                </div>
            </div>
            
            <!-- Analytics Section -->
            <div id="analytics" class="content-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-chart-line"></i> Analytics & Reports
                    </h2>
                    <button class="btn btn-primary" onclick="loadAnalytics()">
                        <i class="fas fa-sync"></i> Refresh Data
                    </button>
                </div>
                
                <div id="analytics-content">
                    <!-- Analytics Cards -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-chart-bar"></i>
                            </div>
                            <div class="stat-number" id="weekly-reservations">0</div>
                            <div class="stat-label">This Week's Reservations</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div class="stat-number" id="monthly-reservations">0</div>
                            <div class="stat-label">This Month's Reservations</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-percentage"></i>
                            </div>
                            <div class="stat-number" id="conversion-rate">0%</div>
                            <div class="stat-label">Conversion Rate</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-star"></i>
                            </div>
                            <div class="stat-number" id="avg-rating">0.0</div>
                            <div class="stat-label">Average Rating</div>
                        </div>
                    </div>
                    
                    <!-- Analytics Charts -->
                    <div class="analytics-section">
                        <h3><i class="fas fa-chart-area"></i> Reservation Trends</h3>
                        <div class="chart-container">
                            <canvas id="reservationChart" width="400" height="200"></canvas>
                        </div>
                    </div>
                    
                    <div class="analytics-section">
                        <h3><i class="fas fa-clock"></i> Peak Hours Analysis</h3>
                        <div class="chart-container">
                            <canvas id="peakHoursChart" width="400" height="200"></canvas>
                        </div>
                    </div>
                    
                    <div class="analytics-section">
                        <h3><i class="fas fa-users"></i> Customer Demographics</h3>
                        <div class="demographics-grid">
                            <div class="demo-card">
                                <h4><i class="fas fa-birthday-cake"></i> Age Groups</h4>
                                <div id="age-demographics"></div>
                            </div>
                            <div class="demo-card">
                                <h4><i class="fas fa-map-marker-alt"></i> Top Locations</h4>
                                <div id="location-demographics"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Settings Section -->
            <div id="settings" class="content-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-cogs"></i> Settings
                    </h2>
                </div>
                
                <div id="settings-content">
                    <h3>Email Configuration</h3>
                    <div class="email-status">
                        <p><strong>SMTP Host:</strong> smtppro.zoho.eu</p>
                        <p><strong>From Email:</strong> info@haveli.co.uk</p>
                        <p><strong>Status:</strong> <span style="color: green;">✅ Connected</span></p>
                    </div>
                    
                    <h3>System Information</h3>
                    <div class="email-status">
                        <p><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?></p>
                        <p><strong>Database:</strong> MySQL (haveli_db)</p>
                        <p><strong>Auto Email Processing:</strong> <span style="color: green;">✅ Enabled</span></p>
                    </div>
                </div>
            </div>
            
            <!-- Blog Admin Section -->
            <div id="blog" class="content-section">
                <div class="section-header">
                    <h2 class="section-title"><i class="fas fa-blog"></i> Blog Administration</h2>
                    <div>
                        <button class="btn" onclick="reloadBlogFrame()"><i class="fas fa-sync"></i> Reload</button>
                        <a class="btn" href="admin_blog.php" target="_blank">Open in new tab</a>
                    </div>
                </div>
                <div style="padding:18px;">
                    <iframe id="admin-blog-frame" src="admin_blog.php" style="width:100%;height:820px;border:0;border-radius:8px;box-shadow:0 8px 30px rgba(2,6,23,0.08);"></iframe>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Loading Overlay -->
    <div id="loading-overlay" class="loading-overlay">
        <div class="loading-spinner"></div>
    </div>
    
    <!-- Toast Notification -->
    <div id="toast" class="toast"></div>
    
    <!-- Refusal Modal -->
    <div id="refusalModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>
                    <i class="fas fa-times-circle"></i> Refuse Reservation
                </h2>
            </div>
            <div class="modal-body">
                <form id="refusalForm">
                    <input type="hidden" id="refusalReservationId" value="">
                    
                    <div class="form-group">
                        <label for="refusalReason">Select Reason:</label>
                        <select id="refusalReason" class="form-control">
                            <option value="">-- Select a reason --</option>
                            <option value="fully_booked">Fully booked for requested time</option>
                            <option value="kitchen_capacity">Kitchen at capacity for this slot</option>
                            <option value="special_event">Private event/Special occasion already booked</option>
                            <option value="custom">Other (specify below)</option>
                        </select>
                    </div>
                    
                    <div id="customReasonGroup" class="form-group" style="display: none;">
                        <label for="customReason">Custom Reason:</label>
                        <textarea id="customReason" class="form-control" placeholder="Please provide details..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="internalNote">Internal Note (optional):</label>
                        <textarea id="internalNote" class="form-control" placeholder="Add a note for staff only (will not be sent to customer)"></textarea>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn" onclick="closeRefusalModal()">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-times"></i> Confirm Refusal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Robust fetch helper that ensures JSON and reports meaningful errors
        async function fetchJsonSafe(url, options = {}, timeoutMs = 30000) {
            const controller = new AbortController();
            const timer = setTimeout(() => controller.abort(), timeoutMs);
            options = Object.assign({}, options, { signal: controller.signal });

            const res = await fetch(url, options);
            const contentType = res.headers.get('content-type') || '';
            // If HTTP error, capture a short body snippet for diagnostics
            if (!res.ok) {
                let bodySnippet = '';
                try { bodySnippet = (await res.text()).slice(0, 400); } catch (e) {}
                const err = new Error(`HTTP ${res.status} ${res.statusText}`);
                err.status = res.status;
                err.url = url;
                err.bodySnippet = bodySnippet;
                err.isHtml = contentType.includes('text/html');
                throw err;
            }
            // Must be JSON; otherwise throw with snippet so we can see if it's an HTML block page
            if (!contentType.toLowerCase().includes('application/json')) {
                let bodySnippet = '';
                try { bodySnippet = (await res.text()).slice(0, 400); } catch (e) {}
                const err = new Error('Non-JSON response');
                err.status = res.status;
                err.url = url;
                err.bodySnippet = bodySnippet;
                err.isHtml = contentType.includes('text/html');
                throw err;
            }
            const json = await res.json();
            clearTimeout(timer);
            return json;
        }

        function reportFetchError(where, error) {
            // Compact but useful diagnostics in console
            console.error(`[${where}] request failed`, {
                message: error && error.message,
                status: error && error.status,
                url: error && error.url,
                isHtml: error && error.isHtml,
                bodySnippet: error && error.bodySnippet
            });
            // Friendly toast for admins
            const status = (error && error.status) ? ` (HTTP ${error.status})` : '';
            const hint = (error && error.isHtml) ? 'Server returned HTML (likely WAF/ModSecurity or maintenance page).' : 'Server did not return JSON.';
            showToast(`${where} failed${status}. ${hint}`, 'warning');
        }

        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            refreshDashboard();
            
            // Load analytics fallback data immediately for better UX
            loadFallbackAnalytics();
            
            // Auto-refresh every 30 seconds
            setInterval(refreshDashboard, 30000);
            
            // Initialize mobile responsiveness
            initializeMobileFeatures();
            optimizeForMobile();
            enhanceTouchExperience();
            
            // Initialize modal handlers
            document.getElementById('refusalReason').addEventListener('change', function() {
                const customGroup = document.getElementById('customReasonGroup');
                customGroup.style.display = this.value === 'custom' ? 'block' : 'none';
            });

        });
        
        // Mobile Menu Functions
        function toggleMobileMenu() {
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.querySelector('.mobile-overlay');
            const toggle = document.querySelector('.mobile-menu-toggle');
            
            sidebar.classList.toggle('mobile-open');
            overlay.classList.toggle('active');
            
            // Change icon
            const icon = toggle.querySelector('i');
            if (sidebar.classList.contains('mobile-open')) {
                icon.className = 'fas fa-times';
            } else {
                icon.className = 'fas fa-bars';
            }
        }
        
        function closeMobileMenu() {
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.querySelector('.mobile-overlay');
            const toggle = document.querySelector('.mobile-menu-toggle');
            
            sidebar.classList.remove('mobile-open');
            overlay.classList.remove('active');
            
            // Reset icon
            toggle.querySelector('i').className = 'fas fa-bars';
        }
        
        function initializeMobileFeatures() {
            // Close mobile menu when clicking nav links
            document.querySelectorAll('.nav-link').forEach(link => {
                link.addEventListener('click', function() {
                    if (window.innerWidth <= 768) {
                        closeMobileMenu();
                    }
                });
            });
            
            // Handle window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth > 768) {
                    closeMobileMenu();
                }
            });
            
            // Add data labels for mobile table responsiveness
            addTableDataLabels();
        }
        
        function addTableDataLabels() {
            // Add data-label attributes for responsive tables
            setTimeout(() => {
                const tables = document.querySelectorAll('.responsive-table');
                tables.forEach(table => {
                    const headers = table.querySelectorAll('th');
                    const rows = table.querySelectorAll('tbody tr');
                    
                    rows.forEach(row => {
                        const cells = row.querySelectorAll('td');
                        cells.forEach((cell, index) => {
                            if (headers[index]) {
                                cell.setAttribute('data-label', headers[index].textContent);
                            }
                        });
                    });
                });
            }, 500);
        }
        
        // Mobile-specific optimizations
        function optimizeForMobile() {
            // Prevent zoom on input focus (if needed in future)
            if (window.innerWidth <= 768) {
                document.querySelectorAll('input, select, textarea').forEach(element => {
                    element.addEventListener('focus', function() {
                        const viewport = document.querySelector('meta[name=viewport]');
                        viewport.setAttribute('content', 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no');
                    });
                    
                    element.addEventListener('blur', function() {
                        const viewport = document.querySelector('meta[name=viewport]');
                        viewport.setAttribute('content', 'width=device-width, initial-scale=1.0');
                    });
                });
            }
        }
        
        // Enhanced touch handling
        function enhanceTouchExperience() {
            // Add touch feedback to buttons
            document.querySelectorAll('.btn, .nav-link').forEach(element => {
                element.addEventListener('touchstart', function() {
                    this.style.transform = 'scale(0.98)';
                });
                
                element.addEventListener('touchend', function() {
                    this.style.transform = 'scale(1)';
                });
            });
        }
        
        // Logout function
        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'logout.php';
            }
        }
        
        
        
        async function refreshDashboard() {
            try {
                // Load stats
                const data = await fetchJsonSafe('admin_dashboard_api.php?action=get_stats');
                if (data && data.success) {
                    document.getElementById('total-reservations').textContent = data.stats.total_reservations;
                    document.getElementById('pending-reservations').textContent = data.stats.pending_reservations;
                    document.getElementById('total-orders').textContent = data.stats.total_orders;
                    document.getElementById('emails-sent').textContent = data.stats.emails_sent;
                    
                    // Update badges
                    updateBadge('pending-count', data.stats.pending_reservations);
                    updateBadge('orders-count', data.stats.total_orders);
                    updateBadge('email-queue-count', data.stats.email_queue);
                }

                // Load email status (lightweight, no processing)
                const emailData = await fetchJsonSafe('admin_dashboard_api.php?action=get_email_status');
                if (emailData && emailData.success) {
                    const queueLabel = (emailData.queue_count === 0)
                        ? '<span class="status-connected">✅ Empty</span>'
                        : `<span class="queue-indicator">${emailData.queue_count} pending</span>`;

                    document.getElementById('email-status-content').innerHTML = `
                        <p><i class="fas fa-check-circle" style="color: green;"></i> Email system operational</p>
                        <p><strong>Queue:</strong> ${queueLabel}</p>
                        <p><strong>Last Check:</strong> ${new Date().toLocaleTimeString()}</p>
                    `;
                }
            } catch (error) {
                reportFetchError('Dashboard refresh', error);
                const el = document.getElementById('email-status-content');
                if (el) {
                    el.innerHTML = `<p><i class="fas fa-exclamation-triangle" style="color: #c0392b;"></i> Unable to load email status. Please check server logs.</p>`;
                }
            }
        }
        
        function updateBadge(elementId, count) {
            const badge = document.getElementById(elementId);
            if (count > 0) {
                badge.textContent = count;
                badge.style.display = 'inline-block';
            } else {
                badge.style.display = 'none';
            }
        }
        
    async function loadReservations() {
            document.getElementById('reservations-content').innerHTML = '<p>Loading reservations...</p>';
            
            try {
        const data = await fetchJsonSafe('admin_dashboard_api.php?action=get_reservations');
                
        if (data && data.success) {
                    let html = `
                        <div class="table-container">
                            <table class="responsive-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Customer</th>
                                    <th>Date & Time</th>
                                    <th>Guests</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                    `;
                    
                    data.reservations.forEach(reservation => {
                        html += `
                            <tr>
                                <td>${reservation.id}</td>
                                <td>${reservation.customer_name}<br><small>${reservation.email}</small></td>
                                <td>${reservation.reservation_date}<br>${reservation.reservation_time}</td>
                                <td>${reservation.num_guests}</td>
                                <td><span class="status-badge status-${reservation.status.toLowerCase()}">${reservation.status}</span></td>
                                <td>
                                    <button class="btn btn-success btn-sm" onclick="confirmReservation(${reservation.id})" ${reservation.status === 'confirmed' ? 'disabled' : ''}>
                                        <i class="fas fa-check"></i> Confirm
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="showRefusalModal(${reservation.id})" ${reservation.status === 'refused' ? 'disabled' : ''} style="margin-left:8px;">
                                        <i class="fas fa-times"></i> Refuse
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                    
                    html += '</tbody></table></div>';
                    document.getElementById('reservations-content').innerHTML = html;
                    addTableDataLabels();
                } else {
                    document.getElementById('reservations-content').innerHTML = '<p>Error loading reservations.</p>';
                }
            } catch (error) {
                reportFetchError('Load reservations', error);
                document.getElementById('reservations-content').innerHTML = '<p>Error loading reservations.</p>';
            }
        }
        
    async function loadOrders() {
            document.getElementById('orders-content').innerHTML = '<p>Loading orders...</p>';
            
            try {
        const data = await fetchJsonSafe('admin_dashboard_api.php?action=get_orders');
                
        if (data && data.success) {
                    if (data.orders.length === 0) {
                        document.getElementById('orders-content').innerHTML = `
                            <div class="email-status">
                                <h3><i class="fas fa-info-circle"></i> No Orders Found</h3>
                                <p>No orders have been placed yet. Orders will appear here when customers complete purchases through the online ordering system.</p>
                            </div>
                        `;
                    } else {
                        let html = `
                            <div class="table-container">
                                <table class="responsive-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Customer</th>
                                        <th>Items</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                        `;
                        
                        data.orders.forEach(order => {
                            html += `
                                <tr>
                                    <td>${order.id}</td>
                                    <td>${order.customer_name}<br><small>${order.customer_email}</small></td>
                                    <td>${order.items_summary}</td>
                                    <td>£${order.total_amount}</td>
                                    <td><span class="status-badge status-${order.status.toLowerCase()}">${order.status}</span></td>
                                    <td>${order.created_at}</td>
                                </tr>
                            `;
                        });
                        
                        html += '</tbody></table></div>';
                        document.getElementById('orders-content').innerHTML = html;
                        addTableDataLabels();
                    }
                } else {
                    document.getElementById('orders-content').innerHTML = '<p>Error loading orders.</p>';
                }
            } catch (error) {
                reportFetchError('Load orders', error);
                document.getElementById('orders-content').innerHTML = '<p>Error loading orders.</p>';
            }
        }
        
    async function loadEmailSystem() {
            document.getElementById('email-system-content').innerHTML = '<p>Loading email system status...</p>';
            
            try {
        const data = await fetchJsonSafe('admin_dashboard_api.php?action=get_email_status');
                
        if (data && data.success) {
                    document.getElementById('email-system-content').innerHTML = `
                        <div class="email-status-grid">
                            <div class="email-status">
                                <h3><i class="fas fa-server"></i> SMTP Status</h3>
                                <p><strong>Host:</strong> ${data.email_config.host}</p>
                                <p><strong>Port:</strong> ${data.email_config.port}</p>
                                <p><strong>Status:</strong> <span class="status-connected">✅ Connected</span></p>
                            </div>
                            
                            <div class="email-status">
                                <h3><i class="fas fa-inbox"></i> Email Queue</h3>
                                <p><strong>Files in Queue:</strong> 
                                    ${data.queue_count === 0 ? 
                                        '<span class="status-connected">✅ Empty</span>' : 
                                        `<span class="queue-indicator">${data.queue_count} pending</span>`
                                    }
                                </p>
                                <p><strong>Last Processed:</strong> ${new Date().toLocaleString()}</p>
                            </div>
                            
                            <div class="email-status">
                                <h3><i class="fas fa-chart-line"></i> Email Statistics</h3>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 10px;">
                                    <div style="text-align: center;">
                                        <div class="metric-value status-connected">${data.stats.emails_today}</div>
                                        <div class="metric-label">Emails Today</div>
                                    </div>
                                    <div style="text-align: center;">
                                        <div class="metric-value status-connected">${data.stats.success_rate}%</div>
                                        <div class="metric-label">Success Rate</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                }
            } catch (error) {
                reportFetchError('Email system status', error);
                document.getElementById('email-system-content').innerHTML = '<p>Error loading email system status.</p>';
            }
        }
        
        // Wrapper for the header button
        function checkEmailStatus() {
            loadEmailSystem();
        }
        
    async function loadAnalytics() {
            try {
        // Load analytics data
        const data = await fetchJsonSafe('admin_dashboard_api.php?action=get_analytics');
                
        if (data && data.success) {
                    // Update analytics stats
                    document.getElementById('weekly-reservations').textContent = data.analytics.weekly_reservations;
                    document.getElementById('monthly-reservations').textContent = data.analytics.monthly_reservations;
                    document.getElementById('conversion-rate').textContent = data.analytics.conversion_rate + '%';
                    document.getElementById('avg-rating').textContent = data.analytics.avg_rating;
                    
                    // Update demographics
                    updateDemographics('age-demographics', data.analytics.age_groups);
                    updateDemographics('location-demographics', data.analytics.locations);
                    
                    // Load charts if Chart.js is available
                    if (typeof Chart !== 'undefined') {
                        loadReservationChart(data.analytics.reservation_trends);
                        loadPeakHoursChart(data.analytics.peak_hours);
                    } else {
                        // Fallback to simple visualization
                        loadSimpleCharts(data.analytics);
                    }
                }
            } catch (error) {
                reportFetchError('Analytics', error);
                // Show fallback data
                loadFallbackAnalytics();
            }
        }
        
        function updateDemographics(containerId, data) {
            const container = document.getElementById(containerId);
            let html = '';
            
            data.forEach(item => {
                const percentage = Math.round((item.count / data.reduce((sum, d) => sum + d.count, 0)) * 100);
                html += `
                    <div class="demo-item">
                        <span class="demo-label">${item.label}</span>
                        <span class="demo-value">${item.count}</span>
                    </div>
                    <div class="demo-bar">
                        <div class="demo-bar-fill" style="width: ${percentage}%"></div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }
        
        function loadSimpleCharts(analytics) {
            // Simple text-based charts for fallback - mobile optimized
            const reservationChart = document.getElementById('reservationChart');
            const peakHoursChart = document.getElementById('peakHoursChart');
            
            // Check if we're on mobile
            const isMobile = window.innerWidth <= 768;
            const chartHeight = isMobile ? 150 : 200;
            const barWidth = isMobile ? 15 : 20;
            const padding = isMobile ? 10 : 20;
            
            reservationChart.parentElement.innerHTML = `
                <h4 style="text-align: center; color: var(--gray-900); margin-bottom: 15px;">
                    <i class="fas fa-chart-line"></i> Recent Reservation Trend
                </h4>
                <div style="display: flex; justify-content: space-between; align-items: end; height: ${chartHeight}px; padding: ${padding}px; border: 1px solid #ddd; border-radius: 8px; background: white; overflow: auto;">
                    ${analytics.reservation_trends.map((value, index) => 
                        `<div style="background: linear-gradient(45deg, #667eea, #764ba2); width: ${barWidth}px; height: ${Math.max(value * 3, 10)}px; margin: 0 2px; border-radius: 2px; flex-shrink: 0;" title="Day ${index + 1}: ${value} reservations"></div>`
                    ).join('')}
                </div>
                <div style="text-align: center; margin-top: 10px; font-size: 12px; color: #666;">Last 7 Days</div>
            `;
            
            peakHoursChart.parentElement.innerHTML = `
                <h4 style="text-align: center; color: var(--gray-900); margin-bottom: 15px;">
                    <i class="fas fa-clock"></i> Peak Hours Distribution
                </h4>
                <div style="padding: ${padding}px; border: 1px solid #ddd; border-radius: 8px; background: white; max-height: 300px; overflow-y: auto;">
                    ${analytics.peak_hours.map(hour => 
                        `<div style="display: flex; justify-content: space-between; align-items: center; padding: ${isMobile ? 6 : 8}px 0; border-bottom: 1px solid #eee; min-height: 44px;">
                            <span style="font-size: ${isMobile ? '14px' : '16px'};">${hour.time}</span>
                            <span style="font-weight: bold; color: #667eea; font-size: ${isMobile ? '14px' : '16px'};">${hour.reservations}</span>
                        </div>`
                    ).join('')}
                </div>
            `;
        }
        
        function loadFallbackAnalytics() {
            // Show real empty state when no data is available
            document.getElementById('weekly-reservations').textContent = '0';
            document.getElementById('monthly-reservations').textContent = '0';
            document.getElementById('conversion-rate').textContent = '0%';
            document.getElementById('avg-rating').textContent = '0.0';
            
            // Clear demographics sections
            const ageDemo = document.getElementById('age-demographics');
            const locationDemo = document.getElementById('location-demographics');
            
            if (ageDemo) {
                ageDemo.innerHTML = '<div style="text-align: center; padding: 20px; color: var(--gray-500);"><i class="fas fa-users"></i><br>No age data yet</div>';
            }
            
            if (locationDemo) {
                locationDemo.innerHTML = '<div style="text-align: center; padding: 20px; color: var(--gray-500);"><i class="fas fa-map-marker-alt"></i><br>No location data yet</div>';
            }
            
            // Empty state charts
            const analytics = {
                reservation_trends: [0, 0, 0, 0, 0, 0, 0],
                peak_hours: []
            };
            
            loadSimpleCharts(analytics);
            
            // Show informative message about empty analytics
            const analyticsSection = document.querySelector('.analytics-section');
            if (analyticsSection) {
                // Remove any existing empty message
                const existingMessage = analyticsSection.querySelector('.empty-analytics-message');
                if (existingMessage) {
                    existingMessage.remove();
                }
                
                // Add new empty state message
                const emptyMessage = document.createElement('div');
                emptyMessage.className = 'empty-analytics-message';
                emptyMessage.innerHTML = `
                    <div style="text-align: center; padding: 30px 20px; background: var(--white); border-radius: 12px; margin: 20px 0; border: 1px solid var(--gray-200); box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <i class="fas fa-chart-bar" style="font-size: 48px; color: var(--gray-400); margin-bottom: 15px;"></i>
                        <h3 style="color: var(--gray-700); margin-bottom: 10px; font-weight: 600;">No Analytics Data Available</h3>
                        <p style="color: var(--gray-500); margin-bottom: 15px; line-height: 1.5;">Start accepting reservations to see detailed analytics and insights.</p>
                        <p style="font-size: 13px; color: var(--gray-400);">Charts, demographics, and trends will appear automatically once you have customer data.</p>
                    </div>
                `;
                
                // Insert after the analytics grid
                const analyticsGrid = analyticsSection.querySelector('.analytics-grid');
                if (analyticsGrid) {
                    analyticsGrid.parentNode.insertBefore(emptyMessage, analyticsGrid.nextSibling);
                }
            }
        }
        
        async function confirmReservation(id) {
            if (!confirm('Confirm this reservation? Customer will receive a confirmation email.')) return;
            
            showLoading();
            
            try {
                const result = await fetchJsonSafe('admin_dashboard_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        'action': 'confirm_reservation',
                        'reservation_id': id
                    })
                });
                
                if (result.success) {
                    showToast('Reservation confirmed! Email sent.', 'success');
                    // Trigger queue processing in the background (non-blocking)
                    try {
                        fetchJsonSafe('process_email_queue.php', {}, 60000)
                            .then(processData => {
                                if (processData && !processData.success) {
                                    showToast('Email queue processed with issues: ' + (processData.message || ''), 'warning');
                                }
                            })
                            .catch(err => { reportFetchError('Background email processing', err); });
                    } catch (e) { /* ignore */ }
                    loadReservations();
                    refreshDashboard();
                } else {
                    showToast('Error: ' + result.message, 'error');
                }
            } catch (error) {
                reportFetchError('Confirm reservation', error);
            } finally {
                hideLoading();
            }
        }
        
        // Refusal form handler
        async function handleRefusalFormSubmit(e) {
            e.preventDefault();
            
            const reservationId = document.getElementById('refusalReservationId').value;
            const reasonType = document.getElementById('refusalReason').value;
            const customReason = document.getElementById('customReason').value;
            const internalNote = document.getElementById('internalNote').value;
            
            if (!reasonType) {
                showToast('Please select a reason for refusal', 'warning');
                return;
            }
            
            if (reasonType === 'custom' && !customReason.trim()) {
                showToast('Please provide a custom reason', 'warning');
                return;
            }
            
            // Get customer-friendly reason text
            let customerReason = '';
            switch(reasonType) {
                case 'fully_booked':
                    customerReason = 'We are fully booked for your requested time slot.';
                    break;
                case 'kitchen_capacity':
                    customerReason = 'Our kitchen is at maximum capacity for this time slot.';
                    break;
                case 'special_event':
                    customerReason = 'We have a private event/special occasion already scheduled.';
                    break;
                case 'custom':
                    customerReason = customReason;
                    break;
            }
            
            closeRefusalModal();
            await refuseReservation(reservationId, customerReason, internalNote);
        }

        // Refusal Modal Functions
        function showRefusalModal(reservationId) {
            document.getElementById('refusalReservationId').value = reservationId;
            document.getElementById('refusalReason').value = '';
            document.getElementById('customReason').value = '';
            document.getElementById('internalNote').value = '';
            document.getElementById('customReasonGroup').style.display = 'none';
            document.getElementById('refusalModal').style.display = 'block';
            
            // Ensure we only have one submit event listener
            const form = document.getElementById('refusalForm');
            form.removeEventListener('submit', handleRefusalFormSubmit);
            form.addEventListener('submit', handleRefusalFormSubmit);
        }
        
        function closeRefusalModal() {
            document.getElementById('refusalModal').style.display = 'none';
        }
        
        // Handle reason type selection
        document.getElementById('refusalReason').addEventListener('change', function() {
            const customGroup = document.getElementById('customReasonGroup');
            customGroup.style.display = this.value === 'custom' ? 'block' : 'none';
        });
        
        // Make sure showSection is defined at the top level
        window.showSection = function(sectionId, event) {
            if (event) {
                event.preventDefault();
            }
            // Hide all sections
            document.querySelectorAll('.content-section').forEach(section => {
                section.classList.remove('active');
            });
            
            // Remove active class from all nav links
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
            });
            
            // Show selected section
            document.getElementById(sectionId).classList.add('active');
            
            // Add active class to clicked nav link
            if (event && event.target) {
                event.target.classList.add('active');
            }
            
            // Load section-specific content
            switch(sectionId) {
                case 'reservations':
                    loadReservations();
                    break;
                case 'orders':
                    loadOrders();
                    break;
                case 'email-system':
                    loadEmailSystem();
                    break;
                case 'analytics':
                    loadAnalytics();
                    break;
            }
        };


        async function refuseReservation(id, reason = '', internalNote = '') {
            showLoading();
            try {
                // Log the data being sent for debugging
                console.log('Refusing reservation:', {
                    id: id,
                    reason: reason,
                    internalNote: internalNote
                });
                
                const result = await fetchJsonSafe('admin_dashboard_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        'action': 'refuse_reservation',
                        'reservation_id': id,
                        'reason': reason,
                        'internal_note': internalNote
                    })
                });

                if (result.success) {
                    showToast('Reservation refused and customer notified.', 'success');
                    // process email queue in background
                    try {
                        fetchJsonSafe('process_email_queue.php', {}, 60000)
                            .then(processData => {
                                if (processData && !processData.success) {
                                    showToast('Email queue processed with issues: ' + (processData.message || ''), 'warning');
                                }
                            })
                            .catch(err => { reportFetchError('Background email processing', err); });
                    } catch (e) { /* ignore */ }
                    loadReservations();
                    refreshDashboard();
                } else {
                    showToast('Error: ' + result.message, 'error');
                    // Re-enable the refuse button since the operation failed
                    loadReservations(); // Reload to restore the button state
                }
            } catch (error) {
                reportFetchError('Refuse reservation', error);
            } finally {
                hideLoading();
            }
        }
        
        async function processEmailQueue() {
            showLoading();
            
            try {
                const result = await fetchJsonSafe('process_email_queue.php', {}, 60000);
                
                if (result.success) {
                    showToast(result.message, 'success');
                } else {
                    showToast('Error processing email queue', 'error');
                }
                
                refreshDashboard();
            } catch (error) {
                reportFetchError('Process email queue', error);
                showToast('Error processing email queue', 'error');
            } finally {
                hideLoading();
            }
        }
        
        async function testEmailSystem() {
            const suggested = 'kalakaarstudios@gmail.com';
            const to = prompt('Enter test email address:', suggested);
            if (to === null || to.trim() === '') return;

            showLoading();
            try {
                const data = await fetchJsonSafe('test_email_providers.php?to=' + encodeURIComponent(to.trim()));
                if (data && data.success) {
                    showToast(data.message || 'Test email sent!', 'success');
                } else {
                    showToast((data && data.message) ? data.message : 'Failed to send test email', 'error');
                }
            } catch (e) {
                reportFetchError('Send test email', e);
                showToast('Error sending test email', 'error');
            } finally {
                hideLoading();
            }
        }
        
        function showLoading() {
            document.getElementById('loading-overlay').style.display = 'flex';
        }
        
        function hideLoading() {
            document.getElementById('loading-overlay').style.display = 'none';
        }
        
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            
            // Add appropriate icon based on type
            let icon = '';
            switch(type) {
                case 'success':
                    icon = '<i class="fas fa-check-circle"></i>';
                    break;
                case 'error':
                    icon = '<i class="fas fa-exclamation-circle"></i>';
                    break;
                case 'warning':
                    icon = '<i class="fas fa-exclamation-triangle"></i>';
                    break;
                case 'info':
                    icon = '<i class="fas fa-info-circle"></i>';
                    break;
                default:
                    icon = '<i class="fas fa-check-circle"></i>';
            }
            
            toast.innerHTML = `${icon} ${message}`;
            toast.className = `toast ${type}`;
            toast.classList.add('show');
            
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }

        // Reloads the blog iframe inside the dashboard (adds cache-bust)
        function reloadBlogFrame() {
            var f = document.getElementById('admin-blog-frame');
            if (!f) return;
            f.src = 'admin_blog.php?cb=' + Date.now();
        }
    </script>
</body>
</html>