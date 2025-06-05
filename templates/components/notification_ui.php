<?php
// C:\xampp\htdocs\UNICRIBS\templates\components\notification_ui.php
?>
<div class="notification-system">
    <!-- Notification Bell Icon -->
    <div class="notification-bell" id="notificationBell">
        <i class="fas fa-bell"></i>
        <span class="notification-count" id="notificationCount">0</span>
    </div>
    
    <!-- Notification Dropdown/Popup -->
    <div class="notification-container" id="notificationContainer">
        <div class="notification-header">
            <h3>Notifications</h3>
            <button class="mark-all-read" id="markAllRead">Mark all as read</button>
        </div>
        <div class="notification-list" id="notificationList">
            <!-- Notifications will be loaded here via AJAX -->
            <div class="notification-empty">No new notifications</div>
        </div>
        <div class="notification-footer">
            <a href="/notifications">View all notifications</a>
        </div>
    </div>
    
    <!-- Toast Notification (for new notifications) -->
    <div class="notification-toast" id="notificationToast"></div>
</div>

<style>
.notification-system {
    position: relative;
    z-index: 1000;
}

.notification-bell {
    position: fixed;
    bottom: 30px;
    right: 30px;
    width: 56px;
    height: 56px;
    background: linear-gradient(135deg, #6e8efb, #a777e3);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    cursor: pointer;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    transition: all 0.3s ease;
    z-index: 1001;
}

.notification-bell:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 25px rgba(0, 0, 0, 0.2);
}

.notification-count {
    position: absolute;
    top: -5px;
    right: -5px;
    background-color: #ff4757;
    color: white;
    border-radius: 50%;
    width: 22px;
    height: 22px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: bold;
}

.notification-container {
    position: fixed;
    bottom: 100px;
    right: 30px;
    width: 350px;
    max-height: 500px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    overflow: hidden;
    display: none;
    flex-direction: column;
    z-index: 1000;
}

.notification-header {
    padding: 16px;
    border-bottom: 1px solid #f1f1f1;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: linear-gradient(135deg, #6e8efb, #a777e3);
    color: white;
}

.notification-header h3 {
    margin: 0;
    font-size: 18px;
}

.mark-all-read {
    background: transparent;
    border: none;
    color: white;
    cursor: pointer;
    font-size: 12px;
    text-decoration: underline;
    padding: 4px;
}

.notification-list {
    flex: 1;
    overflow-y: auto;
    background: white;
}

.notification-item {
    padding: 14px 16px;
    border-bottom: 1px solid #f5f5f5;
    cursor: pointer;
    transition: background 0.2s;
}

.notification-item.unread {
    background: #f8f9fe;
}

.notification-item:hover {
    background: #f1f3ff;
}

.notification-title {
    font-weight: 600;
    margin-bottom: 4px;
    color: #333;
}

.notification-message {
    font-size: 14px;
    color: #666;
    margin-bottom: 4px;
    line-height: 1.4;
}

.notification-time {
    font-size: 12px;
    color: #999;
    text-align: right;
}

.notification-empty {
    padding: 20px;
    text-align: center;
    color: #999;
}

.notification-footer {
    padding: 12px;
    text-align: center;
    border-top: 1px solid #f1f1f1;
    background: #f9f9f9;
}

.notification-footer a {
    color: #6e8efb;
    text-decoration: none;
    font-size: 14px;
}

.notification-toast {
    position: fixed;
    bottom: 100px;
    right: 30px;
    width: 300px;
    padding: 16px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
    transform: translateY(30px);
    opacity: 0;
    transition: all 0.3s ease;
    z-index: 1002;
    display: none;
}

/* iPhone-style notification */
@media (max-width: 768px) {
    .notification-container {
        position: fixed;
        bottom: 0;
        right: 0;
        width: 100%;
        max-height: 70vh;
        border-radius: 16px 16px 0 0;
    }
    
    .notification-bell {
        bottom: 20px;
        right: 20px;
        width: 50px;
        height: 50px;
    }
    
    .notification-toast {
        width: calc(100% - 40px);
        right: 20px;
        bottom: 80px;
    }
}

/* Animation for notification items */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.notification-item {
    animation: fadeIn 0.3s ease forwards;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const notificationBell = document.getElementById('notificationBell');
    const notificationContainer = document.getElementById('notificationContainer');
    const notificationList = document.getElementById('notificationList');
    const notificationCount = document.getElementById('notificationCount');
    const markAllRead = document.getElementById('markAllRead');
    const notificationToast = document.getElementById('notificationToast');
    
    let isOpen = false;
    
    // Toggle notification dropdown
    notificationBell.addEventListener('click', function(e) {
        e.stopPropagation();
        isOpen = !isOpen;
        
        if (isOpen) {
            notificationContainer.style.display = 'flex';
            loadNotifications();
        } else {
            notificationContainer.style.display = 'none';
        }
    });
    
    // Close when clicking outside
    document.addEventListener('click', function() {
        if (isOpen) {
            notificationContainer.style.display = 'none';
            isOpen = false;
        }
    });
    
    // Prevent dropdown from closing when clicking inside
    notificationContainer.addEventListener('click', function(e) {
        e.stopPropagation();
    });
    
    // Mark all as read
    markAllRead.addEventListener('click', function() {
        fetch('/core/ajax/notifications.php?action=mark_all_read', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove unread styles
                document.querySelectorAll('.notification-item.unread').forEach(item => {
                    item.classList.remove('unread');
                });
                // Update count
                updateNotificationCount(0);
            }
        });
    });
    
    // Load notifications
    function loadNotifications() {
        fetch('/core/ajax/notifications.php?action=get', {
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.notifications && data.notifications.length > 0) {
                let html = '';
                data.notifications.forEach(notification => {
                    const timeAgo = getTimeAgo(notification.created_at);
                    const unreadClass = notification.is_read ? '' : 'unread';
                    
                    html += `
                        <div class="notification-item ${unreadClass}" data-id="${notification.id}">
                            <div class="notification-title">${notification.title}</div>
                            <div class="notification-message">${notification.message}</div>
                            <div class="notification-time">${timeAgo}</div>
                        </div>
                    `;
                });
                
                notificationList.innerHTML = html;
                
                // Add click handlers for each notification
                document.querySelectorAll('.notification-item').forEach(item => {
                    item.addEventListener('click', function() {
                        const notificationId = this.getAttribute('data-id');
                        
                        // Mark as read
                        if (this.classList.contains('unread')) {
                            fetch('/core/ajax/notifications.php?action=mark_read&id=' + notificationId, {
                                method: 'POST',
                                credentials: 'same-origin'
                            });
                            
                            this.classList.remove('unread');
                            updateNotificationCount(parseInt(notificationCount.textContent) - 1);
                        }
                        
                        // TODO: Handle notification click action based on type
                        // For now just close the dropdown
                        notificationContainer.style.display = 'none';
                        isOpen = false;
                    });
                });
            } else {
                notificationList.innerHTML = '<div class="notification-empty">No notifications found</div>';
            }
        });
    }
    
    // Update notification count
    function updateNotificationCount(count) {
        notificationCount.textContent = count;
        if (count > 0) {
            notificationCount.style.display = 'flex';
        } else {
            notificationCount.style.display = 'none';
        }
    }
    
    // Show toast notification
    function showToastNotification(title, message) {
        notificationToast.innerHTML = `
            <div class="notification-title">${title}</div>
            <div class="notification-message">${message}</div>
        `;
        notificationToast.style.display = 'block';
        
        setTimeout(() => {
            notificationToast.style.opacity = '1';
            notificationToast.style.transform = 'translateY(0)';
        }, 10);
        
        setTimeout(() => {
            notificationToast.style.opacity = '0';
            notificationToast.style.transform = 'translateY(30px)';
            setTimeout(() => {
                notificationToast.style.display = 'none';
            }, 300);
        }, 5000);
    }
    
    // Helper function to format time ago
    function getTimeAgo(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const seconds = Math.floor((now - date) / 1000);
        
        let interval = Math.floor(seconds / 31536000);
        if (interval >= 1) return interval + " year" + (interval === 1 ? "" : "s") + " ago";
        
        interval = Math.floor(seconds / 2592000);
        if (interval >= 1) return interval + " month" + (interval === 1 ? "" : "s") + " ago";
        
        interval = Math.floor(seconds / 86400);
        if (interval >= 1) return interval + " day" + (interval === 1 ? "" : "s") + " ago";
        
        interval = Math.floor(seconds / 3600);
        if (interval >= 1) return interval + " hour" + (interval === 1 ? "" : "s") + " ago";
        
        interval = Math.floor(seconds / 60);
        if (interval >= 1) return interval + " minute" + (interval === 1 ? "" : "s") + " ago";
        
        return Math.floor(seconds) + " second" + (seconds === 1 ? "" : "s") + " ago";
    }
    
    // Initialize - get unread count
    function initNotificationSystem() {
        fetch('/core/ajax/notifications.php?action=count', {
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.count !== undefined) {
                updateNotificationCount(data.count);
            }
        });
    }
    
    // Initialize
    initNotificationSystem();
    
    // Check for new notifications periodically (every 60 seconds)
    setInterval(initNotificationSystem, 60000);
    
    // For real-time notifications (using Server-Sent Events or WebSocket would be better)
    // This is a simple polling alternative
    let lastCheck = 0;
    setInterval(() => {
        fetch(`/core/ajax/notifications.php?action=check_new&since=${lastCheck}`, {
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.notifications && data.notifications.length > 0) {
                // Update last check time
                lastCheck = Math.max(...data.notifications.map(n => new Date(n.created_at).getTime()));
                
                // Show toast for each new notification
                data.notifications.forEach(notification => {
                    showToastNotification(notification.title, notification.message);
                });
                
                // Update count
                updateNotificationCount(parseInt(notificationCount.textContent) + data.notifications.length);
                
                // If dropdown is open, reload notifications
                if (isOpen) {
                    loadNotifications();
                }
            }
        });
    }, 15000); // Check every 15 seconds
});
</script>