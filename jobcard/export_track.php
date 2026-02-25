<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
  header('Location: ../login/index.php');
  exit();
}

require_once '../config/database.php';

try {
  $pdo = getDBConnection();
  
  // Get date filters if provided
  $startDate = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
  $endDate = isset($_GET['end_date']) ? trim($_GET['end_date']) : '';
  
  // Build query based on filters
  if ($startDate !== '' && $endDate !== '') {
    $stmt = $pdo->prepare('SELECT job_card_no, customer_name, vehicle_number, vehicle_model, service_date, fuel_level, submitted_by, mechanic_name, bay_code, job_status, created_at FROM job_cards WHERE service_date BETWEEN ? AND ? ORDER BY service_date DESC');
    $stmt->execute([$startDate, $endDate]);
  } elseif ($startDate !== '') {
    $stmt = $pdo->prepare('SELECT job_card_no, customer_name, vehicle_number, vehicle_model, service_date, fuel_level, submitted_by, mechanic_name, bay_code, job_status, created_at FROM job_cards WHERE service_date >= ? ORDER BY service_date DESC');
    $stmt->execute([$startDate]);
  } elseif ($endDate !== '') {
    $stmt = $pdo->prepare('SELECT job_card_no, customer_name, vehicle_number, vehicle_model, service_date, fuel_level, submitted_by, mechanic_name, bay_code, job_status, created_at FROM job_cards WHERE service_date <= ? ORDER BY service_date DESC');
    $stmt->execute([$endDate]);
  } else {
    $stmt = $pdo->query('SELECT job_card_no, customer_name, vehicle_number, vehicle_model, service_date, fuel_level, submitted_by, mechanic_name, bay_code, job_status, created_at FROM job_cards ORDER BY service_date DESC');
  }
  
  $jobCards = $stmt->fetchAll(PDO::FETCH_ASSOC);
  
  // Bay map for display
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
  
} catch (Exception $e) {
  die('Error loading job cards: ' . $e->getMessage());
}

// Set headers for Excel download
$filename = 'job_cards_export_' . date('Y-m-d_H-i-s') . '.xls';
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Start Excel HTML format
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <!--[if gte mso 9]>
  <xml>
    <x:ExcelWorkbook>
      <x:ExcelWorksheets>
        <x:ExcelWorksheet>
          <x:Name>Job Cards</x:Name>
          <x:WorksheetOptions>
            <x:DisplayGridlines/>
          </x:WorksheetOptions>
        </x:ExcelWorksheet>
      </x:ExcelWorksheets>
    </x:ExcelWorkbook>
  </xml>
  <![endif]-->
  <style>
    table {
      border-collapse: collapse;
      width: 100%;
    }
    th {
      background-color: #0a668b;
      color: white;
      font-weight: bold;
      padding: 10px;
      border: 1px solid #ddd;
      text-align: left;
    }
    td {
      padding: 8px;
      border: 1px solid #ddd;
      text-align: left;
    }
    tr:nth-child(even) {
      background-color: #f9f9f9;
    }
  </style>
</head>
<body>
  <table>
    <thead>
      <tr>
        <th>Job Card No</th>
        <th>Customer Name</th>
        <th>Vehicle Number</th>
        <th>Vehicle Model</th>
        <th>Bay</th>
        <th>Service Date</th>
        <th>Mechanic</th>
        <th>Status</th>
        <th>Fuel Level</th>
        <th>Submitted By</th>
        <th>Created At</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($jobCards as $card): ?>
      <tr>
        <td><?php echo htmlspecialchars($card['job_card_no']); ?></td>
        <td><?php echo htmlspecialchars($card['customer_name']); ?></td>
        <td><?php echo htmlspecialchars($card['vehicle_number']); ?></td>
        <td><?php echo htmlspecialchars($card['vehicle_model']); ?></td>
        <td><?php echo htmlspecialchars($bayMap[$card['bay_code']] ?? $card['bay_code']); ?></td>
        <td><?php echo htmlspecialchars($card['service_date']); ?></td>
        <td><?php echo htmlspecialchars($card['mechanic_name'] ?? ''); ?></td>
        <td><?php echo htmlspecialchars(ucwords(str_replace('-', ' ', $card['job_status']))); ?></td>
        <td><?php echo htmlspecialchars($card['fuel_level']); ?></td>
        <td><?php echo htmlspecialchars($card['submitted_by']); ?></td>
        <td><?php echo htmlspecialchars($card['created_at']); ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</body>
</html>
<?php
exit();
?>
