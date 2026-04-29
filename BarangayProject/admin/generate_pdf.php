<?php
require_once __DIR__ . '/../includes/config.php';
require_admin();

$type = $_GET['type'] ?? 'full';
$rid  = trim($_GET['record_id'] ?? '');
$date = date('F d, Y  H:i');
$sys  = APP_NAME . ' — ' . BARANGAY;

function pdf_status_style(string $s): string {
    return match($s) {
        'Pending'  => 'color:#B45309;background:#FEF9C3;',
        'Approved' => 'color:#1D4ED8;background:#DBEAFE;',
        'Done'     => 'color:#166534;background:#DCFCE7;',
        'Rejected' => 'color:#991B1B;background:#FEE2E2;',
        default    => 'color:#444;',
    };
}

ob_start();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Barangay PDF Export</title>
<style>
  body { font-family: Arial, sans-serif; font-size: 12px; color: #222; margin: 32px; }
  .header { text-align: center; border-bottom: 3px solid #1B5E20; padding-bottom: 14px; margin-bottom: 20px; }
  .header h1 { font-size: 20px; color: #1B5E20; margin: 4px 0; }
  .header p  { color: #666; margin: 2px 0; font-size: 11px; }
  table { width: 100%; border-collapse: collapse; margin-top: 10px; }
  th { background: #1B5E20; color: #fff; padding: 9px 10px; text-align: left; font-size: 11px; }
  td { padding: 8px 10px; border-bottom: 1px solid #E0E0E0; vertical-align: top; }
  tr:nth-child(even) td { background: #F4F9F4; }
  .badge { padding: 3px 9px; border-radius: 4px; font-size: 11px; font-weight: bold; }
  .label-col { background: #F4F9F4; font-weight: bold; color: #555; width: 30%; }
  .footer { margin-top: 24px; text-align: center; color: #999; font-size: 10px; border-top: 1px solid #E0E0E0; padding-top: 10px; }
  @media print { .no-print { display: none; } }
</style>
</head>
<body>

<div class="no-print" style="margin-bottom:20px;">
  <button onclick="window.print()" style="background:#1B5E20;color:#fff;border:none;padding:8px 20px;border-radius:6px;cursor:pointer;margin-right:8px;">
    🖨 Print / Save as PDF
  </button>
  <button onclick="window.close()" style="background:#EEE;border:none;padding:8px 16px;border-radius:6px;cursor:pointer;">
    Close
  </button>
</div>

<?php if ($type === 'full'): ?>
<?php
$records = db_fetch_all('
    SELECT r.*, res.name, res.address, res.contact
    FROM records r
    JOIN residents res ON r.resident_id = res.resident_id
    ORDER BY r.date_submitted DESC
');
?>
<div class="header">
  <p><?= e($sys) ?></p>
  <h1>FULL RECORDS LIST</h1>
  <p>Total: <?= count($records) ?> records &nbsp;·&nbsp; Generated: <?= $date ?></p>
</div>
<table>
  <thead>
    <tr><th>#</th><th>Record ID</th><th>Type</th><th>Category</th><th>Resident</th><th>Status</th><th>Date Filed</th></tr>
  </thead>
  <tbody>
    <?php foreach ($records as $i => $r):
      $st_style = pdf_status_style($r['status']);
    ?>
    <tr>
      <td><?= $i + 1 ?></td>
      <td><b><?= e($r['record_id']) ?></b></td>
      <td><?= ucfirst($r['record_type']) ?></td>
      <td><?= e($r['category']) ?></td>
      <td><?= e($r['name']) ?><br><small style="color:#888"><?= e($r['address']) ?></small></td>
      <td><span class="badge" style="<?= $st_style ?>"><?= e($r['status']) ?></span></td>
      <td><?= substr($r['date_submitted'], 0, 16) ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php elseif ($type === 'dashboard'): ?>
<?php
$s = db_fetch_one('
    SELECT COUNT(*) as total,
    SUM(status="Pending") as pending, SUM(status="Approved") as approved,
    SUM(status="Done") as done, SUM(record_type="request") as requests,
    SUM(record_type="complaint") as complaints FROM records
') ?? [];
?>
<div class="header">
  <p><?= e($sys) ?></p>
  <h1>DASHBOARD SUMMARY REPORT</h1>
  <p>As of <?= $date ?></p>
</div>
<table>
  <thead><tr><th>Metric</th><th>Count</th></tr></thead>
  <tbody>
    <?php foreach ([
      ['Total Records',       $s['total']      ?? 0, ''],
      ['Pending',             $s['pending']    ?? 0, pdf_status_style('Pending')],
      ['Approved',            $s['approved']   ?? 0, pdf_status_style('Approved')],
      ['Done / Completed',    $s['done']       ?? 0, pdf_status_style('Done')],
      ['Document Requests',   $s['requests']   ?? 0, ''],
      ['Complaints Filed',    $s['complaints'] ?? 0, ''],
    ] as [$label, $val, $style]): ?>
    <tr>
      <td class="label-col"><?= $label ?></td>
      <td><span class="badge" style="font-size:14px;<?= $style ?>"><?= (int)$val ?></span></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php elseif ($type === 'individual' && $rid): ?>
<?php
$r = db_fetch_one('
    SELECT rec.*, res.name, res.address, res.contact
    FROM records rec
    JOIN residents res ON rec.resident_id = res.resident_id
    WHERE rec.record_id = ?
', [$rid]);

if (!$r) {
    echo "<div style='color:red;padding:20px;'>Record not found: " . e($rid) . "</div>";
    echo ob_get_clean();
    exit;
}

$notifs = db_fetch_all('SELECT * FROM notifications WHERE record_id = ? ORDER BY notif_date DESC', [$rid]);
?>
<div class="header">
  <p><?= e($sys) ?></p>
  <h1>INDIVIDUAL RECORD</h1>
  <p>Record ID: <b><?= e($r['record_id']) ?></b> &nbsp;·&nbsp; Generated: <?= $date ?></p>
</div>
<table>
  <tbody>
    <?php foreach ([
      ['Record ID',    $r['record_id']],
      ['Type',         ucfirst($r['record_type'])],
      ['Category',     $r['category']],
      ['Resident',     $r['name']],
      ['Address',      $r['address']],
      ['Contact',      $r['contact']],
      ['Details',      $r['details'] ?: '—'],
      ['Date Filed',   substr($r['date_submitted'], 0, 16)],
    ] as [$k, $v]): ?>
    <tr><td class="label-col"><?= $k ?></td><td><?= e($v) ?></td></tr>
    <?php endforeach; ?>
    <tr>
      <td class="label-col">Status</td>
      <td>
        <span class="badge" style="font-size:13px;<?= pdf_status_style($r['status']) ?>">
          <?= e($r['status']) ?>
        </span>
      </td>
    </tr>
  </tbody>
</table>

<?php if (!empty($notifs)): ?>
<h3 style="margin-top:24px;color:#1B5E20;font-size:13px;">Notification History</h3>
<table>
  <thead><tr><th>#</th><th>Message</th><th>Date</th></tr></thead>
  <tbody>
    <?php foreach ($notifs as $i => $n): ?>
    <tr>
      <td><?= $i + 1 ?></td>
      <td><?= e($n['message']) ?></td>
      <td><?= substr($n['notif_date'], 0, 16) ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>

<?php endif; ?>

<div class="footer">
  Generated by <?= e($sys) ?> &nbsp;·&nbsp; <?= $date ?> &nbsp;·&nbsp; Official Document
</div>
</body>
</html>
<?php
echo ob_get_clean();
