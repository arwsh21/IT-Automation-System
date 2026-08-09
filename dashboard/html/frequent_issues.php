<?php
include("config/query.php");
include("components/header.php");

$database = new Database();
$conn = $database->getConnection();

$userRole = isset($_SESSION['role']) ? $_SESSION['role'] : 'guest';

// Redirect if not admin or technician
if ($userRole != 'admin' && $userRole != 'technician') {
    header("Location: dashboard.php");
    exit();
}

// ==================== VIEW frequent_issues_view has subquery ====================
$sql = "SELECT * FROM frequent_issues_view";
$stmt = $conn->prepare($sql);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalSql = "SELECT COUNT(*) as total FROM ticket";
$totalStmt = $conn->prepare($totalSql);
$totalStmt->execute();
$totalTickets = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'];
?>

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1">
        <h4 class="fw-bold py-3 mb-4">
            <span class="text-muted fw-light">Reports /</span> Frequent Issues
        </h4>

        <!-- Summary Card -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Tickets</h5>
                        <h2><?php echo $totalTickets; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <h5 class="card-title">Issue Categories</h5>
                        <h2><?php echo count($categories); ?></h2>
                        <small>Above average</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h5 class="card-title">Top Category</h5>
                        <h2><?php echo isset($categories[0]['category']) ? $categories[0]['category'] : 'N/A'; ?></h2>
                        <small><?php echo isset($categories[0]['ticket_count']) ? $categories[0]['ticket_count'] . ' tickets' : '0'; ?></small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Frequent Issues Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Categories with Above-Average Ticket Count</h5>
                <small>Showing only categories that exceed the average ticket count</small>
            </div>
            <div class="table-responsive text-nowrap">
                <table class="table table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Category</th>
                            <th>Ticket Count</th>
                            <th>Average per Category</th>
                            <th>% Above Average</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($categories) > 0): ?>
                            <?php 
                            $maxCount = $categories[0]['ticket_count']; 
                            foreach($categories as $row): 
                                $percentage = ($row['ticket_count'] / $maxCount) * 100;
                            ?>
                            <tr>
                                <td>
                                    <span class="badge bg-label-<?php 
                                        echo $row['category'] == 'Hardware' ? 'danger' : 
                                            ($row['category'] == 'Software' ? 'info' : 'warning'); 
                                    ?>">
                                        <i class="bx bx-category"></i> <?php echo $row['category']; ?>
                                    </span>
                                </td>
                                <td>
                                    <strong><?php echo $row['ticket_count']; ?></strong> tickets
                                    <div class="progress" style="height: 5px; width: 150px; display: inline-block; margin-left: 10px;">
                                        <div class="progress-bar bg-primary" style="width: <?php echo $percentage; ?>%"></div>
                                    </div>
                                </td>
                                <td><?php echo round($row['avg_per_category'], 1); ?> tickets</td>
                                <td>
                                    <span class="badge bg-success">
                                        +<?php echo $row['percentage_above_avg']; ?>%
                                    </span>
                                </td>
                                <td>
                                    <?php if($row['ticket_count'] > $row['avg_per_category'] * 2): ?>
                                        <span class="badge bg-danger">Critical</span>
                                    <?php elseif($row['ticket_count'] > $row['avg_per_category'] * 1.5): ?>
                                        <span class="badge bg-warning">High</span>
                                    <?php else: ?>
                                        <span class="badge bg-info">Moderate</span>
                                    <?php endif; ?>
                                 </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    <div class="alert alert-info mb-0">
                                        <i class="bx bx-info-circle"></i> No categories found above average.
                                    </div>
                                 </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Pie Chart Visualization -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">Issue Distribution</h5>
            </div>
            <div class="card-body">
                <canvas id="issueChart" style="max-height: 300px;"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('issueChart').getContext('2d');
    const categories = <?php echo json_encode(array_column($categories, 'category')); ?>;
    const counts = <?php echo json_encode(array_column($categories, 'ticket_count')); ?>;
    
    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: categories,
            datasets: [{
                data: counts,
                backgroundColor: ['#ff6b6b', '#4ecdc4', '#45b7d1', '#96ceb4', '#ffeaa7'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
</script>

<?php
include("components/footer.php");
?>