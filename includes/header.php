<?php $page_title=($page_title??'Page').' — '.APP_NAME; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= e($page_title) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    :root{--navy:#0B1F4B;--navy-mid:#152D6E;--navy-light:#1E3A8A;--gold:#D4A017;--gold-bright:#F0B429;--gold-pale:#FEF9EC;--bg:#EEF2F9;--surface:#fff;--border:#E2E8F0;--text:#1E293B;--muted:#64748B;--sw:260px;--r:12px;}
    *,*::before,*::after{box-sizing:border-box;}
    body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;-webkit-font-smoothing:antialiased;}
    h1,h2,h3,h4,h5,h6{font-family:'Sora',sans-serif;}
    /* SIDEBAR */
    .sidebar{width:var(--sw);min-height:100vh;background:var(--navy);position:fixed;top:0;left:0;display:flex;flex-direction:column;z-index:1000;box-shadow:4px 0 24px rgba(11,31,75,.2);transition:transform .25s ease;}
    .sidebar-brand{padding:20px 20px 16px;border-bottom:1px solid rgba(212,160,23,.18);text-align:center;}
    .sidebar-brand .brand-logo{width:64px;height:64px;border-radius:50%;border:2px solid var(--gold);object-fit:cover;box-shadow:0 0 18px rgba(212,160,23,.3);margin-bottom:8px;}
    .sidebar-brand h6{color:#fff;font-weight:700;font-size:.74rem;letter-spacing:.09em;text-transform:uppercase;margin:0 0 2px;}
    .sidebar-brand small{color:var(--gold-bright);font-size:.65rem;opacity:.8;}
    .sidebar-user{margin:10px 14px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.08);border-radius:10px;padding:10px 12px;display:flex;align-items:center;gap:10px;}
    .sidebar-user .avatar{width:34px;height:34px;flex-shrink:0;background:linear-gradient(135deg,var(--gold),#C8890A);border-radius:8px;display:flex;align-items:center;justify-content:center;font-weight:700;color:var(--navy);font-size:.82rem;font-family:'Sora',sans-serif;}
    .sidebar-user .user-info{min-width:0;}
    .sidebar-user .user-info span{color:#fff;font-size:.8rem;font-weight:600;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
    .sidebar-user .user-info small{color:rgba(255,255,255,.38);font-size:.65rem;}
    .sidebar-nav{flex:1;padding:6px 12px;display:flex;flex-direction:column;gap:1px;overflow-y:auto;}
    .sidebar-nav .nav-section{font-size:.58rem;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:rgba(255,255,255,.2);padding:10px 10px 3px;}
    .sidebar-nav .nav-link{color:rgba(255,255,255,.55);padding:7px 11px;border-radius:7px;font-size:.8rem;font-weight:500;display:flex;align-items:center;gap:9px;transition:all .15s;position:relative;}
    .sidebar-nav .nav-link i{font-size:.92rem;width:16px;text-align:center;flex-shrink:0;}
    .sidebar-nav .nav-link:hover{background:rgba(255,255,255,.08);color:#fff;}
    .sidebar-nav .nav-link.active{background:rgba(212,160,23,.14);color:var(--gold-bright);font-weight:600;}
    .sidebar-nav .nav-link.active::before{content:'';position:absolute;left:0;top:20%;height:60%;width:3px;background:var(--gold);border-radius:0 3px 3px 0;}
    .sidebar-footer{padding:10px 14px;border-top:1px solid rgba(255,255,255,.07);}
    .btn-logout{width:100%;background:transparent;border:none;color:rgba(255,255,255,.42);padding:9px 13px;border-radius:8px;font-size:.82rem;display:flex;align-items:center;gap:10px;transition:all .15s;cursor:pointer;text-align:left;}
    .btn-logout:hover{background:rgba(185,28,28,.18);color:#FCA5A5;}
    /* HAMBURGER */
    .hamburger-btn{display:none;background:none;border:none;color:var(--navy);font-size:1.35rem;cursor:pointer;padding:4px;line-height:1;flex-shrink:0;}
    .sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:999;}
    /* MAIN */
    .main-content{margin-left:var(--sw);min-height:100vh;}
    .topbar{background:var(--surface);border-bottom:1px solid var(--border);padding:13px 28px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100;box-shadow:0 1px 6px rgba(0,0,0,.04);gap:12px;flex-wrap:wrap;}
    .topbar h5{font-family:'Sora',sans-serif;font-weight:700;font-size:.98rem;color:var(--navy);margin:0;}
    .topbar small{color:var(--muted);font-size:.75rem;display:block;}
    /* CARDS */
    .card{border:1px solid var(--border);border-radius:var(--r);box-shadow:0 1px 4px rgba(0,0,0,.04);background:var(--surface);}
    .card-header{background:transparent;border-bottom:1px solid var(--border);border-radius:var(--r) var(--r) 0 0!important;padding:14px 20px;font-family:'Sora',sans-serif;font-weight:700;font-size:.84rem;color:var(--navy);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;}
    .stat-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);padding:18px 20px;transition:transform .15s,box-shadow .15s;position:relative;overflow:hidden;}
    .stat-card:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(0,0,0,.08);}
    .stat-card::after{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:var(--accent-color,var(--navy));border-radius:var(--r) var(--r) 0 0;}
    .stat-card .stat-value{font-family:'Sora',sans-serif;font-size:1.9rem;font-weight:800;line-height:1;}
    .stat-card .stat-label{font-size:.75rem;color:var(--muted);font-weight:500;margin-top:4px;}
    /* TABLES */
    .table{font-size:.845rem;}
    .table th{font-size:.66rem;text-transform:uppercase;letter-spacing:.07em;color:var(--muted);font-weight:600;background:#F8FAFF;border-bottom:2px solid var(--border);padding:11px 14px;white-space:nowrap;}
    .table td{padding:11px 14px;vertical-align:middle;border-color:var(--border);}
    .table tbody tr:hover{background:#F5F8FF;}
    /* FORMS */
    .form-label{font-size:.76rem;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:5px;}
    .form-control,.form-select{border:1.5px solid var(--border);border-radius:8px;padding:9px 13px;font-size:.88rem;transition:border-color .15s,box-shadow .15s;}
    .form-control:focus,.form-select:focus{border-color:var(--navy-light);box-shadow:0 0 0 3px rgba(30,58,138,.1);outline:none;}
    .form-control.is-invalid,.form-select.is-invalid{border-color:#dc3545;}
    .invalid-feedback{font-size:.76rem;color:#dc3545;display:block;margin-top:3px;}
    /* BUTTONS */
    .btn{border-radius:8px;font-weight:600;font-size:.84rem;transition:all .15s;}
    .btn-primary{background:var(--navy);border-color:var(--navy);}
    .btn-primary:hover{background:var(--navy-mid);border-color:var(--navy-mid);}
    .btn-outline-primary{color:var(--navy);border-color:var(--navy);}
    .btn-outline-primary:hover{background:var(--navy);color:#fff;}
    .btn-gold{background:var(--gold);border-color:var(--gold);color:var(--navy);font-weight:700;}
    .btn-gold:hover{background:#B8880F;border-color:#B8880F;color:#fff;}
    .btn-sm{font-size:.76rem;padding:5px 11px;}
    /* BADGES */
    .badge{font-size:.67rem;font-weight:600;padding:.32em .7em;border-radius:6px;}
    .record-id-chip{font-family:'Courier New',monospace;background:var(--gold-pale);color:var(--navy);padding:3px 10px;border-radius:6px;font-size:.75rem;font-weight:700;border:1px solid rgba(212,160,23,.22);white-space:nowrap;}
    /* MISC */
    .alert{border:none;border-radius:10px;font-size:.86rem;}
    .modal-content{border:none;border-radius:14px;box-shadow:0 20px 60px rgba(0,0,0,.18);}
    .modal-header.brand-header{background:var(--navy);color:#fff;border-bottom:3px solid var(--gold);border-radius:14px 14px 0 0;}
    .modal-header.brand-header .btn-close{filter:invert(1);}
    .pagination .page-link{border-radius:6px!important;margin:0 2px;color:var(--navy);border-color:var(--border);font-size:.78rem;}
    .pagination .page-item.active .page-link{background:var(--navy);border-color:var(--navy);}
    ::-webkit-scrollbar{width:4px;height:4px;}
    ::-webkit-scrollbar-thumb{background:var(--border);border-radius:4px;}
    @keyframes fadeUp{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}
    .fade-up{animation:fadeUp .3s ease both;}
    .fade-up-1{animation-delay:.05s;}.fade-up-2{animation-delay:.1s;}.fade-up-3{animation-delay:.15s;}
    tr.row-deleted td{opacity:.42;}
    /* LOGOUT MODAL */
    .brgy-modal-overlay{display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.55);align-items:center;justify-content:center;backdrop-filter:blur(2px);}
    .brgy-modal-overlay.show{display:flex;animation:fadeUp .2s ease;}
    .brgy-modal-box{background:#fff;border-radius:16px;padding:30px 28px 24px;max-width:360px;width:92%;text-align:center;box-shadow:0 24px 60px rgba(0,0,0,.25);}
    .brgy-modal-logo{width:60px;height:60px;border-radius:50%;border:2px solid var(--gold);margin-bottom:12px;}
    .brgy-modal-title{font-family:'Sora',sans-serif;font-size:1.15rem;font-weight:800;color:var(--navy);margin:0 0 6px;}
    .brgy-modal-text{color:var(--muted);font-size:.86rem;margin-bottom:22px;line-height:1.5;}
    /* Cancel LEFT · Yes Log Out RIGHT */
    .brgy-modal-actions{display:flex;gap:10px;justify-content:center;flex-direction:row-reverse;}
    .brgy-btn-cancel{padding:9px 24px;border-radius:8px;border:1.5px solid var(--border);background:#F8FAFF;color:var(--text);cursor:pointer;font-size:.86rem;font-weight:600;font-family:inherit;transition:all .15s;}
    .brgy-btn-cancel:hover{background:var(--border);}
    .brgy-btn-confirm{padding:9px 24px;border-radius:8px;border:none;background:#dc3545;color:#fff;cursor:pointer;font-size:.86rem;font-weight:700;font-family:inherit;transition:all .15s;}
    .brgy-btn-confirm:hover{background:#b02a37;}
    /* RT dot */
    .rt-dot{display:inline-block;width:7px;height:7px;border-radius:50%;background:#22c55e;animation:rtpulse 2s infinite;margin-right:4px;vertical-align:middle;}
    @keyframes rtpulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.4;transform:scale(.8)}}
    /* ── RESPONSIVE ─────────────────────────────────────── */
    /* Tablet: shrink sidebar to icon-only */
    @media(max-width:1024px) and (min-width:769px){
      :root{--sw:200px;}
      .sidebar-nav .nav-link{font-size:.78rem;padding:8px 10px;gap:8px;}
      .sidebar-brand h6{font-size:.68rem;}
      .sidebar-user .user-info span{font-size:.75rem;}
      .topbar{padding:12px 18px;}
    }
    /* Mobile: hide sidebar completely, show hamburger */
    @media(max-width:768px){
      :root{--sw:260px;}
      /* Sidebar slides off-screen by default */
      .sidebar{transform:translateX(-100%);box-shadow:none;}
      .sidebar.mobile-open{transform:translateX(0);box-shadow:6px 0 32px rgba(0,0,0,.35);}
      /* Main content takes full width */
      .main-content{margin-left:0!important;}
      /* Hamburger always visible on mobile */
      .hamburger-btn{display:flex!important;align-items:center;justify-content:center;
        position:fixed;top:12px;left:12px;z-index:1100;
        width:38px;height:38px;border-radius:8px;
        background:var(--navy);color:#fff!important;
        box-shadow:0 2px 8px rgba(11,31,75,.3);}
      /* Overlay when sidebar open */
      .sidebar-overlay.show{display:block;}
      /* Topbar left-padding to clear hamburger button */
      .topbar{padding:11px 14px 11px 62px;}
      .topbar h5{font-size:.88rem;}
      .topbar small{font-size:.72rem;}
      /* Padding adjustments */
      .p-4{padding:.85rem!important;}
      /* Stat cards: 2 per row on mobile */
      .stat-card .stat-value{font-size:1.4rem;}
      .stat-card{padding:14px 16px;}
      /* Tables */
      .table th{font-size:.62rem;padding:8px 10px;}
      .table td{padding:8px 10px;font-size:.78rem;}
      /* Card headers stack on small screens */
      .card-header{flex-direction:column;align-items:flex-start!important;gap:8px;}
      /* Badges wrap */
      .d-flex.gap-1{flex-wrap:wrap;}
      /* Modal buttons full width on very small screens */
      .brgy-modal-actions{flex-direction:column-reverse;align-items:stretch;}
      .brgy-btn-cancel,.brgy-btn-confirm{width:100%;text-align:center;}
      /* Hide topbar badge on tiny screens */
      .topbar-badge{display:none;}
    }
    @media(max-width:480px){
      .topbar h5{font-size:.82rem;}
      .record-id-chip{font-size:.68rem;padding:2px 7px;}
      .btn-sm{font-size:.72rem;padding:4px 8px;}
      .pagination .page-link{padding:4px 8px;font-size:.72rem;}
    }
  </style>
</head>
<body>
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>
