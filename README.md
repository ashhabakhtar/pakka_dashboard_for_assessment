# 🌿 Pakka Employee Assessment & Management Portal

> **Enterprise-Grade. Role-Aware. Dynamically Validated.** A state-of-the-art competency evaluation and organizational alignment portal designed specifically for Pakka Limited.

[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![Database](https://img.shields.io/badge/SQLite-3.0-003B57?style=for-the-badge&logo=sqlite&logoColor=white)](https://www.sqlite.org/)
[![Styling](https://img.shields.io/badge/CSS-Vanilla-1572B6?style=for-the-badge&logo=css3&logoColor=white)](https://developer.mozilla.org/en-US/docs/Web/CSS)
[![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)](LICENSE)

---

## 📖 Project Overview

The **Pakka Employee Assessment & Management Portal** is an advanced, high-performance internal system that bridges the gap between high-level company targets and everyday operations. Engineered using a secure **Role-Based Access Control (RBAC)** architecture, this portal empowers members (**Utpadak**), unit leads (**Sangrakshak**), and corporate administrators (**Sewak**) to orchestrate and evaluate technical, operational, and leadership competencies.

This system was custom-engineered to replicate the high-fidelity aesthetics and strict mathematical validation rules of Pakka's production UAT ecosystems, delivering:
1. **Zero-Overhead Local Performance** via a lightweight SQLite architecture.
2. **Dynamic Serialization** storing structured designation profiles in robust JSON strings.
3. **Rigorous Weight Alignment Validation** to guarantee 100% mathematical precision across all skills.
4. **Real-time Gap and Competency Metrics** dynamically recalculated on the home dashboard.

---

## 🚀 Key Architectural Features

### 1️⃣ Dynamic Competency Gap & Skill Analytics Dashboard
* **Dynamic Database Aggregation**: All statistics on the home page (`dashboard.php`) are dynamically calculated by scanning the JSON profile configurations and active user assessments inside SQLite.
* **Live Competency Counts**: Displays the exact number of **Core Competencies** (unique category count, e.g., `99`) and **Evaluated Metrics** (total technical skills count, e.g., `297`) dynamically seeded.
* **Match Rate Metrics**: Automatically computes average alignment indexes against the corporate **UAT Target of 4.00**, changing indicator colors in real time (🟢 Green $\geq 90\%$, 🟡 Yellow $\geq 75\%$, 🔴 Red $< 75\%$).
* **SVG Task Rings**: Renders dynamic, responsive task completion donut graphs utilizing SVG stroke offsets to track individual user progress.

### 2️⃣ Multi-Mode Structured Role Profiles Grid
* **List View (Search & Sort)**: Displays all active corporate designations (IT, Commercial, Projects, Sales, Plant Head, etc.) in a clean, search-filtered table.
* **Interactive Grid Editor (`Sewak` Mode)**: Admins can modify the role mission, add/delete key outcomes, append attributes, and re-balance individual skill weights.
* **Strict Two-Tier Math Validation (JavaScript)**:
  1. The sum of all Attribute Category weights must equal exactly **100%**.
  2. The sum of child technical skill weights under any category must equal exactly that category's parent weight.
  * If weights are unbalanced, the UI highlights invalid rows in soft red (`#ffe6e6` background, `#dc3545` border) and **disables the Save Profile button** to enforce data integrity.
* **Read-Only Grid View (`Sangrakshak` / `Utpadak` Mode)**: Renders the full structured grid with all inputs, textareas, and controls disabled to allow secure peer review.

### 3️⃣ High-Fidelity "Assessment List" (Live UAT Replicated)
* **Alphabetical Ordering**: Lists all designation reviews sorted alphabetically (`d.title ASC`) matching corporate UAT formats.
* **Financial Year Dropdown**: Filter reviews dynamically by year (`2024-25`, `2025-26`, `2026-27`) with a custom gold `"Current Year"` indicator tag for the active `2026-27` review cycle.
* **Search-on-Keypress Filtering**: Instantly filters card rows client-side as you type.
* **Role-Aware Action Routing**:
  * Admins/Leads see `"Start Assessment"` or `"Re-Evaluate"` links.
  * Members see view-only `"View"` options for past completed cycles or a greyed `"Not Reviewed"` status.

### 4️⃣ Personal Skill Proposals & Admin Verification Loop
* **Proposal Form**: Employees can suggest new personal technical skills directly from their active assessment page.
* **Pending Verification Queue**: Proposed skills enter the Admin's (`Sewak`) pending queue displayed dynamically on their home dashboard.
* **Approved Insertion**: If approved, the system dynamically injects the skill into the designation's JSON profile, immediately loading it inside subsequent evaluation grids.

### 5️⃣ Secure RBAC Enterprise Guards
* **Page-Level Protection**: Strict session checks block unauthorized users from administrative actions (`add_role_skill.php`, `users.php`, `approve_skill.php`), issuing a `403 Access Denied` alert.
* **Preselection query parsing**: Navigating to `add_role_skill.php?designation_id=X` automatically pre-selects the target role in the dropdown for an efficient workflow.

---

## 🗄️ Database Architecture

The SQLite database (`database/pakka_dash.sqlite`) uses a highly optimized relational schema:

```mermaid
erDiagram
    designations ||--o{ users : "has"
    designations ||--|| role_profiles : "defines"
    designations ||--o{ assessments : "assessed"
    users ||--o{ tasks : "assigned_to / created_by"
    users ||--o{ personal_skills : "proposes"
    assessments ||--o{ users : "assessor"

    designations {
        INTEGER id PK
        TEXT title UNIQUE
        TEXT description
    }
    users {
        INTEGER id PK
        TEXT name
        TEXT email UNIQUE
        TEXT password_hash
        TEXT system_role
        INTEGER designation_id FK
    }
    tasks {
        INTEGER id PK
        TEXT title
        TEXT description
        TEXT status
        TEXT priority
        TEXT due_date
        INTEGER assigned_to FK
        INTEGER created_by FK
    }
    role_profiles {
        INTEGER id PK
        INTEGER designation_id FK
        TEXT profile_text "Serialized JSON"
    }
    assessments {
        INTEGER id PK
        INTEGER designation_id FK
        TEXT fiscal_year
        TEXT status
        INTEGER assessor_id FK
        TEXT assessment_data "Serialized JSON"
    }
    personal_skills {
        INTEGER id PK
        INTEGER user_id FK
        INTEGER designation_id FK
        TEXT skill_name
        TEXT attribute_desc
        TEXT status
    }
```

### Serialized JSON Formats

#### 1. `role_profiles.profile_text` Structure
```json
{
  "designation_id": 1,
  "title": "Role Profile — IT HEAD",
  "mission": "Enable business and all stakeholders to have digital driven decision making...",
  "outcomes": [
    "End to end product & customer transparency",
    "Complete data driven automated decision making"
  ],
  "skills": [
    {
      "sn": 1,
      "category": "Strategic Alignment",
      "weightage": 25,
      "assessment": "KPI Completion Rate ≥ 90%",
      "attributes": [
        {
          "desc": "Align team activities with high-level corporate roadmap metrics.",
          "weight": 9
        },
        {
          "desc": "Execute priority project objectives on schedule.",
          "weight": 8
        }
      ]
    }
  ]
}
```

#### 2. `assessments.assessment_data` Structure
```json
{
  "self_rating": {
    "1": {"0": "3.5", "1": "4.0"}
  },
  "leader_rating": {
    "1": {"0": "4.0", "1": "4.0"}
  },
  "dev_plan": {
    "1": "Enforce monthly monitoring..."
  },
  "totals": {
    "self_score_sum": "375.00",
    "final_score_sum": "400.00",
    "self_index_score": "3.75",
    "final_index_score": "4.00"
  }
}
```

---

## 🔐 Credentials & Role Demo Matrix

Access the portal locally using these seeded test profiles:

| Account Role | Demo Email | Password | Primary Permissions |
| :--- | :--- | :--- | :--- |
| **Sewak** (Admin) | `sewak@pakka.com` | `password123` | Full CRUD, Interactive Grids Editor, Dynamic Skill Additions, Skill Approvals, User RBAC Management |
| **Sangrakshak** (Lead) | `lead@pakka.com` | `password123` | Read-only Profiles Grid, Launch/Complete Peer Assessments, Task Status Updates |
| **Utpadak** (Member) | `member@pakka.com` | `password123` | Read-only Profiles Grid, View Personal Assessments, Propose Personal Skills |

---

## 🛠️ Local Installation & Setup

1. **Clone the Repository**:
   ```bash
   git clone https://github.com/ashhabakhtar/pakka-employee-assessment-and-management-portal.git
   cd pakka-employee-assessment-and-management-portal
   ```

2. **Verify PHP Installation**:
   Ensure PHP (8.0+) is installed and that the SQLite extension is enabled in your `php.ini` (`extension=pdo_sqlite` and `extension=sqlite3`).
   ```bash
   php -v
   ```

3. **Initialize & Seed the Database**:
   Run the SQLite setup script. This automatically parses the comprehensive `parsed_profiles.json` and loads all designations, users, tasks, and initial competency profiles.
   ```bash
   php setup_sqlite.php
   ```

4. **Launch Local Server**:
   Start a local PHP development server:
   ```bash
   php -S 127.0.0.1:8000
   ```
   Now open your browser and navigate to **`http://127.0.0.1:8000`** to view the app!

---

## 🧭 Visual Walkthrough Guide

### Step 1: Login & Dynamic Dashboard
* Sign in as **Sewak** (`sewak@pakka.com` / `password123`).
* You are immediately greeted with a forest green themed page displaying **live dynamic metrics** pulled from the SQLite DB.
* You will see the **Competency Gap & Skill Analytics** displaying overall match rates, core competency counts, and progress bars matching the active evaluation stats.

### Step 2: Role Profile Management & Weight Balancing
* Go to **"ALL ROLE PROFILE"** in the sidebar.
* Click **"Edit"** next to **"Brand and Marketing Head"**.
* Scroll to the **Competency Grid**.
* Try increasing a technical skill weight. Notice that the parent category immediately highlights in light red, an warning notice alerts you that the profile is unbalanced, and the **"Save Profile"** button is disabled.
* Balance the weights so that they match exactly. Watch the warning disappear and the **"Save Profile"** button re-activate. Click save to persist.

### Step 3: Add Dynamic Skills with Access Controls
* Click on **"Add Role Skill Component"** in the dashboard **Quick Actions Panel** or the profiles header.
* Fill out the form to add a skill under a category for a target designation (e.g. IT Head).
* Submit the form. You are redirected back to the profile with a **green confirmation banner** highlighting the dynamic insertion.
* Log out, sign in as `member@pakka.com` (Utpadak), and attempt to access `add_role_skill.php` directly. The system blocks the request and outputs a secure **Access Denied** alert.

### Step 4: dynamic Peer Assessments
* Log in as **Sangrakshak** (`lead@pakka.com`).
* Go to **"Assessment List"** or click review.
* Toggle the Financial Year filters. Select `"FY 2026-27"`.
* Click **"Start Assessment"** next to **"Brand and Marketing Head"**.
* Change the ratings in the **Leader Rating** select dropdowns. Watch the scores recalculate and index scores balance automatically.
* Propose a custom skill using the proposal form at the bottom.
* Log in as **Sewak** to view the pending skill proposal on your dashboard queue, approve it, and see it loaded instantly into subsequent grids!

---

## 📁 Repository Structure

```text
├── assets/                     # Frontend Stylesheets and Scripts
│   ├── css/style.css           # Premium Custom styling tokens
│   └── js/app.js               # Responsive sidebar & modal events
├── database/                   # Database Storage
│   ├── schema.sql              # Core SQLite SQL schema definition
│   └── pakka_dash.sqlite       # (Ignored) Seeded SQLite database file
├── includes/                   # Shared Layout Elements
│   ├── db.php                  # PDO SQLite Connection bootstrap
│   ├── header.php              # RBAC Page Navigation Header
│   └── footer.php              # Shared HTML page footer
├── add_role_skill.php          # Admin Preselected Skill Insertion Tool
├── approve_skill.php           # Dynamic skill approval endpoint
├── assessments.php             # Sorted Assessment card-grid with FY filter
├── dashboard.php               # Dynamic home stats and gap calculations
├── designations.php            # Designations CRUD operations
├── index.php                   # Portal Login gateway
├── logout.php                  # Session end handler
├── parsed_profiles.json        # Seeded Role Profile JSON dataset
├── role_profiles.php           # Role profiles table list & Grid editor
├── role_profile_assessment.php  # Interactive evaluation rating grid
├── setup_sqlite.php            # Automated database seed script
├── tasks.php                   # Task workspace management
└── users.php                   # Secure Sewak user account CRUD panel
```

---

## ⚖️ License
This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
