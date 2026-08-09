<?php
session_start();
include("config/query.php");
include("components/header.php");

$db = new Database();
$conn = $db->getConnection();

$userRole = isset($_SESSION['role']) ? $_SESSION['role'] : 'guest';

// USING VIEW software_by_pc_details
$sql = "SELECT * FROM software_by_pc_details ORDER BY pc_number, software_name";
$stmt = $conn->prepare($sql);
$stmt->execute();
$softwareByPC = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
            <span class="text-muted fw-light">Software Management /</span> Software by PC
        </h4>

        <!-- Software by PC Table -->
        <div class="card">
            <div class="table-responsive text-nowrap">
                <table class="table table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>PC Number</th>
                            <th>Lab</th>
                            <th>PC Status</th>
                            <th>Software Name</th>
                            <th>Version</th>
                            <th>License Type</th>
                            <th>Install Date</th>
                            <th>Installed By</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
<?php if(count($softwareByPC) > 0): ?>
    <?php 
    $current_pc = null;
    foreach($softwareByPC as $item): 
        $isNewPC = $current_pc !== $item['pc_number'];
        if($isNewPC) $current_pc = $item['pc_number'];
    ?>
    <tr>
        <!-- PC NUMBER -->
        <td>
            <?php echo $isNewPC ? '<strong>' . htmlspecialchars($item['pc_number']) . '</strong>' : ''; ?>
        </td>

        <!-- LAB -->
        <td>
            <?php echo $isNewPC ? htmlspecialchars($item['lab_name']) : ''; ?>
        </td>

        <!-- STATUS -->
        <td>
            <?php if($isNewPC): 
                $statusClass = '';
                switch($item['pc_status']) {
                    case 'Available': $statusClass = 'bg-label-success'; break;
                    case 'Assigned': $statusClass = 'bg-label-primary'; break;
                    case 'Under Maintenance': $statusClass = 'bg-label-warning'; break;
                    case 'Retired': $statusClass = 'bg-label-danger'; break;
                }
            ?>
                <span class="badge <?php echo $statusClass; ?>">
                    <?php echo $item['pc_status']; ?>
                </span>
            <?php endif; ?>
        </td>

        <!-- SOFTWARE -->
        <td>
            <?php echo $item['software_name'] ?: '<span class="text-muted">No software installed</span>'; ?>
        </td>

        <!-- VERSION -->
        <td><?php echo $item['software_version'] ?: '-'; ?></td>

        <!-- LICENSE -->
        <td>
            <?php if($item['license_type']): ?>
                <span class="badge bg-label-info"><?php echo $item['license_type']; ?></span>
            <?php else: ?>
                <span class="text-muted">-</span>
            <?php endif; ?>
        </td>

        <!-- DATE -->
        <td>
            <?php echo $item['install_date'] ? date('M d, Y', strtotime($item['install_date'])) : '-'; ?>
        </td>

        <!-- INSTALLED BY -->
        <td>
            <?php echo $item['installed_by_name'] ? htmlspecialchars($item['installed_by_name']) : '-'; ?>
        </td>
    </tr>
    <?php endforeach; ?>
<?php else: ?>
    <tr>
        <td colspan="8" class="text-center py-4">
            <div class="alert alert-info mb-0">
                <i class="bx bx-info-circle"></i> No software installations found.
            </div>
        </td>
    </tr>
<?php endif; ?>
</tbody>
                 </table
            </div>
        </div>
    </div>
</div>

<?php include("components/footer.php"); ?>