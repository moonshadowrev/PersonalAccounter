<?php defined('APP_RAN') or die('Direct access not allowed'); ?>

<?php if (!empty($flash_messages)): ?>
<div class="flash-messages-container">
    <?php foreach ($flash_messages as $flash): ?>
        <?php
        $alertClass = '';
        $iconClass = '';
        switch ($flash['type']) {
            case 'success':
                $alertClass = 'alert-success';
                $iconClass = 'fas fa-check-circle';
                break;
            case 'error':
                $alertClass = 'alert-danger';
                $iconClass = 'fas fa-exclamation-circle';
                break;
            case 'warning':
                $alertClass = 'alert-warning';
                $iconClass = 'fas fa-exclamation-triangle';
                break;
            case 'info':
                $alertClass = 'alert-info';
                $iconClass = 'fas fa-info-circle';
                break;
            default:
                $alertClass = 'alert-primary';
                $iconClass = 'fas fa-info-circle';
        }
        ?>
        <div class="alert <?php echo $alertClass; ?> alert-dismissible fade show flash-message d-flex align-items-start" role="alert">
            <div class="flash-message-content">
                <i class="<?php echo $iconClass; ?> me-2"></i>
                <span class="flash-message-text"><?php echo htmlspecialchars($flash['message']); ?></span>
            </div>
            <button type="button" class="btn-close ms-auto flex-shrink-0" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endforeach; ?>
</div>

<style>
.flash-messages-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 1050;
    max-width: 400px;
}

.flash-message {
    margin-bottom: 10px !important;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    border: none !important;
    border-radius: 8px !important;
    padding: 12px 16px !important;
    position: relative;
    word-wrap: break-word;
    word-break: break-word;
    overflow-wrap: break-word;
    line-height: 1.4;
    gap: 8px; /* Space between content and close button */
}

.flash-message-content {
    display: flex;
    align-items: center;
    flex: 1;
    min-width: 0; /* Allow content to shrink */
}

.flash-message-content i {
    font-size: 16px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    height: 1.4em; /* Match line-height */
    margin-top: 0;
}

.flash-message-text {
    word-wrap: break-word;
    word-break: break-word;
    overflow-wrap: break-word;
    flex: 1;
    min-width: 0; /* Allow text to shrink and wrap */
}

.flash-message .btn-close {
    margin-left: 8px !important;
    margin-top: 0 !important;
    margin-bottom: 0 !important;
    padding: 4px !important;
    width: 20px !important;
    height: 20px !important;
    font-size: 12px !important;
    opacity: 0.7;
    transition: opacity 0.2s ease;
    flex-shrink: 0 !important;
}

.flash-message .btn-close:hover {
    opacity: 1;
}

/* Auto-hide flash messages after 5 seconds */
.flash-message.auto-hide {
    animation: slideInRight 0.3s ease-out, fadeOut 0.5s ease-in 4.5s forwards;
}

@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@keyframes fadeOut {
    from {
        opacity: 1;
        transform: translateX(0);
    }
    to {
        opacity: 0;
        transform: translateX(100%);
    }
}

/* Mobile responsiveness */
@media (max-width: 768px) {
    .flash-messages-container {
        top: 10px;
        right: 10px;
        left: 10px;
        max-width: none;
    }
    
    .flash-message {
        padding: 10px 12px !important;
        font-size: 14px;
        gap: 6px;
    }
    
    .flash-message .btn-close {
        width: 18px !important;
        height: 18px !important;
        font-size: 11px !important;
        margin-left: 6px !important;
    }
}

/* Extra small screens */
@media (max-width: 480px) {
    .flash-message {
        padding: 8px 10px !important;
        font-size: 13px;
        gap: 4px;
    }
    
    .flash-message-content i {
        font-size: 14px;
    }
    
    .flash-message .btn-close {
        width: 16px !important;
        height: 16px !important;
        font-size: 10px !important;
        margin-left: 4px !important;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add auto-hide class to all flash messages
    const flashMessages = document.querySelectorAll('.flash-message');
    flashMessages.forEach(function(message) {
        message.classList.add('auto-hide');
        
        // Auto-remove after animation completes
        setTimeout(function() {
            if (message.parentNode) {
                message.remove();
            }
        }, 5000);
    });
});
</script>
<?php endif; ?> 