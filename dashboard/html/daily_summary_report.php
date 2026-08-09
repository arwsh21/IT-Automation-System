<?php
session_start();
include("config/query.php");
include("components/header.php");

$database = new Database();
$conn = $database->getConnection();

$userRole = isset($_SESSION['role']) ? $_SESSION['role'] : 'guest';

$ticketStats = null;
$softwareCount = null;
$pcChanges = null;
$selectedDate = date('Y-m-d');
$error = '';

// ==================== CALL STORED PROCEDURE ====================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['report_date'])) {
    $selectedDate = $_POST['report_date'];
    
    try {
        // Call stored procedure : DailySummaryReport
        $stmt = $conn->prepare("CALL DailySummaryReport(:report_date)");
        $stmt->bindParam(':report_date', $selectedDate);
        $stmt->execute();
        
        $ticketStats = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt->nextRowset();
        $softwareCount = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt->nextRowset();
        $pcChanges = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt->closeCursor();
        
    } catch(PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
} else {
  // default aj ka report
    try {
        $stmt = $conn->prepare("CALL DailySummaryReport(:report_date)");
        $stmt->bindParam(':report_date', $selectedDate);
        $stmt->execute();
        
        $ticketStats = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->nextRowset();
        $softwareCount = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->nextRowset();
        $pcChanges = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt->closeCursor();
        
    } catch(PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1">
        <h4 class="fw-bold py-3 mb-4">
            <span class="text-muted fw-light">Reports /</span> Daily Summary Report
        </h4>

        <!-- Date Selection Form -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="POST" action="" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Select Date</label>
                        <input type="date" name="report_date" class="form-control" 
                               value="<?php echo $selectedDate; ?>" required>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-search"></i> Generate Report
                        </button>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <a href="?date=today" class="btn btn-secondary">Today</a>
                    </div>
                </form>
            </div>
        </div>

        <?php if($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if($ticketStats && isset($ticketStats['tickets_created'])): ?>
        
        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title">Date</h5>
                        <h3><?php echo date('d-M-Y', strtotime($selectedDate)); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Tickets</h5>
                        <h2><?php echo $ticketStats['tickets_created']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">Resolved Same Day</h5>
                        <h2><?php echo $ticketStats['resolved_same_day']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <h5 class="card-title">Software Installed</h5>
                        <h2><?php echo $softwareCount['software_installed']; ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Stats -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Ticket Statistics</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <tr>
                                <th>Tickets Created</th>
                                <td><?php echo $ticketStats['tickets_created']; ?></td>
                            </tr>
                            <tr>
                                <th>Resolved Same Day</th>
                                <td><?php echo $ticketStats['resolved_same_day']; ?></td>
                            </tr>
                            <tr>
                                <th>Still Open</th>
                                <td><?php echo $ticketStats['still_open']; ?></td>
                            </tr>
                            <tr>
                                <th>In Progress</th>
                                <td><?php echo $ticketStats['in_progress']; ?></td>
                            </tr>
                            <tr class="table-info">
                                <th>Resolution Rate</th>
                                <td>
                                    <?php 
                                    if($ticketStats['tickets_created'] > 0) {
                                        $rate = ($ticketStats['resolved_same_day'] / $ticketStats['tickets_created']) * 100;
                                        echo round($rate, 1) . '%';
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">System Activity</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <tr>
                                <th>Software Installations</th>
                                <td><?php echo $softwareCount['software_installed']; ?></td>
                            </tr>
                            <tr>
                                <th>PC Status Changes</th>
                                <td><?php echo $pcChanges['pc_changes']; ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <?php else: ?>
            <div class="alert alert-info">
                <i class="bx bx-info-circle"></i> No data found for <?php echo date('d-M-Y', strtotime($selectedDate)); ?>.
            </div>
        <?php endif; ?>
    </div>
</div>


<?php include("components/footer.php"); ?>