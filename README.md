# College Tech Fest Management System (INVICTUS 2026)

A complete, beginner-friendly College DBMS Web Application designed for managing technical fests, student registrations, event logistics, attendance, results, and certificate generation.

---

## 🛠️ Technology Stack
- **Frontend:** HTML5, CSS3, JavaScript (Vanilla - No external bloat)
- **Backend:** PHP (using `mysqli`, prepared statements, and session management)
- **Database:** MySQL (14 normalized tables with PK, FK, and UNIQUE constraints)
- **Environment:** Ubuntu Linux, Apache/PHP, MySQL, VS Code

---

## 📂 Project Architecture

```text
tech/
├── css/
│   └── style.css            # Responsive master stylesheet
├── js/
│   └── script.js            # Client-side mobile nav and URL parameter helpers
├── database/
│   └── database.sql         # Complete MySQL schema, constraints, and sample data
│
├── db.php                   # Database connection with error handling
├── auth.php                 # Session protection guard for admin portal
├── logout.php               # Admin logout script
├── get_events.php           # JSON API endpoint returning live events
├── get_departments.php      # JSON API endpoint returning departments
│
├── index.html               # Public fest homepage with statistics & event highlights
├── events.html              # Full events catalog with category filters
├── register.html            # Student registration form with dynamic dropdowns
├── register_process.php     # Validates input, prevents duplicate registrations & saves records
├── login.html               # Administrator login page
├── login_process.php        # Admin authentication and session initialization
│
├── dashboard.php            # Admin metrics (COUNT, SUM, GROUP BY, and JOIN)
├── manage_events.php        # Event CRUD interface (Add, Edit, Delete, View)
├── event_process.php        # Backend handler for event CRUD
├── registrations.php        # Student registrations manager with search & status toggle
├── attendance.php           # Event attendance sheet with date and event filters
├── attendance_process.php   # Backend handler for marking Present/Absent
├── results.php              # Competition results and leaderboard declaration
├── result_process.php       # Backend handler for results & auto-certificate generation
└── certificates.php         # Unique certificate verification & printable certificate view
```

---

## 🗄️ Database Tables (`CollegeTechFestDB`)

| Table | Purpose | Key DBMS Concepts |
|---|---|---|
| `COLLEGE` | Host institution details | Primary Key |
| `DEPARTMENT` | Academic departments | Foreign Key (`College_ID`) |
| `PARTICIPANT` | Student profiles | `Roll_No` UNIQUE constraint |
| `COORDINATOR` | Faculty event coordinators | Foreign Key (`College_ID`) |
| `VENUE` | Auditoriums, labs, and halls | Primary Key |
| `EVENT` | Technical competitions | FK to `VENUE`, FK to `COORDINATOR` |
| `EVENT_SCHEDULE` | Timing slots per event | FK to `EVENT`, FK to `VENUE` |
| `TEAM` | Teams for group competitions | FK to `PARTICIPANT` (Leader) |
| `REGISTRATION` | Many-to-Many link between student & event | Composite `UNIQUE (Participant_ID, Event_ID)` |
| `PAYMENT` | Registration fee transactions | `Transaction_ID` UNIQUE constraint |
| `ATTENDANCE` | Present/Absent tracking | FK to `REGISTRATION`, UPSERT pattern |
| `RESULT` | Competition standings & scores | FK to `REGISTRATION` |
| `CERTIFICATE` | Achievement verification | `Certificate_No` UNIQUE constraint |
| `ADMIN` | Administrator credentials | Secure login, session management |

---

## 🚀 Setup & Execution Guide

### 1. Start Apache & MySQL Services
In your Ubuntu terminal:
```bash
sudo systemctl start apache2
sudo systemctl start mysql
```

### 2. Import Database Schema
```bash
mysql -u root -p < /home/mk/Desktop/tech/database/database.sql
```
*(Or import `database/database.sql` through phpMyAdmin).*

### 3. Verify Database Connection
Open `db.php` and verify the password matches your MySQL root password (default is empty `""`).

### 4. Run the Project
Start PHP's built-in development server from the project directory:
```bash
cd /home/mk/Desktop/tech
php -S localhost:8000
```

### 5. Access the Portals
- **Public Website:** [http://localhost:8000/index.html](http://localhost:8000/index.html)
- **Browse Events:** [http://localhost:8000/events.html](http://localhost:8000/events.html)
- **Student Registration:** [http://localhost:8000/register.html](http://localhost:8000/register.html)
- **Admin Login:** [http://localhost:8000/login.html](http://localhost:8000/login.html)
  - **Username:** `admin`
  - **Password:** `admin123`

---

## 🎓 Viva Questions & Answers

1. **Q: Why is `UNIQUE (Participant_ID, Event_ID)` used in the REGISTRATION table?**
   - *A:* To enforce entity integrity and business rules so a student cannot register more than once for the same technical competition.
2. **Q: Where are SQL JOINs implemented in this project?**
   - *A:* 
     - In `get_events.php` to fetch Venue and Coordinator names for each event.
     - In `dashboard.php` and `registrations.php` to join `REGISTRATION`, `PARTICIPANT`, `EVENT`, and `PAYMENT`.
     - In `certificates.php` to link student names, scores, and event titles onto certificates.
3. **Q: How does the system prevent SQL Injection?**
   - *A:* All form processing scripts (`register_process.php`, `login_process.php`, `event_process.php`, etc.) use **Prepared Statements** (`mysqli_prepare`, `mysqli_stmt_bind_param`, `mysqli_stmt_execute`).
4. **Q: What aggregate functions are used?**
   - *A:* `COUNT(*)` in `dashboard.php` and `register_process.php` (capacity limit), and `SUM(Amount)` in `dashboard.php` for total fee revenue.
