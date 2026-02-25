<?php
session_start();

// Simple auth guard – redirect to login if not logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
  header('Location: login/index.php');
    exit();
}

require_once 'config/database.php';

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

$bays = [];
foreach ($bayMap as $id => $label) {
  $bays[$id] = [
    'id' => $id,
    'label' => $label,
    'status' => 'free',
    'job_card_no' => '',
    'submitted_by' => '',
    'mechanic_name' => '',
    'vehicle_number' => '',
  ];
}

$stats = [
  'total' => 0,
  'pending' => 0,
  'in-progress' => 0,
  'completed' => 0,
];

try {
  $pdo = getDBConnection();
  $rows = $pdo->query('SELECT job_card_no, bay_code, submitted_by, mechanic_name, vehicle_number, job_status, created_at FROM job_cards ORDER BY created_at DESC, id DESC')->fetchAll(PDO::FETCH_ASSOC);

  foreach ($rows as $row) {
    $stats['total']++;
    $status = strtolower((string)($row['job_status'] ?? 'pending'));
    if (isset($stats[$status])) {
      $stats[$status]++;
    }

    $bayCode = (string)($row['bay_code'] ?? '');
    if ($bayCode === '' || !isset($bays[$bayCode])) {
      continue;
    }

    if ($bays[$bayCode]['job_card_no'] === '') {
      $bayStatus = in_array($status, ['pending', 'in-progress'], true) ? $status : 'free';
      $bays[$bayCode]['status'] = $bayStatus;
      $bays[$bayCode]['job_card_no'] = (string)($row['job_card_no'] ?? '');
      $bays[$bayCode]['submitted_by'] = (string)($row['submitted_by'] ?? '');
      $bays[$bayCode]['mechanic_name'] = (string)($row['mechanic_name'] ?? '');
      $bays[$bayCode]['vehicle_number'] = (string)($row['vehicle_number'] ?? '');
    }
  }
} catch (Exception $e) {
}

$bays = array_values($bays);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard - Vehicle Job Card System</title>
  <link rel="stylesheet" href="style.css" />
</head>
<body>
  <div class="layout">
    <!-- Sidebar -->
    <aside class="sidebar">
      <div class="sidebar-logo">
        <div class="logo-circle">
          <img src="image/logo.png" alt="Vehicle logo" />
        </div>
      </div>

      <nav class="sidebar-nav">
        <a href="index.php" class="nav-item active">
          <span class="nav-icon">🏠</span>
          <span class="nav-label">Dashboard</span>
        </a>
        <div class="nav-group" data-hover-menu>
          <a href="jobcard/index.php" class="nav-item">
            <span class="nav-icon">📄</span>
            <span class="nav-label">Job cards</span>
            <span class="nav-caret">▾</span>
          </a>
          <div class="nav-submenu">
            <a href="jobcard/create.php" class="nav-subitem">Create Job Card</a>
            <a href="jobcard/track.php" class="nav-subitem">Track Repair</a>
          </div>
        </div>
        <a href="customer/index.php" class="nav-item">
          <span class="nav-icon">👥</span>
          <span class="nav-label">Customers</span>
        </a>
        <a href="login/index.php" class="nav-item">
          <span class="nav-icon">⏻</span>
          <span class="nav-label">Logout</span>
        </a>
      </nav>
    </aside>

    <!-- Main content -->
    <main class="main">
      <!-- Header -->
      <header class="main-header">
        <div>
          <h1 class="app-title">Vehicle Job Card System</h1>
          <p class="app-subtitle">Professional workshop management</p>
        </div>
        <div class="user-info">
          <p class="user-name">
            <?php echo htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['user_email'] ?? 'User'); ?>
          </p>
          <p class="user-role">Service Incharge</p>
        </div>
      </header>

      <!-- Top stats -->
      <section class="stats-row">
        <div class="stat-card">
          <p class="stat-label">Total Jobs</p>
          <p class="stat-value"><?php echo (int)$stats['total']; ?></p>
        </div>
        <div class="stat-card stat-pending">
          <p class="stat-label">Pending</p>
          <p class="stat-value"><?php echo (int)$stats['pending']; ?></p>
        </div>
        <div class="stat-card stat-in-progress">
          <p class="stat-label">In Progress</p>
          <p class="stat-value"><?php echo (int)$stats['in-progress']; ?></p>
        </div>
        <div class="stat-card stat-completed">
          <p class="stat-label">Completed</p>
          <p class="stat-value"><?php echo (int)$stats['completed']; ?></p>
        </div>
      </section>

      <!-- Bay overview -->
      <section class="bay-section">
        <div class="bay-header">
          <h2>Bay Status Overview</h2>
          <div class="bay-legend">
            <span class="legend-item">
              <span class="legend-dot legend-free"></span> Free
            </span>
            <span class="legend-item">
              <span class="legend-dot legend-pending"></span> Pending
            </span>
            <span class="legend-item">
              <span class="legend-dot legend-in-progress"></span> In progress
            </span>
          </div>
        </div>

        <div class="bay-grid">
          <?php foreach ($bays as $bay): ?>
            <div
              class="bay-card bay-<?php echo htmlspecialchars($bay['status']); ?>"
            >
              <div class="bay-title-row">
                <span class="bay-name"><?php echo htmlspecialchars($bay['label']); ?></span>
                <span class="bay-chip bay-chip-<?php echo htmlspecialchars($bay['status']); ?>">
                  <?php echo $bay['status'] === 'free' ? 'Free' : ($bay['status'] === 'pending' ? 'Pending' : 'In progress'); ?>
                </span>
              </div>
              <p class="bay-subtitle">
                <?php echo $bay['status'] === 'free'
                  ? 'Free'
                  : htmlspecialchars($bay['job_card_no'] . ' • Mechanic: ' . ($bay['mechanic_name'] !== '' ? $bay['mechanic_name'] : 'Not assigned') . ' • Machine: ' . ($bay['vehicle_number'] !== '' ? $bay['vehicle_number'] : '-')); ?>
              </p>
            </div>
          <?php endforeach; ?>
        </div>
      </section>
    </main>
  </div>

  <script src="script.js"></script>
</body>
</html>

