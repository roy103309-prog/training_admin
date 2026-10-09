/**
 * Toast Notification System
 * Theme-aware toast notifications to replace alert()
 */

const Toast = {
    container: null,

    init: function() {
        if (!this.container) {
            this.container = document.createElement('div');
            this.container.id = 'toast-container';
            this.container.style.cssText = 'position:fixed;top:80px;right:20px;z-index:99999;min-width:300px;max-width:400px;';
            document.body.appendChild(this.container);
        }
    },

    show: function(message, type = 'info', duration = 4000) {
        this.init();

        const icons = {
            success: '<i class="bx bx-check-circle" style="font-size:24px;"></i>',
            error: '<i class="bx bx-error-circle" style="font-size:24px;"></i>',
            warning: '<i class="bx bx-error" style="font-size:24px;"></i>',
            info: '<i class="bx bx-info-circle" style="font-size:24px;"></i>'
        };

        const colors = {
            success: { bg: '#d4edda', border: '#28a745', text: '#155724', icon: '#28a745' },
            error: { bg: '#f8d7da', border: '#dc3545', text: '#721c24', icon: '#dc3545' },
            warning: { bg: '#fff3cd', border: '#ffc107', text: '#856404', icon: '#ffc107' },
            info: { bg: '#d1ecf1', border: '#17a2b8', text: '#0c5460', icon: '#17a2b8' }
        };

        const color = colors[type] || colors.info;
        const icon = icons[type] || icons.info;

        const toast = document.createElement('div');
        toast.className = 'toast-item';
        toast.style.cssText = `
            background: ${color.bg};
            border-left: 4px solid ${color.border};
            color: ${color.text};
            padding: 16px 20px;
            margin-bottom: 10px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideIn 0.3s ease-out;
            font-family: 'Roboto', sans-serif;
        `;

        toast.innerHTML = `
            <div style="color:${color.icon};flex-shrink:0;">${icon}</div>
            <div style="flex:1;font-size:14px;font-weight:500;">${message}</div>
            <button onclick="this.parentElement.remove()" style="background:none;border:none;color:${color.text};cursor:pointer;font-size:20px;opacity:0.7;padding:0;line-height:1;">×</button>
        `;

        this.container.appendChild(toast);

        if (duration > 0) {
            setTimeout(() => {
                toast.style.animation = 'slideOut 0.3s ease-in';
                setTimeout(() => toast.remove(), 300);
            }, duration);
        }
    },

    success: function(message, duration) {
        this.show(message, 'success', duration);
    },

    error: function(message, duration) {
        this.show(message, 'error', duration);
    },

    warning: function(message, duration) {
        this.show(message, 'warning', duration);
    },

    info: function(message, duration) {
        this.show(message, 'info', duration);
    }
};

// Add animations
if (!document.getElementById('toast-animations')) {
    const style = document.createElement('style');
    style.id = 'toast-animations';
    style.textContent = `
        @keyframes slideIn {
            from { transform: translateX(400px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(400px); opacity: 0; }
        }
    `;
    document.head.appendChild(style);
}

// Make it globally available
window.Toast = Toast;
window.toast = Toast; // lowercase alias

// Override alert() for backward compatibility
window.originalAlert = window.alert;
window.alert = function(message) {
    if (typeof message === 'string') {
        Toast.info(message);
    } else {
        window.originalAlert(message);
    }
};
