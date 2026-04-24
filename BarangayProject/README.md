# Barangay System — PHP + Bootstrap 5

## Setup Instructions (XAMPP)

### 1. Copy Project
Place the `BarangayPHP` folder inside:
```
C:/xampp/htdocs/BarangayPHP/
```

### 2. Create the Database
1. Open XAMPP → Start **Apache** and **MySQL**
2. Go to `http://localhost/phpmyadmin`
3. Click **New** → name it `barangay_db` → Create
4. Click the `barangay_db` database → go to **SQL** tab
5. Copy and paste the contents of `database.sql` → Click **Go**

### 3. Configure Database (if needed)
Edit `includes/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');        // your MySQL password
define('DB_NAME', 'barangay_db');
```

### 4. Open the System
Go to: `http://localhost/BarangayPHP/`

---

## Default Admin Accounts
| Username | Password  | Role               |
|----------|-----------|--------------------|
| admin1   | admin123  | Barangay Captain   |
| admin2   | admin456  | Barangay Secretary |

---

## File Structure
```
BarangayPHP/
├── index.php               ← Login page (Admin + Resident + Register)
├── database.sql            ← Run this in phpMyAdmin first
├── .htaccess
├── includes/
│   ├── config.php          ← DB config + helper functions
│   ├── header.php          ← Shared HTML head + CSS
│   ├── footer.php          ← Shared Bootstrap JS
│   ├── admin_sidebar.php   ← Admin navigation sidebar
│   └── resident_sidebar.php← Resident navigation sidebar
├── admin/
│   ├── dashboard.php       ← Stats + recent records
│   ├── manage_records.php  ← Filter, update, delete records
│   ├── residents.php       ← Manage resident accounts
│   ├── notifications.php   ← Status update history
│   ├── export_pdf.php      ← PDF export UI
│   ├── generate_pdf.php    ← PDF generation (print/save)
│   └── logout.php
└── resident/
    ├── home.php            ← Dashboard with quick stats
    ├── submit_request.php  ← Request barangay documents
    ├── file_complaint.php  ← File a complaint
    ├── my_submissions.php  ← View all your records + copy ID
    ├── track_status.php    ← Track by Record ID
    ├── profile.php         ← Edit profile + change password
    └── logout.php
```

---

## Features
- **Admin:** Dashboard stats, manage & delete records, manage residents, notifications, PDF export (Full list, Summary, Individual)
- **Resident:** Submit requests (6 document types), file complaints, view all submissions, track by ID with progress steps, edit profile & change password
- **Both:** Show/hide password, Bootstrap 5 modern UI, responsive sidebar
