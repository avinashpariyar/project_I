<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
  http_response_code(401);
  echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
  exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
  exit();
}

require_once '../config/database.php';

function postValue(string $key): string
{
  return trim((string)($_POST[$key] ?? ''));
}

$vehicleNumber = strtoupper(postValue('vehicle_number'));
$vehicleModel = postValue('vehicle_model');
$serviceDate = postValue('service_date');
$customerName = postValue('customer_name');
$phoneNumber = postValue('phone_number');
$kms = postValue('kms');
$bayCode = postValue('bay_code');
$mechanicName = postValue('mechanic_name');
$customerAddress = postValue('customer_address');
$fuelLevel = postValue('fuel_level');
$demandedJobs = postValue('demanded_jobs');
$recommendedJobs = postValue('recommended_jobs');
$submittedBy = trim((string)($_SESSION['user_name'] ?? $_SESSION['user_email'] ?? 'Service Incharge'));
$inventoryItems = $_POST['inventory_items'] ?? [];

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

$requiredMap = [
  'Vehicle Number' => $vehicleNumber,
  'Vehicle Model' => $vehicleModel,
  'Date' => $serviceDate,
  'Customer Name' => $customerName,
  'Phone Number' => $phoneNumber,
  'KMS' => $kms,
  'Bay' => $bayCode,
  'Assigned Mechanic' => $mechanicName,
  'Customer Address' => $customerAddress,
  'Demanded Jobs' => $demandedJobs,
  'Submitted By' => $submittedBy,
  'Fuel Level' => $fuelLevel,
];

foreach ($requiredMap as $label => $value) {
  if ($value === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => $label . ' is required.']);
    exit();
  }
}

if (!preg_match('/^\d{10}$/', $phoneNumber)) {
  http_response_code(422);
  echo json_encode(['success' => false, 'message' => 'Phone number must be 10 digits.']);
  exit();
}

if (!is_numeric($kms) || (int)$kms < 0) {
  http_response_code(422);
  echo json_encode(['success' => false, 'message' => 'KMS must be a valid non-negative number.']);
  exit();
}

$allowedFuel = ['Empty', '1/4', '1/2', '3/4', 'Full'];
if (!in_array($fuelLevel, $allowedFuel, true)) {
  http_response_code(422);
  echo json_encode(['success' => false, 'message' => 'Invalid fuel level selected.']);
  exit();
}

if (!array_key_exists($bayCode, $bayMap)) {
  http_response_code(422);
  echo json_encode(['success' => false, 'message' => 'Invalid bay selected.']);
  exit();
}

$inventoryItems = is_array($inventoryItems) ? array_values(array_filter(array_map('trim', $inventoryItems))) : [];
$inventoryJson = json_encode($inventoryItems);

$jobCardNo = 'JC-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid((string)mt_rand(), true)), 0, 5));

try {
  $pdo = getDBConnection();

  $columnRows = $pdo->query("SHOW COLUMNS FROM job_cards LIKE 'bay_code'")->fetchAll(PDO::FETCH_ASSOC);
  if (empty($columnRows)) {
    $pdo->exec("ALTER TABLE job_cards ADD COLUMN bay_code VARCHAR(20) NOT NULL DEFAULT 'bay1' AFTER submitted_by");
  }

  $statusRows = $pdo->query("SHOW COLUMNS FROM job_cards LIKE 'job_status'")->fetchAll(PDO::FETCH_ASSOC);
  if (empty($statusRows)) {
    $pdo->exec("ALTER TABLE job_cards ADD COLUMN job_status ENUM('pending','in-progress','completed') NOT NULL DEFAULT 'pending' AFTER bay_code");
  }

  $mechanicRows = $pdo->query("SHOW COLUMNS FROM job_cards LIKE 'mechanic_name'")->fetchAll(PDO::FETCH_ASSOC);
  if (empty($mechanicRows)) {
    $pdo->exec("ALTER TABLE job_cards ADD COLUMN mechanic_name VARCHAR(150) NULL AFTER submitted_by");
  }

  $sql = "INSERT INTO job_cards (
    job_card_no,
    vehicle_number,
    vehicle_model,
    service_date,
    customer_name,
    phone_number,
    kms,
    customer_address,
    inventory_items,
    fuel_level,
    demanded_jobs,
    recommended_jobs,
    submitted_by,
    mechanic_name,
    bay_code,
    job_status
  ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

  $stmt = $pdo->prepare($sql);
  $stmt->execute([
    $jobCardNo,
    $vehicleNumber,
    $vehicleModel,
    $serviceDate,
    $customerName,
    $phoneNumber,
    (int)$kms,
    $customerAddress,
    $inventoryJson,
    $fuelLevel,
    $demandedJobs,
    $recommendedJobs,
    $submittedBy,
    $mechanicName,
    $bayCode,
    'pending',
  ]);

  $jobCardId = (int)$pdo->lastInsertId();

  $dataDir = __DIR__ . '/data';
  if (!is_dir($dataDir)) {
    mkdir($dataDir, 0775, true);
  }
  $jsonPath = $dataDir . '/jobcards.json';

  $existing = [];
  if (file_exists($jsonPath)) {
    $decoded = json_decode((string)file_get_contents($jsonPath), true);
    if (is_array($decoded)) {
      $existing = $decoded;
    }
  }

  $existing[] = [
    'id' => $jobCardId,
    'job_card_no' => $jobCardNo,
    'vehicle_number' => $vehicleNumber,
    'vehicle_model' => $vehicleModel,
    'service_date' => $serviceDate,
    'customer_name' => $customerName,
    'phone_number' => $phoneNumber,
    'kms' => (int)$kms,
    'customer_address' => $customerAddress,
    'inventory_items' => $inventoryItems,
    'fuel_level' => $fuelLevel,
    'demanded_jobs' => $demandedJobs,
    'recommended_jobs' => $recommendedJobs,
    'submitted_by' => $submittedBy,
    'mechanic_name' => $mechanicName,
    'bay_code' => $bayCode,
    'bay_label' => $bayMap[$bayCode],
    'job_status' => 'pending',
    'created_at' => date('Y-m-d H:i:s'),
  ];

  file_put_contents($jsonPath, json_encode($existing, JSON_PRETTY_PRINT));

  $customerDataDir = __DIR__ . '/../customer/data';
  if (!is_dir($customerDataDir)) {
    mkdir($customerDataDir, 0775, true);
  }
  $customerJsonPath = $customerDataDir . '/customers.json';

  $customerRows = [];
  if (file_exists($customerJsonPath)) {
    $decodedCustomers = json_decode((string)file_get_contents($customerJsonPath), true);
    if (is_array($decodedCustomers)) {
      $customerRows = $decodedCustomers;
    }
  }

  $matched = false;
  foreach ($customerRows as &$row) {
    $existingPhone = trim((string)($row['phone'] ?? ''));
    if ($existingPhone !== '' && $existingPhone === $phoneNumber) {
      $row['name'] = $customerName;
      $row['vehicle_no'] = $vehicleNumber;
      $row['model'] = $vehicleModel;
      $row['address'] = $customerAddress;
      $row['phone'] = $phoneNumber;
      $matched = true;
      break;
    }
  }
  unset($row);

  if (!$matched) {
    $customerRows[] = [
      'name' => $customerName,
      'vehicle_no' => $vehicleNumber,
      'model' => $vehicleModel,
      'address' => $customerAddress,
      'phone' => $phoneNumber,
    ];
  }

  file_put_contents($customerJsonPath, json_encode($customerRows, JSON_PRETTY_PRINT));

  echo json_encode([
    'success' => true,
    'message' => 'Job card created successfully. Job Card No: ' . $jobCardNo . ' • ' . $bayMap[$bayCode] . ' | Customer data saved.',
    'jobCardNo' => $jobCardNo,
    'viewUrl' => 'view.php?job_card_no=' . urlencode($jobCardNo),
    'submittedBy' => ($_SESSION['user_name'] ?? $_SESSION['user_email'] ?? ''),
  ]);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Failed to save job card. Please check database setup.']);
}
