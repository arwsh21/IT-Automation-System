<?php
session_start();
include("config/query.php");
include("components/header.php");

$database = new Database();
$conn = $database->getConnection();

$userRole = isset($_SESSION['role']) ? $_SESSION['role'] : 'guest';
$userName = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Guest';

$technicians = [];
$searchName = '';
$isSearching = false;
$error = '';

// ==================== CALL PROCEDURE TechnicianWorkloadReport ====================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search_name'])) {
    $searchName = trim($_POST['search_name']);
    $isSearching = !empty($searchName);
    
    try {
        $stmt = $conn->prepare("CALL TechnicianWorkloadReport(:tech_name)");
        $stmt->bindParam(':tech_name', $searchName);
        $stmt->execute();
        $technicians = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
    } catch(PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
} else {
    // Default: show all technicians
    try {
        $stmt = $conn->prepare("CALL TechnicianWorkloadReport('')");
        $stmt->execute();
        $technicians = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
    } catch(PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

//  totals
$totalAssigned = array_sum(array_column($technicians, 'total_assigned'));
$totalResolved = array_sum(array_column($technicians, 'resolved_count'));

// Find top performer 
$topPerformer = null;
if (!$isSearching && count($technicians) > 0) {
    $topPerformer = $technicians[0];
    foreach($technicians as $tech) {
        $resolvedTotal = $tech['resolved_count'] + $tech['closed_count'];
        $topTotal = $topPerformer['resolved_count'] + $topPerformer['closed_count'];
        if($resolvedTotal > $topTotal) {
            $topPerformer = $tech;
        }
    }
}
?>

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1">
        <h4 class="fw-bold py-3 mb-4">
            <span class="text-muted fw-light">Reports /</span> Technician Workload Report
        </h4>

        <!-- Search Form -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="POST" action="" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Technician Name</label>
                        <input type="text" name="search_name" class="form-control" 
                               placeholder="Enter technician name..." 
                               value="<?php echo htmlspecialchars($searchName); ?>">
                        <small class="text-muted">Leave empty to show all technicians</small>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-search"></i> Search
                        </button>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <a href="technician_workload.php" class="btn btn-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <?php if($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Technicians</h5>
                        <h2><?php echo count($technicians); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Tickets Assigned</h5>
                        <h2><?php echo $totalAssigned; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h5 class="card-title">Overall Resolution Rate</h5>
                        <h2><?php echo $totalAssigned > 0 ? round(($totalResolved / $totalAssigned) * 100, 1) . '%' : '0%'; ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Performer Section  -->
        <?php if($topPerformer && !$isSearching): ?>
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card bg-gradient-primary text-white" style="background: linear-gradient(45deg, #f4c868, #f4c868);">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title mb-1">🏆 Top Performer</h5>
                                <h2 class="mb-2"><?php echo htmlspecialchars($topPerformer['name']); ?></h2>
                                <p class="mb-0">
                                    <?php echo ($topPerformer['resolved_count'] + $topPerformer['closed_count']); ?> tickets resolved
                                    <span class="mx-2">•</span>
                                    <?php echo $topPerformer['shift']; ?> shift
                                </p>
                            </div>
                            <div>
                                <i class="bx bx-trophy" style="font-size: 4rem; opacity: 0.8;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Search Result Info -->
        <?php if($isSearching && !empty($searchName)): ?>
        <div class="alert alert-info">
            <i class="bx bx-search-alt"></i> Showing results for: <strong><?php echo htmlspecialchars($searchName); ?></strong>
        </div>
        <?php endif; ?>

        <!-- Technicians Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <?php echo $isSearching ? 'Search Results' : 'All Technicians'; ?>
                </h5>
            </div>
            <div class="table-responsive text-nowrap">
                <table class="table table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Emp No</th>
                            <th>Shift</th>
                            <th>Total Assigned</th>
                            <th>Resolved</th>
                            <th>Closed</th>
                            <th>Open</th>
                            <th>In Progress</th>
                            <th>Today's Tickets</th>
                            <th>Avg Hours</th>
                            <th>Efficiency</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($technicians) > 0): ?>
                            <?php foreach($technicians as $tech): ?>
                            <tr>
                                <td><?php echo $tech['tech_id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($tech['name']); ?></strong></td>
                                <td><?php echo $tech['employee_no']; ?></td>
                                <td>
                                    <span class="badge bg-label-<?php 
                                        echo $tech['shift'] == 'Morning' ? 'success' : 
                                            ($tech['shift'] == 'Evening' ? 'warning' : 'info'); 
                                    ?>">
                                        <?php echo $tech['shift']; ?>
                                    </span>
                                </td>
                                <td><?php echo $tech['total_assigned']; ?></td>
                                <td><span class="badge bg-success"><?php echo $tech['resolved_count']; ?></span></td>
                                <td><span class="badge bg-secondary"><?php echo $tech['closed_count']; ?></span></td>
                                <td><span class="badge bg-danger"><?php echo $tech['open_count']; ?></span></td>
                                <td><span class="badge bg-warning"><?php echo $tech['in_progress_count']; ?></span></td>
                                <td><span class="badge bg-info"><?php echo $tech['today_assigned']; ?></span></td>
                                <td>
                                    <?php 
                                    if($tech['avg_resolution_hours'] > 0) {
                                        echo $tech['avg_resolution_hours'] . ' hrs';
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $resolved = $tech['resolved_count'] + $tech['closed_count'];
                                    $total = $tech['total_assigned'];
                                    $efficiency = $total > 0 ? round(($resolved / $total) * 100, 1) : 0;
                                    $effClass = $efficiency >= 70 ? 'success' : ($efficiency >= 40 ? 'warning' : 'danger');
                                    ?>
                                    <div class="d-flex align-items-center">
                                        <span class="badge bg-<?php echo $effClass; ?> me-2"><?php echo $efficiency; ?>%</span>
                                        <div class="progress" style="width: 80px; height: 5px;">
                                            <div class="progress-bar bg-<?php echo $effClass; ?>" 
                                                 style="width: <?php echo $efficiency; ?>%"></div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="12" class="text-center py-4">
                                    <div class="alert alert-info mb-0">
                                        <i class="bx bx-info-circle"></i> No technicians found.
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
    </div>
</div>

<?php
include("components/footer.php");
?>