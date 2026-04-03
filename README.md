# Construction ERP Web Application

A complete Construction ERP (Enterprise Resource Planning) system built with Core PHP and MySQL.

## Features

- **Role-Based Access Control**: 6 different user roles
- **Project Management**: Create and manage construction projects
- **Material Request Workflow**: Complete workflow from request to approval
- **Purchase Orders**: Generate purchase orders for approved requests
- **Inventory Management**: Track stock levels and manage inventory
- **Goods Receipt**: Record received materials
- **Material Issue**: Issue materials to site
- **Billing**: Generate bills for completed work

## User Roles

| Role | Username | Password | Description |
|------|----------|----------|-------------|
| Admin | admin | password123 | Full system access |
| Site Engineer | site_eng | password123 | Create material requests |
| Project Manager | proj_mgr | password123 | Approve/reject requests |
| Purchase Team | purchase | password123 | Create purchase orders |
| Store Keeper | store_kpr | password123 | Manage inventory & issues |
| Accounts | accounts | password123 | Manage bills & payments |

## Installation Instructions

### Step 1: Start XAMPP

1. Open XAMPP Control Panel
2. Start Apache and MySQL services

### Step 2: Import Database

1. Open browser and go to: `http://localhost/phpmyadmin`
2. Create a new database named: `construction_erp`
3. Click on the database, then go to "Import" tab
4. Browse and select: `database/schema.sql`
5. Click "Go" to import

**Alternatively**, you can import via command line:
```bash
mysql -u root -p construction_erp < database/schema.sql
```

### Step 3: Configure Application

1. The database configuration in `config/db.php` uses default XAMPP settings:
   - Host: localhost
   - Username: root
   - Password: (empty)
   - Database: construction_erp

If your XAMPP has a password, edit `config/db.php` and update the `DB_PASS` constant.

### Step 4: Run the Application

1. Open browser and go to: `http://localhost/new_projectb/`
2. Login with any demo user credentials

## Project Structure

```
new_projectb/
├── config/
│   └── db.php              # Database configuration
├── database/
│   └── schema.sql          # Database schema and sample data
├── includes/
│   ├── auth.php            # Authentication functions
│   ├── header.php          # Page header and navigation
│   ├── sidebar.php         # Sidebar (in header)
│   └── footer.php          # Page footer
├── css/
│   └── style.css           # Main stylesheet
├── js/
│   └── main.js             # JavaScript functions
├── projects/
│   ├── list.php            # List all projects
│   ├── create.php          # Create new project
│   ├── edit.php            # Edit project
│   └── view.php            # View project details
├── requests/
│   ├── list.php            # List material requests
│   └── create.php          # Create material request
├── approval/
│   └── index.php           # Approve/reject requests
├── purchase/
│   └── list.php            # Manage purchase orders
├── inventory/
│   └── list.php            # Manage inventory
├── issues/
│   └── index.php           # Goods receipt & material issue
├── bills/
│   ├── list.php            # List bills
│   └── view.php            # View bill details
├── users/
│   └── list.php            # User management
├── login.php               # Login page
├── logout.php              # Logout page
├── index.php               # Dashboard
└── README.md               # This file
```

## Workflow

1. **Admin** creates projects
2. **Site Engineer** submits material requests
3. **Project Manager** approves or rejects requests
4. **Purchase Team** creates purchase orders for approved items
5. **Store Keeper** receives goods and updates inventory
6. **Store Keeper** issues materials to site
7. **Accounts** creates bills and marks as paid

## Technology Stack

- **Backend**: Core PHP
- **Database**: MySQL
- **Frontend**: HTML5, CSS3, JavaScript
- **Server**: XAMPP (Apache + PHP)

## Security Features

- Password hashing using PHP's `password_hash()`
- Prepared statements for all database queries
- Session-based authentication
- Input sanitization
- Role-based access control

## Troubleshooting

### Login Issues

If you cannot login:
1. Check that XAMPP MySQL is running
2. Verify database was imported correctly
3. Check the database credentials in `config/db.php`

### Blank Page

If you see a blank page:
1. Enable error reporting by adding these lines at the top of your PHP files:
   ```php
   error_reporting(E_ALL);
   ini_set('display_errors', 1);
   ```

### Database Connection Error

If you get a database connection error:
1. Verify MySQL is running in XAMPP
2. Check database credentials in `config/db.php`
3. Make sure the database exists

## Demo Credentials

```
Username: admin      | Password: password123 | Role: Admin
Username: site_eng   | Password: password123 | Role: Site Engineer
Username: proj_mgr   | Password: password123 | Role: Project Manager
Username: purchase   | Password: password123 | Role: Purchase Team
Username: store_kpr  | Password: password123 | Role: Store Keeper
Username: accounts   | Password: password123 | Role: Accounts
```

Or use email addresses:
- admin@erp.com
- engineer@erp.com
- manager@erp.com
- purchase@erp.com
- store@erp.com
- accounts@erp.com

All passwords are: `password123`

## License

This project is for educational purposes. Feel free to use and modify as needed.