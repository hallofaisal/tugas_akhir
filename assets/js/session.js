/**
 * Session Management JavaScript
 * File: assets/js/session.js
 * Description: Handles client-side session timeout warnings and automatic logout
 */

class SessionManager {
    constructor(timeoutMinutes = 30, warningMinutes = 5) {
        this.timeoutMinutes = timeoutMinutes;
        this.warningMinutes = warningMinutes;
        this.timeoutSeconds = timeoutMinutes * 60;
        this.warningSeconds = warningMinutes * 60;
        this.warningShown = false;
        this.countdownInterval = null;
        this.init();
    }

    init() {
        // Check if user is logged in
        if (this.isLoggedIn()) {
            this.startSessionMonitoring();
            this.setupActivityListeners();
        }
    }

    isLoggedIn() {
        // Check if there's a user session (you can customize this check)
        return document.body.classList.contains('logged-in') || 
               document.querySelector('[data-user-id]') !== null;
    }

    startSessionMonitoring() {
        // Check session every minute
        setInterval(() => {
            this.checkSession();
        }, 60000);

        // Initial check
        this.checkSession();
    }

    checkSession() {
        const lastActivity = this.getLastActivity();
        const currentTime = Math.floor(Date.now() / 1000);
        const timeSinceActivity = currentTime - lastActivity;

        // Show warning if approaching timeout
        if (timeSinceActivity >= (this.timeoutSeconds - this.warningSeconds) && !this.warningShown) {
            this.showTimeoutWarning();
        }

        // Auto logout if timeout reached
        if (timeSinceActivity >= this.timeoutSeconds) {
            this.autoLogout();
        }
    }

    getLastActivity() {
        return parseInt(localStorage.getItem('lastActivity') || Date.now() / 1000);
    }

    updateLastActivity() {
        localStorage.setItem('lastActivity', Math.floor(Date.now() / 1000).toString());
    }

    setupActivityListeners() {
        // Update activity on user interaction
        const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'];
        
        events.forEach(event => {
            document.addEventListener(event, () => {
                this.updateLastActivity();
            }, true);
        }

        // Update activity on page focus
        window.addEventListener('focus', () => {
            this.updateLastActivity();
        });

        // Update activity on page visibility change
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                this.updateLastActivity();
            }
        });
    }

    showTimeoutWarning() {
        this.warningShown = true;
        
        // Create warning modal
        const modal = document.createElement('div');
        modal.id = 'session-warning-modal';
        modal.innerHTML = `
            <div class="session-warning-overlay">
                <div class="session-warning-modal">
                    <div class="session-warning-header">
                        <h3>⚠️ Sesi Akan Berakhir</h3>
                    </div>
                    <div class="session-warning-body">
                        <p>Sesi Anda akan berakhir dalam <span id="session-countdown">${this.warningMinutes}</span> menit karena tidak aktif.</p>
                        <p>Klik "Lanjutkan Sesi" untuk tetap login.</p>
                    </div>
                    <div class="session-warning-footer">
                        <button id="extend-session" class="btn btn-primary">🔄 Lanjutkan Sesi</button>
                        <button id="logout-now" class="btn btn-secondary">🚪 Logout Sekarang</button>
                    </div>
                </div>
            </div>
        `;

        // Add styles
        const style = document.createElement('style');
        style.textContent = `
            .session-warning-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 9999;
            }
            
            .session-warning-modal {
                background: white;
                padding: 30px;
                border-radius: 10px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
                max-width: 400px;
                width: 90%;
                text-align: center;
            }
            
            .session-warning-header h3 {
                margin: 0 0 20px 0;
                color: #dc3545;
            }
            
            .session-warning-body {
                margin-bottom: 25px;
            }
            
            .session-warning-body p {
                margin: 10px 0;
                color: #666;
            }
            
            .session-warning-footer {
                display: flex;
                gap: 10px;
                justify-content: center;
                flex-wrap: wrap;
            }
            
            .btn {
                padding: 10px 20px;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                font-weight: 600;
                transition: background 0.3s ease;
            }
            
            .btn-primary {
                background: #007bff;
                color: white;
            }
            
            .btn-primary:hover {
                background: #0056b3;
            }
            
            .btn-secondary {
                background: #6c757d;
                color: white;
            }
            
            .btn-secondary:hover {
                background: #545b62;
            }
            
            #session-countdown {
                font-weight: bold;
                color: #dc3545;
            }
        `;

        document.head.appendChild(style);
        document.body.appendChild(modal);

        // Start countdown
        let countdown = this.warningMinutes * 60;
        this.countdownInterval = setInterval(() => {
            countdown--;
            const minutes = Math.floor(countdown / 60);
            const seconds = countdown % 60;
            document.getElementById('session-countdown').textContent = 
                `${minutes}:${seconds.toString().padStart(2, '0')}`;
            
            if (countdown <= 0) {
                this.autoLogout();
            }
        }, 1000);

        // Handle extend session
        document.getElementById('extend-session').addEventListener('click', () => {
            this.extendSession();
        });

        // Handle logout now
        document.getElementById('logout-now').addEventListener('click', () => {
            this.logoutNow();
        });
    }

    extendSession() {
        // Clear warning
        this.clearWarning();
        
        // Update activity
        this.updateLastActivity();
        
        // Reset warning flag
        this.warningShown = false;
        
        // Show success message
        this.showMessage('Sesi diperpanjang!', 'success');
    }

    logoutNow() {
        // Clear warning
        this.clearWarning();
        
        // Redirect to logout
        window.location.href = 'logout.php';
    }

    autoLogout() {
        // Clear warning
        this.clearWarning();
        
        // Show timeout message
        this.showMessage('Sesi Anda telah berakhir karena tidak aktif.', 'error');
        
        // Redirect to login after delay
        setTimeout(() => {
            window.location.href = 'logout.php?timeout=1';
        }, 2000);
    }

    clearWarning() {
        const modal = document.getElementById('session-warning-modal');
        if (modal) {
            modal.remove();
        }
        
        if (this.countdownInterval) {
            clearInterval(this.countdownInterval);
            this.countdownInterval = null;
        }
    }

    showMessage(message, type = 'info') {
        // Create message element
        const messageDiv = document.createElement('div');
        messageDiv.className = `session-message session-message-${type}`;
        messageDiv.textContent = message;
        messageDiv.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 5px;
            color: white;
            font-weight: 600;
            z-index: 10000;
            animation: slideIn 0.3s ease;
        `;

        // Set background color based on type
        switch (type) {
            case 'success':
                messageDiv.style.background = '#28a745';
                break;
            case 'error':
                messageDiv.style.background = '#dc3545';
                break;
            default:
                messageDiv.style.background = '#17a2b8';
        }

        // Add animation styles
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
        `;
        document.head.appendChild(style);

        document.body.appendChild(messageDiv);

        // Remove message after 5 seconds
        setTimeout(() => {
            messageDiv.remove();
        }, 5000);
    }
}

// Initialize session manager when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    // Initialize session manager with 30 minutes timeout and 5 minutes warning
    window.sessionManager = new SessionManager(30, 5);
});

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = SessionManager;
} 