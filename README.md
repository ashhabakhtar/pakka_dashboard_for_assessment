# Pakka Employee Assessment & Management Portal

> Modern. Role-based. Assessment-ready. Built for Pakka Limited.

Pakka Employee Assessment & Management Portal is an internal web application that helps teams manage **employee assessments**, **role profiles**, and **day-to-day objectives (tasks)**—all through a clean, role-based workflow.

This project was built during my internship at **Pakka Limited** and designed to be:
- **Fast to use** (search, filters, inline actions)
- **Role-aware** (Sewak / Sangrakshak / Utpadak views)
- **Assessment structured** (UAT-style competency scoring + development plans)
- **Data-backed** (SQLite database with seeded demo data)

---

## ✨ Key Features

### 1) Role-based portal (RBAC UI)
- Login routes users into the right experience based on `system_role`.
- Sidebar navigation automatically shows/hides sections.

### 2) Dashboard (Overview + quick actions)
- Role-specific metrics:
  - Total tasks + pending tasks
  - Pending skill verification (Sewak admin)
  - Pending assessments (for leaders)
- Recent activity preview
- Task completion visualization (donut-style)

### 3) Tasks management
- Tasks list with:
  - Search by title/description/assignee
  - Status filter tabs
  - Inline status updates (Sewak & Sangrakshak)
- Sewak can register new tasks via a modern modal

### 4) Designations & Role Profiles
- Manage designations (CRUD)
- View & maintain role profiles
- Launch assessment workflow from designation list

### 5) Role Profile Assessment (UAT-style competency scoring)
- Dynamic mission/outcomes/competencies based on designation.
- Self vs Leader ratings + weighted scoring
- Development plan capture
- Personal skill proposals:
  - Employees propose personal skills
  - Sewak approves/rejects
  - Approved custom skills automatically appear in the evaluation grid

### 6) Users management (Sewak only)
- Register/edit/delete users
- Configure designation linkage
- Configure system role (RBAC)

---

## 🧰 Tech Stack
- **PHP** (server-side rendering)
- **SQLite** (project-local database)
- **vanilla JavaScript** (UI helpers)
- **CSS** (custom modern styling; no external UI framework required)

---

## 📸 Screenshots

Place screenshots in:
- `screenshots/`

Use these filenames (example):
- `screenshots/01-login.png`
- `screenshots/02-dashboard.png`
- `screenshots/03-tasks.png`
- `screenshots/04-designations.png`
- `screenshots/05-assessments-list.png`
- `screenshots/06-role-profile-assessment.png`
- `screenshots/07-users-management.png`

Then the README will display them below.

### Gallery

| Page | Preview |
|---|---|
| Login | ![Login](screenshots/01-login.png) |
| Dashboard | ![Dashboard](screenshots/02-dashboard.png) |
| Tasks | ![Tasks](screenshots/03-tasks.png) |
| Designations | ![Designations](screenshots/04-designations.png) |
| Assessments List | ![Assessments List](screenshots/05-assessments-list.png) |
| Role Profile Assessment | ![Role Profile Assessment](screenshots/06-role-profile-assessment.png) |
| Users (Sewak) | ![Users](screenshots/07-users-management.png) |

> Note: The image files are intentionally not committed yet until you add them locally.

---

## 🚀 Local Setup (SQLite)

### 1) Requirements
- PHP (with PDO + SQLite enabled)
- A local server to run PHP (e.g., XAMPP/WAMP/Laragon or any PHP built-in server)

### 2) Start the project
From the project folder (`pakk a dash2` in your local machine):
- Open `index.php` in your browser.

### 3) Initialize database
Run:
- `setup_sqlite.php`

This recreates the SQLite database at:
- `database/pakka_dash.sqlite`

It also seeds the app with sample roles, users, tasks, and role profile data.

---

## 🔐 Demo Credentials

After opening `index.php`, the login page shows these demo accounts:

- **sewak@pakka.com** / `password123`
- **lead@pakka.com** / `password123`
- **member@pakka.com** / `password123`

---

## 🧭 How to Use (Page-by-page)

### `index.php` — Login
- Enter email + password.
- On success, users land on `dashboard.php`.

### `dashboard.php` — Role dashboard
- Sewak (Admin): sees pending personal skill verification requests.
- Sangrakshak (Lead) / Utpadak (Member): sees relevant task & assessment status.

### `tasks.php` — Tasks workspace
- Search tasks quickly.
- Filter by status.
- Sewak registers new tasks.
- Sewak & Sangrakshak can update task status inline.

### `designations.php` — Designations CRUD
- Create/edit designations.
- Navigate to Role Profile for a designation.

### `role_profiles.php` — Role profile list
- View the role profiles for existing designations.

### `assessments.php` — Assessment list
- Pick a fiscal year.
- Start assessment or re-evaluate (Sewak/Sangrakshak).

### `role_profile_assessment.php` — Assessment scoring grid
- Mission + key outcomes
- Competencies/skills table with weighted scoring
- Development plan textarea
- Self + leader rating selectors
- Personal skill proposal form

### `users.php` — Users management (Sewak only)
- Register new users
- Edit user RBAC role and designation
- Delete users (except self)

---

## 📁 Project Structure

Key files/folders:
- `index.php` — Login
- `dashboard.php` — Dashboard
- `tasks.php` — Task management
- `designations.php` — Designations CRUD
- `role_profiles.php` — Role profiles
- `assessments.php` — Assessment list
- `role_profile_assessment.php` — Assessment scoring + skill proposal
- `users.php` — RBAC user management (Sewak)
- `approve_skill.php` — Skill verification actions

- `includes/` — shared layout + auth/db
- `assets/css/style.css` — modern UI styling
- `assets/js/app.js` — modal + basic UI helpers
- `database/` — SQLite DB and schema

---

## 🧾 Notes
- This portal uses server-side rendering and a lightweight SQLite database for easy internship/demo deployment.
- Personal skills proposed by employees are stored as `Pending` and become usable after Sewak approval.

---

## ✅ License
MIT (or replace with your preferred license)

