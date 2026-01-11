# School System – PHP 

A web-based **School Management System** built using **PHP**, and **XAMPP**.  
The system manages users, courses, enrollments, and grades with role-based access control.

---

##  Actors & Roles

### Admin
- Add / edit / delete teachers, students, and courses  
- Assign teachers to courses  
- Enroll students in courses  
- View and search teachers and students  
- Update personal information  
- Login / Logout  

### Teacher
- View assigned courses  
- Search students enrolled in their courses  
- Add grades  
- Update profile  
- Login / Logout  

### Student
- View enrolled courses  
- Drop a course **only if the grade is not yet assigned**  
- Update profile  
- Login / Logout  

---

##  Technologies Used
- PHP (Core PHP)
- phpMyAdmin
- XAMPP (Apache & MySQL)
- HTML / CSS /JAVA SCRIPT 

---

##  Installation & Setup (IMPORTANT)

### 1️- Install XAMPP
Download and install **XAMPP**, then start:
- Apache
- MySQL

---

### 2️- Create the Database (FIRST STEP)
1. Open **phpMyAdmin**
2. Create a database named:
   ```
   school_system
   ```
3. Import the provided SQL file into the database:
   - `school_system.sql`

 **The database must be created before running any PHP files.**

---

### 3️- Create the First Admin (SECOND STEP)
After the database is created and imported:

1. Open your browser and run:
   ```
   http://localhost/School_System/hash_admin.php
   ```
2. This will automatically create the **first admin user**.

#### Default Admin Credentials
- **Email:** `admin@gmail.com`
- **Password:** `1234`
- **Role:** Admin

---

### 4️- Login to the System
1. Open:
   ```
   http://localhost/School_System/
   ```
2. Login using the admin credentials above.
3. You can now create teachers, students, and courses.

---

##  Email Notifications
The system supports **email notifications** for important actions (already implemented).

---

##  Scalability Notes
-  The system supports adding **more users** (admins, teachers, students).
---

##  Author
**Mohammad Ali**  
Computer Science Student  
GitHub: https://github.com/mohammadali-pro

---

##  Notes
- This project was developed for **academic purposes**.
- The database was created using **XAMPP & phpMyAdmin**.

