<?php
require_once __DIR__.'/../includes/config.php';
start_admin_session(); require_admin();

$record_id = trim($_GET['id'] ?? '');
if (!$record_id) die('<div class="alert alert-danger m-4">No record ID provided.</div>');

$record = db_fetch_one('SELECT r.*,res.name,res.address,res.contact,res.resident_id as res_id FROM records r JOIN residents res ON r.resident_id=res.resident_id WHERE r.record_id=?', [$record_id]);
if (!$record) die('<div class="alert alert-danger m-4">Record not found.</div>');

$admin       = current_admin();
$status      = $record['status'];
$category    = $record['category'];
$type        = $record['record_type'];
$printable   = in_array($status, ['Approved','Done']);
$doc_number  = strtoupper($record['record_id']);
$print_date  = date('F j, Y');
$print_time  = date('h:i A');
$control_no  = 'BRY-'.date('Y').'-'.strtoupper(substr($record_id,-6));

// Map category to official document title
$doc_titles = [
    'Barangay Clearance'          => 'BARANGAY CLEARANCE',
    'Certificate of Indigency'    => 'CERTIFICATE OF INDIGENCY',
    'Certificate of Residency'    => 'CERTIFICATE OF RESIDENCY',
    'Business Permit'             => 'BARANGAY BUSINESS CLEARANCE',
    'Business Clearance'          => 'BARANGAY BUSINESS CLEARANCE',
    'Business Permit/Clearance'   => 'BARANGAY BUSINESS CLEARANCE',
    'Community Tax Certificate (CEDULA)' => 'COMMUNITY TAX CERTIFICATE',
    'Barangay ID'                 => 'BARANGAY IDENTIFICATION CARD',
];
$doc_title = $doc_titles[$category] ?? 'BARANGAY SERVICE RECORD';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= e($doc_title) ?> — <?= e($doc_number) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=EB+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    *{box-sizing:border-box;margin:0;padding:0;}
    body{font-family:'Inter',sans-serif;background:#E8E8E8;padding:24px;}

    /* Screen controls */
    .controls{max-width:800px;margin:0 auto 16px;display:flex;gap:10px;align-items:center;flex-wrap:wrap;}
    .btn-ctrl{padding:9px 20px;border-radius:8px;border:none;cursor:pointer;font-size:.85rem;font-weight:600;display:inline-flex;align-items:center;gap:6px;}
    .btn-print{background:#0B1F4B;color:#fff;}
    .btn-print:hover{background:#152D6E;}
    .btn-back{background:#6c757d;color:#fff;}
    .btn-back:hover{background:#5a6268;}
    .locked-notice{background:#FEF3C7;border:1px solid #FCD34D;border-radius:8px;padding:10px 16px;font-size:.83rem;color:#92400E;display:flex;align-items:center;gap:8px;}
    .approved-notice{background:#D1FAE5;border:1px solid #6EE7B7;border-radius:8px;padding:10px 16px;font-size:.83rem;color:#065F46;display:flex;align-items:center;gap:8px;}

    /* Document page */
    .document-page{max-width:800px;margin:0 auto;background:#fff;padding:52px 60px;box-shadow:0 4px 24px rgba(0,0,0,.15);position:relative;min-height:1050px;}

    /* Watermark for non-printable */
    .document-page.draft::before{
      content:'DRAFT — NOT APPROVED';
      position:absolute;top:50%;left:50%;
      transform:translate(-50%,-50%) rotate(-35deg);
      font-size:4rem;font-weight:900;
      color:rgba(220,53,69,.06);
      letter-spacing:.15em;pointer-events:none;z-index:0;white-space:nowrap;
    }
    .document-page > *{position:relative;z-index:1;}

    /* Republic header */
    .rep-header{text-align:center;margin-bottom:20px;}
    .rep-header .rep-line{font-size:.72rem;color:#444;letter-spacing:.1em;text-transform:uppercase;margin-bottom:2px;}
    .logo-row{display:flex;align-items:center;justify-content:center;gap:24px;margin:12px 0;}
    .logo-row img{width:88px;height:88px;border-radius:50%;border:3px solid #D4A017;object-fit:cover;}
    .logo-row .brgy-info{text-align:center;}
    .logo-row .brgy-name{font-size:1.45rem;font-weight:800;color:#0B1F4B;letter-spacing:.02em;font-family:'EB Garamond',serif;}
    .logo-row .brgy-city{font-size:.88rem;color:#444;font-weight:500;}
    .logo-row .brgy-office{font-size:.78rem;color:#666;margin-top:2px;}

    /* Horizontal rule with gold */
    .hr-gold{border:none;border-top:4px solid #0B1F4B;margin:0;position:relative;}
    .hr-gold::after{content:'';display:block;border-top:2px solid #D4A017;margin-top:3px;}

    /* Document title box */
    .doc-title-box{text-align:center;margin:22px 0 18px;padding:14px 20px;background:#F8FAFF;border:2px solid #0B1F4B;border-radius:4px;}
    .doc-title-box .doc-title{font-family:'EB Garamond',serif;font-size:1.55rem;font-weight:700;color:#0B1F4B;letter-spacing:.15em;text-transform:uppercase;}
    .doc-title-box .doc-subtitle{font-size:.75rem;color:#666;margin-top:3px;letter-spacing:.05em;}

    /* Control number row */
    .control-row{display:flex;justify-content:space-between;align-items:center;font-size:.75rem;color:#666;margin-bottom:18px;padding:8px 12px;background:#F8FAFF;border:1px solid #E2E8F0;border-radius:4px;}
    .control-row strong{color:#0B1F4B;}

    /* Body text */
    .doc-body{font-family:'EB Garamond',serif;font-size:1.08rem;line-height:1.9;color:#1a1a1a;margin:20px 0;}
    .doc-body .salutation{margin-bottom:16px;}
    .doc-body .indent{text-indent:3em;}
    .doc-body .resident-name{font-weight:700;text-decoration:underline;font-size:1.12rem;}
    .doc-body .highlight{font-weight:700;color:#0B1F4B;}

    /* Details box */
    .details-box{border:1px solid #E2E8F0;border-radius:4px;margin:16px 0;overflow:hidden;}
    .details-box table{width:100%;border-collapse:collapse;}
    .details-box table td{padding:9px 14px;border-bottom:1px solid #F1F5F9;font-size:.88rem;vertical-align:top;}
    .details-box table td:first-child{background:#F8FAFF;font-weight:600;color:#444;width:35%;font-size:.82rem;text-transform:uppercase;letter-spacing:.04em;}
    .details-box table tr:last-child td{border-bottom:none;}

    /* Certification line */
    .cert-line{margin:18px 0;padding:14px 18px;background:#EFF6FF;border-left:4px solid #0B1F4B;border-radius:0 4px 4px 0;font-size:.9rem;color:#1E293B;}

    /* Validity */
    .validity-box{margin:16px 0;padding:10px 16px;background:#FEF9EC;border:1px solid rgba(212,160,23,.3);border-radius:4px;font-size:.82rem;color:#92400E;display:flex;align-items:center;gap:8px;}

    /* Signature section */
    .sig-section{margin-top:36px;display:grid;grid-template-columns:1fr 1fr;gap:48px;}
    .sig-block{text-align:center;}
    .sig-line{border-bottom:1.5px solid #333;height:48px;margin-bottom:6px;}
    .sig-name{font-weight:700;font-size:.9rem;color:#0B1F4B;}
    .sig-title{font-size:.75rem;color:#666;margin-top:2px;}
    .sig-stamp{width:80px;height:80px;border:2px dashed #CCC;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.6rem;color:#CCC;text-align:center;margin:8px auto 0;line-height:1.3;}

    /* OR Number box */
    .or-box{margin-top:24px;padding:10px 16px;border:1px dashed #CCC;border-radius:4px;display:flex;gap:32px;font-size:.8rem;color:#666;}
    .or-box .or-field{flex:1;}
    .or-box .or-label{font-weight:600;color:#444;display:block;margin-bottom:4px;font-size:.72rem;text-transform:uppercase;}
    .or-box .or-value{border-bottom:1px solid #CCC;padding-bottom:3px;min-width:100px;display:inline-block;}

    /* Document footer */
    .doc-footer{margin-top:28px;padding-top:12px;border-top:1px solid #DDD;display:flex;justify-content:space-between;align-items:flex-end;}
    .doc-footer small{font-size:.68rem;color:#999;line-height:1.6;}
    .not-valid-box{background:#FEF2F2;border:1px solid #FECACA;border-radius:4px;padding:6px 12px;font-size:.72rem;color:#991B1B;font-weight:600;}

    @media print{
      body{background:#fff;padding:0;}
      .controls{display:none!important;}
      .document-page{box-shadow:none;margin:0;max-width:100%;padding:40px 48px;min-height:auto;}
    }
  </style>
</head>
<body>

<!-- SCREEN CONTROLS -->
<div class="controls">
  <button class="btn-ctrl btn-back" onclick="history.back()">← Back</button>
  <?php if($printable): ?>
  <button class="btn-ctrl btn-print" onclick="window.print()">
    🖨️ Print / Save as PDF
  </button>
  <div class="approved-notice">✅ Document is <strong><?= e($status) ?></strong> — ready to print and release to resident.</div>
  <?php else: ?>
  <div class="locked-notice">🔒 Printing is locked. Status is <strong><?= e($status) ?></strong>. Document can only be printed once the Barangay Captain or Secretary sets status to <strong>Approved</strong>.</div>
  <?php endif; ?>
</div>

<!-- DOCUMENT PAGE -->
<div class="document-page <?= !$printable?'draft':'' ?>">

  <?php if($type==='complaint'): ?>
  <!-- ══════════════════════════════════════════════ -->
  <!-- COMPLAINT RECORD                               -->
  <!-- ══════════════════════════════════════════════ -->
  <div class="rep-header">
    <div class="rep-line">Republic of the Philippines</div>
    <div class="rep-line">Province of Laguna · City of San Pablo</div>
  </div>
  <div class="logo-row">
    <img src="/BarangayProject/Logo.jpg" alt="Logo">
    <div class="brgy-info">
      <div class="brgy-name">Barangay San Rafael</div>
      <div class="brgy-city">City of San Pablo, Laguna</div>
      <div class="brgy-office">Office of the Barangay Captain</div>
    </div>
  </div>
  <hr class="hr-gold">
  <div class="doc-title-box">
    <div class="doc-title">Barangay Complaint Record</div>
    <div class="doc-subtitle">Official Record of Filed Complaint — Lupong Tagapamayapa</div>
  </div>
  <div class="control-row">
    <span>Control No.: <strong><?= e($control_no) ?></strong></span>
    <span>Record ID: <strong><?= e($doc_number) ?></strong></span>
    <span>Date Filed: <strong><?= substr($record['date_submitted'],0,10) ?></strong></span>
    <span>Status: <strong><?= e($status) ?></strong></span>
  </div>
  <div class="details-box">
    <table>
      <tr><td>Complainant</td><td><?= e($record['name']) ?></td></tr>
      <tr><td>Address</td><td><?= e($record['address']) ?></td></tr>
      <tr><td>Contact No.</td><td><?= e($record['contact']) ?></td></tr>
      <tr><td>Resident ID</td><td><?= e($record['res_id']??$record['resident_id']) ?></td></tr>
      <tr><td>Nature of Complaint</td><td><?= e($category) ?></td></tr>
      <tr><td>Details / Description</td><td style="white-space:pre-wrap;line-height:1.6;"><?= e($record['details']??'—') ?></td></tr>
      <tr><td>Resolution Status</td><td><strong><?= e($status) ?></strong></td></tr>
    </table>
  </div>
  <div class="cert-line">
    This certifies that the above complaint was duly filed and received by the Barangay San Rafael. The matter shall be referred to the <strong>Lupong Tagapamayapa</strong> for mediation and resolution in accordance with the Katarungang Pambarangay Law (RA 7160).
  </div>

  <?php else:
    // ══════════════════════════════════════════════
    // DOCUMENT REQUEST — category-specific body text
    // ══════════════════════════════════════════════
    $resident_name = strtoupper($record['name']);
    $address       = $record['address'];
    $purpose       = $record['details'] ?? 'general purposes';

    // Body text per document type
    $bodies = [
      'Barangay Clearance' => "This is to certify that <span class='resident-name'>{$resident_name}</span>, of legal age, Filipino citizen, and a bonafide resident of <span class='highlight'>{$address}</span>, Barangay San Rafael, City of San Pablo, Laguna, is personally known to this Office and has no pending case, derogatory record, or adverse information filed with this Barangay.
      <br><br>This clearance is issued upon the request of the interested party for <span class='highlight'>{$purpose}</span>, and for whatever legal purpose it may serve.",

      'Certificate of Residency' => "This is to certify that <span class='resident-name'>{$resident_name}</span> is a bonafide resident of <span class='highlight'>{$address}</span>, Barangay San Rafael, City of San Pablo, Laguna, Philippines.
      <br><br>This certification is issued upon the request of the interested party for <span class='highlight'>{$purpose}</span>, and for whatever legal purpose it may serve.",

      'Certificate of Indigency' => "This is to certify that <span class='resident-name'>{$resident_name}</span>, a resident of <span class='highlight'>{$address}</span>, Barangay San Rafael, City of San Pablo, Laguna, belongs to the indigent sector of our community. The said person is known to be of low income and is in need of assistance.
      <br><br>This certification is issued upon the request of the interested party for <span class='highlight'>{$purpose}</span>, and for whatever legal purpose it may serve.",

      'Business Permit' => "This is to certify that the business establishment owned/operated by <span class='resident-name'>{$resident_name}</span>, located at <span class='highlight'>{$address}</span>, Barangay San Rafael, City of San Pablo, Laguna, has been duly inspected and found to be in compliance with the rules and regulations of this Barangay.
      <br><br>This clearance is issued as a requirement for the issuance of the Mayor's Business Permit for <span class='highlight'>{$purpose}</span>.",

      'Business Permit/Clearance' => "This is to certify that the business establishment owned/operated by <span class='resident-name'>{$resident_name}</span>, located at <span class='highlight'>{$address}</span>, Barangay San Rafael, City of San Pablo, Laguna, has been duly inspected and found to be in compliance with the rules and regulations of this Barangay.
      <br><br>This clearance is issued as a requirement for the issuance of the Mayor's Business Permit for <span class='highlight'>{$purpose}</span>.",

      'Barangay ID' => "This is to certify that <span class='resident-name'>{$resident_name}</span>, of legal age, Filipino citizen, is a bonafide resident of <span class='highlight'>{$address}</span>, Barangay San Rafael, City of San Pablo, Laguna.
      <br><br>This identification document is issued for the purpose of establishing the identity of the above-named resident within the jurisdiction of Barangay San Rafael.",

      'Community Tax Certificate (CEDULA)' => "This is to certify that <span class='resident-name'>{$resident_name}</span>, a resident of <span class='highlight'>{$address}</span>, Barangay San Rafael, City of San Pablo, Laguna, has paid the Community Tax for the year <span class='highlight'>".date('Y')."</span>.
      <br><br>This certification is issued upon request for <span class='highlight'>{$purpose}</span>, and for whatever legal purpose it may serve.",
    ];

    $body_text = $bodies[$category] ?? "This is to certify that <span class='resident-name'>{$resident_name}</span>, of legal age, Filipino citizen, and a bonafide resident of <span class='highlight'>{$address}</span>, Barangay San Rafael, City of San Pablo, Laguna, has requested and been granted the barangay service/document described herein.
    <br><br>This certification is issued upon the request of the interested party for <span class='highlight'>{$purpose}</span>, and for whatever legal purpose it may serve.";
  ?>
  <div class="rep-header">
    <div class="rep-line">Republic of the Philippines</div>
    <div class="rep-line">Province of Laguna · City of San Pablo</div>
  </div>
  <div class="logo-row">
    <img src="/BarangayProject/Logo.jpg" alt="Logo">
    <div class="brgy-info">
      <div class="brgy-name">Barangay San Rafael</div>
      <div class="brgy-city">City of San Pablo, Laguna</div>
      <div class="brgy-office">Office of the Barangay Captain</div>
    </div>
  </div>
  <hr class="hr-gold">
  <div class="doc-title-box">
    <div class="doc-title"><?= e($doc_title) ?></div>
    <div class="doc-subtitle">Barangay San Rafael · City of San Pablo, Laguna</div>
  </div>
  <div class="control-row">
    <span>Control No.: <strong><?= e($control_no) ?></strong></span>
    <span>Record ID: <strong><?= e($doc_number) ?></strong></span>
    <span>Date Issued: <strong><?= $print_date ?></strong></span>
  </div>
  <div class="doc-body">
    <p class="salutation">TO WHOM IT MAY CONCERN:</p>
    <p class="indent"><?= $body_text ?></p>
  </div>
  <div class="validity-box">
    ⚠️ <strong>Validity:</strong> This document is valid for <strong>30 days</strong> from date of issue. Issued on <?= $print_date ?>.
  </div>
  <?php endif; ?>

  <!-- OR / Fee Box -->
  <div class="or-box">
    <div class="or-field"><span class="or-label">O.R. Number</span><span class="or-value">&nbsp;</span></div>
    <div class="or-field"><span class="or-label">Amount Paid</span><span class="or-value">&nbsp;</span></div>
    <div class="or-field"><span class="or-label">Date of Payment</span><span class="or-value">&nbsp;</span></div>
    <div class="or-field"><span class="or-label">Received By</span><span class="or-value">&nbsp;</span></div>
  </div>

  <!-- Signatures -->
  <div class="sig-section">
    <div class="sig-block">
      <div class="sig-line"></div>
      <div class="sig-name"><?= e($record['name']) ?></div>
      <div class="sig-title">Signature of Requesting Party</div>
      <div class="sig-title" style="margin-top:2px;">Date: _______________</div>
    </div>
    <div class="sig-block">
      <div class="sig-line"></div>
      <div class="sig-name">BARANGAY CAPTAIN / SECRETARY</div>
      <div class="sig-title">Barangay San Rafael</div>
      <div class="sig-stamp">OFFICIAL<br>DRY<br>SEAL</div>
    </div>
  </div>

  <!-- Document Footer -->
  <div class="doc-footer">
    <div>
      <small><strong>Printed by:</strong> <?= e($admin['full_name']) ?> &nbsp;·&nbsp; <strong>Date:</strong> <?= $print_date ?> at <?= $print_time ?></small><br>
      <small>Record No.: <?= e($doc_number) ?> &nbsp;·&nbsp; Barangay San Rafael Records Management System</small>
    </div>
    <?php if(!$printable): ?>
    <div class="not-valid-box">⚠ NOT VALID — Status: <?= e($status) ?></div>
    <?php endif; ?>
  </div>

</div>

<script>
const params=new URLSearchParams(window.location.search);
if(params.get('print')==='1' && <?= $printable?'true':'false' ?>) window.print();
</script>
</body></html>
