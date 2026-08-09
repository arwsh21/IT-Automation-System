<?php
session_start();
include("components/header.php");
require_once 'config/query.php';

$database = new Database();
$conn = $database->getConnection();

$error = '';
$success = '';

$installedQuery = "SELECT i.installation_id, p.pc_number, s.name as software_name, s.version, i.install_status
                   FROM INSTALLATION i
                   JOIN PC p ON i.pc_id = p.pc_id
                   JOIN SOFTWARE s ON i.software_id = s.software_id
                   WHERE i.install_status = 'Active'
                   ORDER BY p.pc_number, s.name";
$installedStmt = $conn->prepare($installedQuery);
$installedStmt->execute();
$installations = $installedStmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['installation_id'])) {
    $installation_id = $_POST['installation_id'];

    try {  // TRA N S AC TI ON YIPEEE
        $conn->beginTransaction();

        $query = "UPDATE INSTALLATION SET install_status = 'Uninstalled', last_updated = CURDATE() WHERE installation_id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $installation_id);
        $stmt->execute();

        $conn->commit();
        $_SESSION['success_message'] = "Software uninstalled successfully!";
        echo "<script>location.assign('uninstall_software.php')</script>";
        exit();

    } catch(PDOException $e) {
        $conn->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}

if (isset($_SESSION['success_message'])) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bx bx-check-circle"></i> ' . $_SESSION['success_message'] . '
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
    unset($_SESSION['success_message']);
}
if (!empty($error)) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bx bx-error-circle"></i> ' . $error . '
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
}
?>

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1">
        <h4 class="fw-bold py-3 mb-4">
            <span class="text-muted fw-light">Software Management /</span> Uninstall Software
        </h4>

        <?php if (count($installations) > 0): ?>
            <!-- Column card grid -->
            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-xl-4 g-3">
                <?php foreach ($installations as $inst): ?>
                <div class="col">
                    <div class="card h-100">
                        <div class="card-body d-flex flex-column gap-2">

                            <div class="d-flex justify-content-between align-items-center">
                                <span class="badge bg-label-success">Active</span>
                                <small class="text-muted fw-semibold">
                                    <?php echo htmlspecialchars($inst['pc_number']); ?>
                                </small>
                            </div>

                            <div class="mt-1">
                                <h6 class="mb-1">
                                    <?php echo htmlspecialchars($inst['software_name']); ?>
                                </h6>
                                <small class="text-muted">
                                    v<?php echo htmlspecialchars($inst['version']); ?>
                                </small>
                            </div>

                            <form method="POST"
                                  class="mt-auto"
                                  onsubmit="return confirm('Uninstall <?php echo htmlspecialchars($inst['software_name']); ?> from <?php echo htmlspecialchars($inst['pc_number']); ?>?')">
                                <input type="hidden" name="installation_id" value="<?php echo $inst['installation_id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger w-100">
                                    <i class="bx bx-trash"></i> Uninstall
                                </button>
                            </form>

                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            <div class="alert alert-info text-center py-4">
                <i class="bx bx-info-circle"></i> No active software installations found.
                <br>
                <a href="install_software.php" class="btn btn-primary mt-2">Install Software</a>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php include("components/footer.php"); ?>