<?php
session_start();
include("config/query.php");
include("components/header.php");

$database = new Database();
$conn = $database->getConnection();

$userRole = isset($_SESSION['role']) ? $_SESSION['role'] : 'guest';
$userName = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Guest';
$studentId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

if ($userRole != 'student') {
    header("Location: dashboard.php");
    exit();
}

// ==================== STUDENT DASHBOARD ====================

// My tickets stats
$myTickets = $conn->prepare("SELECT COUNT(*) FROM ticket WHERE raised_by = ?");
$myTickets->execute([$studentId]);
$totalMyTickets = $myTickets->fetchColumn();

$myOpenTickets = $conn->prepare("SELECT COUNT(*) FROM ticket WHERE raised_by = ? AND status IN ('Open', 'In Progress')");
$myOpenTickets->execute([$studentId]);
$totalMyOpenTickets = $myOpenTickets->fetchColumn();

$myResolvedTickets = $conn->prepare("SELECT COUNT(*) FROM ticket WHERE raised_by = ? AND status IN ('Resolved', 'Closed')");
$myResolvedTickets->execute([$studentId]);
$totalMyResolvedTickets = $myResolvedTickets->fetchColumn();

// My assigned PC
$myPC = $conn->prepare("SELECT pc_number, status, assignment_start, assignment_end 
                        FROM pc WHERE assigned_to = ?");
$myPC->execute([$studentId]);
$assignedPC = $myPC->fetch(PDO::FETCH_ASSOC);

// Recent tickets 
$recentTickets = $conn->prepare("SELECT ticket_id, category, status, created_at 
                                  FROM ticket WHERE raised_by = ? 
                                  ORDER BY ticket_id DESC LIMIT 5");
$recentTickets->execute([$studentId]);
$recentTicketsList = $recentTickets->fetchAll(PDO::FETCH_ASSOC);

// Available PCs count
$availablePCs = $conn->query("SELECT COUNT(*) FROM pc WHERE status = 'Available'")->fetchColumn();
?>

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1">
        
        <!-- Welcome Banner -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h3 class="mb-1">Welcome, <?php echo htmlspecialchars($userName); ?>! 👋</h3>
                        <p class="mb-0">Here's your personal IT support dashboard.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card border-left-primary shadow h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="text-muted small fw-bold">MY TICKETS</div>
                                <h2 class="mb-0"><?php echo $totalMyTickets; ?></h2>
                            </div>
                            <div class="text-primary">
                                <i class="bx bx-ticket" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-left-warning shadow h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="text-muted small fw-bold">OPEN TICKETS</div>
                                <h2 class="mb-0"><?php echo $totalMyOpenTickets; ?></h2>
                            </div>
                            <div class="text-warning">
                                <i class="bx bx-time" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-left-success shadow h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="text-muted small fw-bold">RESOLVED</div>
                                <h2 class="mb-0"><?php echo $totalMyResolvedTickets; ?></h2>
                            </div>
                            <div class="text-success">
                                <i class="bx bx-check-circle" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- My PC Info -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="bx bx-desktop"></i> My Assigned PC</h5>
                    </div>
                    <div class="card-body">
                        <?php if($assignedPC): ?>
                            <table class="table table-bordered">
                                <tr>
                                    <th>PC Number</th>
                                    <td><strong><?php echo $assignedPC['pc_number']; ?></strong></td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td>
                                        <span class="badge bg-<?php echo $assignedPC['status'] == 'Assigned' ? 'success' : 'warning'; ?>">
                                            <?php echo $assignedPC['status']; ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Assigned From</th>
                                    <td><?php echo $assignedPC['assignment_start'] ?? 'N/A'; ?></td>
                                </tr>
                                <tr>
                                    <th>Assigned Until</th>
                                    <td><?php echo $assignedPC['assignment_end'] ?? 'N/A'; ?></td>
                                </tr>
                            </table>
                        <?php else: ?>
                            <div class="alert alert-info mb-0">
                                <i class="bx bx-info-circle"></i> No PC assigned to you yet.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="bx bx-desktop"></i> Available PCs</h5>
                    </div>
                    <div class="card-body text-center">
                        <h2 class="display-4"><?php echo $availablePCs; ?></h2>
                        <p>PCs ready for assignment</p>
                        <a href="available_pc.php" class="btn btn-sm btn-success">View Available PCs</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Tickets -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bx bx-list-ul"></i> My Recent Tickets</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Ticket ID</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($recentTicketsList) > 0): ?>
                                    <?php foreach($recentTicketsList as $ticket): ?>
                                    <tr>
                                        <td>#<?php echo $ticket['ticket_id']; ?></td>
                                        <td>
                                            <span class="badge bg-label-<?php 
                                                echo $ticket['category'] == 'Hardware' ? 'danger' : 
                                                    ($ticket['category'] == 'Software' ? 'info' : 'warning'); 
                                            ?>">
                                                <?php echo $ticket['category']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            $statusClass = $ticket['status'] == 'Open' ? 'warning' : 
                                                          ($ticket['status'] == 'In Progress' ? 'info' : 'success');
                                            ?>
                                            <span class="badge bg-<?php echo $statusClass; ?>">
                                                <?php echo $ticket['status']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d-M-Y', strtotime($ticket['created_at'])); ?></td>
                                        <td>
                                            <a href="my_open_tickets.php" class="btn btn-sm btn-info">Track</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center">No tickets raised yet.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer">
                        <a href="add_ticket.php" class="btn btn-primary">
                            <i class="bx bx-plus-circle"></i> Raise New Ticket
                        </a>
                        <a href="my_open_tickets.php" class="btn btn-secondary">
                            <i class="bx bx-list-ul"></i> View All My Tickets
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
</div>

<?php include("components/footer.php"); ?>