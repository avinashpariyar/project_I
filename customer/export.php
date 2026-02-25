<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
  header('Location: ../login/index.php');
  exit();
}

// Read customers data
$customersFile = __DIR__ . '/data/customers.json';
$customers = [];

if (file_exists($customersFile)) {
  $jsonContent = file_get_contents($customersFile);
  $customers = json_decode($jsonContent, true);
  if (!is_array($customers)) {
    $customers = [];
  }
}

// Set headers for Excel download
$filename = 'customers_export_' . date('Y-m-d_H-i-s') . '.xls';
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
          <x:Name>Customers</x:Name>
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
        <th>Customer Name</th>
        <th>Phone Number</th>
        <th>Vehicle Number</th>
        <th>Vehicle Model</th>
        <th>Address</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($customers as $customer): ?>
      <tr>
        <td><?php echo htmlspecialchars($customer['name'] ?? ''); ?></td>
        <td><?php echo htmlspecialchars($customer['phone'] ?? ''); ?></td>
        <td><?php echo htmlspecialchars($customer['vehicle_no'] ?? ''); ?></td>
        <td><?php echo htmlspecialchars($customer['model'] ?? ''); ?></td>
        <td><?php echo htmlspecialchars($customer['address'] ?? ''); ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</body>
</html>
<?php
exit();
?>
