<?php
session_start();
include("config/query.php");
include("components/header.php");

$db = new Database();
$conn = $db->getConnection();

$userRole = isset($_SESSION['role']) ? $_SESSION['role'] : 'guest';

// USING VIEW tickets_per_pc_details
$sql = "SELECT * FROM tickets_per_pc_details ORDER BY ticket_count DESC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$ticketsPerPC = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
            <span class="text-muted fw-light">Tickets /</span> Tickets Per PC
        </h4>

        <div class="card">
            <div class="table-responsive text-nowrap">
                <table class="table table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>PC Number</th>
                            <th>Lab</th>
                            <th>PC Status</th>
                            <th>Total Tickets</th>
                            <th>Open Tickets</th>
                            <th>In Progress</th>
                            <th>Resolved</th>
                            <th>Closed</th>
                            <th>Last Ticket Date</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        <?php if(count($ticketsPerPC) > 0): ?>
                            <?php foreach($ticketsPerPC as $row): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['pc_number']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['lab_name']); ?></td>
                                <td>
                                    <?php
                                    $statusClass = '';
                                    switch($row['pc_status']) {
                                        case 'Available':
                                            $statusClass = 'bg-label-success';
                                            break;
                                        case 'Assigned':
                                            $statusClass = 'bg-label-primary';
                                            break;
                                        case 'Under Maintenance':
                                            $statusClass = 'bg-label-warning';
                                            break;
                                        case 'Retired':
                                            $statusClass = 'bg-label-danger';
                                            break;
                                        default:
                                            $statusClass = 'bg-label-secondary';
                                    }
                                    ?>
                                    <span class="badge <?php echo $statusClass; ?>">
                                        <?php echo $row['pc_status']; ?>
                                    </span>
                                        </td>
                                <td>
                                    <span class="badge bg-label-primary">
                                        <?php echo $row['ticket_count']; ?>
                                    </span>
                                        </td>
                                <td>
                                    <span class="badge bg-label-danger">
                                        <?php echo $row['open_tickets']; ?>
                                    </span>
                                        </td>
                                <td>
                                    <span class="badge bg-label-warning">
                                        <?php echo $row['in_progress_tickets']; ?>
                                    </span>
                                        </td>
                                <td>
                                    <span class="badge bg-label-info">
                                        <?php echo $row['resolved_tickets']; ?>
                                    </span>
                                        </td>
                                <td>
                                    <span class="badge bg-label-success">
                                        <?php echo $row['closed_tickets']; ?>
                                    </span>
                                        </td>
                                <td>
                                    <?php echo $row['last_ticket_date'] ? date('M d, Y', strtotime($row['last_ticket_date'])) : '-'; ?>
                                        </td>
                                        </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center py-4">
                                    <div class="alert alert-info mb-0">
                                        <i class="bx bx-info-circle"></i> No ticket data available.
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