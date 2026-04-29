<?php
// includes/header.php — shared <head> and Bootstrap imports
$page_title = ($page_title ?? 'Page') . ' — ' . APP_NAME;
$base = '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($page_title) ?></title>

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  <style>
    :root {
      --brand-blue:       #1A3A8F;
      --brand-blue-mid:   #1E4DB7;
      --brand-blue-dark:  #0F2460;
      --brand-gold:       #E8A800;
      --brand-gold-light: #F5C100;
      --brand-gold-pale:  #FFF8E1;
      --brand-red:        #C0392B;
      --bs-primary:       #1A3A8F;
      --sidebar-w:        240px;
    }
    * { font-family: 'Inter', sans-serif; }
    body { background: #EEF2FF; min-height: 100vh; }

    /* Sidebar */
    .sidebar {
      width: var(--sidebar-w); min-height: 100vh;
      background: linear-gradient(180deg, #0F2460 0%, #1A3A8F 60%, #1E4DB7 100%);
      position: fixed; top: 0; left: 0;
      display: flex; flex-direction: column;
      z-index: 1000; box-shadow: 4px 0 20px rgba(0,0,0,.22);
    }
    .sidebar-brand {
      padding: 20px 16px 16px;
      border-bottom: 1px solid rgba(232,168,0,.25);
      text-align: center; background: rgba(0,0,0,.15);
    }
    .sidebar-brand .brand-logo {
      width: 72px; height: 72px; border-radius: 50%;
      border: 3px solid var(--brand-gold); object-fit: cover;
      box-shadow: 0 0 16px rgba(232,168,0,.4); margin-bottom: 8px;
    }
    .sidebar-brand h6 {
      color: #fff; font-weight: 800;
      letter-spacing: .06em; font-size: .82rem;
      margin: 4px 0 2px; text-transform: uppercase;
    }
    .sidebar-brand small { color: var(--brand-gold-light); font-size: .7rem; font-weight: 500; }
    .sidebar-user {
      margin: 12px 14px;
      background: rgba(255,255,255,.1);
      border: 1px solid rgba(232,168,0,.2);
      border-radius: 10px; padding: 10px 14px;
      display: flex; align-items: center; gap: 10px;
    }
    .sidebar-user .avatar {
      width: 36px; height: 36px;
      background: linear-gradient(135deg, var(--brand-gold), #F5A623);
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-weight: 700; color: #1A3A8F; font-size: .9rem; flex-shrink: 0;
    }
    .sidebar-user .user-info small { color: rgba(255,255,255,.55); font-size: .68rem; }
    .sidebar-user .user-info span  { color: #fff; font-size: .82rem; font-weight: 600; display: block; }
    .sidebar-nav { flex: 1; padding: 8px 0; }
    .sidebar-nav .nav-link {
      color: rgba(255,255,255,.72); padding: 11px 22px;
      border-radius: 0; font-size: .88rem; font-weight: 500;
      display: flex; align-items: center; gap: 12px;
      transition: all .18s; border-left: 3px solid transparent;
    }
    .sidebar-nav .nav-link:hover,
    .sidebar-nav .nav-link.active {
      background: rgba(232,168,0,.15);
      color: var(--brand-gold-light);
      border-left-color: var(--brand-gold);
    }
    .sidebar-nav .nav-link i { font-size: 1.05rem; width: 20px; text-align: center; }
    .sidebar-footer { padding: 12px 14px; border-top: 1px solid rgba(232,168,0,.2); }
    .sidebar-footer .status-dot { color: #69F0AE; font-size: .7rem; }
    .btn-logout {
      width: 100%; text-align: left;
      background: transparent; border: none;
      color: rgba(255,255,255,.6); padding: 9px 22px;
      font-size: .85rem; display: flex; align-items: center; gap: 10px; transition: all .18s;
    }
    .btn-logout:hover { background: rgba(192,57,43,.3); color: #FFCDD2; }

    /* Main Content */
    .main-content { margin-left: var(--sidebar-w); min-height: 100vh; padding: 0; }
    .topbar {
      background: #fff;
      border-bottom: 2px solid var(--brand-gold);
      padding: 14px 28px;
      display: flex; align-items: center; justify-content: space-between;
      position: sticky; top: 0; z-index: 100;
      box-shadow: 0 2px 12px rgba(26,58,143,.1);
    }
    .topbar h5 { margin: 0; font-weight: 700; color: var(--brand-blue); font-size: 1.1rem; }
    .topbar small { color: #888; font-size: .8rem; }

    /* Cards */
    .card { border: none; border-radius: 14px; box-shadow: 0 2px 12px rgba(26,58,143,.08); }
    .card-header {
      background: #fff; border-bottom: 1px solid #F0F0F0;
      border-radius: 14px 14px 0 0 !important;
      padding: 16px 22px; font-weight: 700; color: var(--brand-blue);
    }
    .stat-card {
      border-radius: 14px; background: #fff;
      padding: 20px 22px; box-shadow: 0 2px 12px rgba(26,58,143,.08);
      border-left: 5px solid transparent; transition: transform .15s;
    }
    .stat-card:hover { transform: translateY(-2px); }
    .stat-card .stat-value { font-size: 2rem; font-weight: 800; }
    .stat-card .stat-label { font-size: .8rem; color: #888; font-weight: 500; }
    .stat-card .stat-icon  { font-size: 2rem; opacity: .18; }

    /* Tables */
    .table th { font-size: .78rem; text-transform: uppercase;
                letter-spacing: .05em; color: #888; font-weight: 600;
                background: #F8FAFF; border-bottom: 2px solid #E8EDF8; }
    .table td { font-size: .875rem; vertical-align: middle; }
    .table tbody tr:hover { background: #F5F8FF; }

    /* Buttons */
    .btn-primary { background: var(--brand-blue); border-color: var(--brand-blue); }
    .btn-primary:hover { background: var(--brand-blue-mid); border-color: var(--brand-blue-mid); }
    .btn-outline-primary { color: var(--brand-blue); border-color: var(--brand-blue); }
    .btn-outline-primary:hover { background: var(--brand-blue); color: #fff; }
    .btn-gold { background: var(--brand-gold); border-color: var(--brand-gold); color: #1A3A8F; font-weight: 700; }
    .btn-gold:hover { background: #D09700; border-color: #D09700; color: #fff; }

    /* Forms */
    .form-control:focus, .form-select:focus {
      border-color: var(--brand-gold);
      box-shadow: 0 0 0 .2rem rgba(232,168,0,.25);
    }
    .form-label { font-size: .85rem; font-weight: 600; color: #555; }
    .badge { font-size: .75rem; padding: .38em .7em; border-radius: 6px; }
    .alert { border-radius: 10px; border: none; }

    /* Login */
    .login-wrapper {
      min-height: 100vh;
      background: linear-gradient(135deg, #0F2460 0%, #1A3A8F 45%, #0F2460 100%);
      display: flex; align-items: center; justify-content: center;
      position: relative; overflow: hidden;
    }
    .login-wrapper::before {
      content: ''; position: absolute; top: -50%; left: -50%;
      width: 200%; height: 200%;
      background: radial-gradient(ellipse at center, rgba(232,168,0,.12) 0%, transparent 60%);
      pointer-events: none;
    }
    .login-card {
      background: #fff; border-radius: 20px;
      box-shadow: 0 24px 64px rgba(0,0,0,.3);
      overflow: hidden; width: 100%; max-width: 460px;
      position: relative; z-index: 1;
    }
    .login-header {
      background: linear-gradient(135deg, #0F2460, #1A3A8F);
      padding: 28px 36px 22px;
      text-align: center; color: #fff;
      border-bottom: 4px solid var(--brand-gold);
    }
    .login-header .login-logo {
      width: 90px; height: 90px; border-radius: 50%;
      border: 3px solid var(--brand-gold); object-fit: cover;
      box-shadow: 0 0 24px rgba(232,168,0,.5); margin-bottom: 12px;
    }
    .login-header h4 { font-weight: 800; margin: 6px 0 2px; letter-spacing: .03em; font-size: 1.1rem; }
    .login-header small { opacity: .75; font-size: .8rem; color: var(--brand-gold-light); }
    .login-body { padding: 28px 36px 32px; }

    /* Nav pills */
    .nav-pills .nav-link { color: #555; background: #F5F5F5; border-radius: 8px; font-size: .85rem; }
    .nav-pills .nav-link.active { background: var(--brand-blue); color: #fff; }

    /* Page header */
    .page-header { background: #fff; border-bottom: 1px solid #EEEEEE; padding: 20px 28px 16px; }
    .page-header h4 { font-weight: 800; color: var(--brand-blue); margin: 0; }
    .page-header p  { color: #888; font-size: .85rem; margin: 2px 0 0; }

    /* Record chip */
    .record-id-chip {
      font-family: 'Courier New', monospace;
      background: var(--brand-gold-pale);
      color: var(--brand-blue);
      padding: 3px 10px; border-radius: 6px;
      font-size: .82rem; font-weight: 700;
      border: 1px solid rgba(232,168,0,.3);
    }

    /* Deleted row */
    tr.row-deleted td { opacity: .5; }

    /* Modal headers */
    .modal-header.brand-header {
      background: linear-gradient(135deg, #0F2460, #1A3A8F);
      color: #fff; border-bottom: 3px solid var(--brand-gold);
    }
    .modal-header.brand-header .btn-close { filter: invert(1); }

    /* Responsive */
    @media (max-width: 768px) {
      .sidebar { transform: translateX(-100%); transition: transform .3s; }
      .sidebar.show { transform: translateX(0); }
      .main-content { margin-left: 0; }
    }
    ::-webkit-scrollbar { width: 6px; }
    ::-webkit-scrollbar-thumb { background: var(--brand-gold); opacity: .5; border-radius: 4px; }
  </style>
</head>
<body>
