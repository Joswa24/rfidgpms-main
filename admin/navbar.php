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
        <a href="index.html" class="navbar-brand d-flex align-items-center">
            <h2 class="text-warning mb-0 fs-6">Admin Panel</h2>
        </a>
        
        <!-- Mobile Menu Toggler -->
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Sidebar Toggler - Visible on all screens -->
        <a href="#" class="sidebar-toggler flex-shrink-0 me-3 d-none d-lg-block" id="sidebarToggleDesktop">
            <i class="fa fa-bars fs-5"></i>
        </a>

        <!-- Mobile Sidebar Toggler - Visible only on mobile -->
        <a href="#" class="sidebar-toggler flex-shrink-0 me-3 d-block d-lg-none" id="sidebarToggleMobile">
            <i class="fa fa-bars fs-5"></i>
        </a>

        <div class="collapse navbar-collapse" id="navbarCollapse">
            <div class="navbar-nav align-items-center ms-auto">
                <div class="nav-item dropdown">
                    <a href="#" class="nav-link dropdown-toggle d-flex align-items-center" data-bs-toggle="dropdown">
                        <img class="rounded-circle me-2" src="img/2601828.png" alt="Profile" style="width: 35px; height: 35px;">
                        <span class="d-none d-sm-inline"><?php echo $username; ?></span>
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

<!-- JavaScript for Sidebar Toggle -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Function to toggle sidebar
    function toggleSidebar() {
        const sidebar = document.querySelector('.sidebar');
        const content = document.querySelector('.content');
        
        if (sidebar && content) {
            sidebar.classList.toggle('active');
            content.classList.toggle('sidebar-active');
            
            // Update the icons
            const icons = document.querySelectorAll('.sidebar-toggler i');
            icons.forEach(icon => {
                if (sidebar.classList.contains('active')) {
                    icon.classList.remove('fa-bars');
                    icon.classList.add('fa-times');
                } else {
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-bars');
                }
            });
            
            // Store state in localStorage
            const isActive = sidebar.classList.contains('active');
            localStorage.setItem('sidebarActive', isActive);
        }
    }
    
    // Initialize sidebar state
    function initializeSidebar() {
        const sidebar = document.querySelector('.sidebar');
        const content = document.querySelector('.content');
        const savedState = localStorage.getItem('sidebarActive');
        
        if (sidebar && content) {
            // On mobile, sidebar starts closed by default
            if (window.innerWidth <= 992) {
                sidebar.classList.remove('active');
                content.classList.remove('sidebar-active');
                updateIcons('bars');
            } else {
                // On desktop, use saved state or default to open
                if (savedState === 'true') {
                    sidebar.classList.add('active');
                    content.classList.add('sidebar-active');
                    updateIcons('times');
                } else if (savedState === 'false') {
                    sidebar.classList.remove('active');
                    content.classList.remove('sidebar-active');
                    updateIcons('bars');
                } else {
                    // Default state for desktop (open)
                    sidebar.classList.add('active');
                    content.classList.add('sidebar-active');
                    updateIcons('times');
                }
            }
        }
    }
    
    // Update all sidebar toggle icons
    function updateIcons(state) {
        const icons = document.querySelectorAll('.sidebar-toggler i');
        icons.forEach(icon => {
            if (state === 'times') {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            } else {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        });
    }
    
    // Add event listeners to both togglers
    const sidebarToggleDesktop = document.getElementById('sidebarToggleDesktop');
    const sidebarToggleMobile = document.getElementById('sidebarToggleMobile');
    
    if (sidebarToggleDesktop) {
        sidebarToggleDesktop.addEventListener('click', function(e) {
            e.preventDefault();
            toggleSidebar();
        });
    }
    
    if (sidebarToggleMobile) {
        sidebarToggleMobile.addEventListener('click', function(e) {
            e.preventDefault();
            toggleSidebar();
            
            // Close mobile navbar collapse when sidebar is toggled
            const navbarCollapse = document.getElementById('navbarCollapse');
            if (navbarCollapse && navbarCollapse.classList.contains('show')) {
                new bootstrap.Collapse(navbarCollapse).toggle();
            }
        });
    }
    
    // Initialize sidebar on load
    initializeSidebar();
    
    // Handle window resize
    window.addEventListener('resize', function() {
        initializeSidebar();
    });
    
    // Close sidebar when clicking on a link (mobile only)
    if (window.innerWidth <= 992) {
        document.addEventListener('click', function(e) {
            const sidebar = document.querySelector('.sidebar');
            const content = document.querySelector('.content');
            
            if (sidebar && sidebar.classList.contains('active') && 
                !e.target.closest('.sidebar') && 
                !e.target.closest('.sidebar-toggler')) {
                sidebar.classList.remove('active');
                content.classList.remove('sidebar-active');
                updateIcons('bars');
            }
        });
    }
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
}

.sidebar.active + .sidebar-backdrop {
    display: block;
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
    
    /* Hide desktop toggler on mobile */
    #sidebarToggleDesktop {
        display: none !important;
    }
    
    /* Show mobile toggler */
    #sidebarToggleMobile {
        display: block !important;
    }
    
    /* Adjust navbar padding for mobile */
    .navbar {
        padding: 0.5rem 1rem;
    }
    
    /* Mobile-specific backdrop */
    .sidebar::after {
        content: '';
        position: fixed;
        top: 0;
        left: 280px;
        width: calc(100% - 280px);
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: -1;
        display: none;
    }
    
    .sidebar.active::after {
        display: block;
    }
}

@media (min-width: 993px) {
    .sidebar {
        position: relative;
        left: 0;
        height: 100vh;
    }
    
    .sidebar:not(.active) {
        margin-left: -280px;
    }
    
    /* Hide mobile toggler on desktop */
    #sidebarToggleMobile {
        display: none !important;
    }
    
    /* Show desktop toggler */
    #sidebarToggleDesktop {
        display: block !important;
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
}

/* Smooth transitions */
.container-fluid.position-relative {
    transition: all 0.3s ease;
}

/* Ensure proper scrolling on mobile */
body {
    overflow-x: hidden;
}

/* Improve touch targets for mobile */
.sidebar-toggler {
    padding: 8px 12px;
    border-radius: 5px;
    transition: background-color 0.2s ease;
}

.sidebar-toggler:hover {
    background-color: rgba(255,255,255,0.1);
}

.navbar-toggler {
    padding: 4px 8px;
}
</style>