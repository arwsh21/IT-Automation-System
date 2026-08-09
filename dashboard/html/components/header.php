<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==================== DATABASE CONNECTION ====================
require_once 'config/database.php';
$database = new Database();
$conn = $database->getConnection();

$userRole = isset($_SESSION['role']) ? $_SESSION['role'] : 'guest';
$userName = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Guest';
?>
<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="../assets/" data-template="vertical-menu-template-free">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>IT Automation System</title>
    <meta name="description" content="IT Automation System for University Labs" />
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../assets/img/favicon/favicon.ico" />
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet" />
    
    <!-- Icons -->
    <link rel="stylesheet" href="../assets/vendor/fonts/boxicons.css" />
    
    <!-- Core CSS -->
    <link rel="stylesheet" href="../assets/vendor/css/core.css" class="template-customizer-core-css" />
    <link rel="stylesheet" href="../assets/vendor/css/theme-default.css" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="../assets/css/demo.css" />
    
    <!-- Vendors CSS -->
    <link rel="stylesheet" href="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    <link rel="stylesheet" href="../assets/vendor/libs/apex-charts/apex-charts.css" />
    
    <!-- Helpers -->
    <script src="../assets/vendor/js/helpers.js"></script>
    <script src="../assets/js/config.js"></script>
    
    <style>
    .role-badge {
        position: fixed;
        top: 10px;
        right: 20px;
        z-index: 9999;
        font-size: 12px;
        padding: 5px 10px;
        border-radius: 20px;
        background: #696cff;
        color: white;
    }
    .notification-badge {
        position: absolute;
        top: -5px;
        right: -5px;
    }
    
    /* Dashboard card borders womp womp */
    .border-left-primary {
        border-left: 4px solid #696cff !important;
    }
    .border-left-success {
        border-left: 4px solid #28c76f !important;
    }
    .border-left-info {
        border-left: 4px solid #00cfe8 !important;
    }
    .border-left-warning {
        border-left: 4px solid #ff9f43 !important;
    }
</style>

</head>
<body>
    <!-- Role Display Badge -->
    <div class="role-badge">
        <i class="bx bx-user-circle"></i> <?php echo $userRole; ?>: <?php echo $userName; ?>
    </div>

    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <!-- Menu Sidebar -->
            <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
                <div class="app-brand demo">
                    <a href="index.php" class="app-brand-link">
                        <span class="app-brand-logo demo">
                            <i class="bx bx-server bx-lg"></i>
                        </span>
                        <span class="app-brand-text demo menu-text fw-bolder ms-2">IT Automation</span>
                    </a>
                    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
                        <i class="bx bx-chevron-left bx-sm align-middle"></i>
                    </a>
                </div>

                <div class="menu-inner-shadow"></div>

                <ul class="menu-inner py-1">
                    <!-- Dashboard - admin and tech wala -->
                    <?php if($userRole == 'admin' || $userRole == 'technician'): ?>
                    <li class="menu-item">
                        <a href="dashboard.php" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-home-circle"></i>
                            <div data-i18n="Dashboard">Dashboard</div>
                        </a>
                    </li>
                <?php endif; ?>
                
                <?php if($userRole == 'student'): ?>
                    <li class="menu-item">
                        <a href="student_dashboard.php" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-home-circle"></i>
                            <div data-i18n="Dashboard">Dashboard</div>
                        </a>
                    </li>
                <?php endif; ?>
                
                    <!-- ==================== TICKET SECTION ==================== -->
                    
                    <?php if($userRole == 'admin' || $userRole == 'technician' || $userRole == 'student'): ?>
                    <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link menu-toggle">
                            <i class="menu-icon tf-icons bx bx-ticket"></i>
                            <div data-i18n="Ticket">Ticket</div>
                        </a>
                        <ul class="menu-sub">
                            
                            <!-- Raise a Ticket - Student ONLY -->
                            <?php if($userRole == 'student'): ?>
                            <li class="menu-item">
                                <a href="add_ticket_std.php" class="menu-link">
                                    <div>Raise a Ticket</div>
                                </a>
                            </li>
                            <?php endif; ?>

                            <!-- View All Tickets - Admin & Tech ONLY -->
                            <?php if($userRole == 'admin' || $userRole == 'technician'): ?>
                            <li class="menu-item">
                                <a href="view_all_tickets.php" class="menu-link">
                                    <div>View All Tickets</div>
                                </a>
                            </li>
                            
                            <!-- Get Ticket Details - Admin & Tech ONLY -->
                            <li class="menu-item">
                                <a href="get_ticket_details.php" class="menu-link">
                                    <div>Get Ticket Details</div>
                                </a>
                            </li>    
                            <?php endif; ?>
                        
                            <!-- My Open Tickets - Student ONLY -->
                            <?php if($userRole == 'student'): ?>
                            <li class="menu-item">
                                <a href="my_open_tickets.php" class="menu-link">
                                    <div>My Open Tickets</div>
                                </a>
                            </li>
                            <?php endif; ?>

                            <!-- Resolution Summary - Admin & Tech ONLY -->
                            <?php if($userRole == 'admin' || $userRole == 'technician'): ?>
                            <li class="menu-item">
                                <a href="resolution_summary.php" class="menu-link">
                                    <div>Resolution Summary</div>
                                </a>
                            </li>
                            
                            <!-- Tickets Per PC - Admin & Tech ONLY -->
                            <li class="menu-item">
                                <a href="tickets_per_pc.php" class="menu-link">
                                    <div>Tickets Per PC</div>
                                </a>
                            </li>
                            
                            <!-- Ticket Statistics - Admin & Tech ONLY -->
                            <li class="menu-item">
                                <a href="ticket_statistics.php" class="menu-link">
                                    <div>Ticket Statistics</div>
                                </a>
                                </li>
                            
                            <!-- Frequent Issues - Admin & Tech ONLY -->
                            <li class="menu-item">
                                <a href="frequent_issues.php" class="menu-link">
                                    <div>Frequently Asked Issues</div>
                                </a>
                            </li>
                            <?php endif; ?>
                            
                        </ul>
                    </li>
                    <?php endif; ?>

                    <!-- ==================== SOFTWARE SECTION ==================== -->
                    <?php if($userRole == 'admin' || $userRole == 'technician'): ?>
                    <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link menu-toggle">
                            <i class="menu-icon tf-icons bx bx-download"></i>
                            <div data-i18n="Software">Software</div>
                        </a>
                        <ul class="menu-sub">
                            <!-- Add New Software - Admin ONLY -->
                            <li class="menu-item">
                                <a href="add_software.php" class="menu-link">
                                    <div>Add New Software</div>
                                </a>
                            </li>
                            
                            <!-- View All Software - Admin & Tech -->
                            <li class="menu-item">
                                <a href="view_all_software.php" class="menu-link">
                                    <div>View All Softwares</div>
                                </a>
                            </li>
                            
                            <!-- Installation Log - Admin & Tech -->
                             <li class="menu-item">
                                <a href="install_software.php" class="menu-link">
                                    <div>Install Software on PC</div>
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="uninstall_software.php" class="menu-link">
                                    <div>Uninstall Software</div>
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="installation_log.php" class="menu-link">
                                    <div>Installation Log</div>
                                </a>
                            </li>
                            
                            <!-- Software by PC - Admin & Tech -->
                            <li class="menu-item">
                                <a href="software_by_pc.php" class="menu-link">
                                    <div>Software by PC</div>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <?php endif; ?>
             
                    <!-- ==================== PC / LAB SECTION ==================== -->
                    <?php if($userRole == 'admin' || $userRole == 'technician'): ?>
                    <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link menu-toggle">
                            <i class="menu-icon tf-icons bx bx-desktop"></i>
                            <div data-i18n="PC Lab">PC / Lab</div>
                        </a>
                        <ul class="menu-sub">
                            <!-- Add New PC - Admin ONLY -->
                            <?php if($userRole == 'admin'): ?>
                            <li class="menu-item">
                                <a href="add_pc.php" class="menu-link">
                                    <div>Add New PC</div>
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <!-- View All PCs - Admin & Tech -->
                            <li class="menu-item">
                                <a href="view_all_pcs.php" class="menu-link">
                                    <div>View All PCs</div>
                                </a>
                            </li>
                            
                            <!-- Retire PC - Admin ONLY -->
                            <?php if($userRole == 'admin'): ?>
                            <li class="menu-item">
                                <a href="retire_pc.php" class="menu-link">
                                    <div>Retire PC</div>
                                </a>
                            </li>
                            <?php endif; ?>
                    
                        </ul>
                    </li>
                    <?php endif; ?>

                    <!-- ==================== STUDENT SECTION ==================== -->
                    <?php if($userRole == 'admin' || $userRole == 'technician'): ?>
                    <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link menu-toggle">
                            <i class="menu-icon tf-icons bx bx-user"></i>
                            <div data-i18n="Student">Student</div>
                        </a>
                        <ul class="menu-sub">
                            <!-- Add New Student - Admin ONLY -->
                            <?php if($userRole == 'admin'): ?>
                            <li class="menu-item">
                                <a href="add_student.php" class="menu-link">
                                    <div>Add New Student</div>
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <!-- View All Students - Admin & Tech -->
                            <li class="menu-item">
                                <a href="view_all_students.php" class="menu-link">
                                    <div>View All Students</div>
                                </a>
                            </li>
                            
                            <!-- Assigned PC - Admin & Tech -->
                            <li class="menu-item">
                                <a href="assigned_pc.php" class="menu-link">
                                    <div>Assigned PC</div>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <?php endif; ?>

                    <?php if($userRole == 'student'): ?>
                    <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link menu-toggle">
                            <i class="menu-icon tf-icons bx bx-desktop"></i>
                            <div data-i18n="PC Lab">PC / Lab</div>
                        </a>
                        <ul class="menu-sub">
                           <!-- View Avilable PCs in lab - student -->
                            <li class="menu-item">
                                <a href="available_pc.php" class="menu-link">
                                    <div>Available PCs</div>
                                </a>
                            </li> 
                            <!-- View labs - student -->
                            <li class="menu-item">
                                <a href="lab_guide.php" class="menu-link">
                                    <div>Lab Guide</div>
                                </a>
                            </li>         
                        </ul>
                    </li>
                    <?php endif; ?>

                    <!-- ==================== REPORTS SECTION ==================== -->
                    <?php if($userRole == 'admin' || $userRole == 'technician'): ?>
                    <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link menu-toggle">
                            <i class="menu-icon tf-icons bx bx-bar-chart-alt-2"></i>
                            <div data-i18n="Reports">Reports</div>
                        </a>
                        <ul class="menu-sub">
                              <!-- Lab-Technician Assignment - Admin & Tech -->
                            <li class="menu-item">
                                <a href="daily_summary_report.php" class="menu-link">
                                    <div>Daily Summary Report</div>
                                </a>
                            </li>
                        
                            <li class="menu-item">
                                <a href="unassigned_tickets.php" class="menu-link">
                                    <div>Unassigned Tickets</div>
                                </a>
                            </li>

                            <li class="menu-item">
                                <a href="pc_status_report.php" class="menu-link">
                                    <div>PC Status Report</div>
                                </a>
                            </li>
                            
                            <!-- Daily Summary - Admin & Tech -->
                            <li class="menu-item">
                                <a href="technician_workload.php" class="menu-link">
                                    <div>Technician Workload</div>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <?php endif; ?>

                    <!-- ==================== ADMIN SECTION ==================== -->
                    <?php if($userRole == 'admin'): ?>
                    <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link menu-toggle">
                            <i class="menu-icon tf-icons bx bx-cog"></i>
                            <div data-i18n="Admin">Admin</div>
                        </a>
                        <ul class="menu-sub">
                            <li class="menu-item">
                                <a href="add_technician.php" class="menu-link">
                                    <div>Add Technician</div>
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="view_all_technicians.php" class="menu-link">
                                    <div>View All Technicians</div>
                                </a>
                            </li>
                            
                        </ul>
                    </li>
                    <?php endif; ?>

                    <!-- ==================== ACCOUNT SECTION (Everyone) ==================== -->
                    <li class="menu-header small text-uppercase">
                        <span class="menu-header-text">Account</span>
                    </li>
                    
                    <li class="menu-item">
                        <a href="profile.php" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-user"></i>
                            <div>My Profile</div>
                        </a>
                    </li>
                    
                    <li class="menu-item">
                        <a href="change_password.php" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-key"></i>
                            <div>Change Password</div>
                        </a>
                    </li>
                    
                    <li class="menu-item">
                        <a href="logout.php" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-log-out"></i>
                            <div>Logout</div>
                        </a>
                    </li>
                </ul>
            </aside>
            <!-- / Menu -->

            <!-- Layout container -->
            <div class="layout-page">
                <!-- Navbar -->
                <nav class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme" id="layout-navbar">
                    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
                        <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
                            <i class="bx bx-menu bx-sm"></i>
                        </a>
                    </div>

                    <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
                        <!-- Brand/Logo Area -->
                        <div class="navbar-nav align-items-center">
                            <div class="nav-item d-flex align-items-center">
                                <i class="bx bx-server fs-4 lh-0 me-2" style="color: #696cff;"></i>
                                <div>
                                    <h5 class="mb-0 fw-semibold" style="color: #566a7f;">
                                        IT Automation System
                                    </h5>
                                    <small class="text-muted" style="font-size: 11px;">
                                        <i class="bx bx-check-circle" style="color: #28c76f;"></i>
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- User Dropdown -->
                        <ul class="navbar-nav flex-row align-items-center ms-auto">
                            <li class="nav-item dropdown-user dropdown">
                                <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                                    <div class="avatar avatar-online">
                                        <i class="bx bx-user-circle bx-lg" style="font-size: 2rem;"></i>
                                    </div>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="profile.php">
                                            <div class="d-flex">
                                                <div class="flex-grow-1">
                                                    <span class="fw-semibold d-block"><?php echo $userName; ?></span>
                                                    <small class="text-muted"><?php echo ucfirst($userRole); ?></small>
                                                </div>
                                            </div>
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item" href="profile.php">
                                            <i class="bx bx-user me-2"></i>
                                            <span>My Profile</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="change_password.php">
                                            <i class="bx bx-key me-2"></i>
                                            <span>Change Password</span>
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item" href="logout.php">
                                            <i class="bx bx-power-off me-2"></i>
                                            <span>Log Out</span>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </nav>
                <!-- / Navbar -->

                <!-- Content wrapper starts here -->
                <div class="content-wrapper">
                    <div class="container-xxl flex-grow-1 container-p-y">