<?php
session_start();
include("config/query.php");
include("components/header.php");

$database = new Database();
$conn = $database->getConnection();

$userRole = isset($_SESSION['role']) ? $_SESSION['role'] : 'guest';

$ticketData = null;
$ticketId = '';
$error = '';
$searched = false;

//  STORED PROCEDURE: GetTicketDetails
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ticket_id'])) {
    $ticketId = trim($_POST['ticket_id']);
    $searched = true;
    
    if (!is_numeric($ticketId)) {
        $error = "Please enter a valid numeric Ticket ID.";
    } else {
        try {
            $stmt = $conn->prepare("CALL GetTicketDetails(:ticket_id)");
            $stmt->bindParam(':ticket_id', $ticketId);
            $stmt->execute();
            $ticketData = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            
            if (!$ticketData) {
                $error = "No ticket found with ID: " . $ticketId;
                $ticketData = null;
            }
        } catch(PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}
?>

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1">
        <h4 class="fw-bold py-3 mb-4">
            <span class="text-muted fw-light">Reports /</span> Ticket Details
        </h4>

    
        <div class="card mb-4">
            <div class="card-body">
                <form method="POST" action="" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Enter Ticket ID</label>
                        <input type="number" name="ticket_id" class="form-control" 
                               placeholder="Enter Ticket ID (e.g., 1, 2, 3...)" 
                               value="<?php echo htmlspecialchars($ticketId); ?>" required>
                        <small class="text-muted">Enter the numeric Ticket ID to view details</small>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-search"></i> Get Details
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <?php if($error): ?>
            <div class="alert alert-danger">
                <i class="bx bx-error-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if($searched && $ticketData): ?>
        
        <!-- Ticket Details Card -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Ticket #<?php echo $ticketData['ticket_id']; ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Basic Info -->
                            <div class="col-md-6">
                                <h6 class="text-primary">📋 Ticket Information</h6>
                                <table class="table table-bordered">
                                    <tr>
                                        <th>Category</th>
                                        <td>
                                            <span class="badge bg-label-<?php 
                                                echo $ticketData['category'] == 'Hardware' ? 'danger' : 
                                                    ($ticketData['category'] == 'Software' ? 'info' : 'warning'); 
                                            ?>">
                                                <?php echo $ticketData['category']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Status</th>
                                        <td>
                                            <?php
                                            $statusClass = '';
                                            switch($ticketData['status']) {
                                                case 'Open': $statusClass = 'warning'; break;
                                                case 'In Progress': $statusClass = 'info'; break;
                                                case 'Resolved': $statusClass = 'success'; break;
                                                case 'Closed': $statusClass = 'secondary'; break;
                                                default: $statusClass = 'dark';
                                            }
                                            ?>
                                            <span class="badge bg-<?php echo $statusClass; ?>">
                                                <?php echo $ticketData['status']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Created</th>
                                        <td><?php echo date('d-M-Y H:i', strtotime($ticketData['created_at'])); ?></td>
                                    </tr>
                                    <tr>
                                        <th>PC Number</th>
                                        <td><?php echo $ticketData['pc_number'] ?? 'N/A'; ?></td>
                                    </tr>
                                </table>
                            </div>
                            
                            <!-- Student & Technician Info -->
                            <div class="col-md-6">
                                <h6 class="text-success">👥 People</h6>
                                <table class="table table-bordered">
                                    <tr>
                                        <th>Raised By</th>
                                        <td><?php echo $ticketData['student_name']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Assigned To</th>
                                        <td>
                                            <?php echo $ticketData['technician_name'] ?? '<span class="text-muted">Not Assigned</span>'; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Updates Count</th>
                                        <td>
                                            <span class="badge bg-secondary">
                                                <?php echo $ticketData['update_count']; ?> updates
                                            </span>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Description -->
                        <hr>
                        <div class="row">
                            <div class="col-md-12">
                                <h6 class="text-warning">📝 Description</h6>
                                <div class="alert alert-info">
                                    <?php echo nl2br(htmlspecialchars($ticketData['description'])); ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Days Pending stuff -->
                        <?php if($ticketData['status'] == 'Open' || $ticketData['status'] == 'In Progress'): ?>
                        <div class="alert alert-warning">
                            <i class="bx bx-time"></i> 
                            Pending for: 
                            <?php 
                            $created = new DateTime($ticketData['created_at']);
                            $now = new DateTime();
                            $diff = $now->diff($created);
                            echo $diff->days . ' days, ' . $diff->h . ' hours';
                            ?>
                        </div>
                        <?php endif; ?>
                        
                    </div>
                </div>
            </div>
        </div>
        
        <?php elseif($searched && !$ticketData && !$error): ?>
            <div class="alert alert-info">
                <i class="bx bx-info-circle"></i> No ticket found. Please enter a valid Ticket ID.
            </div>
        <?php endif; ?>
        
        <!-- Quick Links -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h6 class="mb-3">Quick Navigation</h6>
                        <a href="view_all_tickets.php" class="btn btn-secondary me-2">
                            <i class="bx bx-ticket"></i> View All Tickets
                        </a>
                        <a href="unassigned_tickets.php" class="btn btn-info">
                            <i class="bx bx-user-x"></i> Unassigned Tickets
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
</div>

<?php
include("components/footer.php");
?>