<?php
$page_title = ($page_title ?? 'Page') . ' — ' . APP_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($page_title) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600&display=swap" rel="stylesheet">
  <style>
    :root {
      --navy:       #0B1F4B;
      --navy-mid:   #152D6E;
      --navy-light: #1E3A8A;
      --gold:       #D4A017;
      --gold-bright:#F0B429;
      --gold-pale:  #FEF9EC;
      --red:        #B91C1C;
      --green:      #15803D;
      --bg:         #EEF2F9;
      --surface:    #FFFFFF;
      --border:     #E2E8F0;
      --text:       #1E293B;
      --muted:      #64748B;
      --sidebar-w:  260px;
      --radius:     12px;
    }
    *, *::before, *::after { box-sizing: border-box; }
    body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; -webkit-font-smoothing: antialiased; }
    h1,h2,h3,h4,h5,h6 { font-family: 'Sora', sans-serif; }

    /* SIDEBAR */
    .sidebar {
      width: var(--sidebar-w); min-height: 100vh;
      background: var(--navy);
      position: fixed; top: 0; left: 0;
      display: flex; flex-direction: column;
      z-index: 1000; box-shadow: 4px 0 24px rgba(11,31,75,.2);
    }
    .sidebar-brand {
      padding: 22px 20px 18px; border-bottom: 1px solid rgba(212,160,23,.18);
      text-align: center;
    }
    .sidebar-brand .brand-logo {
      width: 66px; height: 66px; border-radius: 50%;
      border: 2px solid var(--gold); object-fit: cover;
      box-shadow: 0 0 18px rgba(212,160,23,.3); margin-bottom: 10px;
    }
    .sidebar-brand h6 { color: #fff; font-weight: 700; font-size: .76rem; letter-spacing: .09em; text-transform: uppercase; margin: 0 0 2px; }
    .sidebar-brand small { color: var(--gold-bright); font-size: .67rem; opacity: .8; }
    .sidebar-user {
      margin: 12px 14px; background: rgba(255,255,255,.06);
      border: 1px solid rgba(255,255,255,.08); border-radius: 10px;
      padding: 11px 13px; display: flex; align-items: center; gap: 10px;
    }
    .sidebar-user .avatar {
      width: 36px; height: 36px; flex-shrink: 0;
      background: linear-gradient(135deg, var(--gold), #C8890A);
      border-radius: 8px; display: flex; align-items: center; justify-content: center;
      font-weight: 700; color: var(--navy); font-size: .85rem; font-family: 'Sora', sans-serif;
    }
    .sidebar-user .user-info { min-width: 0; }
    .sidebar-user .user-info span { color: #fff; font-size: .81rem; font-weight: 600; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .sidebar-user .user-info small { color: rgba(255,255,255,.4); font-size: .67rem; }
    .sidebar-nav { flex: 1; padding: 8px 12px; display: flex; flex-direction: column; gap: 2px; }
    .sidebar-nav .nav-section { font-size: .6rem; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; color: rgba(255,255,255,.22); padding: 10px 10px 3px; }
    .sidebar-nav .nav-link {
      color: rgba(255,255,255,.58); padding: 9px 13px; border-radius: 8px;
      font-size: .83rem; font-weight: 500; display: flex; align-items: center; gap: 10px;
      transition: all .15s; position: relative;
    }
    .sidebar-nav .nav-link i { font-size: .95rem; width: 18px; text-align: center; flex-shrink: 0; }
    .sidebar-nav .nav-link:hover { background: rgba(255,255,255,.08); color: #fff; }
    .sidebar-nav .nav-link.active { background: rgba(212,160,23,.14); color: var(--gold-bright); font-weight: 600; }
    .sidebar-nav .nav-link.active::before { content: ''; position: absolute; left: 0; top: 20%; height: 60%; width: 3px; background: var(--gold); border-radius: 0 3px 3px 0; }
    .sidebar-footer { padding: 12px 14px; border-top: 1px solid rgba(255,255,255,.07); }
    .btn-logout { width: 100%; background: transparent; border: none; color: rgba(255,255,255,.45); padding: 9px 13px; border-radius: 8px; font-size: .83rem; display: flex; align-items: center; gap: 10px; transition: all .15s; cursor: pointer; text-align: left; }
    .btn-logout:hover { background: rgba(185,28,28,.18); color: #FCA5A5; }

    /* MAIN CONTENT */
    .main-content { margin-left: var(--sidebar-w); min-height: 100vh; }
    .topbar { background: var(--surface); border-bottom: 1px solid var(--border); padding: 14px 30px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 100; box-shadow: 0 1px 6px rgba(0,0,0,.04); }
    .topbar .page-title { font-family: 'Sora', sans-serif; font-weight: 700; font-size: 1rem; color: var(--navy); margin: 0; }
    .topbar .page-sub { color: var(--muted); font-size: .77rem; margin: 0; }
    .topbar-badge { background: var(--navy); color: #fff; font-size: .7rem; font-weight: 600; padding: 4px 12px; border-radius: 20px; font-family: 'Sora', sans-serif; }

    /* CARDS */
    .card { border: 1px solid var(--border); border-radius: var(--radius); box-shadow: 0 1px 4px rgba(0,0,0,.04); background: var(--surface); }
    .card-header { background: transparent; border-bottom: 1px solid var(--border); border-radius: var(--radius) var(--radius) 0 0 !important; padding: 15px 22px; font-family: 'Sora', sans-serif; font-weight: 700; font-size: .86rem; color: var(--navy); display: flex; align-items: center; justify-content: space-between; }

    /* STAT CARDS */
    .stat-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 20px 22px; transition: transform .15s, box-shadow .15s; position: relative; overflow: hidden; }
    .stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,.08); }
    .stat-card::after { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; background: var(--accent-color, var(--navy)); border-radius: var(--radius) var(--radius) 0 0; }
    .stat-card .stat-value { font-family: 'Sora', sans-serif; font-size: 2rem; font-weight: 800; line-height: 1; }
    .stat-card .stat-label { font-size: .77rem; color: var(--muted); font-weight: 500; margin-top: 4px; }
    .stat-card .stat-icon { font-size: 1.75rem; opacity: .1; }

    /* TABLES */
    .table { font-size: .865rem; }
    .table th { font-size: .68rem; text-transform: uppercase; letter-spacing: .07em; color: var(--muted); font-weight: 600; background: #F8FAFF; border-bottom: 2px solid var(--border); padding: 12px 16px; }
    .table td { padding: 13px 16px; vertical-align: middle; border-color: var(--border); }
    .table tbody tr:hover { background: #F5F8FF; }

    /* FORMS */
    .form-label { font-size: .78rem; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: .04em; margin-bottom: 6px; }
    .form-control, .form-select { border: 1.5px solid var(--border); border-radius: 8px; padding: 10px 14px; font-size: .9rem; transition: border-color .15s, box-shadow .15s; }
    .form-control:focus, .form-select:focus { border-color: var(--navy-light); box-shadow: 0 0 0 3px rgba(30,58,138,.1); outline: none; }

    /* BUTTONS */
    .btn { border-radius: 8px; font-weight: 600; font-size: .85rem; transition: all .15s; }
    .btn-primary { background: var(--navy); border-color: var(--navy); }
    .btn-primary:hover { background: var(--navy-mid); border-color: var(--navy-mid); }
    .btn-outline-primary { color: var(--navy); border-color: var(--navy); }
    .btn-outline-primary:hover { background: var(--navy); color: #fff; }
    .btn-gold { background: var(--gold); border-color: var(--gold); color: var(--navy); font-weight: 700; }
    .btn-gold:hover { background: #B8880F; border-color: #B8880F; color: #fff; }
    .btn-sm { font-size: .77rem; padding: 5px 12px; }

    /* BADGES & CHIPS */
    .badge { font-size: .69rem; font-weight: 600; padding: .34em .72em; border-radius: 6px; letter-spacing: .02em; }
    .record-id-chip { font-family: 'Courier New', monospace; background: var(--gold-pale); color: var(--navy); padding: 3px 10px; border-radius: 6px; font-size: .77rem; font-weight: 700; border: 1px solid rgba(212,160,23,.22); }

    /* MISC */
    .alert { border: none; border-radius: 10px; font-size: .87rem; }
    .modal-content { border: none; border-radius: 14px; box-shadow: 0 20px 60px rgba(0,0,0,.18); }
    .modal-header.brand-header { background: var(--navy); color: #fff; border-bottom: 3px solid var(--gold); border-radius: 14px 14px 0 0; }
    .modal-header.brand-header .btn-close { filter: invert(1); }
    .page-header { background: var(--surface); border-bottom: 1px solid var(--border); padding: 22px 30px 18px; }
    .page-header h4 { font-family: 'Sora', sans-serif; font-weight: 800; color: var(--navy); margin: 0; }
    .page-header p { color: var(--muted); font-size: .83rem; margin: 3px 0 0; }
    tr.row-deleted td { opacity: .42; }
    ::-webkit-scrollbar { width: 5px; height: 5px; }
    ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 4px; }
    @keyframes fadeUp { from { opacity:0; transform: translateY(10px); } to { opacity:1; transform: translateY(0); } }
    .fade-up { animation: fadeUp .3s ease both; }
    .fade-up-1 { animation-delay:.05s; } .fade-up-2 { animation-delay:.1s; } .fade-up-3 { animation-delay:.15s; } .fade-up-4 { animation-delay:.2s; }
    @media (max-width: 768px) { .sidebar { transform: translateX(-100%); transition: transform .25s; } .sidebar.show { transform: translateX(0); } .main-content { margin-left: 0; } }
  </style>
</head>
<body>
