# Workido — Skilled-Worker Management Platform

Workido is a full-stack web application designed for contractors, site engineers, and trade business managers to organize, dispatch, and track skilled craft workers across four foundational trades:

- **Carpenters**
- **Electricians**
- **Plumbers**
- **Masons**

The platform provides complete worker lifecycle management: profile creation, work allocations, dynamic wage and payroll calculations, and verified job completion performance scorecards.

---

## Technology Stack

- **Frontend**: HTML5, CSS3, Vanilla JavaScript, Bootstrap 5, Bootstrap Icons
- **Backend**: Core PHP (PHP 8.x, Object-Oriented PDO Architecture)
- **Database**: MySQL 5.7+ / MariaDB 10.4+
- **Local Environment**: XAMPP (Apache + MySQL + phpMyAdmin)
- **Security**: Prepared SQL statements (PDO), `password_hash()` / `password_verify()`, CSRF tokens, Session hijacking protection, and full XSS output escaping.

---

## Directory Structure

```text
c:\xampp\htdocs\Workidoo/ (or workido/)
│
├── index.php                      # Public landing page with live statistics and contact form
├── about.php                      # Detailed company mission, trade pillars & coverage
├── contact.php                    # Direct support and message inquiry form
├── login.php                      # Secure administrator and staff sign-in
├── register.php                   # Account registration with role selection
├── logout.php                     # Secure session termination
├── install.php                    # One-Click Web Database Setup & Installer
│
├── config/
│   ├── database.php               # PDO database connection & host configuration
│   └── functions.php              # Global helper functions, CSRF, flash, formatting & math
│
├── includes/
│   ├── header.php                 # Public site header and navigation
│   ├── footer.php                 # Public site footer
│   ├── auth_check.php             # Role-based route guard for admin sections
│   ├── admin_header.php           # Admin dashboard topbar and user menu
│   ├── admin_sidebar.php          # Admin collapsible navigation menu
│   └── admin_footer.php           # Admin footer and common scripts
│
├── admin/
│   ├── dashboard.php              # Live executive dashboard with dynamic database metrics
│   ├── workers.php                # Worker directory with search & multi-filter
│   ├── add_worker.php             # Worker registration form (trades, wages, skills)
│   ├── edit_worker.php            # Edit worker profile and availability status
│   ├── view_worker.php            # Detailed worker profile, job history, wages & reviews
│   ├── delete_worker.php          # Secure worker deletion handler
│   ├── jobs.php                   # Work orders directory with status filters
│   ├── add_job.php                # Post new work order and allocate workers
│   ├── edit_job.php               # Edit job order specifications and update milestones
│   ├── view_job.php               # Full work order details, assigned worker & wage logs
│   ├── delete_job.php             # Secure job deletion handler
│   ├── assignments.php            # Work allocation center (dispatch tradesmen)
│   ├── wages.php                  # Wage ledger, financial summary cards & settlement status
│   ├── calculate_wage.php         # Wage calculation engine (Daily & Fixed) with live preview
│   ├── view_wage.php              # Official printable wage payslip voucher
│   ├── performance.php            # Worker performance tracking & zero-division safe scorecards
│   ├── add_performance.php        # Record quality ratings and supervisor remarks
│   └── reports.php                # Master reports & One-Click CSV Data Exports
│
├── assets/
│   ├── css/
│   │   ├── style.css              # Custom styling for public site and branding
│   │   └── dashboard.css          # Custom styling for admin dashboard & print layout
│   └── js/
│       ├── script.js              # Sidebar toggle, alert timers & instant table search
│       ├── validation.js          # Client-side input and phone validation
│       └── wage_calc.js           # Real-time interactive wage preview engine
│
├── database/
│   └── workido_db.sql             # Relational database schema, foreign keys & demo seeds
│
└── README.md                      # Installation and execution instructions
```

---

## Step-by-Step XAMPP Setup & Installation

### 1. Install XAMPP
If you have not installed XAMPP yet:
1. Download **XAMPP for Windows** (with PHP 8.x) from [Apache Friends](https://www.apachefriends.org/).
2. Run the installer and install Apache and MySQL to the default directory (recommended: `C:\xampp`).

### 2. Start Apache and MySQL
1. Open the **XAMPP Control Panel** (`C:\xampp\xampp-control.exe`).
2. Click the **Start** button next to **Apache**. (The module turns green).
3. Click the **Start** button next to **MySQL**. (The module turns green).

### 3. Place the Workido Project Folder
Verify that the Workido folder is located inside your XAMPP `htdocs` directory:
```text
C:\xampp\htdocs\Workidoo\
```
*(On Windows, Apache URLs are case-insensitive, so both `http://localhost/workido/` and `http://localhost/Workidoo/` work seamlessly).*

---

### 4. Database Setup (Choose Option A or Option B)

#### Option A: One-Click Web Installer (Fastest & Easiest)
1. Ensure Apache and MySQL are running in XAMPP.
2. Open your web browser and visit:
   ```text
   http://localhost/Workidoo/install.php
   ```
3. Click the **"Install Workido Database Now"** button.
4. The system will automatically create `workido_db` and seed all tables with realistic sample data.

---

#### Option B: Manual Import via phpMyAdmin
1. Open your browser and navigate to:
   ```text
   http://localhost/phpmyadmin/
   ```
2. Click on **New** in the left sidebar to create a new database.
3. In the "Database name" field, type:
   ```text
   workido_db
   ```
   Select collation: `utf8mb4_unicode_ci` and click **Create**.
4. With `workido_db` selected, click on the **Import** tab in the top navigation bar.
5. Under "File to import", click **Choose File** and select:
   ```text
   C:\xampp\htdocs\Workidoo\database\workido_db.sql
   ```
6. Scroll down and click **Import** (or **Go**).
7. You should see a success message indicating that all tables were imported successfully.

---

### 5. Database Configuration (If customized)
If your local MySQL uses custom credentials (e.g., custom root password or non-standard port), open `config/database.php` in Visual Studio Code and modify the constants:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');            // Default in XAMPP is blank
define('DB_NAME', 'workido_db');
define('DB_PORT', 3306);
```

---

## 6. Running the Website & User Roles

Once Apache and MySQL are running:

- **Public Home Page**:
  `http://localhost/workido/` *(or `http://localhost/Workidoo/`)*
- **User / Admin Login**:
  `http://localhost/workido/login.php`
- **Customer Registration**:
  `http://localhost/workido/register.php`
- **Customer Portal**:
  `http://localhost/workido/customer/dashboard.php`
- **Admin Control Center**:
  `http://localhost/workido/admin/dashboard.php`

---

## 7. Ready-to-Use Accounts & Roles

Workido provides role-based workspaces for both **Customers (Clients)** and **Administrators**:

### A. Customer / Client Account (For Hiring Skilled Workers)
- **Email**: `customer@example.com`
- **Password**: `Customer@123`
- **Role**: `Customer / Client`
- **Capabilities**:
  - Browse verified Carpenters, Electricians, Plumbers, and Masons.
  - Filter workers by trade, daily wage, and ratings.
  - **Hire a specific worker directly** with custom job title, scope of work, address, dates, and budget.
  - **Post an open requirement** so coordinators can allocate available craftsmen.
  - Monitor live job status (Pending, Assigned, In Progress, Completed).
  - Rate and review craftsmen after job completion.

### B. Administrator Account (For Platform Operations)
- **Email**: `admin@workido.com`
- **Password**: `Admin@123`
- **Role**: `Administrator`
- **Capabilities**:
  - Full control over worker directory, work allocations, daily wages, and financial reports.
  - Dispatch skilled personnel to incoming customer orders.

*(Both accounts feature quick 1-click fill buttons directly on the Login page).*

---

## 8. Testing Key Workflows

Follow this verification checklist to inspect every module in action:

1. **Public Site Inspection**:
   - Open `http://localhost/Workidoo/`.
   - Inspect the live database metrics (workers, work orders, completed jobs).
   - Review the trade sections for **Carpenters**, **Electricians**, **Plumbers**, and **Masons**.
   - Submit a test inquiry on the Contact form.
2. **Admin Authentication**:
   - Click **Sign In** and log in with `admin@workido.com` / `Admin@123`.
   - Test accessing an admin page directly in an incognito window to confirm that `auth_check.php` prevents unauthorized entry.
3. **Worker Management**:
   - Navigate to **Worker Directory**.
   - Use the live search and filter by Trade Category, Availability, and Experience.
   - Click **Add New Worker**, select **Carpenter** (or another trade), fill the fields, and submit.
   - View the created worker's profile, assignment history, and wage ledger.
4. **Work Allocation**:
   - Navigate to **Post New Job** (e.g., "Modular Kitchen Cabinets").
   - Set dates, location, payment structure, and assign a worker.
   - Go to **Work Allocations** (`assignments.php`) and update the assignment status to `In Progress` and then `Completed`.
5. **Dynamic Wage Calculation**:
   - Navigate to **Calculate Wages**.
   - Select a worker: notice their base daily wage auto-fills into the input.
   - Test Daily Rate mode: enter 5 days, add ₹500 overtime bonus, add ₹200 advance deduction.
   - Observe the real-time vanilla JS breakdown preview.
   - Submit the form: the server strictly verifies and computes `Total = (Daily × Days) + Extra − Deductions`.
   - View the generated printable **Wage Payslip Voucher** and test print layout.
6. **Worker Performance Tracking**:
   - Navigate to **Worker Performance**.
   - Verify that the Job Completion Rate is dynamically calculated using `(Completed / Total Assigned) * 100` with zero-division safety.
   - Click **Record Review** to evaluate workmanship quality, punctuality, and safety compliance.
7. **Audited Reports & CSV Export**:
   - Navigate to **Reports & Export**.
   - Switch between Workers, Work Orders, Wage Payroll, and Performance tabs.
   - Click **Download CSV** on any tab to download an Excel-compatible `.csv` file with real data.
8. **Sign Out**:
   - Click the user profile dropdown in the top-right corner and select **Sign Out**. Confirm session termination.

---

## License & Credits
Developed as an enterprise-grade skilled-worker management web platform built using standard Core PHP and MySQL for high reliability and local XAMPP execution.
