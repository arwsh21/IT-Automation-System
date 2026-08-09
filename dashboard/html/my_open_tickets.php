<?php
session_start();
include("config/query.php");
include("components/header.php");

$database = new Database();
$conn = $database->getConnection();

$userRole = isset($_SESSION['role']) ? $_SESSION['role'] : 'guest';
$studentId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

// Redirect if not student
if ($userRole != 'student') {
    header("Location: dashboard.php");
    exit();
}

// ====================  VIEW student_open_tickets_view ====================
$sql = "SELECT * FROM student_open_tickets_view WHERE raised_by = :student_id";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':student_id', $studentId);
$stmt->execute();
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1">
        <h4 class="fw-bold py-3 mb-4">My Open Tickets</h4>

        <div class="card">
            <div class="table-responsive text-nowrap">
                <table class="table table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Ticket ID</th>
                            <th>Category</th>
                            <th>PC</th>
                            <th>Lab</th>
                            <th>Status</th>
                            <th>Assigned To</th>
                            <th>Created</th>
                            <th>Updates</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($tickets) > 0): ?>
                            <?php foreach($tickets as $ticket): ?>
                            <tr>
                                <td>#<?php echo $ticket['ticket_id']; ?></td>
                                <td><?php echo $ticket['category']; ?></td>
                                <td><?php echo $ticket['pc_number'] ?? 'N/A'; ?></td>
                                <td><?php echo $ticket['lab_name'] ?? 'N/A'; ?></td>
                                <td><?php echo $ticket['status']; ?></td>
                                <td><?php echo $ticket['assigned_technician']; ?></td>
                                <td><?php echo date('d-M-Y', strtotime($ticket['created_at'])); ?></td>
                                <td><?php echo $ticket['update_count']; ?> updates</td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">No open tickets found.</td>
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