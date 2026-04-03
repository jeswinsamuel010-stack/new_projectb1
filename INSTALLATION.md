# Project Management Module Integration Guide

## Files Created

### Database Tables
- `sql/project_modules.sql` - SQL statements for new tables
- `sql/setup.php` - Alternative PHP script to create tables

### Milestones Module
- `milestones/list.php` - View all milestones
- `milestones/add_milestone.php` - Add new milestone
- `milestones/edit_milestone.php` - Edit existing milestone
- `api/milestones.php` - API for milestones data

### Payments Module
- `payments/list.php` - View all stage payments
- `payments/add_payment.php` - Add new payment record
- `api/payments.php` - API for payments data

### Reports Module
- `reports/micro_report.php` - Detailed project report
- `reports/macro_report.php` - Summary project report

### Updated Files
- `includes/sidebar.php` - Added navigation for new modules
- `index.php` - Added project progress stats to admin dashboard

---

## Installation Steps

### Step 1: Create Database Tables

**Option A: Run SQL directly**
```bash
mysql -u root -p construction_erp < sql/project_modules.sql
```

**Option B: Use PHP script**
Visit this URL in your browser:
```
http://localhost/new_projectb/sql/setup.php
```

### Step 2: Verify Installation
- Log in as Admin
- Check sidebar for new menu items: "Milestones", "Stage Payments", "Reports"
- Visit Dashboard to see new progress stats

---

## Module Features

### 1. Milestones Module
**Access:** Admin, Project Manager

**Features:**
- Add milestones with phase name, description, start/end dates
- Track status: Pending, In Progress, Completed
- Link milestones to projects

**Navigation:** Milestones > Add Milestone

### 2. Project Progress Tracking
**Access:** Admin Dashboard (auto-calculated)

**Features:**
- Automatic percentage calculation based on completed milestones
- Shows current stage (in-progress milestone)
- No manual configuration needed

### 3. Stage Payments Module
**Access:** Admin, Accounts (full) | Project Manager (view-only)

**Features:**
- Record payments by stage (Advance, Mid-Work, Completion)
- Track payment status: Pending, Paid
- Link to milestones (optional)

**Navigation:** Stage Payments > Add Payment

### 4. Reports Module
**Access:** Admin only

**Micro Report (Detailed):**
- Select project to view:
  - All milestones with status
  - All payments made

**Macro Report (Summary):**
- Overview of all projects:
  - Total project value
  - Completion percentage per project
  - Total payments made
  - Remaining balance

---

## Role Permissions

| Feature | Admin | Project Manager | Accounts |
|---------|-------|-----------------|----------|
| View Milestones | Yes | Yes | No |
| Add/Edit Milestones | Yes | Yes | No |
| View Payments | Yes | Yes | Yes |
| Add Payments | Yes | No | Yes |
| View Reports | Yes | No | No |

---

## Technical Notes

- All forms use prepared statements (SQL injection protected)
- CSRF tokens implemented for all POST forms
- Role-based access control maintained
- Existing tables NOT modified (new tables only)
- UI follows existing style with sidebar navigation
- Status badges: pending=yellow, in_progress=blue, completed=green, paid=green

---

## Sample Data

You can add sample milestones like:
- Week 1: Cupboard Work (2026-01-01 to 2026-01-07)
- Week 2: Partition Work (2026-01-08 to 2026-01-14)
- Week 3: Electrical Work (2026-01-15 to 2026-01-21)
- Month 1: Furniture Work (2026-01-22 to 2026-02-21)
- Month 2: Finishing Work (2026-02-22 to 2026-03-21)
- Final Stage: Handover (2026-03-22 to 2026-03-28)

Sample payments:
- Advance Payment: 20-30% at start
- Mid-Work Payment: 40-50% at halfway
- Completion Payment: 20-30% at handover