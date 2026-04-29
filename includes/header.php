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
      --bs-primary: #1B5E20;
      --green-dark:  #1B5E20;
      --green-mid:   #2E7D32;
      --green-light: #4CAF50;
      --green-pale:  #E8F5E9;
      --sidebar-w:   240px;
    }

    * { font-family: 'Inter', sans-serif; }

    body { background: #F0F4F0; min-height: 100vh; }

    /* ── Sidebar ── */
    .sidebar {
      width: var(--sidebar-w);
      min-height: 100vh;
      background: linear-gradient(180deg, #1B5E20 0%, #2E7D32 100%);
      position: fixed; top: 0; left: 0;
      display: flex; flex-direction: column;
      z-index: 1000;
      box-shadow: 4px 0 20px rgba(0,0,0,.18);
    }
    .sidebar-brand {
      padding: 28px 20px 18px;
      border-bottom: 1px solid rgba(255,255,255,.1);
      text-align: center;
    }
    .sidebar-brand .brand-icon {
      font-size: 2.4rem; color: #fff;
    }
    .sidebar-brand h6 {
      color: #fff; font-weight: 800;
      letter-spacing: .08em; font-size: .85rem;
      margin: 6px 0 2px;
    }
    .sidebar-brand small { color: rgba(255,255,255,.55); font-size: .72rem; }

    .sidebar-user {
      margin: 12px 14px;
      background: rgba(255,255,255,.1);
      border-radius: 10px;
      padding: 10px 14px;
      display: flex; align-items: center; gap: 10px;
    }
    .sidebar-user .avatar {
      width: 36px; height: 36px;
      background: rgba(255,255,255,.25);
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-weight: 700; color: #fff; font-size: .9rem;
      flex-shrink: 0;
    }
    .sidebar-user .user-info small { color: rgba(255,255,255,.55); font-size: .68rem; }
    .sidebar-user .user-info span  { color: #fff; font-size: .82rem; font-weight: 600; display: block; }

    .sidebar-nav { flex: 1; padding: 8px 0; }
    .sidebar-nav .nav-link {
      color: rgba(255,255,255,.75);
      padding: 11px 22px;
      border-radius: 0;
      font-size: .88rem;
      font-weight: 500;
      display: flex; align-items: center; gap: 12px;
      transition: all .18s;
      border-left: 3px solid transparent;
    }
    .sidebar-nav .nav-link:hover,
    .sidebar-nav .nav-link.active {
      background: rgba(255,255,255,.12);
      color: #fff;
      border-left-color: #A5D6A7;
    }
    .sidebar-nav .nav-link i { font-size: 1.05rem; width: 20px; text-align: center; }

    .sidebar-footer {
      padding: 12px 14px;
      border-top: 1px solid rgba(255,255,255,.1);
    }
    .sidebar-footer .status-dot { color: #69F0AE; font-size: .7rem; }
    .btn-logout {
      width: 100%; text-align: left;
      background: transparent; border: none;
      color: rgba(255,255,255,.6);
      padding: 9px 22px;
      font-size: .85rem;
      display: flex; align-items: center; gap: 10px;
      transition: all .18s;
    }
    .btn-logout:hover { background: rgba(229,57,53,.25); color: #EF9A9A; }

    /* ── Main Content ── */
    .main-content {
      margin-left: var(--sidebar-w);
      min-height: 100vh;
      padding: 0;
    }

    /* ── Top Bar ── */
    .topbar {
      background: #fff;
      border-bottom: 1px solid #E0E0E0;
      padding: 14px 28px;
      display: flex; align-items: center; justify-content: space-between;
      position: sticky; top: 0; z-index: 100;
      box-shadow: 0 2px 8px rgba(0,0,0,.05);
    }
    .topbar h5 { margin: 0; font-weight: 700; color: #1B5E20; font-size: 1.1rem; }
    .topbar small { color: #888; font-size: .8rem; }

    /* ── Cards ── */
    .card {
      border: none;
      border-radius: 14px;
      box-shadow: 0 2px 12px rgba(0,0,0,.07);
    }
    .card-header {
      background: #fff;
      border-bottom: 1px solid #F0F0F0;
      border-radius: 14px 14px 0 0 !important;
      padding: 16px 22px;
      font-weight: 700;
      color: #1B5E20;
    }

    /* ── Stat cards ── */
    .stat-card {
      border-radius: 14px;
      background: #fff;
      padding: 20px 22px;
      box-shadow: 0 2px 12px rgba(0,0,0,.07);
      border-left: 5px solid transparent;
      transition: transform .15s;
    }
    .stat-card:hover { transform: translateY(-2px); }
    .stat-card .stat-value { font-size: 2rem; font-weight: 800; }
    .stat-card .stat-label { font-size: .8rem; color: #888; font-weight: 500; }
    .stat-card .stat-icon  { font-size: 2rem; opacity: .18; }

    /* ── Tables ── */
    .table th { font-size: .78rem; text-transform: uppercase;
                letter-spacing: .05em; color: #888; font-weight: 600;
                background: #FAFAFA; border-bottom: 2px solid #EEEEEE; }
    .table td { font-size: .875rem; vertical-align: middle; }
    .table tbody tr:hover { background: #F9FBF9; }

    /* ── Buttons ── */
    .btn-primary { background: #1B5E20; border-color: #1B5E20; }
    .btn-primary:hover { background: #2E7D32; border-color: #2E7D32; }
    .btn-outline-primary { color: #1B5E20; border-color: #1B5E20; }
    .btn-outline-primary:hover { background: #1B5E20; color: #fff; }

    /* ── Forms ── */
    .form-control:focus, .form-select:focus {
      border-color: #4CAF50;
      box-shadow: 0 0 0 .2rem rgba(76,175,80,.2);
    }
    .form-label { font-size: .85rem; font-weight: 600; color: #555; }

    /* ── Badges ── */
    .badge { font-size: .75rem; padding: .38em .7em; border-radius: 6px; }

    /* ── Alerts ── */
    .alert { border-radius: 10px; border: none; }

    /* ── Login page ── */
    .login-wrapper {
      min-height: 100vh;
      background: linear-gradient(135deg, #1B5E20 0%, #388E3C 50%, #1B5E20 100%);
      display: flex; align-items: center; justify-content: center;
    }
    .login-card {
      background: #fff;
      border-radius: 20px;
      box-shadow: 0 20px 60px rgba(0,0,0,.25);
      overflow: hidden;
      width: 100%; max-width: 460px;
    }
    .login-header {
      background: linear-gradient(135deg, #1B5E20, #2E7D32);
      padding: 32px 36px 24px;
      text-align: center; color: #fff;
    }
    .login-header h4 { font-weight: 800; margin: 8px 0 4px; letter-spacing: .04em; }
    .login-header small { opacity: .7; font-size: .82rem; }
    .login-body { padding: 28px 36px 32px; }

    /* ── Page header ── */
    .page-header {
      background: #fff;
      border-bottom: 1px solid #EEEEEE;
      padding: 20px 28px 16px;
    }
    .page-header h4 { font-weight: 800; color: #1B5E20; margin: 0; }
    .page-header p  { color: #888; font-size: .85rem; margin: 2px 0 0; }

    /* ── Record ID chip ── */
    .record-id-chip {
      font-family: 'Courier New', monospace;
      background: #E8F5E9;
      color: #1B5E20;
      padding: 3px 10px;
      border-radius: 6px;
      font-size: .82rem;
      font-weight: 700;
    }

    /* ── Responsive ── */
    @media (max-width: 768px) {
      .sidebar { transform: translateX(-100%); transition: transform .3s; }
      .sidebar.show { transform: translateX(0); }
      .main-content { margin-left: 0; }
    }

    /* ── Scrollbar ── */
    ::-webkit-scrollbar { width: 6px; }
    ::-webkit-scrollbar-thumb { background: #C8E6C9; border-radius: 4px; }
  </style>
</head>
<body>
