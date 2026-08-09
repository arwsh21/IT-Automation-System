<?php
session_start();
include("config/query.php");
include("components/header.php");

$db = new Database();
$conn = $db->getConnection();

$userRole = isset($_SESSION['role']) ? $_SESSION['role'] : 'guest';

// USING VIEW ticket_statistics_details
$sql = "SELECT * FROM ticket_statistics_details";
$stmt = $conn->prepare($sql);
$stmt->execute();
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

if (isset($_SESSION['success_message'])) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bx bx-check-circle"></i> ' . $_SESSION['success_message'] . '
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bx bx-error-circle"></i> ' . $_SESSION['error_message'] . '
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
    unset($_SESSION['error_message']);
}
?>

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1">
        <h4 class="fw-bold py-3 mb-4">
            <span class="text-muted fw-light">Tickets /</span> Ticket Statistics
        </h4>

        <!-- Stats Cards Row -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title text-primary">Total Tickets</h5>
                        <h2 class="display-4"><?php echo $stats['total_tickets'] ?? 0; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title text-danger">Open</h5>
                        <h2 class="display-4"><?php echo $stats['open_tickets'] ?? 0; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title text-warning">In Progress</h5>
                        <h2 class="display-4"><?php echo $stats['in_progress'] ?? 0; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title text-info">Resolved</h5>
                        <h2 class="display-4"><?php echo $stats['resolved'] ?? 0; ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Second Row -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title text-success">Closed</h5>
                        <h2 class="display-4"><?php echo $stats['closed'] ?? 0; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title text-info">Today's Tickets</h5>
                        <h2 class="display-4"><?php echo $stats['today_tickets'] ?? 0; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title text-primary">This Week</h5>
                        <h2 class="display-4"><?php echo $stats['this_week'] ?? 0; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title text-secondary">Resolution Rate</h5>
                        <h2 class="display-4">
                            <?php 
                            $total = $stats['total_tickets'] ?? 0;
                            $resolved = $stats['resolved'] ?? 0;
                            $closed = $stats['closed'] ?? 0;
                            $completed = $resolved + $closed;
                            $rate = $total > 0 ? round(($completed / $total) * 100, 1) : 0;
                            echo $rate . '%';
                            ?>
                        </h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Distribution Table -->
        <div class="card">
            <div class="card-header">
                <h5>Ticket Status Distribution</h5>
            </div>
            <div class="table-responsive text-nowrap">
                <table class="table table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Status</th>
                            <th>Count</th>
                            <th>Percentage</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        <tr>
                            <td><span class="badge bg-label-danger">Open</span></td>
                            <td><?php echo $stats['open_tickets'] ?? 0; ?></td>
                            <td>
                                <?php 
                                $total = $stats['total_tickets'] ?? 1;
                                $percent = ($stats['open_tickets'] ?? 0) / $total * 100;
                                echo round($percent, 1) . '%';
                                ?>
                             </td>
</tr>
                        <tr>
                            <td><span class="badge bg-label-warning">In Progress</span></td>
                            <td><?php echo $stats['in_progress'] ?? 0; ?></td>
                            <td>
                                <?php 
                                $percent = ($stats['in_progress'] ?? 0) / $total * 100;
                                echo round($percent, 1) . '%';
                                ?>
                             </td>
</tr>
                        <tr>
                            <td><span class="badge bg-label-primary">Resolved</span></td>
                            <td><?php echo $stats['resolved'] ?? 0; ?></td>
                            <td>
                                <?php 
                                $percent = ($stats['resolved'] ?? 0) / $total * 100;
                                echo round($percent, 1) . '%';
                                ?>
                             </td>
</tr>
                        <tr>
                            <td><span class="badge bg-label-success">Closed</span></td>
                            <td><?php echo $stats['closed'] ?? 0; ?></td>
                            <td>
                                <?php 
                                $percent = ($stats['closed'] ?? 0) / $total * 100;
                                echo round($percent, 1) . '%';
                                ?>
                             </td>
</tr>
                    </tbody>
</table>
            </div>
        </div>
    </div>
</div>

<?php include("components/footer.php"); ?>