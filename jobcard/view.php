<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
  header('Location: ../login/index.php');
  exit();
}

require_once '../config/database.php';

$jobCardNo = trim((string)($_GET['job_card_no'] ?? ''));
$jobCard = null;
$error = '';

$bayMap = [
  'bay1' => 'Bay 1',
  'bay2' => 'Bay 2',
  'bay3' => 'Bay 3',
  'bay4' => 'Bay 4',
  'bay5' => 'Bay 5',
  'bay6' => 'Bay 6',
  'bay7' => 'Bay 7',
  'bayW' => 'Bay W (Washing)',
];

if ($jobCardNo === '') {
  $error = 'Invalid job card number.';
} else {
  try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare('SELECT * FROM job_cards WHERE job_card_no = ? LIMIT 1');
    $stmt->execute([$jobCardNo]);
    $jobCard = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$jobCard) {
      $error = 'Job card not found.';
    }
  } catch (Exception $e) {
    $error = 'Could not load job card details. Please check database setup.';
  }
}

$inventoryList = [];
if ($jobCard && !empty($jobCard['inventory_items'])) {
  $decoded = json_decode((string)$jobCard['inventory_items'], true);
  if (is_array($decoded)) {
    $inventoryList = $decoded;
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Job Card Preview - Vehicle Job Card System</title>
  <link rel="stylesheet" href="view.css" />
</head>
<body>
  <div class="page-wrap">
    <header class="page-top no-print">
      <h1>Vehicle Job Card</h1>
      <div class="top-actions">
        <a href="create.php" class="btn btn-secondary">Go Back</a>
        <button type="button" class="btn btn-primary" onclick="window.print()">Print</button>
      </div>
    </header>

    <?php if ($error !== ''): ?>
      <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
    <?php elseif ($jobCard): ?>
      <main class="jobcard-sheet">
        <section class="sheet-head">
          <div>
            <h2>Vehicle Job Card System</h2>
            <p>Professional workshop management</p>
          </div>
          <div class="head-meta">
            <p><strong>Job Card No:</strong> <?php echo htmlspecialchars($jobCard['job_card_no']); ?></p>
            <p><strong>Date:</strong> <?php echo htmlspecialchars((string)$jobCard['service_date']); ?></p>
            <p><strong>Bay:</strong> <?php echo htmlspecialchars($bayMap[$jobCard['bay_code']] ?? (string)$jobCard['bay_code']); ?></p>
          </div>
        </section>

        <section class="info-grid">
          <div class="info-block">
            <h3>Vehicle Details</h3>
            <p><span>Vehicle Number</span> <?php echo htmlspecialchars((string)$jobCard['vehicle_number']); ?></p>
            <p><span>Model</span> <?php echo htmlspecialchars((string)$jobCard['vehicle_model']); ?></p>
            <p><span>KMS</span> <?php echo htmlspecialchars((string)$jobCard['kms']); ?></p>
            <p><span>Fuel Level</span> <?php echo htmlspecialchars((string)$jobCard['fuel_level']); ?></p>
          </div>

          <div class="info-block">
            <h3>Customer Details</h3>
            <p><span>Name</span> <?php echo htmlspecialchars((string)$jobCard['customer_name']); ?></p>
            <p><span>Phone</span> <?php echo htmlspecialchars((string)$jobCard['phone_number']); ?></p>
            <p><span>Submitted By</span> <?php echo htmlspecialchars((string)$jobCard['submitted_by']); ?></p>
            <p><span>Mechanic</span> <?php echo htmlspecialchars((string)($jobCard['mechanic_name'] ?? 'Not assigned')); ?></p>
            <p><span>Assigned Bay</span> <?php echo htmlspecialchars($bayMap[$jobCard['bay_code']] ?? (string)$jobCard['bay_code']); ?></p>
            <p><span>Status</span> <?php echo htmlspecialchars((string)$jobCard['job_status']); ?></p>
          </div>
        </section>

        <section class="wide-block">
          <h3>Customer Address</h3>
          <p><?php echo nl2br(htmlspecialchars((string)$jobCard['customer_address'])); ?></p>
        </section>

        <section class="wide-block">
          <h3>Inventory Checklist</h3>
          <?php if (empty($inventoryList)): ?>
            <p>No inventory items marked.</p>
          <?php else: ?>
            <ul>
              <?php foreach ($inventoryList as $item): ?>
                <li><?php echo htmlspecialchars((string)$item); ?></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </section>

        <section class="wide-block">
          <h3>Demanded Jobs (By Customer)</h3>
          <p><?php echo nl2br(htmlspecialchars((string)$jobCard['demanded_jobs'])); ?></p>
        </section>

        <section class="wide-block">
          <h3>Recommended Jobs (By Service Center)</h3>
          <p><?php echo nl2br(htmlspecialchars((string)$jobCard['recommended_jobs'])); ?></p>
        </section>

        <section class="sign-row">
          <div>
            <p>Submitted By</p>
            <strong><?php echo htmlspecialchars((string)$jobCard['submitted_by']); ?></strong>
          </div>
          <div>
            <p>Customer Signature</p>
            <span class="line"></span>
          </div>
          <div>
            <p>Authorized Signature</p>
            <span class="line"></span>
          </div>
        </section>
      </main>
    <?php endif; ?>
  </div>
</body>
</html>
