<?php
session_start();
include("config/query.php");
include("components/header.php");

$db = new Database();
$conn = $db->getConnection();

$userRole = isset($_SESSION['role']) ? $_SESSION['role'] : 'guest';

$sql = "SELECT * FROM resolution_summary_details ORDER BY avg_resolution_hours";
$stmt = $conn->prepare($sql);
$stmt->execute();
$summary = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
            <span class="text-muted fw-light">Tickets /</span> Resolution Summary
        </h4>

        <div class="card">
            <div class="table-responsive text-nowrap">
                <table class="table table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Technician Name</th>
                            <th>Total Tickets</th>
                            <th>Resolved</th>
                            <th>Closed</th>
                            <th>Pending</th>
                            <th>Avg Resolution (Hours)</th>
                            <th>Fastest (Hours)</th>
                            <th>Slowest (Hours)</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        <?php if(count($summary) > 0): ?>
                            <?php foreach($summary as $row): ?>
                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($row['technician_name']); ?>
                                    <br>
                                    <small class="text-muted">Technician</small>
                                </td>
                                <td>
                                    <span class="badge bg-label-primary">
                                        <?php echo $row['total_tickets']; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-label-success">
                                        <?php echo $row['resolved_count']; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-label-info">
                                        <?php echo $row['closed_count']; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-label-warning">
                                        <?php echo $row['pending_count']; ?>
                                    </span>
                                </td>
                                <td><?php echo $row['avg_resolution_hours'] ?: '-'; ?></td>
                                <td><?php echo $row['fastest_resolution'] ?: '-'; ?></td>
                                <td><?php echo $row['slowest_resolution'] ?: '-'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <div class="alert alert-info mb-0">
                                        <i class="bx bx-info-circle"></i> No resolution data available.
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

<?php include("components/footer.php"); ?>