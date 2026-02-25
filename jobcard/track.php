<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
  header('Location: ../login/index.php');
  exit();
}

require_once '../config/database.php';

$jobCards = [];
$error = '';
$flashMessage = '';
$flashType = '';

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

$statusOptions = ['pending', 'in-progress', 'completed'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $jobId = (int)($_POST['job_id'] ?? 0);
  $action = trim((string)($_POST['action'] ?? ''));

  if ($jobId <= 0) {
    $flashMessage = 'Invalid job card update request.';
    $flashType = 'error';
  } else {
    try {
      $pdo = getDBConnection();

      $mechanicRows = $pdo->query("SHOW COLUMNS FROM job_cards LIKE 'mechanic_name'")->fetchAll(PDO::FETCH_ASSOC);
      if (empty($mechanicRows)) {
        $pdo->exec("ALTER TABLE job_cards ADD COLUMN mechanic_name VARCHAR(150) NULL AFTER submitted_by");
      }

      if ($action === 'complete') {
        $stmt = $pdo->prepare('SELECT customer_name, vehicle_number, vehicle_model, customer_address, phone_number, job_status FROM job_cards WHERE id = ? LIMIT 1');
        $stmt->execute([$jobId]);
        $jobData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($jobData) {
          // Check if already completed
          if ($jobData['job_status'] === 'completed') {
            throw new Exception('This job card is already completed.');
          }
          $customerDataDir = __DIR__ . '/../customer/data';
          if (!is_dir($customerDataDir)) {
            mkdir($customerDataDir, 0775, true);
          }
          $customerJsonPath = $customerDataDir . '/customers.json';

          $customerRows = [];
          if (file_exists($customerJsonPath)) {
            $decoded = json_decode((string)file_get_contents($customerJsonPath), true);
            if (is_array($decoded)) {
              $customerRows = $decoded;
            }
          }

          $phoneNumber = trim((string)($jobData['phone_number'] ?? ''));
          $matched = false;

          if ($phoneNumber !== '') {
            foreach ($customerRows as &$row) {
              $existingPhone = trim((string)($row['phone'] ?? ''));
              if ($existingPhone !== '' && $existingPhone === $phoneNumber) {
                $row['name'] = trim((string)($jobData['customer_name'] ?? ''));
                $row['vehicle_no'] = trim((string)($jobData['vehicle_number'] ?? ''));
                $row['model'] = trim((string)($jobData['vehicle_model'] ?? ''));
                $row['address'] = trim((string)($jobData['customer_address'] ?? ''));
                $matched = true;
                break;
              }
            }
            unset($row);
          }

          if (!$matched) {
            $customerRows[] = [
              'name' => trim((string)($jobData['customer_name'] ?? '')),
              'vehicle_no' => trim((string)($jobData['vehicle_number'] ?? '')),
              'model' => trim((string)($jobData['vehicle_model'] ?? '')),
              'address' => trim((string)($jobData['customer_address'] ?? '')),
              'phone' => $phoneNumber,
            ];
          }

          file_put_contents($customerJsonPath, json_encode($customerRows, JSON_PRETTY_PRINT));
        }

        $stmt = $pdo->prepare('UPDATE job_cards SET job_status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
        $stmt->execute(['completed', $jobId]);
        $flashMessage = 'Repair marked as completed. Customer data saved to Customers section.';
        $flashType = 'success';
      } elseif ($action === 'update') {
        // Check if job is already completed
        $stmt = $pdo->prepare('SELECT job_status FROM job_cards WHERE id = ? LIMIT 1');
        $stmt->execute([$jobId]);
        $currentJob = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($currentJob && $currentJob['job_status'] === 'completed') {
          throw new Exception('Cannot update a completed job card. Job is already finished.');
        }

        $customerName = trim((string)($_POST['customer_name'] ?? ''));
        $vehicleNumber = strtoupper(trim((string)($_POST['vehicle_number'] ?? '')));
        $vehicleModel = trim((string)($_POST['vehicle_model'] ?? ''));
        $serviceDate = trim((string)($_POST['service_date'] ?? ''));
        $mechanicName = trim((string)($_POST['mechanic_name'] ?? ''));
        $bayCode = trim((string)($_POST['bay_code'] ?? ''));
        $jobStatus = trim((string)($_POST['job_status'] ?? 'pending'));

        if ($customerName === '' || $vehicleNumber === '' || $vehicleModel === '' || $serviceDate === '') {
          throw new Exception('Customer, vehicle, and date fields are required.');
        }

        if (!isset($bayMap[$bayCode])) {
          throw new Exception('Invalid bay selected.');
        }

        // Prevent manually setting to completed via Update - must use OK button
        if ($jobStatus === 'completed') {
          throw new Exception('Cannot mark as completed via Update. Use OK button to complete.');
        }

        if (!in_array($jobStatus, $statusOptions, true)) {
          throw new Exception('Invalid job status selected.');
        }

        $stmt = $pdo->prepare('UPDATE job_cards SET customer_name = ?, vehicle_number = ?, vehicle_model = ?, service_date = ?, mechanic_name = ?, bay_code = ?, job_status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
        $stmt->execute([$customerName, $vehicleNumber, $vehicleModel, $serviceDate, $mechanicName, $bayCode, $jobStatus, $jobId]);

        $flashMessage = 'Repair details updated successfully.';
        $flashType = 'success';
      }
    } catch (Exception $e) {
      $flashMessage = $e->getMessage();
      $flashType = 'error';
    }
  }
}

try {
  $pdo = getDBConnection();
  $mechanicRows = $pdo->query("SHOW COLUMNS FROM job_cards LIKE 'mechanic_name'")->fetchAll(PDO::FETCH_ASSOC);
  if (empty($mechanicRows)) {
    $pdo->exec("ALTER TABLE job_cards ADD COLUMN mechanic_name VARCHAR(150) NULL AFTER submitted_by");
  }

  $stmt = $pdo->query('SELECT id, job_card_no, customer_name, vehicle_number, vehicle_model, service_date, fuel_level, submitted_by, mechanic_name, bay_code, job_status, created_at FROM job_cards ORDER BY id DESC');
  $jobCards = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
  $error = 'Could not load job cards. Please check database setup.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Track Repair - Vehicle Job Card System</title>
  <link rel="stylesheet" href="style.css" />
</head>
<body>
  <div class="layout">
    <aside class="sidebar">
      <div class="sidebar-logo">
        <div class="logo-circle">
          <img src="../image/logo.png" alt="Vehicle logo" />
        </div>
      </div>

      <nav class="sidebar-nav">
        <a href="../index.php" class="nav-item">
          <span class="nav-icon">🏠</span>
          <span class="nav-label">Dashboard</span>
        </a>

        <div class="nav-group" data-hover-menu>
          <a href="index.php" class="nav-item active">
            <span class="nav-icon">📄</span>
            <span class="nav-label">Job cards</span>
            <span class="nav-caret">▾</span>
          </a>
          <div class="nav-submenu">
            <a href="create.php" class="nav-subitem">Create Job Card</a>
            <a href="track.php" class="nav-subitem nav-subitem-active">Track Repair</a>
          </div>
        </div>

        <a href="../customer/index.php" class="nav-item">
          <span class="nav-icon">👥</span>
          <span class="nav-label">Customers</span>
        </a>

        <a href="../login/index.php" class="nav-item">
          <span class="nav-icon">⏻</span>
          <span class="nav-label">Logout</span>
        </a>
      </nav>
    </aside>

    <main class="main">
      <header class="main-header">
        <div>
          <h1 class="app-title">Vehicle Job Card System</h1>
          <p class="app-subtitle">Professional workshop management</p>
        </div>
        <div class="user-info">
          <p class="user-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['user_email'] ?? 'User'); ?></p>
          <p class="user-role">Service Incharge</p>
        </div>
      </header>

      <section class="form-card">
        <div class="card-head">
          <h2>Track Repair / Job Cards</h2>
          <p>View, edit and complete submitted vehicle repair jobs.</p>
        </div>

        <?php if ($flashMessage !== ''): ?>
          <div class="alert <?php echo $flashType === 'success' ? 'alert-success' : 'alert-error'; ?>">
            <?php echo htmlspecialchars($flashMessage); ?>
          </div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
          <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="filter-bar">
          <div class="filter-left">
            <div class="date-filter">
              <label for="startDate">From:</label>
              <input type="date" id="startDate" class="date-input" />
            </div>
            <div class="date-filter">
              <label for="endDate">To:</label>
              <input type="date" id="endDate" class="date-input" />
            </div>
            <a href="export_track.php" id="exportBtn" class="filter-btn export-filter-btn" title="Export to Excel">📊 Export</a>
          </div>
        </div>

        <div class="table-wrap">
          <table class="track-table">
            <thead>
              <tr>
                <th>Job Card No</th>
                <th>Customer</th>
                <th>Vehicle No.</th>
                <th>Model</th>
                <th>Bay</th>
                <th>Date</th>
                <th>Mechanic</th>
                <th>Status</th>
                <th>Fuel</th>
                <th>Submitted By</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($jobCards)): ?>
                <tr>
                  <td colspan="11" class="empty-cell">No job cards found.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($jobCards as $card): ?>
                  <?php $isCompleted = ($card['job_status'] === 'completed'); ?>
                  <tr>
                    <form method="POST" action="track.php">
                      <input type="hidden" name="job_id" value="<?php echo (int)$card['id']; ?>" />
                      <td><a href="view.php?job_card_no=<?php echo urlencode((string)$card['job_card_no']); ?>"><?php echo htmlspecialchars($card['job_card_no']); ?></a></td>
                      <td>
                        <input class="track-input" type="text" name="customer_name" value="<?php echo htmlspecialchars($card['customer_name']); ?>" required <?php echo $isCompleted ? 'readonly' : ''; ?> />
                      </td>
                      <td>
                        <input class="track-input" type="text" name="vehicle_number" value="<?php echo htmlspecialchars($card['vehicle_number']); ?>" required <?php echo $isCompleted ? 'readonly' : ''; ?> />
                      </td>
                      <td>
                        <input class="track-input" type="text" name="vehicle_model" value="<?php echo htmlspecialchars($card['vehicle_model']); ?>" required <?php echo $isCompleted ? 'readonly' : ''; ?> />
                      </td>
                      <td>
                        <select class="track-select" name="bay_code" required <?php echo $isCompleted ? 'disabled' : ''; ?>>
                          <?php foreach ($bayMap as $code => $label): ?>
                            <option value="<?php echo htmlspecialchars($code); ?>" <?php echo $card['bay_code'] === $code ? 'selected' : ''; ?>>
                              <?php echo htmlspecialchars($label); ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </td>
                      <td>
                        <input class="track-input" type="date" name="service_date" value="<?php echo htmlspecialchars($card['service_date']); ?>" required <?php echo $isCompleted ? 'readonly' : ''; ?> />
                      </td>
                      <td>
                        <input class="track-input" type="text" name="mechanic_name" value="<?php echo htmlspecialchars((string)($card['mechanic_name'] ?? '')); ?>" placeholder="Assign mechanic" <?php echo $isCompleted ? 'readonly' : ''; ?> />
                      </td>
                      <td>
                        <select class="track-select" name="job_status" required <?php echo $isCompleted ? 'disabled' : ''; ?>>
                          <?php foreach ($statusOptions as $status): ?>
                            <?php
                            // Only show "completed" if already completed, otherwise hide it from dropdown
                            if ($status === 'completed' && !$isCompleted) {
                              continue;
                            }
                            ?>
                            <option value="<?php echo htmlspecialchars($status); ?>" <?php echo $card['job_status'] === $status ? 'selected' : ''; ?>>
                              <?php echo htmlspecialchars(ucwords(str_replace('-', ' ', $status))); ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </td>
                      <td><?php echo htmlspecialchars($card['fuel_level']); ?></td>
                      <td><?php echo htmlspecialchars($card['submitted_by']); ?></td>
                      <td class="action-cell">
                        <?php if (!$isCompleted): ?>
                          <button class="action-btn save-btn" type="submit" name="action" value="update">Update</button>
                          <button class="action-btn ok-btn" type="submit" name="action" value="complete">OK</button>
                        <?php else: ?>
                          <span class="completed-badge">✓ Completed</span>
                        <?php endif; ?>
                      </td>
                    </form>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </section>
    </main>
  </div>

  <script src="script.js"></script>
</body>
</html>
