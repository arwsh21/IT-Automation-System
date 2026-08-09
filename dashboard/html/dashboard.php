<?php
session_start();
include("config/query.php");
include("components/header.php");

$database = new Database();
$conn = $database->getConnection();

$userRole = isset($_SESSION['role']) ? $_SESSION['role'] : 'guest';
$userName = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Guest';

// ==================== FETCH DASHBOARD STATS ====================

// Total counts
$totalPCs = $conn->query("SELECT COUNT(*) FROM pc")->fetchColumn();
$totalStudents = $conn->query("SELECT COUNT(*) FROM student")->fetchColumn();
$totalTechnicians = $conn->query("SELECT COUNT(*) FROM it_technician")->fetchColumn();
$totalTickets = $conn->query("SELECT COUNT(*) FROM ticket")->fetchColumn();

// Ticket stats
$openTickets = $conn->query("SELECT COUNT(*) FROM ticket WHERE status = 'Open'")->fetchColumn();
$inProgressTickets = $conn->query("SELECT COUNT(*) FROM ticket WHERE status = 'In Progress'")->fetchColumn();
$resolvedTickets = $conn->query("SELECT COUNT(*) FROM ticket WHERE status = 'Resolved'")->fetchColumn();

// PC stats
$availablePCs = $conn->query("SELECT COUNT(*) FROM pc WHERE status = 'Available'")->fetchColumn();
$assignedPCs = $conn->query("SELECT COUNT(*) FROM pc WHERE status = 'Assigned'")->fetchColumn();
$maintenancePCs = $conn->query("SELECT COUNT(*) FROM pc WHERE status = 'Under Maintenance'")->fetchColumn();

// Today's tickets
$todayTickets = $conn->query("SELECT COUNT(*) FROM ticket WHERE DATE(created_at) = CURDATE()")->fetchColumn();

// Unassigned tickets
$unassignedTickets = $conn->query("SELECT COUNT(*) FROM ticket WHERE assigned_to IS NULL AND status = 'Open'")->fetchColumn();
?>

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1">
        
        <!-- Welcome Banner -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h3 class="mb-1">Welcome back, <?php echo htmlspecialchars($userName); ?>!</h3>
                        <p class="mb-0">Here's what's happening with your IT infrastructure today.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards Row 1 -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="text-muted small fw-bold">TOTAL PCS</div>
                                <h2 class="mb-0"><?php echo $totalPCs; ?></h2>
                            </div>
                            <div class="text-primary">
                                <i class="bx bx-desktop" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="text-muted small fw-bold">TOTAL STUDENTS</div>
                                <h2 class="mb-0"><?php echo $totalStudents; ?></h2>
                            </div>
                            <div class="text-success">
                                <i class="bx bx-user" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="text-muted small fw-bold">TECHNICIANS</div>
                                <h2 class="mb-0"><?php echo $totalTechnicians; ?></h2>
                            </div>
                            <div class="text-info">
                                <i class="bx bx-wrench" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="text-muted small fw-bold">TOTAL TICKETS</div>
                                <h2 class="mb-0"><?php echo $totalTickets; ?></h2>
                            </div>
                            <div class="text-warning">
                                <i class="bx bx-ticket" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards Row 2 - Ticket Status -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title">Open Tickets</h5>
                                <h2 class="mb-0"><?php echo $openTickets; ?></h2>
                                <small>Need attention</small>
                            </div>
                            <i class="bx bx-time bx-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title">In Progress</h5>
                                <h2 class="mb-0"><?php echo $inProgressTickets; ?></h2>
                                <small>Being worked on</small>
                            </div>
                            <i class="bx bx-loader-circle bx-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title">Resolved</h5>
                                <h2 class="mb-0"><?php echo $resolvedTickets; ?></h2>
                                <small>Completed</small>
                            </div>
                            <i class="bx bx-check-circle bx-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards Row 3 - PC Status -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title">Available PCs</h5>
                                <h2 class="mb-0"><?php echo $availablePCs; ?></h2>
                                <small>Ready for use</small>
                            </div>
                            <i class="bx bx-check-shield bx-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title">Assigned PCs</h5>
                                <h2 class="mb-0"><?php echo $assignedPCs; ?></h2>
                                <small>In use</small>
                            </div>
                            <i class="bx bx-user-check bx-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-danger text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title">Maintenance</h5>
                                <h2 class="mb-0"><?php echo $maintenancePCs; ?></h2>
                                <small>Need repair</small>
                            </div>
                            <i class="bx bx-wrench bx-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alerts Row -->
        <div class="row">
            <?php if($unassignedTickets > 0): ?>
            <div class="col-md-6">
                <div class="card border-danger">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <i class="bx bx-error-circle text-danger fs-1 me-3"></i>
                            <div>
                                <h5 class="mb-1 text-danger">Unassigned Tickets</h5>
                                <p class="mb-0"><?php echo $unassignedTickets; ?> tickets waiting for technician assignment.</p>
                                <a href="unassigned_tickets.php" class="btn btn-sm btn-danger mt-2">Assign Now</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if($todayTickets > 0): ?>
            <div class="col-md-6">
                <div class="card border-info">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <i class="bx bx-calendar-event text-info fs-1 me-3"></i>
                            <div>
                                <h5 class="mb-1 text-info">Today's Activity</h5>
                                <p class="mb-0"><?php echo $todayTickets; ?> new tickets raised today.</p>
                                <a href="view_all_tickets.php" class="btn btn-sm btn-info mt-2">View Tickets</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
    </div>
</div>

<?php include("components/footer.php"); ?>