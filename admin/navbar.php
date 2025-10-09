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
 <nav class="navbar navbar-expand  navbar-light sticky-top px-4 py-0" style="background-color: #87abe0ff">
    <a href="index.html" class="navbar-brand d-flex d-lg-none me-4">
        <h2 class="text-warning mb-0"></h2>
    </a>
    
    <!-- Sidebar Toggler -->
    <a href="#" class="sidebar-toggler flex-shrink-0" id="sidebarToggle">
        <i class="fa fa-bars"></i>
    </a>

    <div class="navbar-nav align-items-center ms-auto">
        <div class="nav-item dropdown">
            <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                <img class="rounded-circle me-lg-2" src="img\2601828.png" alt="" style="width: 40px; height: 40px;">
                <span class="d-none d-lg-inline-flex"><?php echo $username; ?></span>
            </a>
            <div class="dropdown-menu dropdown-menu-end bg-light border-0 rounded-0 rounded-bottom m-0">
                <a href="logout.php" class="dropdown-item" style="border: 1px solid #b0a8a7"><i class="bi bi-arrow-right-circle"></i> Log Out</a>
            </div>
        </div>
    </div>
</nav>
<!-- Navbar End -->

<!-- JavaScript for Sidebar Toggle -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.sidebar');
    const content = document.querySelector('.content');
    
    if (sidebarToggle && sidebar && content) {
        sidebarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Toggle sidebar collapsed class
            sidebar.classList.toggle('collapsed');
            content.classList.toggle('expanded');
            
            // Update the icon
            const icon = this.querySelector('i');
            if (sidebar.classList.contains('collapsed')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            } else {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
            
            // Store state in localStorage
            const isCollapsed = sidebar.classList.contains('collapsed');
            localStorage.setItem('sidebarCollapsed', isCollapsed);
        });
        
        // Check for saved state on page load
        const savedState = localStorage.getItem('sidebarCollapsed');
        if (savedState === 'true') {
            sidebar.classList.add('collapsed');
            content.classList.add('expanded');
            const icon = sidebarToggle.querySelector('i');
            icon.classList.remove('fa-bars');
            icon.classList.add('fa-times');
        }
    }
});
</script>

<!-- Optional: Add CSS for smooth transitions -->
<style>
.sidebar {
    transition: all 0.3s ease;
}

.sidebar.collapsed {
    transform: translateX(-100%);
    width: 0 !important;
}

.content.expanded {
    margin-left: 0 !important;
    width: 100% !important;
}

/* Ensure proper transitions */
.container-fluid.position-relative {
    transition: all 0.3s ease;
}
</style>