// SweetAlert2 Dark Theme Helper Function
function showDarkAlert(options) {
    const defaultOptions = {
        background: '#1f2937',
        color: '#fff',
        customClass: {
            popup: 'swal2-dark',
            title: 'swal2-title-dark',
            content: 'swal2-content-dark',
            confirmButton: 'swal2-confirm-dark',
            cancelButton: 'swal2-cancel-dark'
        }
    };
    
    // Merge user options with default dark theme options
    const mergedOptions = { ...defaultOptions, ...options };
    
    return Swal.fire(mergedOptions);
}

document.addEventListener('DOMContentLoaded', function() {
    const mobileToggle = document.querySelector('.mobile-toggle');
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.overlay');
    const closeSidebarButton = document.querySelector('.close-sidebar');

    if (mobileToggle && sidebar) {
        mobileToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }

    if (overlay) {
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('active');
        });
    }

    if (closeSidebarButton) {
        closeSidebarButton.addEventListener('click', function() {
            sidebar.classList.remove('active');
        });
    }

    // Tab functionality for profile page
    const tabs = document.querySelectorAll('.nav-tabs .nav-link');
    const tabContents = document.querySelectorAll('.tab-content');

    tabs.forEach(tab => {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            
            tabs.forEach(item => item.classList.remove('active'));
            tab.classList.add('active');

            const target = tab.getAttribute('data-tab');
            
            tabContents.forEach(content => {
                if (content.id === target) {
                    content.classList.add('active');
                } else {
                    content.classList.remove('active');
                }
            });
        });
    });

    // SweetAlert2 for delete confirmation
    document.body.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('delete-btn')) {
            // Check if this delete button has an onclick handler (like expenses)
            if (e.target.hasAttribute('onclick')) {
                // Let the onclick handler take care of it
                return;
            }
            
            e.preventDefault();
            const form = e.target.closest('form');
            
            // Only proceed if we found a form
            if (!form) {
                console.warn('Delete button clicked but no form found');
                return;
            }
            
            showDarkAlert({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            }).catch((error) => {
                console.error('Alert error:', error);
            });
        }
    });
});
