<?php
session_start();
include("components/header.php");
require_once 'config/query.php';

$database = new Database();
$conn = $database->getConnection();

$userRole = isset($_SESSION['role']) ? $_SESSION['role'] : 'guest';
?>

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1">
        <h4 class="fw-bold py-3 mb-4">
            <span class="text-muted fw-light">Labs /</span> Lab Guide
        </h4>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Labs</h5>
                        <?php
                        $totalLabs = $conn->query("SELECT COUNT(*) FROM lab")->fetchColumn();
                        ?>
                        <h2><?php echo $totalLabs; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total PCs</h5>
                        <?php
                        $totalPCs = $conn->query("SELECT COUNT(*) FROM pc")->fetchColumn();
                        ?>
                        <h2><?php echo $totalPCs; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h5 class="card-title">Available PCs</h5>
                        <?php
                        $availablePCs = $conn->query("SELECT COUNT(*) FROM pc WHERE status = 'Available'")->fetchColumn();
                        ?>
                        <h2><?php echo $availablePCs; ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lab Guide Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Laboratory Information</h5>
                <small>PC availability and occupancy by lab</small>
            </div>
            <div class="table-responsive text-nowrap">
                <table class="table table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Lab Name</th>
                            <th>Location</th>
                            <th>Total PCs</th>
                            <th>Available PCs</th>
                            <th>Occupied PCs</th>
                            <th>Utilization</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                      <!-- VIEW: lab_guide with joins -->
                        <?php
                        $sql = "SELECT * FROM lab_guide ORDER BY lab_name";
                        $stmt = $conn->prepare($sql);
                        $stmt->execute();
                        $labs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        
                        <?php if(count($labs) > 0): ?>
                            <?php foreach($labs as $lab): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($lab['lab_name']); ?></strong>
                                    <?php if($userRole == 'admin' || $userRole == 'technician'): ?>
                                        <br>
                                        <a href="view_all_pcs.php?lab_id=<?php echo $lab['lab_id']; ?>" class="small">View PCs</a>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($lab['location']); ?></td>
                                <td>
                                    <span class="badge bg-secondary"><?php echo $lab['total_pcs']; ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-success"><?php echo $lab['available_pcs']; ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-warning"><?php echo $lab['occupied_pcs']; ?></span>
                                </td>
                                <td>
                                    <?php 
                                    $utilization = $lab['total_pcs'] > 0 ? round(($lab['occupied_pcs'] / $lab['total_pcs']) * 100, 1) : 0;
                                    $utilClass = $utilization >= 70 ? 'danger' : ($utilization >= 40 ? 'warning' : 'success');
                                    ?>
                                    <div class="d-flex align-items-center">
                                        <div class="progress" style="width: 100px; height: 8px; margin-right: 10px;">
                                            <div class="progress-bar bg-<?php echo $utilClass; ?>" 
                                                 style="width: <?php echo $utilization; ?>%"></div>
                                        </div>
                                        <span><?php echo $utilization; ?>%</span>
                                    </div>
                                </td>
                                <td>
                                    <?php if($lab['available_pcs'] > 0): ?>
                                        <span class="badge bg-success">🟢 Available</span>
                                    <?php elseif($lab['occupied_pcs'] == $lab['total_pcs']): ?>
                                        <span class="badge bg-danger">🔴 Full</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">🟡 Limited</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <div class="alert alert-info mb-0">
                                        <i class="bx bx-info-circle"></i> No labs found.
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Legend -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">Status Legend</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <span class="badge bg-success">🟢 Available</span> - PCs available for assignment
                    </div>
                    <div class="col-md-3">
                        <span class="badge bg-warning">🟡 Limited</span> - Few PCs available
                    </div>
                    <div class="col-md-3">
                        <span class="badge bg-danger">🔴 Full</span> - All PCs occupied
                    </div>
                    <div class="col-md-3">
                        <span class="badge bg-secondary">📊 Utilization</span> - Occupancy rate
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="mt-3">
            <?php if($userRole == 'student'): ?>
                <a href="add_ticket_std.php" class="btn btn-primary">
                    <i class="bx bx-plus-circle"></i> Raise a Ticket
                </a>
                <a href="my_open_tickets.php" class="btn btn-info">
                    <i class="bx bx-list-ul"></i> My Open Tickets
                </a>
            <?php endif; ?>
            <a href="view_all_pcs.php" class="btn btn-secondary">
                <i class="bx bx-desktop"></i> View All PCs
            </a>
        </div>
        
    </div>
</div>

<?php include("components/footer.php"); ?>