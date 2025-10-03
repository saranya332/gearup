<?php
$pageTitle = "Notifications - Smart Driving School";

// Get notification statistics
require_once 'classes/NotificationClass.php';
$notificationClass = new NotificationClass($conn);

$notificationStats = $notificationClass->getNotificationStats($_SESSION['user_id']);
$unreadCount = $notificationClass->getUnreadCount($_SESSION['user_id']);

$stats = $notificationStats['success'] ? $notificationStats['stats'] : [];
$unreadTotal = $unreadCount['success'] ? $unreadCount['count'] : 0;
?>

<div class="container py-4">
    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Notifications Header -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="mb-0">
                            <i class="fas fa-bell me-2"></i>
                            Notifications
                            <?php if ($unreadTotal > 0): ?>
                                <span class="badge bg-danger ms-2"><?= $unreadTotal ?></span>
                            <?php endif; ?>
                        </h3>
                        <div class="btn-group">
                            <button class="btn btn-light btn-sm" onclick="markAllAsRead()" 
                                    <?= $unreadTotal > 0 ? '' : 'disabled' ?>>
                                <i class="fas fa-check-double me-1"></i>Mark All Read
                            </button>
                            <button class="btn btn-light btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="fas fa-filter me-1"></i>Filter
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" onclick="filterNotifications('all')">All Notifications</a></li>
                                <li><a class="dropdown-item" href="#" onclick="filterNotifications('unread')">Unread Only</a></li>
                                <li><a class="dropdown-item" href="#" onclick="filterNotifications('info')">Information</a></li>
                                <li><a class="dropdown-item" href="#" onclick="filterNotifications('success')">Success</a></li>
                                <li><a class="dropdown-item" href="#" onclick="filterNotifications('warning')">Warnings</a></li>
                                <li><a class="dropdown-item" href="#" onclick="filterNotifications('reminder')">Reminders</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="card-body">
                    <div class="row g-3 text-center">
                        <div class="col-md-3">
                            <div class="h4 text-primary mb-1"><?= $stats['total_notifications'] ?? 0 ?></div>
                            <small class="text-muted">Total</small>
                        </div>
                        <div class="col-md-3">
                            <div class="h4 text-warning mb-1"><?= $stats['unread_notifications'] ?? 0 ?></div>
                            <small class="text-muted">Unread</small>
                        </div>
                        <div class="col-md-3">
                            <div class="h4 text-success mb-1"><?= $stats['read_notifications'] ?? 0 ?></div>
                            <small class="text-muted">Read</small>
                        </div>
                        <div class="col-md-3">
                            <div class="h4 text-info mb-1"><?= $stats['reminder_notifications'] ?? 0 ?></div>
                            <small class="text-muted">Reminders</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Notifications List -->
            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <div id="notificationsList">
                        <div class="text-center p-4">
                            <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                            <p class="mt-2">Loading notifications...</p>
                        </div>
                    </div>
                    
                    <!-- Load More Button -->
                    <div class="text-center p-3 border-top d-none" id="loadMoreSection">
                        <button class="btn btn-outline-primary" id="loadMoreBtn" onclick="loadMoreNotifications()">
                            <i class="fas fa-chevron-down me-2"></i>Load More
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Quick Actions -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0">
                        <i class="fas fa-bolt me-2 text-warning"></i>
                        Quick Actions
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-primary" onclick="refreshNotifications()">
                            <i class="fas fa-sync me-2"></i>Refresh
                        </button>
                        <button class="btn btn-outline-success" onclick="markAllAsRead()" <?= $unreadTotal > 0 ? '' : 'disabled' ?>>
                            <i class="fas fa-check-double me-2"></i>Mark All Read
                        </button>
                        <button class="btn btn-outline-danger" onclick="clearAllNotifications()">
                            <i class="fas fa-trash me-2"></i>Clear All
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Notification Types -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0">
                        <i class="fas fa-chart-pie me-2 text-info"></i>
                        By Category
                    </h6>
                </div>
                <div class="card-body">
                    <div class="notification-stats">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-info-circle text-info me-2"></i>
                                <span>Information</span>
                            </div>
                            <span class="badge bg-info"><?= $stats['info_notifications'] ?? 0 ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                <span>Success</span>
                            </div>
                            <span class="badge bg-success"><?= $stats['success_notifications'] ?? 0 ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                                <span>Warnings</span>
                            </div>
                            <span class="badge bg-warning"><?= $stats['warning_notifications'] ?? 0 ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-clock text-secondary me-2"></i>
                                <span>Reminders</span>
                            </div>
                            <span class="badge bg-secondary"><?= $stats['reminder_notifications'] ?? 0 ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Notification Settings -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-light">
                    <h6 class="mb-0">
                        <i class="fas fa-cog me-2 text-secondary"></i>
                        Settings
                    </h6>
                </div>
                <div class="card-body">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="emailNotifications" checked>
                        <label class="form-check-label" for="emailNotifications">
                            Email Notifications
                        </label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="lessonReminders" checked>
                        <label class="form-check-label" for="lessonReminders">
                            Lesson Reminders
                        </label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="paymentAlerts" checked>
                        <label class="form-check-label" for="paymentAlerts">
                            Payment Alerts
                        </label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="progressUpdates" checked>
                        <label class="form-check-label" for="progressUpdates">
                            Progress Updates
                        </label>
                    </div>
                    
                    <hr>
                    
                    <button class="btn btn-outline-primary btn-sm w-100" onclick="saveNotificationSettings()">
                        <i class="fas fa-save me-2"></i>Save Settings
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Notification Detail Modal -->
<div class="modal fade" id="notificationModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="notificationModalTitle">Notification Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="notificationModalBody">
                <!-- Notification details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-danger" id="deleteNotificationBtn" onclick="deleteNotification()">
                    <i class="fas fa-trash me-2"></i>Delete
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let currentFilter = 'all';
let currentPage = 1;
let isLoading = false;
let currentNotificationId = null;

document.addEventListener('DOMContentLoaded', function() {
    loadNotifications();
    
    // Auto-refresh notifications every 30 seconds
    setInterval(refreshNotifications, 30000);
});

function loadNotifications(page = 1, filter = 'all') {
    if (isLoading) return;
    
    isLoading = true;
    currentFilter = filter;
    currentPage = page;
    
    const listContainer = document.getElementById('notificationsList');
    
    if (page === 1) {
        listContainer.innerHTML = `
            <div class="text-center p-4">
                <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                <p class="mt-2">Loading notifications...</p>
            </div>
        `;
    }
    
    const params = new URLSearchParams({
        action: 'get_notifications',
        limit: 10
    });
    
    if (filter === 'unread') {
        params.append('unread_only', 'true');
    }
    
    fetch(`controllers/notification.php?${params}`)
        .then(response => response.json())
        .then(data => {
            isLoading = false;
            
            if (data.success) {
                displayNotifications(data.notifications, page === 1);
            } else {
                listContainer.innerHTML = `
                    <div class="text-center text-danger p-4">
                        <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                        <p>Failed to load notifications</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            isLoading = false;
            console.error('Error:', error);
            listContainer.innerHTML = `
                <div class="text-center text-danger p-4">
                    <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                    <p>Error loading notifications</p>
                </div>
            `;
        });
}

function displayNotifications(notifications, replace = true) {
    const listContainer = document.getElementById('notificationsList');
    const loadMoreSection = document.getElementById('loadMoreSection');
    
    if (notifications.length === 0 && replace) {
        listContainer.innerHTML = `
            <div class="text-center p-5">
                <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                <h6 class="text-muted">No notifications</h6>
                <p class="text-muted">You're all caught up! No new notifications at this time.</p>
            </div>
        `;
        loadMoreSection.classList.add('d-none');
        return;
    }
    
    let html = '';
    
    notifications.forEach(notification => {
        const isUnread = !notification.is_read;
        const typeIcon = getNotificationIcon(notification.type);
        const typeClass = getNotificationClass(notification.type);
        const timeAgo = getTimeAgo(notification.created_at);
        
        html += `
            <div class="notification-item border-bottom p-3 ${isUnread ? 'bg-light border-start border-primary border-3' : ''}" 
                 data-id="${notification.id}" onclick="viewNotification(${notification.id})">
                <div class="d-flex align-items-start">
                    <div class="flex-shrink-0 me-3">
                        <div class="notification-icon bg-${typeClass} text-white rounded-circle d-flex align-items-center justify-content-center" 
                             style="width: 40px; height: 40px;">
                            <i class="${typeIcon}"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <h6 class="mb-0 ${isUnread ? 'fw-bold' : ''}">${notification.title}</h6>
                            <div class="d-flex align-items-center gap-2">
                                ${isUnread ? '<span class="badge bg-primary rounded-pill">New</span>' : ''}
                                <small class="text-muted">${timeAgo}</small>
                            </div>
                        </div>
                        <p class="mb-1 text-muted">${truncateText(notification.message, 100)}</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="badge bg-${typeClass} bg-opacity-25 text-${typeClass}">${notification.type.toUpperCase()}</span>
                            ${isUnread ? `
                                <button class="btn btn-sm btn-outline-primary" onclick="markAsRead(${notification.id}, event)">
                                    <i class="fas fa-check me-1"></i>Mark Read
                                </button>
                            ` : ''}
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    
    if (replace) {
        listContainer.innerHTML = html;
    } else {
        listContainer.innerHTML += html;
    }
    
    // Show/hide load more button
    if (notifications.length === 10) {
        loadMoreSection.classList.remove('d-none');
    } else {
        loadMoreSection.classList.add('d-none');
    }
}

function viewNotification(notificationId) {
    currentNotificationId = notificationId;
    
    // Mark as read when viewed
    fetch('controllers/notification.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'mark_as_read',
            notification_id: notificationId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update UI to show as read
            const notificationElement = document.querySelector(`[data-id="${notificationId}"]`);
            if (notificationElement) {
                notificationElement.classList.remove('bg-light', 'border-start', 'border-primary', 'border-3');
                const newBadge = notificationElement.querySelector('.badge.bg-primary.rounded-pill');
                if (newBadge) newBadge.remove();
                const markReadBtn = notificationElement.querySelector('.btn-outline-primary');
                if (markReadBtn) markReadBtn.remove();
            }
            
            // Update unread count
            updateUnreadCount();
        }
    });
    
    // Show notification details (implement modal display)
    showNotificationDetails(notificationId);
}

function showNotificationDetails(notificationId) {
    // For now, just show a simple modal
    // In a real implementation, you'd fetch full notification details
    const modal = new bootstrap.Modal(document.getElementById('notificationModal'));
    
    document.getElementById('notificationModalTitle').textContent = 'Notification Details';
    document.getElementById('notificationModalBody').innerHTML = `
        <div class="text-center">
            <i class="fas fa-info-circle fa-3x text-primary mb-3"></i>
            <h5>Notification viewed</h5>
            <p class="text-muted">This notification has been marked as read.</p>
        </div>
    `;
    
    modal.show();
}

function markAsRead(notificationId, event) {
    if (event) {
        event.stopPropagation();
    }
    
    fetch('controllers/notification.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'mark_as_read',
            notification_id: notificationId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update UI
            const notificationElement = document.querySelector(`[data-id="${notificationId}"]`);
            if (notificationElement) {
                notificationElement.classList.remove('bg-light', 'border-start', 'border-primary', 'border-3');
                const newBadge = notificationElement.querySelector('.badge.bg-primary.rounded-pill');
                if (newBadge) newBadge.remove();
                const markReadBtn = notificationElement.querySelector('.btn-outline-primary');
                if (markReadBtn) markReadBtn.remove();
            }
            
            updateUnreadCount();
        } else {
            alert('Failed to mark notification as read');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error marking notification as read');
    });
}

function markAllAsRead() {
    if (confirm('Mark all notifications as read?')) {
        fetch('controllers/notification.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'mark_all_as_read'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Refresh notifications
                loadNotifications();
                updateUnreadCount();
                
                // Show success message
                alert(`${data.affected_rows || 'All'} notifications marked as read`);
            } else {
                alert('Failed to mark notifications as read');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error marking notifications as read');
        });
    }
}

function deleteNotification() {
    if (!currentNotificationId) return;
    
    if (confirm('Delete this notification?')) {
        fetch('controllers/notification.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'delete_notification',
                notification_id: currentNotificationId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('notificationModal')).hide();
                
                // Remove from UI
                const notificationElement = document.querySelector(`[data-id="${currentNotificationId}"]`);
                if (notificationElement) {
                    notificationElement.remove();
                }
                
                currentNotificationId = null;
            } else {
                alert('Failed to delete notification');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error deleting notification');
        });
    }
}

function clearAllNotifications() {
    if (confirm('Delete all notifications? This action cannot be undone.')) {
        // This would need to be implemented in the backend
        alert('Clear all feature coming soon!');
    }
}

function filterNotifications(filter) {
    loadNotifications(1, filter);
}

function loadMoreNotifications() {
    loadNotifications(currentPage + 1, currentFilter);
}

function refreshNotifications() {
    loadNotifications(1, currentFilter);
    updateUnreadCount();
}

function updateUnreadCount() {
    fetch('controllers/notification.php?action=get_unread_count')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const badgeElements = document.querySelectorAll('.badge.bg-danger');
                badgeElements.forEach(badge => {
                    if (data.count > 0) {
                        badge.textContent = data.count;
                        badge.style.display = '';
                    } else {
                        badge.style.display = 'none';
                    }
                });
                
                // Update mark all read button
                const markAllBtn = document.querySelector('[onclick="markAllAsRead()"]');
                if (markAllBtn) {
                    markAllBtn.disabled = data.count === 0;
                }
            }
        })
        .catch(error => console.error('Error updating unread count:', error));
}

function saveNotificationSettings() {
    const settings = {
        email_notifications: document.getElementById('emailNotifications').checked,
        lesson_reminders: document.getElementById('lessonReminders').checked,
        payment_alerts: document.getElementById('paymentAlerts').checked,
        progress_updates: document.getElementById('progressUpdates').checked
    };
    
    // Save to localStorage for now (in real implementation, save to backend)
    localStorage.setItem('notification_settings', JSON.stringify(settings));
    
    alert('Notification settings saved!');
}

function getNotificationIcon(type) {
    const icons = {
        'info': 'fas fa-info-circle',
        'success': 'fas fa-check-circle',
        'warning': 'fas fa-exclamation-triangle',
        'reminder': 'fas fa-clock',
        'alert': 'fas fa-exclamation-circle'
    };
    return icons[type] || 'fas fa-bell';
}

function getNotificationClass(type) {
    const classes = {
        'info': 'info',
        'success': 'success',
        'warning': 'warning',
        'reminder': 'secondary',
        'alert': 'danger'
    };
    return classes[type] || 'primary';
}

function truncateText(text, maxLength) {
    if (text.length <= maxLength) return text;
    return text.substring(0, maxLength) + '...';
}

function getTimeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffInSeconds = Math.floor((now - date) / 1000);
    
    if (diffInSeconds < 60) return 'Just now';
    if (diffInSeconds < 3600) return Math.floor(diffInSeconds / 60) + 'm ago';
    if (diffInSeconds < 86400) return Math.floor(diffInSeconds / 3600) + 'h ago';
    if (diffInSeconds < 2592000) return Math.floor(diffInSeconds / 86400) + 'd ago';
    return date.toLocaleDateString();
}
</script>

<style>
.notification-item {
    cursor: pointer;
    transition: all 0.3s ease;
}

.notification-item:hover {
    background-color: #f8f9fa !important;
    transform: translateX(5px);
}

.notification-icon {
    transition: all 0.3s ease;
}

.notification-item:hover .notification-icon {
    transform: scale(1.1);
}

.notification-stats .d-flex {
    transition: all 0.3s ease;
}

.notification-stats .d-flex:hover {
    background-color: #f8f9fa;
    border-radius: 6px;
    padding: 8px;
    margin: -8px;
}

.form-check-input:checked {
    background-color: var(--bs-primary);
    border-color: var(--bs-primary);
}

.form-check-input:focus {
    border-color: var(--bs-primary);
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}
</style>