<?php
require_once __DIR__ . '/../includes/config.php';
require_admin();

$record_id = trim($_GET['id'] ?? '');
if (!$record_id) {
    die('<div class="alert alert-danger m-4">No record ID provided.</div>');
}

$record = db_fetch_one('
    SELECT r.*, res.name, res.address, res.contact, res.resident_id as res_id
    FROM records r
    JOIN residents res ON r.resident_id = res.resident_id
    WHERE r.record_id = ?
', [$record_id]);

if (!$record) {
    die('<div class="alert alert-danger m-4">Record not found.</div>');
}

$admin = current_admin();
$is_deleted = $record['is_deleted'] ?? 0;
$doc_number = strtoupper($record['record_id']);
$type_label = strtoupper($record['record_type']);
$print_date = date('F j, Y');
$print_time = date('h:i A');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Official Copy — <?= e($doc_number) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    * { font-family: 'Inter', sans-serif; margin: 0; padding: 0; box-sizing: border-box; }

    body {
      background: #f0f0f0;
      padding: 20px;
    }

    .print-controls {
      max-width: 760px; margin: 0 auto 16px;
      display: flex; gap: 10px;
    }

    .document-page {
      max-width: 760px; margin: 0 auto;
      background: #fff;
      padding: 48px 52px;
      box-shadow: 0 4px 24px rgba(0,0,0,.12);
      border-radius: 4px;
      position: relative;
    }

    /* Watermark for deleted records */
    .document-page.is-deleted::before {
      content: 'DELETED';
      position: absolute; top: 50%; left: 50%;
      transform: translate(-50%,-50%) rotate(-35deg);
      font-size: 6rem; font-weight: 900;
      color: rgba(220,53,69,.08);
      letter-spacing: .2em;
      pointer-events: none;
      z-index: 0;
    }

    .document-page > * { position: relative; z-index: 1; }

    /* Header */
    .doc-header {
      text-align: center;
      border-bottom: 3px solid #1A3A8F;
      padding-bottom: 20px;
      margin-bottom: 24px;
    }
    .doc-header .logo-row {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 20px;
      margin-bottom: 12px;
    }
    .doc-header img.doc-logo {
      width: 80px; height: 80px;
      border-radius: 50%;
      border: 3px solid #E8A800;
    }
    .doc-header .header-text { text-align: center; }
    .doc-header .republic-line { font-size: .75rem; color: #555; letter-spacing: .08em; text-transform: uppercase; }
    .doc-header .barangay-name { font-size: 1.4rem; font-weight: 800; color: #1A3A8F; letter-spacing: .03em; }
    .doc-header .city-line { font-size: .9rem; color: #444; font-weight: 500; }

    .doc-title {
      font-size: 1.15rem; font-weight: 800;
      letter-spacing: .1em; text-transform: uppercase;
      color: #1A3A8F; margin-top: 10px;
      display: inline-block;
      background: #FFF8E1;
      border: 2px solid #E8A800;
      padding: 6px 24px;
      border-radius: 4px;
    }

    /* Gold accent line */
    .gold-line {
      height: 3px;
      background: linear-gradient(90deg, #E8A800, #F5C100, #E8A800);
      border-radius: 2px;
      margin: 20px 0;
    }

    /* Document Info Box */
    .doc-info-box {
      display: grid; grid-template-columns: 1fr 1fr;
      gap: 10px; margin-bottom: 20px;
    }
    .doc-info-item label {
      font-size: .7rem; text-transform: uppercase;
      letter-spacing: .07em; color: #888; font-weight: 600; display: block;
    }
    .doc-info-item span {
      font-size: .9rem; font-weight: 700; color: #1A3A8F;
    }

    /* Section headers */
    .section-header {
      background: #1A3A8F; color: #fff;
      padding: 6px 14px; font-size: .78rem;
      text-transform: uppercase; letter-spacing: .1em;
      font-weight: 700; margin: 20px 0 12px;
      border-left: 5px solid #E8A800;
    }

    /* Detail rows */
    .detail-table { width: 100%; border-collapse: collapse; }
    .detail-table tr td {
      padding: 8px 10px; border: 1px solid #E0E0E0;
      font-size: .88rem; vertical-align: top;
    }
    .detail-table tr td:first-child {
      background: #F8FAFF; font-weight: 600;
      color: #555; width: 32%; font-size: .82rem;
    }

    /* Status badge */
    .status-box {
      display: inline-block; padding: 5px 18px;
      border-radius: 4px; font-weight: 700;
      font-size: .88rem; letter-spacing: .05em;
    }
    .status-Pending  { background: #FFF3CD; color: #856404; border: 1px solid #FFE69C; }
    .status-Approved { background: #CFE2FF; color: #084298; border: 1px solid #B6D4FE; }
    .status-Done     { background: #D1E7DD; color: #0F5132; border: 1px solid #A3CFBB; }
    .status-Rejected { background: #F8D7DA; color: #842029; border: 1px solid #F5C2C7; }

    /* Signature section */
    .signature-section {
      display: grid; grid-template-columns: 1fr 1fr;
      gap: 40px; margin-top: 36px;
    }
    .sig-block { text-align: center; }
    .sig-block .sig-line {
      border-bottom: 1px solid #333;
      margin-bottom: 6px;
      height: 40px;
    }
    .sig-block .sig-name { font-weight: 700; font-size: .88rem; }
    .sig-block .sig-title { font-size: .75rem; color: #666; }

    /* Footer */
    .doc-footer {
      margin-top: 28px; padding-top: 14px;
      border-top: 1px solid #DDD;
      display: flex; justify-content: space-between; align-items: center;
    }
    .doc-footer small { font-size: .7rem; color: #999; }
    .doc-footer .qr-placeholder {
      width: 50px; height: 50px;
      border: 1px dashed #CCC;
      display: flex; align-items: center; justify-content: center;
      font-size: .55rem; color: #CCC; text-align: center;
      border-radius: 4px;
    }

    /* Copy label */
    .copy-ribbon {
      position: absolute; top: 18px; right: -8px;
      background: #E8A800; color: #1A3A8F;
      font-weight: 800; font-size: .72rem;
      letter-spacing: .1em; text-transform: uppercase;
      padding: 4px 14px;
      clip-path: polygon(0 0, 100% 0, 100% 100%, 8px 100%);
    }

    /* Print controls */
    .btn-print-ctrl {
      padding: 8px 20px; border-radius: 8px;
      border: none; cursor: pointer;
      font-size: .88rem; font-weight: 600;
      display: inline-flex; align-items: center; gap: 6px;
    }
    .btn-blue { background: #1A3A8F; color: #fff; }
    .btn-blue:hover { background: #0F2460; }
    .btn-back { background: #6c757d; color: #fff; }
    .btn-back:hover { background: #5a6268; }

    @media print {
      body { background: #fff; padding: 0; }
      .print-controls { display: none !important; }
      .document-page { box-shadow: none; border-radius: 0; margin: 0; max-width: 100%; }
    }
  </style>
</head>
<body>

<div class="print-controls">
  <button class="btn-print-ctrl btn-blue" onclick="window.print()">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
      <rect x="6" y="14" width="12" height="8"/>
    </svg>
    Print / Save as PDF
  </button>
  <button class="btn-print-ctrl btn-back" onclick="history.back()">
    ← Back
  </button>
  <?php if ($is_deleted): ?>
  <span style="background:#dc3545;color:#fff;padding:8px 16px;border-radius:8px;font-size:.85rem;font-weight:600;">
    ⚠ This record is in TRASH
  </span>
  <?php endif; ?>
</div>

<div class="document-page <?= $is_deleted ? 'is-deleted' : '' ?>">

  <div class="copy-ribbon">Official Copy</div>

  <!-- Document Header -->
  <div class="doc-header">
    <div class="logo-row">
      <img src="/BarangayProject/Logo.jpg" alt="Barangay Logo" class="doc-logo">
      <div class="header-text">
        <div class="republic-line">Republic of the Philippines</div>
        <div class="republic-line">City of San Pablo, Laguna</div>
        <div class="barangay-name">Barangay San Rafael</div>
        <div class="city-line">Office of the Barangay Captain</div>
      </div>
    </div>
    <div class="doc-title">
      <?= $type_label === 'REQUEST' ? 'Barangay Service Request' : 'Barangay Complaint Record' ?>
    </div>
  </div>

  <div class="gold-line"></div>

  <!-- Document Info -->
  <div class="doc-info-box">
    <div class="doc-info-item">
      <label>Document / Reference No.</label>
      <span><?= e($doc_number) ?></span>
    </div>
    <div class="doc-info-item">
      <label>Record Type</label>
      <span><?= e($type_label) ?></span>
    </div>
    <div class="doc-info-item">
      <label>Date & Time Filed</label>
      <span><?= e(substr($record['date_submitted'],0,16)) ?></span>
    </div>
    <div class="doc-info-item">
      <label>Current Status</label>
      <span class="status-box status-<?= e($record['status']) ?>"><?= e($record['status']) ?></span>
    </div>
  </div>

  <!-- Resident Information -->
  <div class="section-header">I. Resident / Complainant Information</div>
  <table class="detail-table">
    <tr>
      <td>Full Name</td>
      <td><?= e($record['name']) ?></td>
    </tr>
    <tr>
      <td>Resident ID</td>
      <td><?= e($record['res_id'] ?? $record['resident_id']) ?></td>
    </tr>
    <tr>
      <td>Address / Purok</td>
      <td><?= e($record['address']) ?></td>
    </tr>
    <tr>
      <td>Contact Number</td>
      <td><?= e($record['contact']) ?></td>
    </tr>
  </table>

  <!-- Record Details -->
  <div class="section-header">II. Record / <?= $type_label === 'COMPLAINT' ? 'Complaint' : 'Request' ?> Details</div>
  <table class="detail-table">
    <tr>
      <td>Category / Concern</td>
      <td><?= e($record['category']) ?></td>
    </tr>
    <tr>
      <td>Description / Details</td>
      <td style="min-height:80px;white-space:pre-wrap;"><?= e($record['details'] ?? 'No additional details provided.') ?></td>
    </tr>
    <tr>
      <td>Date Submitted</td>
      <td><?= e($record['date_submitted']) ?></td>
    </tr>
    <tr>
      <td>Final Status</td>
      <td><span class="status-box status-<?= e($record['status']) ?>"><?= e($record['status']) ?></span></td>
    </tr>
  </table>

  <?php if ($is_deleted): ?>
  <!-- Deletion Info -->
  <div class="section-header" style="background:#dc3545;">III. Deletion Record</div>
  <table class="detail-table">
    <tr>
      <td>Deleted By</td>
      <td><?= e($record['deleted_by'] ?? '—') ?></td>
    </tr>
    <tr>
      <td>Deleted At</td>
      <td><?= e($record['deleted_at'] ?? '—') ?></td>
    </tr>
    <tr>
      <td>Reason</td>
      <td><?= e($record['delete_reason'] ?? '—') ?></td>
    </tr>
  </table>
  <?php endif; ?>

  <!-- Signature Section -->
  <div class="signature-section">
    <div class="sig-block">
      <div class="sig-line"></div>
      <div class="sig-name"><?= e($record['name']) ?></div>
      <div class="sig-title">Resident / Complainant Signature</div>
    </div>
    <div class="sig-block">
      <div class="sig-line"></div>
      <div class="sig-name">Barangay Captain / Authorized Official</div>
      <div class="sig-title">Signature over Printed Name</div>
    </div>
  </div>

  <!-- Footer -->
  <div class="doc-footer">
    <div>
      <small><strong>Printed by:</strong> <?= e($admin['full_name']) ?></small><br>
      <small><strong>Date Printed:</strong> <?= $print_date ?> at <?= $print_time ?></small><br>
      <small style="color:#bbb;">This is an official copy of record <?= e($doc_number) ?> from the Barangay Records Management System.</small>
    </div>
    <div class="qr-placeholder">QR<br>CODE<br>HERE</div>
  </div>

</div>

<script>
// Auto-trigger print if ?print=1
const params = new URLSearchParams(window.location.search);
if (params.get('print') === '1') window.print();
</script>
</body>
</html>
