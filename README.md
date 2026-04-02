<div align="center">
  
# 🏥 Patient Record Management System (PRMS)

**A Next-Generation Digital Healthcare & Clinical Administration Platform**

[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)](#)
[![MySQL](https://img.shields.io/badge/MySQL-Database-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](#)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)](#)
[![Status: Active](https://img.shields.io/badge/Project_Status-Active-success?style=for-the-badge)](#)

</div>

---

## 📖 Executive Summary

The **Patient Record Management System (PRMS)** is a comprehensive, scalable, and highly responsive web application designed specifically to modernize hospital and clinical workflows. By completely digitizing the traditional paper-based approach, PRMS eliminates administrative bottlenecks, prevents overbooking, secures sensitive patient medical data, and provides powerful oversight through financial and operational analytics.

Designed with a strict **Role-Based Access Control (RBAC)** architecture, the system safely isolates environments for **Administrators, Doctors**, and **Receptionists**, ensuring each staff member has specialized tools tailored to their specific operational needs.

---

## ✨ Comprehensive Feature Matrix

### 💼 1. Administrative Command Center (Admin)
The administrative portal acts as the operational brain of the clinic, providing deep analytical insights and overriding control of the directory.
- **Real-Time Analytics Dashboard:** Visualized statistical reporting (via Chart.js) tracking patient influx, revenue growth, and doctor utilization rates.
- **Dynamic Human Resources:** Provision, edit, and revoke access for medical professionals (Doctors) and front-desk staff (Receptionists).
- **Specialty & Category Mapping:** Create medical specialties (e.g., Cardiology, Neurology) and assign doctors accordingly.
- **Global Schedule Management:** A centralized matrix to define and monitor doctor availability, shift timings, and daily patient intake thresholds.
- **Enterprise Announcements:** Broadcast system-wide or role-specific announcements and alerts directly to staff dashboards.
- **Financial Oversight:** Monitor transaction histories, calculate daily revenue, and track incomplete invoice settlements.

### 🩺 2. Clinical Workspace (Doctor)
A distraction-free, securely isolated portal designed entirely around patient care and clinical record-keeping.
- **Personalized Itinerary:** A pristine view of the day's scheduled appointments and patient queues.
- **Electronic Medical Records (EMR):** Drill down into a patient's complete historical medical timeline, including past visits, prior diagnoses, and historical notes.
- **Digital Prescriptions & Encounters:** Log acute/chronic diagnoses, record confidential clinical notes, and digitally draft treatment plans.
- **Automated Workflow:** Mark appointments as 'Completed' which instantly updates the Receptionist's queue management interface.

### 🤝 3. Front-Desk Operations (Receptionist)
Built for high-speed, high-volume interaction to manage waiting rooms effectively.
- **Intelligent Appointment Routing:** Book appointments by selecting a medical category first; the system dynamically filters the available doctors and checks their exact daily patient capacity to prevent double-booking.
- **Patient Onboarding:** Rapid registration module for new patients, capturing immediate demographic and contact prerequisites.
- **Queue & Status Tracking:** Monitor real-time status of patients (Pending, In-Consultation, Cancelled, Completed).
- **Billing & Transaction Processing:** Settle appointment fees and mark payments as completed in the financial ledger.

---

## 🛠️ Technology Stack & Architecture

PRMS is built using a robust, proven stack that prioritizes speed, security, and ease of deployment on any standard LAMP/WAMP/XAMPP stack.

### Frontend Technologies
- **HTML5 & CSS3:** Semantic markup with modern layout standards.
- **Tailwind CSS (Utility-First E-Model):** Provides a highly responsive, modern, and fluid user interface. Ensures the application looks beautiful on both desktop monitors and mobile devices.
- **Vanilla JavaScript:** Powers asynchronous modal interactions, form validations, and dynamic UI updates without heavy framework overhead.
- **Chart.js:** Renders beautiful, interactive canvas-based data visualizations on the admin dashboard.
- **FontAwesome 6:** Premium vector iconography for intuitive navigation.

### Backend & Database Architecture
- **PHP (Procedural / Core):** Handling server-side logic, secure session management, and database transactions cleanly and efficiently.
- **MySQL (Relational Database):** A structured, highly normalized database design.

#### 🗄️ Database Relational Model (Core)
The relational structure guarantees data integrity across the platform:
- `users`: The master authentication table handling credential verification and RBAC (`role` column).
- `doctors` / `receptionists`: Child tables linking to `users` holding profession-specific metadata.
- `categories`: The taxonomical table defining medical specialties.
- `patients`: Master registry of all clinical patients.
- `schedules`: Tracks the integer value of `max_patients` per doctor alongside shift timings.
- `appointments`: The central hinge table linking a `patient_id` to a `doctor_id` at a specific timestamp.
- `records`: The clinical payload containing the actual medical notes written by doctors.
- `payments`: Financial ledger linking successful transactions back to specific appointments.

---

## 🔒 Security Posture & Standards

- **Strict Access Control:** Server-side route protection. If an unauthenticated user or an unauthorized role attempts to access a protected directory, they are immediately forcefully redirected.
- **Password Cryptography:** All user credentials are obfuscated using modern PHP password hashing algorithms (`PASSWORD_BCRYPT`).
- **SQL Injection Prevention:** Core database queries utilize `mysqli_real_escape_string` and prepared structural methodologies to sanitize inputs.
- **XSS Mitigation:** Output data, especially patient names and clinical notes, are processed through `htmlspecialchars()` before rendering to the DOM to prevent cross-site scripting.

---

## 🚀 Installation & Deployment Guide

Follow these instructions to deploy PRMS into a local development environment.

### Prerequisites
- **XAMPP / WAMP / LAMP** server installed.
- **PHP 8.0+** recommended.
- **MySQL 5.7+ / MariaDB**.

### Step 1: Repository Clone
Navigate to your server's root web directory (e.g., `C:\xampp\htdocs\` or `/var/www/html/`) and clone the project:
```bash
git clone <your-repository-url> prms
cd prms
```

### Step 2: Database Initialization
1. Launch **phpMyAdmin** (usually `http://localhost/phpmyadmin`).
2. Create a new, blank database named `prms_db`.
3. Select the `prms_db` database, go to the **Import** tab, and upload the master database dump file (e.g., `database.sql` / `prms.sql` located in the project root or database folder).

### Step 3: Environment Configuration
Open the project directory and verify the database connection file:
1. Navigate to `/config/db.php`.
2. Inspect and modify the connection string to match your local setup:
```php
<?php
$host = "localhost";
$user = "root";       // Default XAMPP MySQL user
$pass = "";           // Default XAMPP MySQL password is empty
$dbname = "prms_db";  // The database you just created

$conn = mysqli_connect($host, $user, $pass, $dbname);
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
?>
```

### Step 4: System Launch
Start your Apache and MySQL engines via your local control panel. Open a web browser and visit:
```text
http://localhost/prms
```

### 🔑 Default Credentials
Use the system administrator account to initialize the clinic:
- **Email:** `admin@prms.com` *(adjust if seeded differently)*
- **Password:** `password` *(adjust if seeded differently)*

*(Note: Ensure you create new, secure administrator credentials immediately upon first successful deployment and remove the default seed accounts).*

---

<div align="center">
  <p>Engineered for precision, speed, and reliability in healthcare management.</p>
</div>
