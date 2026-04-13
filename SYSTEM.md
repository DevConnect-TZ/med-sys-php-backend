# AFYA Medical Center - Complete System Documentation

## 📋 Table of Contents
1. [System Overview](#system-overview)
2. [System Architecture](#system-architecture)
3. [User Roles & Permissions](#user-roles--permissions)
4. [Database Schema (MySQL/PHP Implementation)](#database-schema-mysqlphp-implementation)
5. [API Endpoints](#api-endpoints)
6. [System Workflows](#system-workflows)
7. [Architecture Diagrams](#architecture-diagrams)
8. [Technology Stack](#technology-stack)

---

## 🏥 System Overview

**AFYA Medical Center** is a complete Hospital Management System that manages:
- Patient registration and records
- Appointment scheduling
- Electronic Medical Records (EMR)
- Lab test ordering and results
- Pharmacy and prescriptions
- Billing and invoicing
- Multi-role user management

### Key Features
✅ Role-based access control (7 user roles)
✅ Real-time updates and notifications
✅ Complete medical workflow automation
✅ Secure authentication and authorization
✅ Comprehensive billing system
✅ Lab and pharmacy integration
✅ Electronic Medical Records (EMR)

---

## 🏗️ System Architecture

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                     CLIENT LAYER                             │
│  ┌──────────────────────────────────────────────────────┐   │
│  │         React Frontend (SPA)                         │   │
│  │  - Pages (Dashboard, Patients, Appointments, etc.)   │   │
│  │  - Components (Forms, Tables, Modals)                │   │
│  │  - State Management (Context API)                    │   │
│  │  - Routing (React Router)                            │   │
│  └──────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
                            │
                            │ HTTPS/REST API
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                   APPLICATION LAYER                          │
│  ┌──────────────────────────────────────────────────────┐   │
│  │         Backend API (Node.js/Express or PHP)         │   │
│  │  ┌────────────┐  ┌────────────┐  ┌────────────┐     │   │
│  │  │ Auth       │  │ Middleware │  │ Routes     │     │   │
│  │  │ Service    │  │ - Logger   │  │ - Patients │     │   │
│  │  │            │  │ - Auth     │  │ - Appts    │     │   │
│  │  │            │  │ - Roles    │  │ - Labs     │     │   │
│  │  └────────────┘  └────────────┘  └────────────┘     │   │
│  └──────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
                            │
                            │ Database Queries
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                     DATA LAYER                               │
│  ┌──────────────────────────────────────────────────────┐   │
│  │         Database (MySQL/PostgreSQL)                  │   │
│  │  - users                - lab_orders                 │   │
│  │  - patients             - lab_results                │   │
│  │  - appointments         - prescriptions              │   │
│  │  - visits               - pharmacy_inventory         │   │
│  │  - invoices             - invitations                │   │
│  └──────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
```

### Request Flow Diagram

```
┌──────────┐
│  User    │
│ (Browser)│
└────┬─────┘
     │ 1. User Action (e.g., Create Patient)
     ▼
┌─────────────────┐
│  React Frontend │
│  - Validate     │
│  - Prepare Data │
└────┬────────────┘
     │ 2. HTTP Request (POST /api/patients)
     │    Headers: Authorization: Bearer <token>
     ▼
┌─────────────────┐
│  API Gateway    │
│  (Express/PHP)  │
└────┬────────────┘
     │ 3. Middleware Chain
     ▼
┌─────────────────┐
│  Logger         │ → Log request details
└────┬────────────┘
     │
     ▼
┌─────────────────┐
│  Auth Verify    │ → Verify JWT token
└────┬────────────┘
     │
     ▼
┌─────────────────┐
│  Role Check     │ → Check user role permissions
└────┬────────────┘
     │ 4. Route Handler
     ▼
┌─────────────────┐
│  Business Logic │
│  - Validate     │
│  - Process      │
└────┬────────────┘
     │ 5. Database Query
     ▼
┌─────────────────┐
│  MySQL Database │
│  - INSERT/      │
│    UPDATE/      │
│    SELECT       │
└────┬────────────┘
     │ 6. Response
     ▼
┌─────────────────┐
│  JSON Response  │
│  {success:true} │
└────┬────────────┘
     │ 7. Update UI
     ▼
┌─────────────────┐
│  React Frontend │
│  - Update State │
│  - Show Toast   │
└─────────────────┘
```

### Authentication Flow

```
┌──────────────┐
│ User Login   │
│ Page         │
└──────┬───────┘
       │ 1. Enter email/password
       ▼
┌──────────────────┐
│ Firebase Auth    │ (or JWT in PHP)
│ - Verify         │
│ - Generate Token │
└──────┬───────────┘
       │ 2. Token returned
       ▼
┌──────────────────┐
│ Fetch User Data  │
│ from Database    │
│ (users table)    │
└──────┬───────────┘
       │ 3. User data with role
       ▼
┌──────────────────┐
│ Store in Context │
│ - currentUser    │
│ - userData       │
│ - role           │
└──────┬───────────┘
       │ 4. Redirect to Dashboard
       ▼
┌──────────────────┐
│ Dashboard Page   │
│ (Role-based UI)  │
└──────────────────┘

For subsequent requests:
┌──────────────────┐
│ API Request      │
└──────┬───────────┘
       │ Include: Authorization: Bearer <token>
       ▼
┌──────────────────┐
│ Backend Verify   │
│ - Decode token   │
│ - Check expiry   │
│ - Fetch user role│
└──────┬───────────┘
       │ Authorized
       ▼
┌──────────────────┐
│ Process Request  │
└──────────────────┘
```

---

## 👥 User Roles & Permissions

### Role Hierarchy

```
┌─────────────────────────────────────────────────────────┐
│                        ADMIN                             │
│              (Full System Access)                        │
│  - Manage all users                                      │
│  - Access all modules                                    │
│  - View all data                                         │
│  - System configuration                                  │
└─────────────────────────────────────────────────────────┘
                            │
        ┌───────────────────┼───────────────────┐
        │                   │                   │
┌───────▼────────┐  ┌──────▼──────┐  ┌────────▼────────┐
│    DOCTOR      │  │ RECEPTIONIST │  │   CASHIER       │
│                │  │              │  │                 │
│ - Create visits│  │ - Register   │  │ - Process       │
│ - Order labs   │  │   patients   │  │   payments      │
│ - Prescribe    │  │ - Book appts │  │ - View invoices │
│ - View records │  │ - Generate   │  │                 │
│                │  │   invoices   │  │                 │
└────────────────┘  └──────────────┘  └─────────────────┘
        │                   │
        │                   │
┌───────▼────────┐  ┌──────▼──────────┐
│  LAB TECH      │  │   PHARMACIST    │
│                │  │                 │
│ - View lab     │  │ - View          │
│   orders       │  │   prescriptions │
│ - Upload       │  │ - Dispense meds │
│   results      │  │ - Manage        │
│                │  │   inventory     │
└────────────────┘  └─────────────────┘
        │
┌───────▼────────┐
│     NURSE      │
│                │
│ - View patients│
│ - View visits  │
│ - Assist care  │
└────────────────┘
```

### Detailed Permissions Matrix

| Module | Admin | Doctor | Receptionist | Lab Tech | Pharmacist | Nurse | Cashier |
|--------|-------|--------|--------------|----------|------------|-------|---------|
| **Dashboard** |
| View Dashboard | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| View Sales Stats | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Users** |
| View Users | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Create Users | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Edit Users | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Delete Users | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Patients** |
| View Patients | ✅ | ✅ | ✅ | ❌ | ❌ | ✅ | ❌ |
| Register Patient | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Edit Patient | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Delete Patient | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Appointments** |
| View Appointments | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Book Appointment | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Edit Appointment | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Cancel Appointment | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ |
| **Visits (EMR)** |
| View Visits | ✅ | ✅ | ❌ | ❌ | ❌ | ✅ | ❌ |
| Create Visit | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Edit Visit | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Delete Visit | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Lab Tests** |
| View Lab Orders | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ |
| Create Lab Order | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Upload Lab Result | ✅ | ❌ | ❌ | ✅ | ❌ | ❌ | ❌ |
| View Lab Results | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ |
| **Pharmacy** |
| View Prescriptions | ✅ | ✅ | ❌ | ❌ | ✅ | ❌ | ❌ |
| Create Prescription | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Dispense Medication | ✅ | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ |
| View Inventory | ✅ | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ |
| Manage Inventory | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Billing** |
| View Invoices | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ | ✅ |
| Create Invoice | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Process Payment | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ |
| View Sales Reports | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |

---

## 💾 Database Schema (MySQL/PHP Implementation)

### Entity Relationship Diagram

```
┌─────────────┐         ┌──────────────┐         ┌─────────────┐
│   users     │         │   patients   │         │appointments │
│─────────────│         │──────────────│         │─────────────│
│ id (PK)     │         │ id (PK)      │    ┌────│ id (PK)     │
│ email       │         │ first_name   │    │    │ patient_id  │
│ role        │    ┌────│ last_name    │◄───┘    │ doctor_id   │
│ name        │    │    │ dob          │         │ appt_date   │
│ phone       │    │    │ gender       │         │ appt_time   │
│ ...         │    │    │ phone        │         │ status      │
└──────┬──────┘    │    │ ...          │         │ ...         │
       │           │    └──────┬───────┘         └──────┬──────┘
       │           │           │                        │
       │           │           │                        │
       ▼           │           ▼                        ▼
┌─────────────┐    │    ┌──────────────┐         ┌─────────────┐
│   visits    │    │    │   invoices   │         │  lab_orders │
│─────────────│    │    │──────────────│         │─────────────│
│ id (PK)     │    │    │ id (PK)      │         │ id (PK)     │
│ patient_id  │────┘    │ patient_id   │         │ patient_id  │
│ doctor_id   │         │ visit_id     │         │ doctor_id   │
│ visit_date  │         │ invoice_no   │         │ visit_id    │
│ diagnosis   │         │ total        │         │ test_name   │
│ notes       │         │ status       │         │ status      │
│ ...         │         │ ...          │         │ ...         │
└──────┬──────┘         └──────────────┘         └──────┬──────┘
       │                                                 │
       │                                                 ▼
       │                                          ┌─────────────┐
       │                                          │ lab_results │
       │                                          │─────────────│
       │                                          │ id (PK)     │
       │                                          │ order_id    │
       │                                          │ results     │
       │                                          │ file_url    │
       │                                          │ ...         │
       │                                          └─────────────┘
       │
       ▼
┌──────────────────┐
│  prescriptions   │
│──────────────────│
│ id (PK)          │
│ patient_id       │
│ doctor_id        │
│ visit_id         │
│ medications      │
│ status           │
│ ...              │
└──────────────────┘
```

### Table Definitions (MySQL)

#### 1. users
```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    uid VARCHAR(255) UNIQUE NOT NULL,  -- Firebase UID or generated
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255),  -- If using PHP auth
    role ENUM('admin', 'doctor', 'receptionist', 'cashier', 'nurse', 'lab_technician', 'pharmacist') NOT NULL,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    specialization VARCHAR(255),  -- For doctors
    date_of_birth DATE,
    national_id_number VARCHAR(50),
    employee_number VARCHAR(50) UNIQUE,
    photo_url TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_employee_number (employee_number)
);
```

#### 2. patients
```sql
CREATE TABLE patients (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_number VARCHAR(50) UNIQUE NOT NULL,
    first_name VARCHAR(255) NOT NULL,
    last_name VARCHAR(255) NOT NULL,
    date_of_birth DATE NOT NULL,
    gender ENUM('male', 'female', 'other') NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(255),
    address TEXT,
    emergency_contact_name VARCHAR(255),
    emergency_contact_phone VARCHAR(20),
    emergency_contact_relationship VARCHAR(100),
    blood_group VARCHAR(10),
    allergies TEXT,  -- JSON or comma-separated
    medical_history TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_patient_number (patient_number),
    INDEX idx_name (first_name, last_name),
    INDEX idx_phone (phone)
);
```

#### 3. appointments
```sql
CREATE TABLE appointments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    patient_name VARCHAR(255) NOT NULL,
    doctor_id INT NOT NULL,
    doctor_name VARCHAR(255) NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    reason TEXT,
    status ENUM('scheduled', 'completed', 'cancelled') DEFAULT 'scheduled',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_patient (patient_id),
    INDEX idx_doctor (doctor_id),
    INDEX idx_date (appointment_date),
    INDEX idx_status (status)
);
```

#### 4. visits
```sql
CREATE TABLE visits (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    patient_name VARCHAR(255) NOT NULL,
    doctor_id INT NOT NULL,
    doctor_name VARCHAR(255) NOT NULL,
    appointment_id INT,
    visit_date DATE NOT NULL,
    chief_complaint TEXT,
    diagnosis TEXT,
    medical_notes TEXT,
    vital_signs JSON,  -- {bp, temp, pulse, weight}
    consultation_fee DECIMAL(10, 2) DEFAULT 0.00,
    status ENUM('in_progress', 'completed') DEFAULT 'completed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL,
    INDEX idx_patient (patient_id),
    INDEX idx_doctor (doctor_id),
    INDEX idx_date (visit_date)
);
```

#### 5. lab_orders
```sql
CREATE TABLE lab_orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    patient_name VARCHAR(255) NOT NULL,
    doctor_id INT NOT NULL,
    doctor_name VARCHAR(255) NOT NULL,
    visit_id INT,
    test_name VARCHAR(255) NOT NULL,
    test_type VARCHAR(100),
    priority ENUM('normal', 'urgent') DEFAULT 'normal',
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
    notes TEXT,
    order_date DATE NOT NULL,
    cost DECIMAL(10, 2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (visit_id) REFERENCES visits(id) ON DELETE SET NULL,
    INDEX idx_patient (patient_id),
    INDEX idx_status (status),
    INDEX idx_date (order_date)
);
```

#### 6. lab_results
```sql
CREATE TABLE lab_results (
    id INT PRIMARY KEY AUTO_INCREMENT,
    lab_order_id INT NOT NULL,
    patient_id INT NOT NULL,
    patient_name VARCHAR(255) NOT NULL,
    test_name VARCHAR(255) NOT NULL,
    results TEXT,
    result_file_url TEXT,
    technician_id INT NOT NULL,
    technician_name VARCHAR(255) NOT NULL,
    result_date DATE NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (lab_order_id) REFERENCES lab_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (technician_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_order (lab_order_id),
    INDEX idx_patient (patient_id)
);
```

#### 7. prescriptions
```sql
CREATE TABLE prescriptions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    patient_name VARCHAR(255) NOT NULL,
    doctor_id INT NOT NULL,
    doctor_name VARCHAR(255) NOT NULL,
    visit_id INT,
    medications JSON,  -- Array of {name, dosage, frequency, duration, quantity, instructions}
    status ENUM('pending', 'dispensed', 'cancelled') DEFAULT 'pending',
    prescription_date DATE NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (visit_id) REFERENCES visits(id) ON DELETE SET NULL,
    INDEX idx_patient (patient_id),
    INDEX idx_status (status),
    INDEX idx_date (prescription_date)
);
```

#### 8. pharmacy_inventory
```sql
CREATE TABLE pharmacy_inventory (
    id INT PRIMARY KEY AUTO_INCREMENT,
    medication_name VARCHAR(255) NOT NULL,
    generic_name VARCHAR(255),
    dosage VARCHAR(100),
    form VARCHAR(100),  -- Tablet, Syrup, Injection, etc.
    manufacturer VARCHAR(255),
    quantity INT NOT NULL DEFAULT 0,
    reorder_level INT DEFAULT 100,
    unit_price DECIMAL(10, 2) NOT NULL,
    expiry_date DATE,
    batch_number VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (medication_name),
    INDEX idx_quantity (quantity)
);
```

#### 9. invoices
```sql
CREATE TABLE invoices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    invoice_number VARCHAR(50) UNIQUE NOT NULL,
    patient_id INT NOT NULL,
    patient_name VARCHAR(255) NOT NULL,
    visit_id INT,
    invoice_date DATE NOT NULL,
    items JSON,  -- Array of {description, quantity, unit_price, total}
    subtotal DECIMAL(10, 2) NOT NULL,
    tax DECIMAL(10, 2) DEFAULT 0.00,
    discount DECIMAL(10, 2) DEFAULT 0.00,
    total DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'paid', 'cancelled') DEFAULT 'pending',
    payment_method ENUM('cash', 'card', 'insurance', 'other'),
    amount_paid DECIMAL(10, 2),
    payment_date DATE,
    paid_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (visit_id) REFERENCES visits(id) ON DELETE SET NULL,
    INDEX idx_invoice_number (invoice_number),
    INDEX idx_patient (patient_id),
    INDEX idx_status (status),
    INDEX idx_date (invoice_date)
);
```

#### 10. invitations
```sql
CREATE TABLE invitations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) NOT NULL,
    role ENUM('admin', 'doctor', 'receptionist', 'cashier', 'nurse', 'lab_technician', 'pharmacist') NOT NULL,
    token VARCHAR(255) UNIQUE NOT NULL,
    status ENUM('pending', 'accepted', 'expired') DEFAULT 'pending',
    expires_at TIMESTAMP NOT NULL,
    created_by INT NOT NULL,
    used_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_email (email),
    INDEX idx_status (status)
);
```

---

## 🔌 API Endpoints

### Base URL
```
Development: http://localhost:5000/api
Production: https://your-domain.com/api
```

### Authentication Header
```
Authorization: Bearer <JWT_TOKEN>
```

---

### 1. Authentication Endpoints

#### POST /api/auth/register
**Description:** Register a new user (Admin only)

**Required Role:** `admin`

**Request Body:**
```json
{
  "email": "doctor@example.com",
  "password": "securePassword123",
  "role": "doctor",
  "name": "Dr. John Doe",
  "phone": "+1234567890",
  "specialization": "Cardiology",
  "dateOfBirth": "1980-01-01",
  "nationalIdNumber": "12345678",
  "employeeNumber": "EMP001"
}
```

**Response:**
```json
{
  "success": true,
  "message": "User registered successfully",
  "userId": "user_id",
  "user": {
    "id": "user_id",
    "email": "doctor@example.com",
    "role": "doctor",
    "name": "Dr. John Doe"
  }
}
```

---

#### POST /api/auth/login
**Description:** User login

**Required Role:** None (Public)

**Request Body:**
```json
{
  "email": "doctor@example.com",
  "password": "securePassword123"
}
```

**Response:**
```json
{
  "success": true,
  "token": "jwt_token_here",
  "user": {
    "id": "user_id",
    "email": "doctor@example.com",
    "role": "doctor",
    "name": "Dr. John Doe"
  }
}
```

---

#### GET /api/auth/user/:id
**Description:** Get user by ID

**Required Role:** Authenticated user

**Response:**
```json
{
  "success": true,
  "user": {
    "id": "user_id",
    "email": "doctor@example.com",
    "role": "doctor",
    "name": "Dr. John Doe",
    "phone": "+1234567890",
    "specialization": "Cardiology"
  }
}
```

---

### 2. Patient Endpoints

#### GET /api/patients
**Description:** Get all patients

**Required Role:** `admin`, `receptionist`, `doctor`, `nurse`

**Query Parameters:**
- `search` (optional): Search by name or patient number

**Response:**
```json
{
  "success": true,
  "patients": [
    {
      "id": 1,
      "patientNumber": "P001",
      "firstName": "Jane",
      "lastName": "Doe",
      "dateOfBirth": "1990-05-15",
      "gender": "female",
      "phone": "+1234567890",
      "email": "jane@example.com"
    }
  ]
}
```

---

#### GET /api/patients/:id

**Response:**
```json
{
  "success": true,
  "patient": {
    "id": 1,
    "patientNumber": "P001",
    "firstName": "Jane",
    "lastName": "Doe",
    "dateOfBirth": "1990-05-15",
    "gender": "female",
    "phone": "+1234567890",
    "email": "jane@example.com",
    "address": "123 Main St",
    "bloodGroup": "O+",
    "allergies": ["Penicillin"],
    "medicalHistory": "Diabetes"
  }
}
```

---

#### POST /api/patients
**Description:** Register new patient

**Required Role:** `admin`, `receptionist`

**Request Body:**
```json
{
  "firstName": "Jane",
  "lastName": "Doe",
  "dateOfBirth": "1990-05-15",
  "gender": "female",
  "phone": "+1234567890",
  "email": "jane@example.com",
  "address": "123 Main St",
  "emergencyContact": {
    "name": "John Doe",
    "phone": "+1234567890",
    "relationship": "Spouse"
  },
  "bloodGroup": "O+",
  "allergies": ["Penicillin"],
  "medicalHistory": "Diabetes"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Patient registered successfully",
  "patientId": 1
}
```

---

#### PUT /api/patients/:id
**Description:** Update patient information

**Required Role:** `admin`, `receptionist`

**Request Body:** Same as POST (partial updates allowed)

**Response:**
```json
{
  "success": true,
  "message": "Patient updated successfully"
}
```

---

#### GET /api/patients/:id/visits
**Description:** Get patient visit history

**Required Role:** `admin`, `doctor`, `nurse`

**Response:**
```json
{
  "success": true,
  "visits": [
    {
      "id": 1,
      "visitDate": "2024-01-15",
      "doctorName": "Dr. John Doe",
      "diagnosis": "Hypertension",
      "consultationFee": 50.00
    }
  ]
}
```

---

### 3. Appointment Endpoints

#### GET /api/appointments
**Description:** Get all appointments with optional filters

**Required Role:** `admin`, `receptionist`, `doctor`

**Query Parameters:**
- `status` (optional): scheduled, completed, cancelled
- `doctorId` (optional): Filter by doctor
- `date` (optional): Filter by date (YYYY-MM-DD)

**Response:**
```json
{
  "success": true,
  "appointments": [
    {
      "id": 1,
      "patientId": 1,
      "patientName": "Jane Doe",
      "doctorId": 2,
      "doctorName": "Dr. John Doe",
      "appointmentDate": "2024-01-15",
      "appointmentTime": "10:00",
      "reason": "Regular checkup",
      "status": "scheduled"
    }
  ]
}
```

---

#### GET /api/appointments/:id
**Description:** Get appointment by ID

**Required Role:** `admin`, `receptionist`, `doctor`

**Response:**
```json
{
  "success": true,
  "appointment": {
    "id": 1,
    "patientId": 1,
    "patientName": "Jane Doe",
    "doctorId": 2,
    "doctorName": "Dr. John Doe",
    "appointmentDate": "2024-01-15",
    "appointmentTime": "10:00",
    "reason": "Regular checkup",
    "status": "scheduled",
    "notes": "Patient requested morning slot"
  }
}
```

---

#### POST /api/appointments
**Description:** Create new appointment

**Required Role:** `admin`, `receptionist`

**Request Body:**
```json
{
  "patientId": 1,
  "patientName": "Jane Doe",
  "doctorId": 2,
  "doctorName": "Dr. John Doe",
  "appointmentDate": "2024-01-15",
  "appointmentTime": "10:00",
  "reason": "Regular checkup",
  "notes": "Patient requested morning slot"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Appointment created successfully",
  "appointmentId": 1
}
```

---

#### PUT /api/appointments/:id
**Description:** Update appointment

**Required Role:** `admin`, `receptionist`, `doctor`

**Request Body:** Same as POST (partial updates allowed)

**Response:**
```json
{
  "success": true,
  "message": "Appointment updated successfully"
}
```

---

#### PATCH /api/appointments/:id/cancel
**Description:** Cancel appointment

**Required Role:** `admin`, `receptionist`

**Response:**
```json
{
  "success": true,
  "message": "Appointment cancelled successfully"
}
```

---

### 4. Visit (EMR) Endpoints

#### GET /api/doctors/visits
**Description:** Get all visit records

**Required Role:** `admin`, `doctor`, `nurse`

**Query Parameters:**
- `patientId` (optional): Filter by patient
- `doctorId` (optional): Filter by doctor
- `date` (optional): Filter by date

**Response:**
```json
{
  "success": true,
  "visits": [
    {
      "id": 1,
      "patientId": 1,
      "patientName": "Jane Doe",
      "doctorId": 2,
      "doctorName": "Dr. John Doe",
      "visitDate": "2024-01-15",
      "chiefComplaint": "Chest pain",
      "diagnosis": "Angina pectoris",
      "consultationFee": 50.00,
      "status": "completed"
    }
  ]
}
```

---

#### GET /api/doctors/visits/:id
**Description:** Get visit by ID

**Required Role:** `admin`, `doctor`, `nurse`

**Response:**
```json
{
  "success": true,
  "visit": {
    "id": 1,
    "patientId": 1,
    "patientName": "Jane Doe",
    "doctorId": 2,
    "doctorName": "Dr. John Doe",
    "visitDate": "2024-01-15",
    "chiefComplaint": "Chest pain",
    "diagnosis": "Angina pectoris",
    "medicalNotes": "Patient reports intermittent chest pain...",
    "vitalSigns": {
      "bloodPressure": "120/80",
      "temperature": "98.6",
      "pulse": "72",
      "weight": "70"
    },
    "consultationFee": 50.00,
    "status": "completed"
  }
}
```

---

#### POST /api/doctors/visits
**Description:** Create visit record (EMR)

**Required Role:** `admin`, `doctor`

**Request Body:**
```json
{
  "patientId": 1,
  "patientName": "Jane Doe",
  "doctorId": 2,
  "doctorName": "Dr. John Doe",
  "appointmentId": 1,
  "visitDate": "2024-01-15",
  "chiefComplaint": "Chest pain",
  "diagnosis": "Angina pectoris",
  "medicalNotes": "Patient reports intermittent chest pain...",
  "vitalSigns": {
    "bloodPressure": "120/80",
    "temperature": "98.6",
    "pulse": "72",
    "weight": "70"
  },
  "consultationFee": 50.00
}
```

**Response:**
```json
{
  "success": true,
  "message": "Visit record created successfully",
  "visitId": 1
}
```

---

#### PUT /api/doctors/visits/:id
**Description:** Update visit record

**Required Role:** `admin`, `doctor`

**Request Body:** Same as POST (partial updates allowed)

**Response:**
```json
{
  "success": true,
  "message": "Visit updated successfully"
}
```

---

### 5. Lab Test Endpoints

#### GET /api/labs/orders
**Description:** Get all lab orders

**Required Role:** `admin`, `doctor`, `lab_technician`

**Query Parameters:**
- `status` (optional): pending, completed, cancelled
- `patientId` (optional): Filter by patient

**Response:**
```json
{
  "success": true,
  "labOrders": [
    {
      "id": 1,
      "patientId": 1,
      "patientName": "Jane Doe",
      "doctorId": 2,
      "doctorName": "Dr. John Doe",
      "visitId": 1,
      "testName": "Complete Blood Count",
      "testType": "Blood Test",
      "priority": "normal",
      "status": "pending",
      "orderDate": "2024-01-15",
      "cost": 25.00
    }
  ]
}
```

---

#### GET /api/labs/orders/:id
**Description:** Get lab order by ID

**Required Role:** `admin`, `doctor`, `lab_technician`

**Response:**
```json
{
  "success": true,
  "labOrder": {
    "id": 1,
    "patientId": 1,
    "patientName": "Jane Doe",
    "doctorId": 2,
    "doctorName": "Dr. John Doe",
    "visitId": 1,
    "testName": "Complete Blood Count",
    "testType": "Blood Test",
    "priority": "normal",
    "status": "pending",
    "notes": "Fasting required",
    "orderDate": "2024-01-15",
    "cost": 25.00
  }
}
```

---

#### POST /api/labs/orders
**Description:** Create lab order

**Required Role:** `admin`, `doctor`

**Request Body:**
```json
{
  "patientId": 1,
  "patientName": "Jane Doe",
  "doctorId": 2,
  "doctorName": "Dr. John Doe",
  "visitId": 1,
  "testName": "Complete Blood Count",
  "testType": "Blood Test",
  "priority": "normal",
  "notes": "Fasting required",
  "orderDate": "2024-01-15",
  "cost": 25.00
}
```

**Response:**
```json
{
  "success": true,
  "message": "Lab order created successfully",
  "labOrderId": 1
}
```

---

#### PUT /api/labs/orders/:id
**Description:** Update lab order

**Required Role:** `admin`, `doctor`, `lab_technician`

**Request Body:** Same as POST (partial updates allowed)

**Response:**
```json
{
  "success": true,
  "message": "Lab order updated successfully"
}
```

---

#### POST /api/labs/results
**Description:** Upload lab result

**Required Role:** `admin`, `lab_technician`

**Request Body:**
```json
{
  "labOrderId": 1,
  "patientId": 1,
  "patientName": "Jane Doe",
  "testName": "Complete Blood Count",
  "results": "All values within normal range",
  "resultFileURL": "https://example.com/results/file.pdf",
  "technicianId": 3,
  "technicianName": "Lab Tech Name",
  "resultDate": "2024-01-16",
  "notes": "No abnormalities detected"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Lab result uploaded successfully",
  "resultId": 1
}
```

---

#### GET /api/labs/results/:orderId
**Description:** Get lab results for an order

**Required Role:** `admin`, `doctor`, `lab_technician`

**Response:**
```json
{
  "success": true,
  "labResult": {
    "id": 1,
    "labOrderId": 1,
    "patientId": 1,
    "patientName": "Jane Doe",
    "testName": "Complete Blood Count",
    "results": "All values within normal range",
    "resultFileURL": "https://example.com/results/file.pdf",
    "technicianName": "Lab Tech Name",
    "resultDate": "2024-01-16"
  }
}
```

---

### 6. Pharmacy Endpoints

#### GET /api/pharmacy/prescriptions
**Description:** Get all prescriptions

**Required Role:** `admin`, `doctor`, `pharmacist`

**Query Parameters:**
- `status` (optional): pending, dispensed, cancelled
- `patientId` (optional): Filter by patient

**Response:**
```json
{
  "success": true,
  "prescriptions": [
    {
      "id": 1,
      "patientId": 1,
      "patientName": "Jane Doe",
      "doctorId": 2,
      "doctorName": "Dr. John Doe",
      "visitId": 1,
      "medications": [
        {
          "name": "Aspirin",
          "dosage": "100mg",
          "frequency": "Once daily",
          "duration": "30 days",
          "quantity": 30,
          "instructions": "Take with food"
        }
      ],
      "status": "pending",
      "prescriptionDate": "2024-01-15"
    }
  ]
}
```

---

#### GET /api/pharmacy/prescriptions/:id
**Description:** Get prescription by ID

**Required Role:** `admin`, `doctor`, `pharmacist`

**Response:**
```json
{
  "success": true,
  "prescription": {
    "id": 1,
    "patientId": 1,
    "patientName": "Jane Doe",
    "doctorId": 2,
    "doctorName": "Dr. John Doe",
    "visitId": 1,
    "medications": [
      {
        "name": "Aspirin",
        "dosage": "100mg",
        "frequency": "Once daily",
        "duration": "30 days",
        "quantity": 30,
        "instructions": "Take with food"
      }
    ],
    "status": "pending",
    "prescriptionDate": "2024-01-15",
    "notes": "Continue for one month"
  }
}
```

---

#### POST /api/pharmacy/prescriptions
**Description:** Create prescription

**Required Role:** `admin`, `doctor`

**Request Body:**
```json
{
  "patientId": 1,
  "patientName": "Jane Doe",
  "doctorId": 2,
  "doctorName": "Dr. John Doe",
  "visitId": 1,
  "medications": [
    {
      "name": "Aspirin",
      "dosage": "100mg",
      "frequency": "Once daily",
      "duration": "30 days",
      "quantity": 30,
      "instructions": "Take with food"
    }
  ],
  "prescriptionDate": "2024-01-15",
  "notes": "Continue for one month"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Prescription created successfully",
  "prescriptionId": 1
}
```

---

#### PUT /api/pharmacy/prescriptions/:id
**Description:** Update prescription (e.g., mark as dispensed)

**Required Role:** `admin`, `doctor`, `pharmacist`

**Request Body:**
```json
{
  "status": "dispensed"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Prescription updated successfully"
}
```

---

#### GET /api/pharmacy/inventory
**Description:** Get pharmacy inventory

**Required Role:** `admin`, `pharmacist`

**Response:**
```json
{
  "success": true,
  "inventory": [
    {
      "id": 1,
      "medicationName": "Aspirin",
      "genericName": "Acetylsalicylic Acid",
      "dosage": "100mg",
      "form": "Tablet",
      "manufacturer": "Pharma Co",
      "quantity": 1000,
      "reorderLevel": 100,
      "unitPrice": 0.50,
      "expiryDate": "2025-12-31",
      "batchNumber": "BATCH001"
    }
  ]
}
```

---

#### POST /api/pharmacy/inventory
**Description:** Add inventory item

**Required Role:** `admin`

**Request Body:**
```json
{
  "medicationName": "Aspirin",
  "genericName": "Acetylsalicylic Acid",
  "dosage": "100mg",
  "form": "Tablet",
  "manufacturer": "Pharma Co",
  "quantity": 1000,
  "reorderLevel": 100,
  "unitPrice": 0.50,
  "expiryDate": "2025-12-31",
  "batchNumber": "BATCH001"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Inventory item added successfully",
  "inventoryId": 1
}
```

---

#### PUT /api/pharmacy/inventory/:id
**Description:** Update inventory item

**Required Role:** `admin`

**Request Body:** Same as POST (partial updates allowed)

**Response:**
```json
{
  "success": true,
  "message": "Inventory updated successfully"
}
```

---

### 7. Billing Endpoints

#### GET /api/billing/invoices
**Description:** Get all invoices

**Required Role:** `admin`, `receptionist`, `cashier`

**Query Parameters:**
- `status` (optional): pending, paid, cancelled
- `patientId` (optional): Filter by patient

**Response:**
```json
{
  "success": true,
  "invoices": [
    {
      "id": 1,
      "invoiceNumber": "INV001",
      "patientId": 1,
      "patientName": "Jane Doe",
      "visitId": 1,
      "invoiceDate": "2024-01-15",
      "total": 75.00,
      "status": "pending"
    }
  ]
}
```

---

#### GET /api/billing/invoices/:id
**Description:** Get invoice by ID

**Required Role:** `admin`, `receptionist`, `cashier`

**Response:**
```json
{
  "success": true,
  "invoice": {
    "id": 1,
    "invoiceNumber": "INV001",
    "patientId": 1,
    "patientName": "Jane Doe",
    "visitId": 1,
    "invoiceDate": "2024-01-15",
    "items": [
      {
        "description": "Consultation Fee",
        "quantity": 1,
        "unitPrice": 50.00,
        "total": 50.00
      },
      {
        "description": "Lab Test - CBC",
        "quantity": 1,
        "unitPrice": 25.00,
        "total": 25.00
      }
    ],
    "subtotal": 75.00,
    "tax": 0.00,
    "discount": 0.00,
    "total": 75.00,
    "status": "pending"
  }
}
```

---

#### POST /api/billing/invoices
**Description:** Create invoice

**Required Role:** `admin`, `receptionist`

**Request Body:**
```json
{
  "patientId": 1,
  "patientName": "Jane Doe",
  "visitId": 1,
  "invoiceDate": "2024-01-15",
  "items": [
    {
      "description": "Consultation Fee",
      "quantity": 1,
      "unitPrice": 50.00,
      "total": 50.00
    },
    {
      "description": "Lab Test - CBC",
      "quantity": 1,
      "unitPrice": 25.00,
      "total": 25.00
    }
  ],
  "subtotal": 75.00,
  "tax": 0.00,
  "discount": 0.00,
  "total": 75.00
}
```

**Response:**
```json
{
  "success": true,
  "message": "Invoice created successfully",
  "invoiceId": 1,
  "invoiceNumber": "INV001"
}
```

---

#### PUT /api/billing/invoices/:id
**Description:** Update invoice

**Required Role:** `admin`, `receptionist`, `cashier`

**Request Body:** Same as POST (partial updates allowed)

**Response:**
```json
{
  "success": true,
  "message": "Invoice updated successfully"
}
```

---

#### PATCH /api/billing/invoices/:id/pay
**Description:** Mark invoice as paid

**Required Role:** `admin`, `cashier`

**Request Body:**
```json
{
  "paymentMethod": "cash",
  "amountPaid": 75.00,
  "paymentDate": "2024-01-15"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Invoice marked as paid successfully"
}
```

---

### 8. User Management Endpoints

#### GET /api/users
**Description:** Get all users

**Required Role:** `admin`

**Response:**
```json
{
  "success": true,
  "users": [
    {
      "id": 1,
      "email": "doctor@example.com",
      "role": "doctor",
      "name": "Dr. John Doe",
      "phone": "+1234567890",
      "specialization": "Cardiology",
      "employeeNumber": "EMP001",
      "isActive": true
    }
  ]
}
```

---

#### GET /api/users/:id
**Description:** Get user by ID

**Required Role:** `admin`

**Response:**
```json
{
  "success": true,
  "user": {
    "id": 1,
    "email": "doctor@example.com",
    "role": "doctor",
    "name": "Dr. John Doe",
    "phone": "+1234567890",
    "specialization": "Cardiology",
    "employeeNumber": "EMP001",
    "isActive": true
  }
}
```

---

#### PUT /api/users/:id
**Description:** Update user

**Required Role:** `admin`

**Request Body:**
```json
{
  "name": "Dr. John Smith",
  "phone": "+1234567890",
  "specialization": "Neurology"
}
```

**Response:**
```json
{
  "success": true,
  "message": "User updated successfully"
}
```

---

#### DELETE /api/users/:id
**Description:** Delete user (soft delete - set isActive to false)

**Required Role:** `admin`

**Response:**
```json
{
  "success": true,
  "message": "User deleted successfully"
}
```

---

### 9. Invitation Endpoints

#### POST /api/invitations
**Description:** Create user invitation

**Required Role:** `admin`

**Request Body:**
```json
{
  "email": "newuser@example.com",
  "role": "doctor"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Invitation created successfully",
  "invitationId": 1,
  "token": "unique_token_string",
  "invitationLink": "http://localhost:3000/invite/unique_token_string"
}
```

---

#### GET /api/invitations
**Description:** Get all invitations

**Required Role:** `admin`

**Response:**
```json
{
  "success": true,
  "invitations": [
    {
      "id": 1,
      "email": "newuser@example.com",
      "role": "doctor",
      "status": "pending",
      "expiresAt": "2024-01-22T10:00:00Z",
      "createdAt": "2024-01-15T10:00:00Z"
    }
  ]
}
```

---

#### GET /api/invitations/:token
**Description:** Validate invitation token

**Required Role:** None (Public)

**Response:**
```json
{
  "success": true,
  "invitation": {
    "email": "newuser@example.com",
    "role": "doctor",
    "status": "pending"
  }
}
```

---

#### POST /api/invitations/:token/accept
**Description:** Accept invitation and create account

**Required Role:** None (Public)

**Request Body:**
```json
{
  "password": "securePassword123",
  "name": "Dr. New User",
  "phone": "+1234567890"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Account created successfully",
  "userId": 1
}
```

---

## 🔄 System Workflows

### Workflow 1: Patient Registration to Billing

```
┌─────────────────────────────────────────────────────────────┐
│                  COMPLETE PATIENT WORKFLOW                   │
└─────────────────────────────────────────────────────────────┘

Step 1: PATIENT REGISTRATION
┌──────────────────┐
│  Receptionist    │
└────────┬─────────┘
         │ 1. Fill patient registration form
         │    - Personal details
         │    - Contact information
         │    - Medical history
         ▼
┌──────────────────┐
│ POST /api/       │
│ patients         │
└────────┬─────────┘
         │ 2. Patient created in database
         │    - Auto-generate patient number (P001)
         │    - Store all patient data
         ▼
┌──────────────────┐
│  Patient Record  │
│  Created         │
└──────────────────┘

Step 2: APPOINTMENT BOOKING
┌──────────────────┐
│  Receptionist    │
└────────┬─────────┘
         │ 3. Book appointment
         │    - Select patient
         │    - Select doctor
         │    - Choose date/time
         ▼
┌──────────────────┐
│ POST /api/       │
│ appointments     │
└────────┬─────────┘
         │ 4. Appointment created
         │    - Status: scheduled
         │    - Linked to patient & doctor
         ▼
┌──────────────────┐
│  Appointment     │
│  Scheduled       │
└──────────────────┘

Step 3: DOCTOR CONSULTATION
┌──────────────────┐
│     Doctor       │
└────────┬─────────┘
         │ 5. View appointment
         │ 6. Create visit record (EMR)
         │    - Chief complaint
         │    - Diagnosis
         │    - Medical notes
         │    - Vital signs
         │    - Consultation fee
         ▼
┌──────────────────┐
│ POST /api/       │
│ doctors/visits   │
└────────┬─────────┘
         │ 7. Visit record created
         │    - Linked to patient & appointment
         │    - Status: completed
         ▼
┌──────────────────┐
│  Visit Record    │
│  (EMR) Created   │
└────────┬─────────┘
         │
         ├─────────────────────────────────┐
         │                                 │
         ▼                                 ▼
Step 4a: LAB TEST (if needed)    Step 4b: PRESCRIPTION (if needed)
┌──────────────────┐              ┌──────────────────┐
│     Doctor       │              │     Doctor       │
└────────┬─────────┘              └────────┬─────────┘
         │ 8a. Order lab test              │ 8b. Create prescription
         │    - Test name                  │    - Medications
         │    - Test type                  │    - Dosage
         │    - Priority                   │    - Instructions
         ▼                                 ▼
┌──────────────────┐              ┌──────────────────┐
│ POST /api/labs/  │              │ POST /api/       │
│ orders           │              │ pharmacy/        │
└────────┬─────────┘              │ prescriptions    │
         │ 9a. Lab order created  └────────┬─────────┘
         │    - Status: pending            │ 9b. Prescription created
         ▼                                 │    - Status: pending
┌──────────────────┐                      ▼
│  Lab Technician  │              ┌──────────────────┐
└────────┬─────────┘              │   Pharmacist     │
         │ 10a. View pending order└────────┬─────────┘
         │ 11a. Upload result              │ 10b. View prescription
         │    - Test results               │ 11b. Dispense medication
         │    - PDF/Image file             │    - Update inventory
         ▼                                 │    - Mark as dispensed
┌──────────────────┐                      ▼
│ POST /api/labs/  │              ┌──────────────────┐
│ results          │              │ PUT /api/        │
└────────┬─────────┘              │ pharmacy/        │
         │ 12a. Result uploaded   │ prescriptions/:id│
         │    - Order status:     └────────┬─────────┘
         │      completed                  │ 12b. Status: dispensed
         ▼                                 ▼
┌──────────────────┐              ┌──────────────────┐
│  Lab Result      │              │  Medication      │
│  Available       │              │  Dispensed       │
└──────────────────┘              └──────────────────┘

Step 5: BILLING & PAYMENT
┌──────────────────┐
│  Receptionist    │
└────────┬─────────┘
         │ 13. Generate invoice
         │     - Consultation fee (from visit)
         │     - Lab test cost (from lab order)
         │     - Medication cost (from prescription)
         ▼
┌──────────────────┐
│ POST /api/       │
│ billing/invoices │
└────────┬─────────┘
         │ 14. Invoice created
         │     - Auto-generate invoice number (INV001)
         │     - Calculate total
         │     - Status: pending
         ▼
┌──────────────────┐
│     Cashier      │
└────────┬─────────┘
         │ 15. Process payment
         │     - Payment method (cash/card)
         │     - Amount paid
         ▼
┌──────────────────┐
│ PATCH /api/      │
│ billing/         │
│ invoices/:id/pay │
└────────┬─────────┘
         │ 16. Invoice marked as paid
         │     - Status: paid
         │     - Payment date recorded
