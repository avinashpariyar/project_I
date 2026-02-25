<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
  header('Location: ../login/index.php');
  exit();
}

$bays = [
  ['id' => 'bay1', 'label' => 'Bay 1'],
  ['id' => 'bay2', 'label' => 'Bay 2'],
  ['id' => 'bay3', 'label' => 'Bay 3'],
  ['id' => 'bay4', 'label' => 'Bay 4'],
  ['id' => 'bay5', 'label' => 'Bay 5'],
  ['id' => 'bay6', 'label' => 'Bay 6'],
  ['id' => 'bay7', 'label' => 'Bay 7'],
  ['id' => 'bayW', 'label' => 'Bay W (Washing)'],
];

$submittedByName = trim((string)($_SESSION['user_name'] ?? $_SESSION['user_email'] ?? 'Service Incharge'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Create Job Card - Vehicle Job Card System</title>
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
            <a href="create.php" class="nav-subitem nav-subitem-active">Create Job Card</a>
            <a href="track.php" class="nav-subitem">Track Repair</a>
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
          <h2>Create Vehicle Job Card</h2>
          <p>Fill all required details to register a new service job card.</p>
        </div>

        <div id="successAlert" class="alert alert-success" hidden></div>
        <div id="errorAlert" class="alert alert-error" hidden></div>

        <form id="jobCardForm" class="job-grid" novalidate>
          <div class="field">
            <label for="vehicle_number">Vehicle Number <span>*</span></label>
            <input type="text" id="vehicle_number" name="vehicle_number" required placeholder="BA-01-PA-1234" />
          </div>

          <div class="field">
            <label for="vehicle_model">Vehicle Model <span>*</span></label>
            <input type="text" id="vehicle_model" name="vehicle_model" required placeholder="Scorpio" />
          </div>

          <div class="field">
            <label for="service_date">Date <span>*</span></label>
            <input type="date" id="service_date" name="service_date" required />
          </div>

          <div class="field">
            <label for="customer_name">Customer Name <span>*</span></label>
            <input type="text" id="customer_name" name="customer_name" required placeholder="Customer full name" />
          </div>

          <div class="field">
            <label for="phone_number">Phone Number <span>*</span></label>
            <input type="tel" id="phone_number" name="phone_number" required placeholder="98XXXXXXXX" pattern="[0-9]{10}" />
          </div>

          <div class="field">
            <label for="kms">KMS <span>*</span></label>
            <input type="number" id="kms" name="kms" required min="0" placeholder="e.g. 42000" />
          </div>

          <div class="field bay-field">
            <label for="bay_code">Bay <span>*</span></label>
            <select id="bay_code" name="bay_code" required>
              <option value="">Select Bay</option>
              <?php foreach ($bays as $bay): ?>
                <option value="<?php echo htmlspecialchars($bay['id']); ?>"><?php echo htmlspecialchars($bay['label']); ?></option>
              <?php endforeach; ?>
            </select>
            <div class="bay-preview" id="bayPreview">No bay selected</div>
          </div>
          
          <div class="field">
            <label for="mechanic_name">Assigned Mechanic <span>*</span></label>
            <input type="text" id="mechanic_name" name="mechanic_name" required placeholder="Enter mechanic name" />
          </div>

          <div class="field field-full">
            <label for="customer_address">Customer Address <span>*</span></label>
            <textarea id="customer_address" name="customer_address" rows="3" required placeholder="Enter full address"></textarea>
          </div>

          <div class="field field-full">
            <label>Inventory Checklist (Accessories & Items availability)</label>
            <div class="checklist-grid">
              <label><input type="checkbox" name="inventory_items[]" value="Stepney" /> Stepney</label>
              <label><input type="checkbox" name="inventory_items[]" value="Jack" /> Jack</label>
              <label><input type="checkbox" name="inventory_items[]" value="Tool Kit" /> Tool Kit</label>
              <label><input type="checkbox" name="inventory_items[]" value="Music System" /> Music System</label>
              <label><input type="checkbox" name="inventory_items[]" value="Floor Mat" /> Floor Mat</label>
              <label><input type="checkbox" name="inventory_items[]" value="Documents" /> Documents</label>
            </div>
          </div>

          <div class="field field-full">
            <label>Fuel Level Selection <span>*</span></label>
            <div class="fuel-row">
              <label><input type="radio" name="fuel_level" value="Empty" required /> Empty</label>
              <label><input type="radio" name="fuel_level" value="1/4" /> 1/4</label>
              <label><input type="radio" name="fuel_level" value="1/2" /> 1/2</label>
              <label><input type="radio" name="fuel_level" value="3/4" /> 3/4</label>
              <label><input type="radio" name="fuel_level" value="Full" /> Full</label>
            </div>
          </div>

          <div class="field field-full">
            <label for="demanded_jobs">Demanded Jobs (By Customer) <span>*</span></label>
            <textarea id="demanded_jobs" name="demanded_jobs" rows="4" required placeholder="Describe customer requested jobs"></textarea>
          </div>

          <div class="field field-full">
            <label for="recommended_jobs">Recommended Jobs (By Service Center)</label>
            <textarea id="recommended_jobs" name="recommended_jobs" rows="4" placeholder="Service center recommendations"></textarea>
          </div>

          <div class="field field-full">
            <label>Submitted By (Service Incharge)</label>
            <div class="submitted-by-display"><?php echo htmlspecialchars($submittedByName); ?></div>
          </div>

          <div class="actions field-full">
            <button type="submit" class="btn-primary">Create Job Card</button>
            <button type="reset" class="btn-secondary">Reset</button>
          </div>
        </form>
      </section>
    </main>
  </div>

  <script src="script.js"></script>
</body>
</html>
