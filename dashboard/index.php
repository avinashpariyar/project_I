<?php
session_start();

// Simple auth guard – redirect to login if not logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login/index.php');
    exit();
}

// Example bay list – later you can load this from the database
$bays = [
    ['id' => 'bay1',  'label' => 'Bay 1',  'status' => 'free'],
    ['id' => 'bay2',  'label' => 'Bay 2',  'status' => 'free'],
    ['id' => 'bay3',  'label' => 'Bay 3',  'status' => 'free'],
    ['id' => 'bay4',  'label' => 'Bay 4',  'status' => 'pending'],
    ['id' => 'bay5',  'label' => 'Bay 5',  'status' => 'in-progress'],
    ['id' => 'bay6',  'label' => 'Bay 6',  'status' => 'free'],
    ['id' => 'bay7',  'label' => 'Bay 7',  'status' => 'free'],
    ['id' => 'bayW',  'label' => 'Bay W (Washing)',  'status' => 'free'],
];
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
          <img src="../image/logo.png" alt="Vehicle logo" />
        </div>
      </div>

      <nav class="sidebar-nav">
        <a href="index.php" class="nav-item active">
          <span class="nav-icon">🏠</span>
          <span class="nav-label">Dashboard</span>
        </a>
        <div class="nav-group" data-hover-menu>
          <a href="../jobcard/index.php" class="nav-item">
            <span class="nav-icon">📄</span>
            <span class="nav-label">Job cards</span>
            <span class="nav-caret">▾</span>
          </a>
          <div class="nav-submenu">
            <a href="../jobcard/create.php" class="nav-subitem">Create New Job Card</a>
            <a href="../jobcard/track.php" class="nav-subitem">Track Repair</a>
          </div>
        </div>
        <a href="../customers/index.php" class="nav-item">
          <span class="nav-icon">👥</span>
          <span class="nav-label">Customers</span>
        </a>
        <a href="../login/index.php" class="nav-item">
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
          <p class="stat-value">0</p>
        </div>
        <div class="stat-card stat-pending">
          <p class="stat-label">Pending</p>
          <p class="stat-value">0</p>
        </div>
        <div class="stat-card stat-in-progress">
          <p class="stat-label">In Progress</p>
          <p class="stat-value">0</p>
        </div>
        <div class="stat-card stat-completed">
          <p class="stat-label">Completed</p>
          <p class="stat-value">0</p>
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
            <span class="legend-item">
              <span class="legend-dot legend-selected"></span> Selected
            </span>
          </div>
        </div>

        <div class="bay-grid">
          <?php foreach ($bays as $bay): ?>
            <button
              class="bay-card bay-<?php echo htmlspecialchars($bay['status']); ?>"
              data-bay-id="<?php echo htmlspecialchars($bay['id']); ?>"
              data-bay-status="<?php echo htmlspecialchars($bay['status']); ?>"
              type="button"
            >
              <div class="bay-title-row">
                <span class="bay-name"><?php echo htmlspecialchars($bay['label']); ?></span>
                <span class="bay-chip bay-chip-<?php echo htmlspecialchars($bay['status']); ?>">
                  <?php echo $bay['status'] === 'free' ? 'Free' : ($bay['status'] === 'pending' ? 'Pending' : 'In progress'); ?>
                </span>
              </div>
              <p class="bay-subtitle">
                Free
              </p>
            </button>
          <?php endforeach; ?>
        </div>
      </section>
    </main>
  </div>

  <script src="script.js"></script>
</body>
</html>

