<?php
/**
 * MODERN TRENDY ADMIN DASHBOARD
 * Contemporary design with smooth animations and micro-interactions
 */

session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_access.php");
    exit;
}

// Check session timeout (30 minutes)
$session_timeout = 1800;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_timeout)) {
    session_destroy();
    header("Location: admin_access.php?timeout=1");
    exit;
}

// Update last activity time
$_SESSION['last_activity'] = time();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Haveli Restaurant - Modern Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="admin-dashboard-modern.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="logo">
                <h1><i class="fas fa-utensils"></i> HAVELI</h1>
                <p>Modern Admin</p>
            </div>
            
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="#dashboard" class="nav-link active" onclick="showSection('dashboard')">
                        <i class="fas fa-chart-line"></i>
                        Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#reservations" class="nav-link" onclick="showSection('reservations')">
                        <i class="fas fa-calendar-alt"></i>
                        Reservations
                        <span id="pending-count" class="status-badge status-pending" style="display: none;">0</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#orders" class="nav-link" onclick="showSection('orders')">
                        <i class="fas fa-shopping-bag"></i>
                        Orders
                        <span id="orders-count" class="status-badge status-pending" style="display: none;">0</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#email-system" class="nav-link" onclick="showSection('email-system')">
                        <i class="fas fa-paper-plane"></i>
                        Email System
                        <span id="email-queue-count" class="status-badge status-pending" style="display: none;">0</span>
                    </a>
                </li>
                <li class="nav-item" style="margin-top: 20px; border-top: 1px solid var(--glass-border); padding-top: 15px;">
                    <a href="#" class="nav-link" onclick="logout()" style="color: var(--danger);">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </li>
            </ul>
        </div>
        
        <!-- Mobile Menu Toggle -->
        <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
            <i class="fas fa-bars"></i>
        </button>
        
        <!-- Mobile Overlay -->
        <div class="mobile-overlay" onclick="closeMobileMenu()"></div>
        
        <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <h1>Modern Dashboard</h1>
            <p>Contemporary design with smooth interactions</p>
        </div>
        
        <!-- Search Bar -->
        <div class="search-container">
            <div class="search-box">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="searchInput" class="search-input" placeholder="Search reservations, customers..." onkeyup="handleSearch(event)">
                <button id="clearSearch" class="btn btn-sm" onclick="clearSearch()">
                    <i class="fas fa-times"></i> Clear
                </button>
            </div>
            <div class="search-filters">
                <select id="statusFilter" class="form-control" onchange="filterReservations()">
                    <option value="">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="refused">Refused</option>
                    <option value="cancelled">Cancelled</option>
                </select>
                <select id="dateFilter" class="form-control" onchange="filterReservations()">
                    <option value="">All Dates</option>
                    <option value="today">Today</option>
                    <option value="week">This Week</option>
                    <option value="month">This Month</option>
                </select>
                <select id="guestFilter" class="form-control" onchange="filterReservations()">
                    <option value="">All Guests</option>
                    <option value="1">1 Guest</option>
                    <option value="2">2 Guests</option>
                    <option value="3">3 Guests</option>
                    <option value="4">4 Guests</option>
                    <option value="5+">5+ Guests</option>
                </select>
                <select id="sortBy" class="form-control" onchange="filterReservations()">
                    <option value="created_at">Sort by Date</option>
                    <option value="reservation_date">Sort by Reservation Date</option>
                    <option value="customer_name">Sort by Customer Name</option>
                    <option value="num_guests">Sort by Guests</option>
                </select>
            </div>
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
                        <i class="fas fa-shopping-bag"></i>
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
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stat-number" id="total-refused">0</div>
                    <div class="stat-label">Total Refused</div>
                </div>
            </div>
            
            <!-- Dashboard Section -->
            <div id="dashboard" class="content-section active">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-chart-line"></i> Dashboard Overview
                    </h2>
                    <button class="btn btn-primary" onclick="refreshDashboard()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
                
                <div class="email-status">
                    <h3><i class="fas fa-paper-plane"></i> Email System Status</h3>
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
                        <i class="fas fa-calendar-alt"></i> Reservations Management
                    </h2>
                    <div>
                        <button class="btn btn-success" onclick="processEmailQueue()">
                            <i class="fas fa-paper-plane"></i> Send Emails
                        </button>
                        <button class="btn btn-primary" onclick="loadReservations()">
                            <i class="fas fa-sync-alt"></i> Refresh
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
                        <i class="fas fa-shopping-bag"></i> Orders Management
                    </h2>
                    <button class="btn btn-primary" onclick="loadOrders()">
                        <i class="fas fa-sync-alt"></i> Refresh
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
                        <i class="fas fa-paper-plane"></i> Email System
                    </h2>
                    <div>
                        <button class="btn btn-primary" onclick="checkEmailStatus()">
                            <i class="fas fa-sync-alt"></i> Check Status
                        </button>
                        <button class="btn btn-success" onclick="processEmailQueue()">
                            <i class="fas fa-paper-plane"></i> Process Queue
                        </button>
                    </div>
                </div>
                
                <div id="email-system-content">
                    <p>Loading email system status...</p>
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
    
    <!-- Modern Refusal Modal -->
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
                        <label for="refusalReason">Reason for refusal:</label>
                        <select id="refusalReason" class="form-control">
                            <option value="">-- Select a reason --</option>
                            <option value="fully_booked">Fully booked for requested time</option>
                            <option value="kitchen_capacity">Kitchen at capacity for this slot</option>
                            <option value="special_event">Private event already booked</option>
                            <option value="custom">Other (specify below)</option>
                        </select>
                    </div>
                    
                    <div id="customReasonGroup" class="form-group" style="display: none;">
                        <label for="customReason">Custom Reason:</label>
                        <textarea id="customReason" class="form-control" placeholder="Please provide details..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="internalNote">Internal Note (optional):</label>
                        <textarea id="internalNote" class="form-control" placeholder="Add a note for staff only"></textarea>
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
        // Modern JavaScript with enhanced interactions
        document.addEventListener('DOMContentLoaded', function() {
            refreshDashboard();
            
            // Auto-refresh every 30 seconds
            setInterval(refreshDashboard, 30000);
            
            // Initialize mobile features
            initializeMobileFeatures();
            
            // Initialize modal handlers
            initializeModalHandlers();
            
            // Add smooth scroll behavior
            initSmoothScroll();
        });
        
        // Enhanced Mobile Menu Functions
        function toggleMobileMenu() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.querySelector('.mobile-overlay');
            const toggle = document.querySelector('.mobile-menu-toggle');
            
            sidebar.classList.toggle('mobile-open');
            overlay.classList.toggle('active');
            
            const icon = toggle.querySelector('i');
            if (sidebar.classList.contains('mobile-open')) {
                icon.className = 'fas fa-times';
            } else {
                icon.className = 'fas fa-bars';
            }
        }
        
        function closeMobileMenu() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.querySelector('.mobile-overlay');
            const toggle = document.querySelector('.mobile-menu-toggle');
            
            sidebar.classList.remove('mobile-open');
            overlay.classList.remove('active');
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
        }
        
        // Smooth scroll behavior
        function initSmoothScroll() {
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });
        }
        
        // Enhanced Section Navigation
        function showSection(sectionId, event) {
            if (event) {
                event.preventDefault();
            }
            
            // Hide all sections with animation
            document.querySelectorAll('.content-section').forEach(section => {
                section.classList.remove('active');
            });
            
            // Remove active class from all nav links
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
            });
            
            // Show selected section with animation
            setTimeout(() => {
                document.getElementById(sectionId).classList.add('active');
            }, 100);
            
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
            }
        }
        
        // Enhanced Dashboard Functions
        async function refreshDashboard() {
            try {
                // Add loading state to stat cards
                document.querySelectorAll('.stat-number').forEach(el => {
                    el.style.opacity = '0.5';
                });
                
                const response = await fetch('admin_dashboard_api.php?action=get_stats');
                const data = await response.json();
                
                if (data.success) {
                    // Animate number changes
                    animateValue('total-reservations', parseInt(document.getElementById('total-reservations').textContent), data.stats.total_reservations, 1000);
                    animateValue('pending-reservations', parseInt(document.getElementById('pending-reservations').textContent), data.stats.pending_reservations, 1000);
                    animateValue('total-orders', parseInt(document.getElementById('total-orders').textContent), data.stats.total_orders, 1000);
                    animateValue('emails-sent', parseInt(document.getElementById('emails-sent').textContent), data.stats.emails_sent, 1000);
                    animateValue('total-refused', parseInt(document.getElementById('total-refused').textContent), data.stats.total_refused, 1000);
                    
                    // Update badges
                    updateBadge('pending-count', data.stats.pending_reservations);
                    updateBadge('orders-count', data.stats.total_orders);
                    updateBadge('email-queue-count', data.stats.email_queue);
                }
                
                // Load email status
                const emailResponse = await fetch('admin_dashboard_api.php?action=get_email_status');
                const emailData = await emailResponse.json();
                
                if (emailData.success) {
                    const queueLabel = (emailData.queue_count === 0)
                        ? '<span style="color: var(--success);">✅ Empty</span>'
                        : `<span class="status-badge status-pending">${emailData.queue_count} pending</span>`;

                    document.getElementById('email-status-content').innerHTML = `
                        <p><i class="fas fa-check-circle" style="color: var(--success);"></i> Email system operational</p>
                        <p><strong>Queue:</strong> ${queueLabel}</p>
                        <p><strong>Last Check:</strong> ${new Date().toLocaleTimeString()}</p>
                    `;
                }
                
                // Remove loading state
                document.querySelectorAll('.stat-number').forEach(el => {
                    el.style.opacity = '1';
                });
            } catch (error) {
                console.error('Dashboard refresh error:', error);
                showToast('Error loading dashboard data', 'error');
            }
        }
        
        // Animate number changes
        function animateValue(id, start, end, duration) {
            const obj = document.getElementById(id);
            const range = end - start;
            const minTimer = 50;
            let stepTime = Math.abs(Math.floor(duration / range));
            stepTime = Math.max(stepTime, minTimer);
            const startTime = new Date().getTime();
            const endTime = startTime + duration;
            
            function run() {
                const now = new Date().getTime();
                const remaining = Math.max((endTime - now) / duration, 0);
                const value = Math.round(end - (remaining * range));
                obj.textContent = value;
                if (value === end) return;
                requestAnimationFrame(run);
            }
            
            requestAnimationFrame(run);
        }
        
        function updateBadge(elementId, count) {
            const badge = document.getElementById(elementId);
            if (count > 0) {
                badge.textContent = count;
                badge.style.display = 'inline-block';
                // Add pulse animation
                badge.style.animation = 'pulse 2s infinite';
            } else {
                badge.style.display = 'none';
            }
        }
        
        // Enhanced Reservations Functions
        let allReservations = []; // Store all reservations for search/filter
        
        async function loadReservations() {
            const contentEl = document.getElementById('reservations-content');
            contentEl.innerHTML = '<p><i class="fas fa-spinner fa-spin"></i> Loading reservations...</p>';
            
            try {
                const response = await fetch('admin_dashboard_api.php?action=get_reservations');
                const data = await response.json();
                
                if (data.success) {
                    allReservations = data.reservations; // Store for search/filter
                    displayReservations(data.reservations);
                } else {
                    contentEl.innerHTML = '<p>Error loading reservations.</p>';
                }
            } catch (error) {
                console.error('Load reservations error:', error);
                contentEl.innerHTML = '<p>Error loading reservations.</p>';
            }
        }
        
        // Display reservations with search/filter applied
        function displayReservations(reservations) {
            const contentEl = document.getElementById('reservations-content');
                
                if (data.success) {
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
                    
                    data.reservations.forEach((reservation, index) => {
                        html += `
                            <tr style="animation: slideUp 0.5s ease-out ${index * 0.1}s both;">
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
                    contentEl.innerHTML = html;
                }
            }
        }
        
        // Search and Filter Functions
        function handleSearch(event) {
            const searchTerm = event.target.value.toLowerCase();
            filterReservations();
        }
        
        function clearSearch() {
            document.getElementById('searchInput').value = '';
            filterReservations();
        }
        
        function filterReservations() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const statusFilter = document.getElementById('statusFilter').value;
            const dateFilter = document.getElementById('dateFilter').value;
            
            let filteredReservations = allReservations;
            
            // Apply search filter
            if (searchTerm) {
                filteredReservations = filteredReservations.filter(reservation =>
                    reservation.customer_name.toLowerCase().includes(searchTerm) ||
                    reservation.email.toLowerCase().includes(searchTerm) ||
                    reservation.reservation_date.toLowerCase().includes(searchTerm) ||
                    reservation.reservation_time.toLowerCase().includes(searchTerm) ||
                    reservation.num_guests.toString().includes(searchTerm)
                );
            }
            
            // Apply status filter
            if (statusFilter) {
                filteredReservations = filteredReservations.filter(reservation =>
                    reservation.status.toLowerCase() === statusFilter
                );
            }
            
            // Apply date filter
            if (dateFilter) {
                const today = new Date();
                today.setHours(0, 0, 0, 0); // Start of today
                
                filteredReservations = filteredReservations.filter(reservation => {
                    const reservationDate = new Date(reservation.reservation_date);
                    
                    switch(dateFilter) {
                        case 'today':
                            return reservationDate.toDateString() === today.toDateString();
                        case 'week':
                            const weekFromToday = new Date(today);
                            weekFromToday.setDate(today.getDate() - today.getDay());
                            const weekToToday = new Date(weekFromToday);
                            weekToToday.setDate(weekFromToday.getDate() + 6);
                            return reservationDate >= weekFromToday && reservationDate <= weekToToday;
                        case 'month':
                            return reservationDate.getMonth() === today.getMonth() &&
                                   reservationDate.getFullYear() === today.getFullYear();
                        default:
                            return true;
                    }
                });
            }
            
            displayReservations(filteredReservations);
        }
                } else {
                    contentEl.innerHTML = '<p>Error loading reservations.</p>';
                }
            } catch (error) {
                console.error('Load reservations error:', error);
                contentEl.innerHTML = '<p>Error loading reservations.</p>';
            }
        }
        
        // Orders Functions
        async function loadOrders() {
            const contentEl = document.getElementById('orders-content');
            contentEl.innerHTML = '<p><i class="fas fa-spinner fa-spin"></i> Loading orders...</p>';
            
            try {
                const response = await fetch('admin_dashboard_api.php?action=get_orders');
                const data = await response.json();
                
                if (data.success) {
                    if (data.orders.length === 0) {
                        contentEl.innerHTML = `
                            <div class="email-status">
                                <h3><i class="fas fa-info-circle"></i> No Orders Found</h3>
                                <p>No orders have been placed yet. Orders will appear here when customers complete purchases.</p>
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
                        
                        data.orders.forEach((order, index) => {
                            html += `
                                <tr style="animation: slideUp 0.5s ease-out ${index * 0.1}s both;">
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
                        contentEl.innerHTML = html;
                    }
                } else {
                    contentEl.innerHTML = '<p>Error loading orders.</p>';
                }
            } catch (error) {
                console.error('Load orders error:', error);
                contentEl.innerHTML = '<p>Error loading orders.</p>';
            }
        }
        
        // Email System Functions
        async function loadEmailSystem() {
            const contentEl = document.getElementById('email-system-content');
            contentEl.innerHTML = '<p><i class="fas fa-spinner fa-spin"></i> Loading email system status...</p>';
            
            try {
                const response = await fetch('admin_dashboard_api.php?action=get_email_status');
                const data = await response.json();
                
                if (data.success) {
                    contentEl.innerHTML = `
                        <div class="email-status">
                            <h3><i class="fas fa-server"></i> SMTP Status</h3>
                            <p><strong>Host:</strong> ${data.email_config.host}</p>
                            <p><strong>Port:</strong> ${data.email_config.port}</p>
                            <p><strong>Status:</strong> <span style="color: var(--success);">✅ Connected</span></p>
                        </div>
                        
                        <div class="email-status">
                            <h3><i class="fas fa-inbox"></i> Email Queue</h3>
                            <p><strong>Files in Queue:</strong> 
                                ${data.queue_count === 0 ? 
                                    '<span style="color: var(--success);">✅ Empty</span>' : 
                                    `<span class="status-badge status-pending">${data.queue_count} pending</span>`
                                }
                            </p>
                            <p><strong>Last Processed:</strong> ${new Date().toLocaleString()}</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Load email system error:', error);
                contentEl.innerHTML = '<p>Error loading email system status.</p>';
            }
        }
        
        function checkEmailStatus() {
            loadEmailSystem();
        }
        
        async function processEmailQueue() {
            showLoading();
            
            try {
                const response = await fetch('process_email_queue.php');
                const data = await response.json();
                
                if (data.success) {
                    showToast(data.message, 'success');
                } else {
                    showToast('Error processing email queue', 'error');
                }
                
                refreshDashboard();
            } catch (error) {
                console.error('Process email queue error:', error);
                showToast('Error processing email queue', 'error');
            } finally {
                hideLoading();
            }
        }
        
        // Enhanced Reservation Actions
        async function confirmReservation(id) {
            if (!confirm('Confirm this reservation? Customer will receive a confirmation email.')) return;
            
            showLoading();
            
            try {
                const response = await fetch('admin_dashboard_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        'action': 'confirm_reservation',
                        'reservation_id': id
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showToast('Reservation confirmed! Email sent.', 'success');
                    loadReservations();
                    refreshDashboard();
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            } catch (error) {
                console.error('Confirm reservation error:', error);
                showToast('Error confirming reservation', 'error');
            } finally {
                hideLoading();
            }
        }
        
        // Enhanced Modal Functions
        function initializeModalHandlers() {
            // Reason type change handler
            document.getElementById('refusalReason').addEventListener('change', function() {
                const customGroup = document.getElementById('customReasonGroup');
                customGroup.style.display = this.value === 'custom' ? 'block' : 'none';
            });
            
            // Form submission handler
            document.getElementById('refusalForm').addEventListener('submit', handleRefusalSubmit);
        }
        
        function showRefusalModal(reservationId) {
            document.getElementById('refusalReservationId').value = reservationId;
            document.getElementById('refusalReason').value = '';
            document.getElementById('customReason').value = '';
            document.getElementById('internalNote').value = '';
            document.getElementById('customReasonGroup').style.display = 'none';
            document.getElementById('refusalModal').style.display = 'flex';
        }
        
        function closeRefusalModal() {
            document.getElementById('refusalModal').style.display = 'none';
        }
        
        async function handleRefusalSubmit(e) {
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
        
        async function refuseReservation(id, reason = '', internalNote = '') {
            showLoading();
            
            try {
                const response = await fetch('admin_dashboard_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        'action': 'refuse_reservation',
                        'reservation_id': id,
                        'reason': reason,
                        'internal_note': internalNote
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showToast('Reservation refused and customer notified.', 'success');
                    loadReservations();
                    refreshDashboard();
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            } catch (error) {
                console.error('Refuse reservation error:', error);
                showToast('Error refusing reservation', 'error');
            } finally {
                hideLoading();
            }
        }
        
        // Enhanced Utility Functions
        function showLoading() {
            const overlay = document.getElementById('loading-overlay');
            overlay.classList.add('active');
            // Add pulse animation to spinner
            const spinner = overlay.querySelector('.loading-spinner');
            spinner.style.animation = 'spin 1s linear infinite, pulse 2s ease-in-out infinite';
        }
        
        function hideLoading() {
            const overlay = document.getElementById('loading-overlay');
            overlay.classList.remove('active');
        }
        
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            
            // Add appropriate icon
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
                default:
                    icon = '<i class="fas fa-info-circle"></i>';
            }
            
            toast.innerHTML = `${icon} ${message}`;
            toast.className = `toast ${type}`;
            toast.classList.add('show');
            
            setTimeout(() => {
                toast.classList.remove('show');
            }, 4000);
        }
        
        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'logout.php';
            }
        }
    </script>
</body>
</html>