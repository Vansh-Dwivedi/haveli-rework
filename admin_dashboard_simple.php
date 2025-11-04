<?php
/**
 * SIMPLIFIED ADMIN DASHBOARD
 * Clean, easy-to-understand interface for Haveli Restaurant
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
    <title>Haveli Restaurant - Simple Admin Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="admin-dashboard-simple.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
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
                        <span id="pending-count" class="status-badge status-pending" style="display: none;">0</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#orders" class="nav-link" onclick="showSection('orders')">
                        <i class="fas fa-shopping-cart"></i>
                        Orders
                        <span id="orders-count" class="status-badge status-pending" style="display: none;">0</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#email-system" class="nav-link" onclick="showSection('email-system')">
                        <i class="fas fa-envelope"></i>
                        Email System
                        <span id="email-queue-count" class="status-badge status-pending" style="display: none;">0</span>
                    </a>
                </li>
                <li class="nav-item" style="margin-top: 20px; border-top: 1px solid var(--gray-200); padding-top: 15px;">
                    <a href="#blog" class="nav-link blog-link" onclick="openBlogInDashboard(event)">
                        <i class="fas fa-blog"></i>
                        Blog Admin
                    </a>
                </li>
                <li class="nav-item">
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
                <h1>Admin Dashboard</h1>
                <p>Simple, clean interface for managing your restaurant</p>
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
                    <div style="display:flex; gap:8px; align-items:center; flex-direction:column;">
                        <div style="display:flex; gap:8px; width:100%; justify-content:space-between; align-items:center;">
                            <div class="reservations-filters" role="search" aria-label="Filter reservations">
                            <input id="res-search" type="text" placeholder="Search name or email" aria-label="Search by customer name or email">
                            <select id="res-status" aria-label="Filter by status">
                                <option value="all">All</option>
                                <option value="pending">Pending</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="refused">Refused</option>
                            </select>
                            <input id="res-date-from" type="date" aria-label="Reservation date from">
                            <input id="res-date-to" type="date" aria-label="Reservation date to">
                            <input id="res-guests-min" type="number" min="1" placeholder="Min guests" aria-label="Minimum guests">
                            <input id="res-guests-max" type="number" min="1" placeholder="Max guests" aria-label="Maximum guests">
                            <button class="btn" id="res-search-btn" onclick="handleReservationSearch()" aria-label="Apply reservation filters">
                                <i class="fas fa-search"></i>
                            </button>
                            <button class="btn" id="res-clear-btn" onclick="clearReservationFilters()" aria-label="Clear reservation filters">
                                <i class="fas fa-times"></i>
                            </button>
                            </div>
                            <div class="bulk-actions" style="margin-left:12px;">
                                <button class="btn" onclick="toggleSelectAll()" id="select-all-btn" aria-label="Select all visible reservations">Select All</button>
                                <button class="btn btn-success" id="bulk-confirm-btn" onclick="bulkConfirmSelected()" disabled aria-label="Confirm selected reservations">Confirm Selected</button>
                                <button class="btn btn-danger" id="bulk-refuse-btn" onclick="bulkRefuseSelected()" disabled aria-label="Refuse selected reservations">Refuse Selected</button>
                                <button class="btn btn-primary" onclick="loadReservations(1, reservationsPerPage)">
                                    <i class="fas fa-sync"></i> Refresh
                                </button>
                            </div>
                        </div>

                        <div style="width:100%; display:flex; justify-content:space-between; align-items:center;">
                            <div class="filter-chips" id="filter-chips" aria-live="polite"></div>
                            <button class="btn" id="clear-all-filters" onclick="clearAllFilters()" style="display:none;">Clear All Filters</button>
                        </div>
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
                        <i class="fas fa-envelope-open-text"></i> Email System
                    </h2>
                    <div>
                        <button class="btn btn-primary" onclick="checkEmailStatus()">
                            <i class="fas fa-sync"></i> Check Status
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
            
            <!-- Blog Admin Section (loads admin_blog.php in an iframe to keep admin environment isolated) -->
            <div id="blog" class="content-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-blog"></i> Blog Admin
                    </h2>
                    <div>
                        <button class="btn btn-primary" onclick="reloadBlogFrame()">
                            <i class="fas fa-sync"></i> Reload Blog Admin
                        </button>
                    </div>
                </div>

                <div id="blog-content" style="padding:12px;">
                    <iframe id="blog-frame" src="about:blank" style="width:100%; height:72vh; border:1px solid rgba(0,0,0,0.08); border-radius:8px; background:#fff;" title="Blog Admin"></iframe>
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
    
    <!-- Simple Refusal Modal -->
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
        // Simple JavaScript for clean interface
        document.addEventListener('DOMContentLoaded', function() {
            refreshDashboard();
            
            // Auto-refresh every 30 seconds
            setInterval(refreshDashboard, 30000);
            
            // Initialize mobile features
            initializeMobileFeatures();
            
            // Initialize modal handlers
            initializeModalHandlers();
        });
        
        // Mobile Menu Functions
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
        
        // Section Navigation
        function showSection(sectionId, event) {
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
                case 'blog':
                    // ensure blog iframe is loaded when user navigates programmatically
                    ensureBlogLoaded();
                    break;
            }
        }

        // Blog iframe helpers
        let _blogLoaded = false;
        function openBlogInDashboard(event) {
            if (event) event.preventDefault();
            // Use showSection to handle nav active state
            showSection('blog');

            // mark the blog link active
            document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
            const blogLink = document.querySelector('.nav-link.blog-link');
            if (blogLink) blogLink.classList.add('active');

            ensureBlogLoaded(true);
        }

        function ensureBlogLoaded(forceReload = false) {
            const iframe = document.getElementById('blog-frame');
            if (!iframe) return;
            if (!_blogLoaded || forceReload) {
                iframe.src = 'admin_blog.php';
                _blogLoaded = true;
            }
        }

        function reloadBlogFrame() {
            const iframe = document.getElementById('blog-frame');
            if (iframe) {
                iframe.contentWindow.location.reload();
            }
        }
        
        // Dashboard Functions
        async function refreshDashboard() {
            try {
                const response = await fetch('admin_dashboard_api.php?action=get_stats');
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('total-reservations').textContent = data.stats.total_reservations;
                    document.getElementById('pending-reservations').textContent = data.stats.pending_reservations;
                    document.getElementById('total-orders').textContent = data.stats.total_orders;
                    document.getElementById('emails-sent').textContent = data.stats.emails_sent;
                    
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
            } catch (error) {
                console.error('Dashboard refresh error:', error);
                showToast('Error loading dashboard data', 'error');
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
        
        // Reservations Functions
        let currentReservationsPage = 1;
        let reservationsPerPage = 10; // default, can be changed by the UI

    async function loadReservations(page = 1, limit = reservationsPerPage) {
            currentReservationsPage = page;
            reservationsPerPage = limit;

            document.getElementById('reservations-content').innerHTML = '<p>Loading reservations...</p>';

            try {
        // Collect filter parameters from UI if available
                const qEl = document.getElementById('res-search');
                const statusEl = document.getElementById('res-status');
                const dateFromEl = document.getElementById('res-date-from');
                const dateToEl = document.getElementById('res-date-to');
                const guestsMinEl = document.getElementById('res-guests-min');
                const guestsMaxEl = document.getElementById('res-guests-max');

                const q = qEl ? qEl.value.trim() : '';
                const status = statusEl ? statusEl.value : '';
                const dateFrom = dateFromEl ? dateFromEl.value : '';
                const dateTo = dateToEl ? dateToEl.value : '';
                const guestsMin = guestsMinEl ? guestsMinEl.value : '';
                const guestsMax = guestsMaxEl ? guestsMaxEl.value : '';

                let url = `admin_dashboard_api.php?action=get_reservations&page=${page}&limit=${limit}`;
                if (q) url += `&q=${encodeURIComponent(q)}`;
                if (status) url += `&status=${encodeURIComponent(status)}`;
                if (dateFrom) url += `&date_from=${encodeURIComponent(dateFrom)}`;
                if (dateTo) url += `&date_to=${encodeURIComponent(dateTo)}`;
                if (guestsMin) url += `&guests_min=${encodeURIComponent(guestsMin)}`;
                if (guestsMax) url += `&guests_max=${encodeURIComponent(guestsMax)}`;

        const response = await fetch(url);
                const data = await response.json();

                if (data.success) {
                    if (!Array.isArray(data.reservations) || data.reservations.length === 0) {
                        document.getElementById('reservations-content').innerHTML = `
                            <div class="email-status">
                                <h3><i class="fas fa-info-circle"></i> No Reservations Found</h3>
                                <p>No reservations are available for the selected page.</p>
                            </div>
                        `;
                        return;
                    }

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
                        const statusClass = reservation.status ? reservation.status.toLowerCase() : 'unknown';
                        html += `
                            <tr>
                                <td class="checkbox-col"><input type="checkbox" class="row-select" data-id="${reservation.id}" onchange="handleRowSelectChange()"></td>
                                <td>${reservation.id}</td>
                                <td>${reservation.customer_name || ''}<br><small>${reservation.email || ''}</small></td>
                                <td>${reservation.reservation_date || ''}<br>${reservation.reservation_time || ''}</td>
                                <td>${reservation.num_guests || ''}</td>
                                <td><span class="status-badge status-${statusClass}">${reservation.status || ''}</span></td>
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

                    // Render pagination controls
                    const pagination = data.pagination || {};
                    html += renderReservationsPagination(pagination);

                    document.getElementById('reservations-content').innerHTML = html;
                } else {
                    document.getElementById('reservations-content').innerHTML = '<p>Error loading reservations.</p>';
                }
            } catch (error) {
                console.error('Load reservations error:', error);
                document.getElementById('reservations-content').innerHTML = '<p>Error loading reservations.</p>';
            }
        }

        // Filter chips rendering
        function renderFilterChips() {
            const q = document.getElementById('res-search').value.trim();
            const status = document.getElementById('res-status').value;
            const dateFrom = document.getElementById('res-date-from').value;
            const dateTo = document.getElementById('res-date-to').value;
            const gmin = document.getElementById('res-guests-min').value;
            const gmax = document.getElementById('res-guests-max').value;

            const chips = [];
            if (q) chips.push({ key: 'q', label: `Search: ${q}` });
            if (status && status !== 'all') chips.push({ key: 'status', label: `Status: ${status}` });
            if (dateFrom && dateTo) chips.push({ key: 'date', label: `Date: ${dateFrom} → ${dateTo}` });
            else if (dateFrom) chips.push({ key: 'date_from', label: `From: ${dateFrom}` });
            else if (dateTo) chips.push({ key: 'date_to', label: `To: ${dateTo}` });
            if (gmin) chips.push({ key: 'gmin', label: `Guests ≥ ${gmin}` });
            if (gmax) chips.push({ key: 'gmax', label: `Guests ≤ ${gmax}` });

            const container = document.getElementById('filter-chips');
            if (!container) return;
            container.innerHTML = '';
            if (chips.length === 0) {
                const clearBtn = document.getElementById('clear-all-filters');
                if (clearBtn) clearBtn.style.display = 'none';
                return;
            }

            chips.forEach(ch => {
                const el = document.createElement('div');
                el.className = 'filter-chip';
                el.innerHTML = `<span class="label">${ch.label}</span><span class="remove" role="button" tabindex="0" aria-label="Remove filter ${ch.label}" onclick="removeFilter('${ch.key}')">&times;</span>`;
                container.appendChild(el);
            });
            const clearBtn = document.getElementById('clear-all-filters');
            if (clearBtn) clearBtn.style.display = 'inline-block';
        }

        function removeFilter(key) {
            switch (key) {
                case 'q': document.getElementById('res-search').value = ''; break;
                case 'status': document.getElementById('res-status').value = 'all'; break;
                case 'date': document.getElementById('res-date-from').value = ''; document.getElementById('res-date-to').value = ''; break;
                case 'date_from': document.getElementById('res-date-from').value = ''; break;
                case 'date_to': document.getElementById('res-date-to').value = ''; break;
                case 'gmin': document.getElementById('res-guests-min').value = ''; break;
                case 'gmax': document.getElementById('res-guests-max').value = ''; break;
            }
            renderFilterChips();
            loadReservations(1, reservationsPerPage);
        }

        function clearAllFilters() {
            document.getElementById('res-search').value = '';
            document.getElementById('res-status').value = 'all';
            document.getElementById('res-date-from').value = '';
            document.getElementById('res-date-to').value = '';
            document.getElementById('res-guests-min').value = '';
            document.getElementById('res-guests-max').value = '';
            renderFilterChips();
            loadReservations(1, reservationsPerPage);
            const clearBtn = document.getElementById('clear-all-filters');
            if (clearBtn) clearBtn.style.display = 'none';
        }

        function renderReservationsPagination(pagination) {
            const total = pagination.total || 0;
            const current = pagination.current_page || 1;
            const perPage = pagination.per_page || reservationsPerPage;
            const totalPages = pagination.total_pages || Math.max(1, Math.ceil(total / perPage));

            // Helper to build page button HTML
            function pageButton(page, label, isActive = false) {
                const ariaCurrent = isActive ? ' aria-current="page"' : '';
                return `<button class="btn ${isActive ? 'active' : ''}" onclick="loadReservations(${page}, ${perPage})" aria-label="Go to page ${page}"${ariaCurrent}>${label}</button>`;
            }

            // Build a compact page range with ellipses if many pages
            const maxButtons = 9; // including first/last
            let pageHtml = '';

            if (totalPages <= maxButtons) {
                for (let p = 1; p <= totalPages; p++) {
                    pageHtml += pageButton(p, p, p === current);
                }
            } else {
                // Always show first page
                pageHtml += pageButton(1, 1, current === 1);

                let start = Math.max(2, current - 2);
                let end = Math.min(totalPages - 1, current + 2);

                if (start > 2) {
                    pageHtml += `<span class="ellipsis">&hellip;</span>`;
                }

                for (let p = start; p <= end; p++) {
                    pageHtml += pageButton(p, p, p === current);
                }

                if (end < totalPages - 1) {
                    pageHtml += `<span class="ellipsis">&hellip;</span>`;
                }

                // Always show last page
                pageHtml += pageButton(totalPages, totalPages, current === totalPages);
            }

            return `
                <div class="pagination-wrapper" style="margin-top:12px; display:flex; align-items:center; justify-content:space-between; gap:12px;">
                    <div class="pagination-info">Showing ${pagination.start || 0} - ${pagination.end || 0} of ${total}</div>
                    <div class="pagination-controls" style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;" role="navigation" aria-label="Reservations pagination">
                        <button class="btn" ${pagination.has_prev ? '' : 'disabled'} onclick="loadReservations(${pagination.prev_page || 1}, ${perPage})" aria-label="Previous page">&laquo; Prev</button>
                        ${pageHtml}
                        <button class="btn" ${pagination.has_next ? '' : 'disabled'} onclick="loadReservations(${pagination.next_page || totalPages}, ${perPage})" aria-label="Next page">Next &raquo;</button>
                        <select onchange="loadReservations(1, parseInt(this.value))" style="margin-left:12px;">
                            <option value="5" ${perPage==5? 'selected' : ''}>5</option>
                            <option value="10" ${perPage==10? 'selected' : ''}>10</option>
                            <option value="20" ${perPage==20? 'selected' : ''}>20</option>
                            <option value="50" ${perPage==50? 'selected' : ''}>50</option>
                        </select>
                    </div>
                </div>
            `;
        }
        
        // Orders Functions
        async function loadOrders() {
            document.getElementById('orders-content').innerHTML = '<p>Loading orders...</p>';
            
            try {
                const response = await fetch('admin_dashboard_api.php?action=get_orders');
                const data = await response.json();
                
                if (data.success) {
                    if (data.orders.length === 0) {
                        document.getElementById('orders-content').innerHTML = `
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
                    }
                } else {
                    document.getElementById('orders-content').innerHTML = '<p>Error loading orders.</p>';
                }
            } catch (error) {
                console.error('Load orders error:', error);
                document.getElementById('orders-content').innerHTML = '<p>Error loading orders.</p>';
            }
        }
        
        // Email System Functions
        async function loadEmailSystem() {
            document.getElementById('email-system-content').innerHTML = '<p>Loading email system status...</p>';
            
            try {
                const response = await fetch('admin_dashboard_api.php?action=get_email_status');
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('email-system-content').innerHTML = `
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
                document.getElementById('email-system-content').innerHTML = '<p>Error loading email system status.</p>';
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
        
        // Reservation Actions
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

        // Selection & bulk action helpers
        function getSelectedIds() {
            const els = Array.from(document.querySelectorAll('.row-select'));
            return els.filter(e => e.checked).map(e => parseInt(e.getAttribute('data-id')));
        }

        function handleRowSelectChange() {
            const selected = getSelectedIds();
            document.getElementById('bulk-confirm-btn').disabled = selected.length === 0;
            document.getElementById('bulk-refuse-btn').disabled = selected.length === 0;
        }

        function toggleSelectAll() {
            const all = Array.from(document.querySelectorAll('.row-select'));
            const anyUnchecked = all.some(ch => !ch.checked);
            all.forEach(ch => ch.checked = anyUnchecked);
            handleRowSelectChange();
            // Update Select All button text
            document.getElementById('select-all-btn').textContent = anyUnchecked ? 'Deselect All' : 'Select All';
        }

        async function bulkUpdateSelected(status) {
            const ids = getSelectedIds();
            if (ids.length === 0) return;
            if (!confirm(`Apply '${status}' to ${ids.length} reservations?`)) return;

            showLoading();
            try {
                const response = await fetch('admin_dashboard_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ action: 'bulk_update_reservations', reservation_ids: JSON.stringify(ids), status })
                });
                const data = await response.json();
                if (data.success) {
                    // If API returned an undo token, show undo option in toast
                    if (data.undo_token) {
                        showToastWithUndo(data.message || 'Updated reservations', data.undo_token);
                    } else {
                        showToast(data.message || 'Updated reservations', 'success');
                    }
                    loadReservations(currentReservationsPage, reservationsPerPage);
                    refreshDashboard();
                } else {
                    showToast(data.message || 'Error updating reservations', 'error');
                }
            } catch (err) {
                console.error('Bulk update error', err);
                showToast('Error updating reservations', 'error');
            } finally {
                hideLoading();
            }
        }

        function bulkConfirmSelected() { bulkUpdateSelected('confirmed'); }
        function bulkRefuseSelected() { bulkUpdateSelected('refused'); }

        // Undo support: call API to undo a previous bulk update
        async function bulkUndoToken(token) {
            if (!confirm('Undo the last bulk update?')) return;
            showLoading();
            try {
                const response = await fetch('admin_dashboard_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ action: 'bulk_undo_update', token })
                });
                const data = await response.json();
                if (data.success) {
                    showToast(data.message || 'Reverted changes', 'success');
                    loadReservations(1, reservationsPerPage);
                    refreshDashboard();
                } else {
                    showToast(data.message || 'Error reverting changes', 'error');
                }
            } catch (e) {
                console.error('Bulk undo error', e);
                showToast('Error reverting changes', 'error');
            } finally {
                hideLoading();
            }
        }

        function showToastWithUndo(message, token) {
            const toast = document.getElementById('toast');
            // Accessibility attributes so screen readers announce the toast
            toast.setAttribute('role', 'status');
            toast.setAttribute('aria-live', 'polite');
            // Make toast focusable so keyboard users are informed
            toast.setAttribute('tabindex', '0');

            // Build content with a dedicated undo button (no inline onclick)
            toast.innerHTML = `
                <span>${message}</span>
                <button type="button" id="toast-undo-btn" class="btn btn-sm" aria-label="Undo bulk update" style="margin-left:8px;">Undo</button>
            `;

            toast.className = `toast success`;
            toast.classList.add('show');

            // Wire up undo handler using the token from this scope
            const btn = document.getElementById('toast-undo-btn');
            if (btn) {
                // Ensure previous listeners are not duplicated
                btn.replaceWith(btn.cloneNode(true));
                const freshBtn = document.getElementById('toast-undo-btn');
                freshBtn.addEventListener('click', function () { bulkUndoToken(token); });
                // Move keyboard focus to the undo button for immediate action
                setTimeout(() => { freshBtn.focus(); }, 50);
            } else {
                // Fallback: focus the toast itself
                setTimeout(() => { toast.focus(); }, 50);
            }

            // Auto-hide behavior with pause on interaction
            const hide = () => {
                toast.classList.remove('show');
                toast.removeAttribute('tabindex');
                toast.removeAttribute('role');
                toast.removeAttribute('aria-live');
            };

            let hideTimer = setTimeout(hide, 8000);
            const clearHide = () => { clearTimeout(hideTimer); };
            const restartHide = () => { hideTimer = setTimeout(hide, 4000); };

            toast.addEventListener('mouseenter', clearHide);
            toast.addEventListener('mouseleave', restartHide);
            toast.addEventListener('focusin', clearHide);
            toast.addEventListener('focusout', restartHide);
        }

        // Search/filter helpers for reservations
        function handleReservationSearch() {
            // When search applied, go to page 1
            loadReservations(1, reservationsPerPage);
        }

        function clearReservationFilters() {
            const qEl = document.getElementById('res-search');
            const dateEl = document.getElementById('res-date');
            if (qEl) qEl.value = '';
            if (dateEl) dateEl.value = '';
            loadReservations(1, reservationsPerPage);
        }
        
        // Modal Functions
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
        
        // Utility Functions
        function showLoading() {
            document.getElementById('loading-overlay').classList.add('active');
        }
        
        function hideLoading() {
            document.getElementById('loading-overlay').classList.remove('active');
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
            }, 3000);
        }
        
        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'logout.php';
            }
        }
    </script>
        <script>
            // Sidebar active link detection: mark Blog Admin active when current path matches
            (function() {
                try {
                    const path = window.location.pathname.split('/').pop(); // filename
                    const blogLink = document.querySelector('.nav-link.blog-link');
                    if (!blogLink) return;
                    // If current page is admin_blog.php, mark link active
                    if (path === 'admin_blog.php') {
                        blogLink.classList.add('active');
                    }
                } catch (e) {
                    // silent
                }
            })();
        </script>
</body>
</html>