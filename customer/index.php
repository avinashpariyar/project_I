<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
  header('Location: ../login/index.php');
  exit();
}

$customers = [];
$flashMessage = '';
$flashType = '';
$dataDir = __DIR__ . '/data';
$jsonFile = $dataDir . '/customers.json';
$emptyStateMessage = 'No matching customers found.';

// Check for session-based flash message
if (isset($_SESSION['flash_message']) && isset($_SESSION['flash_type'])) {
  $flashMessage = $_SESSION['flash_message'];
  $flashType = $_SESSION['flash_type'];
  unset($_SESSION['flash_message']);
  unset($_SESSION['flash_type']);
}

function hasCreatedJobCards(string $customerDir): bool
{
  $candidates = [
    $customerDir . '/../jobcard/data/jobcards.json',
    $customerDir . '/../jobcard/jobcards.json',
    $customerDir . '/data/jobcards.json',
  ];

  foreach ($candidates as $path) {
    if (!file_exists($path)) {
      continue;
    }

    $content = file_get_contents($path);
    if ($content === false || trim($content) === '') {
      continue;
    }

    $decoded = json_decode($content, true);
    if (is_array($decoded) && count($decoded) > 0) {
      return true;
    }
  }

  return false;
}

function normalizeCustomerRow(array $row): ?array
{
  $name = trim((string)($row['name'] ?? ''));
  $phone = trim((string)($row['phone'] ?? ''));
  $address = trim((string)($row['address'] ?? ''));
  $vehicleNo = trim((string)($row['vehicle_no'] ?? ''));
  $model = trim((string)($row['model'] ?? ''));

  if ($name === '' && $phone === '' && $address === '' && $vehicleNo === '' && $model === '') {
    return null;
  }

  return [
    'name' => $name,
    'phone' => $phone,
    'address' => $address,
    'vehicle_no' => $vehicleNo,
    'model' => $model,
  ];
}

function parseCsvCustomers(string $filePath): array
{
  $rows = [];
  $handle = fopen($filePath, 'r');
  if ($handle === false) {
    return [];
  }

  $headers = fgetcsv($handle);
  if (!is_array($headers)) {
    fclose($handle);
    return [];
  }

  $normalizedHeaders = array_map(function ($value) {
    $header = strtolower(trim((string)$value));
    return preg_replace('/\s+/', ' ', $header);
  }, $headers);

  while (($data = fgetcsv($handle)) !== false) {
    $record = [];
    foreach ($normalizedHeaders as $index => $header) {
      $value = isset($data[$index]) ? trim((string)$data[$index]) : '';
      if (in_array($header, ['customer name', 'name'], true)) {
        $record['name'] = $value;
      }
      if (in_array($header, ['phone', 'phone no', 'phone no.'], true)) {
        $record['phone'] = $value;
      }
      if ($header === 'address') {
        $record['address'] = $value;
      }
      if (in_array($header, ['vehicle no', 'vehicle no.', 'vehicle number', 'vehicle_no'], true)) {
        $record['vehicle_no'] = $value;
      }
      if (in_array($header, ['vehicle model', 'vehicle model.', 'model'], true)) {
        $record['model'] = $value;
      }
    }

    if (empty($record)) {
      $record = [
        'name' => trim((string)($data[0] ?? '')),
        'phone' => trim((string)($data[1] ?? '')),
        'address' => trim((string)($data[2] ?? '')),
        'vehicle_no' => trim((string)($data[3] ?? '')),
        'model' => trim((string)($data[4] ?? '')),
      ];
    }

    $normalized = normalizeCustomerRow($record);
    if ($normalized !== null) {
      $rows[] = $normalized;
    }
  }

  fclose($handle);
  return $rows;
}

function columnLetterToIndex(string $letters): int
{
  $letters = strtoupper($letters);
  $index = 0;
  $length = strlen($letters);
  for ($i = 0; $i < $length; $i++) {
    $index = ($index * 26) + (ord($letters[$i]) - 64);
  }
  return $index - 1;
}

function parseXlsxCustomers(string $filePath): array
{
  if (!class_exists('ZipArchive')) {
    return [];
  }

  $zip = new ZipArchive();
  if ($zip->open($filePath) !== true) {
    return [];
  }

  $sharedStrings = [];
  $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
  if ($sharedStringsXml !== false) {
    $sharedDoc = simplexml_load_string($sharedStringsXml);
    if ($sharedDoc && isset($sharedDoc->si)) {
      foreach ($sharedDoc->si as $item) {
        $text = '';
        if (isset($item->t)) {
          $text = (string)$item->t;
        } elseif (isset($item->r)) {
          foreach ($item->r as $run) {
            $text .= (string)$run->t;
          }
        }
        $sharedStrings[] = trim($text);
      }
    }
  }

  $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
  if ($sheetXml === false) {
    $zip->close();
    return [];
  }

  $sheetDoc = simplexml_load_string($sheetXml);
  $zip->close();
  if (!$sheetDoc || !isset($sheetDoc->sheetData->row)) {
    return [];
  }

  $rows = [];
  $headers = [];
  $isHeaderRow = true;

  foreach ($sheetDoc->sheetData->row as $row) {
    $valuesByIndex = [];

    foreach ($row->c as $cell) {
      $cellRef = (string)$cell['r'];
      $columnLetters = preg_replace('/\d+/', '', $cellRef);
      $colIndex = columnLetterToIndex($columnLetters);

      $cellType = (string)$cell['t'];
      $value = '';

      if ($cellType === 's') {
        $sharedIndex = (int)$cell->v;
        $value = $sharedStrings[$sharedIndex] ?? '';
      } elseif ($cellType === 'inlineStr' && isset($cell->is->t)) {
        $value = (string)$cell->is->t;
      } elseif (isset($cell->v)) {
        $value = (string)$cell->v;
      }

      $valuesByIndex[$colIndex] = trim($value);
    }

    if (empty($valuesByIndex)) {
      continue;
    }

    ksort($valuesByIndex);
    $values = array_values($valuesByIndex);

    if ($isHeaderRow) {
      $headers = array_map(function ($value) {
        $header = strtolower(trim((string)$value));
        return preg_replace('/\s+/', ' ', $header);
      }, $values);
      $isHeaderRow = false;
      continue;
    }

    $record = [];
    foreach ($values as $index => $value) {
      $header = $headers[$index] ?? '';
      if (in_array($header, ['customer name', 'name'], true)) {
        $record['name'] = $value;
      }
      if (in_array($header, ['phone', 'phone no', 'phone no.'], true)) {
        $record['phone'] = $value;
      }
      if ($header === 'address') {
        $record['address'] = $value;
      }
      if (in_array($header, ['vehicle no', 'vehicle no.', 'vehicle number', 'vehicle_no'], true)) {
        $record['vehicle_no'] = $value;
      }
      if (in_array($header, ['vehicle model', 'vehicle model.', 'model'], true)) {
        $record['model'] = $value;
      }
    }

    if (empty($record)) {
      $record = [
        'name' => trim((string)($values[0] ?? '')),
        'phone' => trim((string)($values[1] ?? '')),
        'address' => trim((string)($values[2] ?? '')),
        'vehicle_no' => trim((string)($values[3] ?? '')),
        'model' => trim((string)($values[4] ?? '')),
      ];
    }

    $normalized = normalizeCustomerRow($record);
    if ($normalized !== null) {
      $rows[] = $normalized;
    }
  }

  return $rows;
}

function xmlEscape(string $value): string
{
  return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

function buildCustomersXlsx(string $filePath, array $customers): bool
{
  if (!class_exists('ZipArchive')) {
    return false;
  }

  $headers = ['Customer name', 'Phone no.', 'Address', 'Vehicle no.', 'Vehicle Model'];
  $allRows = [$headers];

  foreach ($customers as $customer) {
    $allRows[] = [
      (string)($customer['name'] ?? ''),
      (string)($customer['phone'] ?? ''),
      (string)($customer['address'] ?? ''),
      (string)($customer['vehicle_no'] ?? ''),
      (string)($customer['model'] ?? ''),
    ];
  }

  $sharedStringMap = [];
  $sharedStrings = [];
  $sheetRowsXml = [];

  foreach ($allRows as $rowIndex => $rowValues) {
    $excelRow = $rowIndex + 1;
    $cellsXml = [];

    foreach ($rowValues as $colIndex => $cellValue) {
      if (!array_key_exists($cellValue, $sharedStringMap)) {
        $sharedStringMap[$cellValue] = count($sharedStrings);
        $sharedStrings[] = $cellValue;
      }

      $sharedIndex = $sharedStringMap[$cellValue];
      $columnLetter = chr(65 + $colIndex);
      $cellRef = $columnLetter . $excelRow;
      $cellsXml[] = '<c r="' . $cellRef . '" t="s"><v>' . $sharedIndex . '</v></c>';
    }

    $sheetRowsXml[] = '<row r="' . $excelRow . '">' . implode('', $cellsXml) . '</row>';
  }

  $sharedStringsXmlItems = array_map(function ($value) {
    return '<si><t>' . xmlEscape($value) . '</t></si>';
  }, $sharedStrings);

  $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
    . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
    . '<Default Extension="xml" ContentType="application/xml"/>'
    . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
    . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
    . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
    . '<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>'
    . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
    . '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
    . '</Types>';

  $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
    . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
    . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
    . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
    . '</Relationships>';

  $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
    . '<sheets><sheet name="Customers" sheetId="1" r:id="rId1"/></sheets>'
    . '</workbook>';

  $workbookRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
    . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
    . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
    . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>'
    . '</Relationships>';

  $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
    . '<fonts count="1"><font><sz val="11"/><name val="Calibri"/></font></fonts>'
    . '<fills count="1"><fill><patternFill patternType="none"/></fill></fills>'
    . '<borders count="1"><border/></borders>'
    . '<cellStyleXfs count="1"><xf/></cellStyleXfs>'
    . '<cellXfs count="1"><xf xfId="0"/></cellXfs>'
    . '</styleSheet>';

  $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
    . '<sheetData>' . implode('', $sheetRowsXml) . '</sheetData>'
    . '</worksheet>';

  $shared = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="' . count($sharedStrings) . '" uniqueCount="' . count($sharedStrings) . '">'
    . implode('', $sharedStringsXmlItems)
    . '</sst>';

  $coreProps = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" '
    . 'xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" '
    . 'xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
    . '<dc:creator>Vehicle Job Card System</dc:creator>'
    . '<cp:lastModifiedBy>Vehicle Job Card System</cp:lastModifiedBy>'
    . '</cp:coreProperties>';

  $appProps = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" '
    . 'xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
    . '<Application>Vehicle Job Card System</Application>'
    . '</Properties>';

  $zip = new ZipArchive();
  if ($zip->open($filePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    return false;
  }

  $zip->addFromString('[Content_Types].xml', $contentTypes);
  $zip->addFromString('_rels/.rels', $rels);
  $zip->addFromString('xl/workbook.xml', $workbook);
  $zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRels);
  $zip->addFromString('xl/styles.xml', $styles);
  $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);
  $zip->addFromString('xl/sharedStrings.xml', $shared);
  $zip->addFromString('docProps/core.xml', $coreProps);
  $zip->addFromString('docProps/app.xml', $appProps);

  $zip->close();
  return file_exists($filePath);
}

if (!is_dir($dataDir)) {
  mkdir($dataDir, 0775, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['import_file'])) {
  if (!isset($_FILES['import_file']['error']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['flash_message'] = 'Import failed. Please choose a valid file.';
    $_SESSION['flash_type'] = 'error';
  } else {
    $originalName = (string)($_FILES['import_file']['name'] ?? '');
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    if (!in_array($extension, ['xlsx', 'csv'], true)) {
      $_SESSION['flash_message'] = 'Only .xlsx or .csv files are supported.';
      $_SESSION['flash_type'] = 'error';
    } else {
      $targetPath = $dataDir . '/customers_import.' . $extension;
      if (move_uploaded_file($_FILES['import_file']['tmp_name'], $targetPath)) {
        $parsed = $extension === 'csv'
          ? parseCsvCustomers($targetPath)
          : parseXlsxCustomers($targetPath);

        if (!empty($parsed)) {
          file_put_contents($jsonFile, json_encode($parsed, JSON_PRETTY_PRINT));
          $_SESSION['flash_message'] = count($parsed) . ' customer records imported successfully.';
          $_SESSION['flash_type'] = 'success';
        } else {
          $_SESSION['flash_message'] = 'File uploaded, but no valid rows were found to import.';
          $_SESSION['flash_type'] = 'error';
        }
      } else {
        $_SESSION['flash_message'] = 'Could not save the uploaded Excel file on server.';
        $_SESSION['flash_type'] = 'error';
      }
    }
  }
  
  header('Location: index.php');
  exit();
}

if (empty($flashMessage) && file_exists($jsonFile)) {
  $stored = json_decode((string)file_get_contents($jsonFile), true);
  if (is_array($stored) && !empty($stored)) {
    $customers = $stored;
  }
}

$jobCardCreated = hasCreatedJobCards(__DIR__);
if (!$jobCardCreated) {
  $customers = [];
  $emptyStateMessage = 'Data will appear here after at least one job card is created.';
}

if (isset($_GET['action']) && $_GET['action'] === 'export') {
  $timestamp = date('Ymd_His');
  $format = isset($_GET['format']) && $_GET['format'] === 'xlsx' ? 'xlsx' : 'csv';
  
  if ($format === 'xlsx' && class_exists('ZipArchive')) {
    $downloadName = 'customers_export_' . $timestamp . '.xlsx';
    $exportPath = $dataDir . '/' . $downloadName;

    if (buildCustomersXlsx($exportPath, $customers)) {
      header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
      header('Content-Disposition: attachment; filename="' . $downloadName . '"');
      header('Content-Length: ' . filesize($exportPath));
      header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
      header('Pragma: no-cache');
      readfile($exportPath);
      exit();
    }
  }
  
  // CSV export (fallback and default)
  $downloadName = 'customers_export_' . $timestamp . '.csv';
  $exportPath = $dataDir . '/' . $downloadName;
  
  $fp = fopen($exportPath, 'w');
  if ($fp !== false) {
    // UTF-8 BOM for Excel compatibility
    fprintf($fp, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Header row
    fputcsv($fp, ['Customer name', 'Phone no.', 'Address', 'Vehicle no.', 'Vehicle Model']);
    
    // Data rows
    foreach ($customers as $customer) {
      fputcsv($fp, [
        (string)($customer['name'] ?? ''),
        (string)($customer['phone'] ?? ''),
        (string)($customer['address'] ?? ''),
        (string)($customer['vehicle_no'] ?? ''),
        (string)($customer['model'] ?? ''),
      ]);
    }
    
    fclose($fp);
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $downloadName . '"');
    header('Content-Length: ' . filesize($exportPath));
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    readfile($exportPath);
    exit();
  } else {
    $_SESSION['flash_message'] = 'Export failed. Could not create export file.';
    $_SESSION['flash_type'] = 'error';
    header('Location: index.php');
    exit();
  }
}

$models = array_values(array_unique(array_filter(array_map(function ($customer) {
  return trim((string)($customer['model'] ?? ''));
}, $customers))));
sort($models);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Customers - Vehicle Job Card System</title>
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
          <a href="../jobcard/index.php" class="nav-item">
            <span class="nav-icon">📄</span>
            <span class="nav-label">Job cards</span>
            <span class="nav-caret">▾</span>
          </a>
          <div class="nav-submenu">
            <a href="../jobcard/create.php" class="nav-subitem">Create Job Card</a>
            <a href="../jobcard/track.php" class="nav-subitem">Track Repair</a>
          </div>
        </div>

        <a href="index.php" class="nav-item active">
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

      <?php if ($flashMessage !== ''): ?>
        <div class="flash-message flash-<?php echo htmlspecialchars($flashType); ?>">
          <?php echo htmlspecialchars($flashMessage); ?>
        </div>
      <?php endif; ?>

      <section class="toolbar-row">
        <div class="control-btn control-left">
          <span class="control-label">Show:</span>
          <select id="show-filter" class="control-select" aria-label="Show filter">
            <option value="all">All Orders</option>
            <option value="with-vehicle">With Vehicle No</option>
            <option value="without-vehicle">Without Vehicle No</option>
          </select>
        </div>

        <div class="toolbar-actions">
          <a class="control-btn export-btn" href="index.php?action=export">
            <span class="control-icon">⭳</span>
            <span>Export</span>
          </a>

          <form class="import-form" action="index.php" method="POST" enctype="multipart/form-data">
            <input id="import-file" name="import_file" type="file" accept=".xlsx,.csv" hidden />
            <button class="control-btn import-btn" id="import-trigger" type="button">
              <span class="control-icon">⇪</span>
              <span>Import</span>
              <span class="control-caret">▾</span>
            </button>
          </form>
        </div>
      </section>

      <section class="customer-card">
        <div class="customer-toolbar">
          <label class="search-box" for="customer-search">
            <span class="search-icon">🔍</span>
            <input id="customer-search" type="text" placeholder="Search by name, vehicle no, or others..." />
          </label>

          <button class="filter-btn" type="button" id="toggle-filters">
            <span>⚙</span>
            <span>Filters</span>
          </button>
        </div>

        <div class="filters-panel" id="filters-panel" hidden>
          <label for="model-filter">Vehicle Model</label>
          <select id="model-filter">
            <option value="">All Models</option>
            <?php foreach ($models as $model): ?>
              <option value="<?php echo htmlspecialchars($model); ?>"><?php echo htmlspecialchars($model); ?></option>
            <?php endforeach; ?>
          </select>
          <button type="button" id="clear-filters" class="clear-filter-btn">Clear</button>
        </div>

        <div class="table-wrap">
          <table class="customer-table">
            <thead>
              <tr>
                <th class="check-col"><input type="checkbox" aria-label="Select all" /></th>
                <th>Customer name <span class="sort">⇵</span></th>
                <th>Phone no. <span class="sort">⇵</span></th>
                <th>Address <span class="sort">⇵</span></th>
                <th>Vehicle no. <span class="sort">⇵</span></th>
                <th>Vehicle Model. <span class="sort">⇵</span></th>
                <th class="menu-col"></th>
              </tr>
            </thead>
            <tbody id="customer-table-body">
              <?php foreach ($customers as $customer): ?>
                <tr
                  data-name="<?php echo htmlspecialchars(strtolower((string)($customer['name'] ?? ''))); ?>"
                  data-phone="<?php echo htmlspecialchars(strtolower((string)($customer['phone'] ?? ''))); ?>"
                  data-address="<?php echo htmlspecialchars(strtolower((string)($customer['address'] ?? ''))); ?>"
                  data-vehicle-no="<?php echo htmlspecialchars(strtolower((string)($customer['vehicle_no'] ?? ''))); ?>"
                  data-model="<?php echo htmlspecialchars(strtolower((string)($customer['model'] ?? ''))); ?>"
                >
                  <td class="check-col"><input type="checkbox" aria-label="Select row" /></td>
                  <td><?php echo htmlspecialchars($customer['name']); ?></td>
                  <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                  <td><?php echo htmlspecialchars($customer['address']); ?></td>
                  <td><?php echo htmlspecialchars($customer['vehicle_no']); ?></td>
                  <td><?php echo htmlspecialchars($customer['model']); ?></td>
                  <td class="menu-col">⋯</td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <p class="empty-state" id="empty-state"<?php echo empty($customers) ? '' : ' hidden'; ?>>
          <?php echo htmlspecialchars($emptyStateMessage); ?>
        </p>

        <div class="card-footer">
          <div class="result-count">
            Show result:
            <select id="page-size" class="count-chip" aria-label="Rows per page">
              <option value="5">5</option>
              <option value="10" selected>10</option>
              <option value="20">20</option>
              <option value="50">50</option>
            </select>
            <span id="result-summary">0 of 0</span>
          </div>
          <div class="pagination" id="pagination"></div>
          <div class="page-nav">
            <button type="button" class="page-btn" id="prev-page">‹</button>
            <button type="button" class="page-btn" id="next-page">›</button>
          </div>
        </div>
      </section>
    </main>
  </div>

  <script src="script.js"></script>
</body>
</html>
