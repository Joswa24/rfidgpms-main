<?php
include '../connection.php';
$logo1 = "";
// Fetch data from the about table
$sql = "SELECT * FROM about LIMIT 1";
$result = $db->query($sql);

if ($result->num_rows > 0) {
    // Output data of each row
    $row = $result->fetch_assoc();
    $logo1 = $row['logo1'];
} 

$username = "";
// Fetch data from the about table
$sql1 = "SELECT * FROM user LIMIT 1";
$result1 = $db->query($sql1);

if ($result1->num_rows > 0) {
    // Output data of each row
    $row = $result1->fetch_assoc();
    $username = $row['username'];
} 
?>
<!-- Navbar Start -->
<nav class="navbar navbar-expand-lg navbar-light sticky-top px-3 py-2" style="background-color: #87abe0ff">
    <div class="container-fluid">
        <!-- Brand/Logo -->
        <a href="index.php" class="navbar-brand d-flex align-items-center me-3">
            <h2 class="text-warning mb-0 fs-6">Admin Panel</h2>
        </a>
        
        <!-- Mobile Menu Toggler -->
        <button class="navbar-toggler border-0 me-2" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Sidebar Toggler - Visible on all screens -->
        <a href="#" class="sidebar-toggler flex-shrink-0 me-3" id="sidebarToggle">
            <i class="fa fa-bars fs-5"></i>
        </a>

        <div class="collapse navbar-collapse" id="navbarCollapse">
            <div class="navbar-nav align-items-center ms-auto">
                <div class="nav-item dropdown">
                    <a href="#" class="nav-link dropdown-toggle d-flex align-items-center" data-bs-toggle="dropdown">
                        <img class="rounded-circle me-2" src="img/2601828.png" alt="Profile" style="width: 35px; height: 35px;">
                        <span class="d-none d-sm-inline"><?php echo htmlspecialchars($username); ?></span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end bg-light border-0 rounded-0 rounded-bottom m-0">
                        <a href="logout.php" class="dropdown-item" style="border: 1px solid #b0a8a7">
                            <i class="bi bi-arrow-right-circle me-2"></i> Log Out
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>
<!-- Navbar End -->

<!-- Backdrop for Mobile Sidebar -->
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<!-- JavaScript for Sidebar Toggle -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarBackdrop = document.getElementById('sidebarBackdrop');
    const sidebar = document.querySelector('.sidebar');
    const content = document.querySelector('.content');
    
    // Function to toggle sidebar
    function toggleSidebar() {
        if (sidebar && content) {
            sidebar.classList.toggle('active');
            content.classList.toggle('sidebar-active');
            
            // Toggle backdrop
            if (sidebarBackdrop) {
                if (sidebar.classList.contains('active')) {
                    sidebarBackdrop.style.display = 'block';
                    // Prevent body scrolling when sidebar is open on mobile
                    if (window.innerWidth <= 992) {
                        document.body.style.overflow = 'hidden';
                    }
                } else {
                    sidebarBackdrop.style.display = 'none';
                    document.body.style.overflow = '';
                }
            }
            
            // Update the icon
            const icon = sidebarToggle.querySelector('i');
            if (sidebar.classList.contains('active')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            } else {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
            
            // Store state in localStorage
            const isActive = sidebar.classList.contains('active');
            localStorage.setItem('sidebarActive', isActive);
        }
    }
    
    // Initialize sidebar state
    function initializeSidebar() {
        const savedState = localStorage.getItem('sidebarActive');
        
        if (sidebar && content) {
            // On mobile (≤ 992px), sidebar starts closed
            if (window.innerWidth <= 992) {
                sidebar.classList.remove('active');
                content.classList.remove('sidebar-active');
                if (sidebarBackdrop) sidebarBackdrop.style.display = 'none';
                updateIcon('bars');
            } else {
                // On desktop (> 992px), use saved state or default to open
                if (savedState === 'true') {
                    sidebar.classList.add('active');
                    content.classList.add('sidebar-active');
                    updateIcon('times');
                } else if (savedState === 'false') {
                    sidebar.classList.remove('active');
                    content.classList.remove('sidebar-active');
                    updateIcon('bars');
                } else {
                    // Default state for desktop (open)
                    sidebar.classList.add('active');
                    content.classList.add('sidebar-active');
                    updateIcon('times');
                }
            }
        }
    }
    
    // Update sidebar toggle icon
    function updateIcon(state) {
        const icon = sidebarToggle.querySelector('i');
        if (icon) {
            if (state === 'times') {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            } else {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        }
    }
    
    // Add event listeners
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            toggleSidebar();
        });
    }
    
    // Close sidebar when clicking on backdrop (mobile)
    if (sidebarBackdrop) {
        sidebarBackdrop.addEventListener('click', function() {
            if (window.innerWidth <= 992) {
                toggleSidebar();
            }
        });
    }
    
    // Close sidebar when clicking on a link (mobile only)
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 992 && sidebar && sidebar.classList.contains('active')) {
            // Check if click is outside sidebar and not on toggler
            if (!e.target.closest('.sidebar') && 
                !e.target.closest('#sidebarToggle') &&
                !e.target.closest('.navbar-toggler')) {
                toggleSidebar();
            }
        }
    });
    
    // Handle window resize
    window.addEventListener('resize', function() {
        initializeSidebar();
    });
    
    // Close sidebar when pressing escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar && sidebar.classList.contains('active')) {
            toggleSidebar();
        }
    });
    
    // Initialize sidebar on load
    initializeSidebar();
});
</script>

<!-- Responsive CSS for Sidebar -->
<style>
/* Sidebar Base Styles */
.sidebar {
    position: fixed;
    top: 0;
    left: -300px;
    width: 280px;
    height: 100vh;
    z-index: 1000;
    transition: all 0.3s ease;
    overflow-y: auto;
    background: #fff;
    box-shadow: 2px 0 10px rgba(0,0,0,0.1);
}

.sidebar.active {
    left: 0;
}

/* Content Area Adjustments */
.content {
    transition: all 0.3s ease;
    width: 100%;
    margin-left: 0;
}

.content.sidebar-active {
    margin-left: 280px;
    width: calc(100% - 280px);
}

/* Backdrop for Mobile */
.sidebar-backdrop {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 999;
    cursor: pointer;
}

/* Responsive Behavior */
@media (max-width: 992px) {
    .sidebar {
        left: -300px;
    }
    
    .sidebar.active {
        left: 0;
    }
    
    .content.sidebar-active {
        margin-left: 0;
        width: 100%;
    }
    
    /* Adjust navbar for mobile */
    .navbar {
        padding: 0.5rem 1rem;
    }
    
    .navbar-brand {
        font-size: 0.9rem;
    }
}

@media (min-width: 993px) {
    .sidebar {
        position: fixed;
        left: 0;
        height: 100vh;
    }
    
    .sidebar:not(.active) {
        left: -280px;
    }
    
    .content:not(.sidebar-active) {
        margin-left: 0;
        width: 100%;
    }
}

/* Navbar Mobile Optimizations */
@media (max-width: 576px) {
    .navbar-brand h2 {
        font-size: 1rem !important;
    }
    
    .nav-link.dropdown-toggle span {
        font-size: 0.9rem;
    }
    
    .dropdown-menu {
        min-width: 200px;
    }
    
    .sidebar {
        width: 260px;
    }
    
    .container-fluid.px-3 {
        padding-left: 1rem !important;
        padding-right: 1rem !important;
    }
}

/* Smooth transitions */
.container-fluid.position-relative {
    transition: all 0.3s ease;
}

/* Ensure proper scrolling */
body {
    overflow-x: hidden;
}

/* Improve touch targets */
.sidebar-toggler {
    padding: 8px 12px;
    border-radius: 5px;
    transition: background-color 0.2s ease;
    text-decoration: none;
    color: inherit;
}

.sidebar-toggler:hover {
    background-color: rgba(255,255,255,0.1);
}

.navbar-toggler {
    padding: 4px 8px;
}

/* Prevent horizontal scroll */
html, body {
    max-width: 100%;
    overflow-x: hidden;
}

/* Mobile navbar collapse improvements */
@media (max-width: 992px) {
    .navbar-collapse {
        background-color: #87abe0ff;
        padding: 1rem;
        margin-top: 0.5rem;
        border-radius: 5px;
    }
    
    .nav-item.dropdown {
        width: 100%;
    }
    
    .dropdown-menu {
        position: static !important;
        transform: none !important;
        width: 100%;
        border: none;
        box-shadow: none;
    }
}
</style>