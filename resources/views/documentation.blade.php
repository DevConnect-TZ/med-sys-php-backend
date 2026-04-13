<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Management System API Documentation</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link
  rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"/>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333;
            line-height: 1.6;
            padding-bottom: 80px;
        }

        .header {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            color: white;
            padding: 30px 15px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }

        .header p {
            font-size: 1.1em;
            opacity: 0.95;
        }

        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .nav-menu {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .nav-menu h2 {
            color: #4CAF50;
            margin-bottom: 15px;
            font-size: 1.5em;
        }

        .nav-links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
        }

        .nav-links a {
            display: block;
            padding: 12px 15px;
            background: linear-gradient(135deg, #18d422ff 0%, #18d422ff 100%);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s ease;
            text-align: center;
            font-weight: 500;
        }

        .nav-links a:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .section {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .section h2 {
            color: #4CAF50;
            font-size: 2em;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid #4CAF50;
        }

        .section h3 {
            color: #667eea;
            font-size: 1.5em;
            margin-top: 25px;
            margin-bottom: 15px;
        }

        .section h4 {
            color: #555;
            font-size: 1.1em;
            margin-top: 15px;
            margin-bottom: 10px;
        }

        .endpoint {
            background: #f8f9fa;
            border-left: 4px solid #4CAF50;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }

        .endpoint-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .method {
            padding: 8px 16px;
            border-radius: 5px;
            font-weight: bold;
            font-size: 0.9em;
            color: white;
            text-transform: uppercase;
        }

        .method.get { background: #61affe; }
        .method.post { background: #49cc90; }
        .method.put { background: #fca130; }
        .method.patch { background: #50e3c2; }
        .method.delete { background: #f93e3e; }

        .endpoint-url {
            font-family: 'Courier New', monospace;
            font-size: 1.1em;
            color: #333;
            font-weight: 600;
        }

        .code-block {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 20px;
            border-radius: 5px;
            overflow-x: auto;
            margin: 15px 0;
            position: relative;
        }

        .code-block pre {
            margin: 0;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
            line-height: 1.5;
        }

        .copy-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #4CAF50;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.85em;
            transition: all 0.3s ease;
        }

        .copy-btn:hover {
            background: #45a049;
            transform: scale(1.05);
        }

        .copy-btn.copied {
            background: #2196F3;
        }

        .description {
            color: #555;
            margin: 10px 0;
            line-height: 1.8;
        }

        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
            margin: 5px 5px 5px 0;
        }

        .badge.auth { background: #ffe0b2; color: #e65100; }
        .badge.admin { background: #ffcdd2; color: #c62828; }
        .badge.public { background: #c8e6c9; color: #2e7d32; }

        .table-container {
            overflow-x: auto;
            margin: 20px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        table th {
            background: #4CAF50;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
        }

        table td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
        }

        table tr:hover {
            background: #f5f5f5;
        }

        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }

        .warning-box {
            background: #fff3e0;
            border-left: 4px solid #ff9800;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }

        .success-box {
            background: #e8f5e9;
            border-left: 4px solid #4CAF50;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }

        .footer {
            background: #333;
            color: white;
            text-align: center;
            padding: 20px;
            position: fixed;
            width: 100%;
            bottom: 0;
            box-shadow: 0 -4px 6px rgba(0,0,0,0.1);
        }

        .footer a {
            color: #4CAF50;
            text-decoration: none;
            font-weight: 600;
        }

        .footer a:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .header h1 {
                font-size: 1.8em;
            }

            .nav-links {
                grid-template-columns: 1fr;
            }

            .endpoint-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .section {
                padding: 20px;
            }
        }

        .json-key { color: #92d192; }
        .json-string { color: #f0c674; }
        .json-number { color: #cc99cd; }
        .json-boolean { color: #de935f; }
    </style>
</head>
<body>
    <div class="header">
        <h1> <i class="fa fa-hospital-o" ></i>
Hospital Management System</h1>
    </div>

    <div class="container">
        <!-- Navigation Menu -->
        <div class="nav-menu">
            <h2> Quick Navigation</h2>
            <div class="nav-links">
                <a href="#base-url">Base URL</a>
                <a href="#authentication">Authentication</a>
                <a href="#users">Users & Invitations</a>
                <a href="#patients">Patients</a>
                <a href="#appointments">Appointments</a>
                <a href="#visits">Visits (EMR)</a>
                <a href="#lab">Lab Tests</a>
                <a href="#pharmacy">Pharmacy</a>
                <a href="#billing">Billing</a>
                <a href="#responses">Response Formats</a>
                <a href="#roles">Roles & Permissions</a>
            </div>
        </div>

        <!-- Base URL Section -->
        <div class="section" id="base-url">
            <h2> Base URL</h2>
            <div class="info-box">
                <strong>Production:</strong> <code>http://localhost:8000/api</code><br>
                <strong>API Version:</strong> 1.0<br>
                <strong>Status:</strong> Production Ready
            </div>
        </div>

        <!-- Authentication Section -->
        <div class="section" id="authentication">
            <h2> Authentication</h2>
            
            <div class="endpoint">
                <div class="endpoint-header">
                    <span class="method post">POST</span>
                    <span class="endpoint-url">/auth/login</span>
                    <span class="badge public">Public</span>
                </div>
                <p class="description">Authenticate user and receive access token for subsequent API requests.</p>
                
                <h4>Request Body:</h4>
                <div class="code-block">
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                    <pre>{
  "email": "admin@hospital.com",
  "password": "SecurePass123!@"
}</pre>
                </div>

                <h4>Success Response (200):</h4>
                <div class="code-block">
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                    <pre>{
  "success": true,
  "token": "1|abcdefghijklmnopqrstuvwxyz123456",
  "user": {
    "id": 1,
    "name": "Admin User",
    "email": "admin@hospital.com",
    "role": "admin",
    "phone": "+1234567890"
  }
}</pre>
                </div>

                <h4>cURL Example:</h4>
                <div class="code-block">
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                    <pre>curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@hospital.com","password":"SecurePass123!@"}'</pre>
                </div>
            </div>

            <div class="warning-box">
                <strong>Important:</strong> All subsequent requests must include the token in the Authorization header:<br>
                <code>Authorization: Bearer YOUR_TOKEN_HERE</code>
            </div>
        </div>

        <!-- Users & Invitations Section -->
        <div class="section" id="users">
            <h2>Users & Invitations</h2>

            <h3>List Users</h3>
            <div class="endpoint">
                <div class="endpoint-header">
                    <span class="method get">GET</span>
                    <span class="endpoint-url">/users</span>
                    <span class="badge auth">Auth Required</span>
                    <span class="badge admin">Admin Only</span>
                </div>
                <p class="description">Retrieve a list of all users in the system.</p>

                <h4>cURL Example:</h4>
                <div class="code-block">
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                    <pre>curl http://localhost:8000/api/users \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"</pre>
                </div>
            </div>

            <h3>Create Invitation</h3>
            <div class="endpoint">
                <div class="endpoint-header">
                    <span class="method post">POST</span>
                    <span class="endpoint-url">/invitations</span>
                    <span class="badge auth">Auth Required</span>
                    <span class="badge admin">Admin Only</span>
                </div>
                <p class="description">Create an invitation for a new user to join the system.</p>

                <h4>Request Body:</h4>
                <div class="code-block">
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                    <pre>{
  "email": "newdoctor@hospital.com",
  "role": "doctor"
}</pre>
                </div>

                <h4>Available Roles:</h4>
                <div class="info-box">
                    <code>admin</code>, <code>doctor</code>, <code>receptionist</code>, <code>cashier</code>, 
                    <code>nurse</code>, <code>lab_technician</code>, <code>pharmacist</code>
                </div>

                <h4>cURL Example:</h4>
                <div class="code-block">
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                    <pre>curl -X POST http://localhost:8000/api/invitations \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{"email":"newdoctor@hospital.com","role":"doctor"}'</pre>
                </div>
            </div>

            <h3>Accept Invitation</h3>
            <div class="endpoint">
                <div class="endpoint-header">
                    <span class="method post">POST</span>
                    <span class="endpoint-url">/invitations/{token}/accept</span>
                    <span class="badge public">Public</span>
                </div>
                <p class="description">Accept an invitation and complete user registration.</p>

                <h4>Request Body:</h4>
                <div class="code-block">
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                    <pre>{
  "password": "SecurePass123!@",
  "name": "Dr. John Doe",
  "phone": "+1234567890"
}</pre>
                </div>

                <h4>Password Requirements:</h4>
                <div class="warning-box">
                    • Minimum 8 characters<br>
                    • At least one uppercase letter<br>
                    • At least one lowercase letter<br>
                    • At least one number<br>
                    • At least one special character (!@#$%^&*)
                </div>

                <h4>cURL Example:</h4>
                <div class="code-block">
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                    <pre>curl -X POST http://localhost:8000/api/invitations/abc123xyz789/accept \
  -H "Content-Type: application/json" \
  -d '{"password":"SecurePass123!@","name":"Dr. John Doe","phone":"+1234567890"}'</pre>
                </div>
            </div>
        </div>

        <!-- Patients Section -->
        <div class="section" id="patients">
            <h2>🏥 Patients</h2>

            <h3>List Patients</h3>
            <div class="endpoint">
                <div class="endpoint-header">
                    <span class="method get">GET</span>
                    <span class="endpoint-url">/patients</span>
                    <span class="badge auth">Auth Required</span>
                </div>
                <p class="description">Retrieve a paginated list of patients with optional search and filtering.</p>

                <h4>Query Parameters:</h4>
                <div class="info-box">
                    <code>?search=John</code> - Search by name, email, or phone<br>
                    <code>?per_page=20</code> - Results per page (default: 15)<br>
                    <code>?page=2</code> - Page number
                </div>

                <h4>cURL Example:</h4>
                <div class="code-block">
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                    <pre>curl "http://localhost:8000/api/patients?search=John&per_page=15" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"</pre>
                </div>
            </div>

            <h3>Create Patient</h3>
            <div class="endpoint">
                <div class="endpoint-header">
                    <span class="method post">POST</span>
                    <span class="endpoint-url">/patients</span>
                    <span class="badge auth">Auth Required</span>
                </div>
                <p class="description">Register a new patient in the system.</p>

                <h4>Request Body:</h4>
                <div class="code-block">
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                    <pre>{
  "first_name": "John",
  "last_name": "Doe",
  "date_of_birth": "1990-01-15",
  "gender": "male",
  "phone": "+1234567890",
  "email": "john@example.com",
  "blood_group": "O+",
  "allergies": ["Penicillin", "Peanuts"],
  "medical_history": "Diabetes Type 2, Hypertension"
}</pre>
                </div>

                <h4>cURL Example:</h4>
                <div class="code-block">
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                    <pre>curl -X POST http://localhost:8000/api/patients \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{"first_name":"John","last_name":"Doe","date_of_birth":"1990-01-15","gender":"male","phone":"+1234567890"}'</pre>
                </div>
            </div>

            <h3>Get Patient Visits</h3>
            <div class="endpoint">
                <div class="endpoint-header">
                    <span class="method get">GET</span>
                    <span class="endpoint-url">/patients/{id}/visits</span>
                    <span class="badge auth">Auth Required</span>
                </div>
                <p class="description">Retrieve all visit records for a specific patient.</p>

                <h4>cURL Example:</h4>
                <div class="code-block">
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                    <pre>curl http://localhost:8000/api/patients/1/visits \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"</pre>
                </div>
            </div>
        </div>

        <!-- Appointments Section -->
        <div class="section" id="appointments">
            <h2>📅 Appointments</h2>

            <h3>List Appointments</h3>
            <div class="endpoint">
                <div class="endpoint-header">
                    <span class="method get">GET</span>
                    <span class="endpoint-url">/appointments</span>
                    <span class="badge auth">Auth Required</span>
                </div>
                <p class="description">Retrieve a paginated list of appointments with optional filtering.</p>

                <h4>Query Parameters:</h4>
                <div class="info-box">
                    <code>?status=scheduled</code> - Filter by status (scheduled, completed, cancelled)<br>
                    <code>?doctor_id=2</code> - Filter by doctor<br>
                    <code>?per_page=15</code> - Results per page
                </div>

                <h4>cURL Example:</h4>
                <div class="code-block">
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                    <pre>curl "http://localhost:8000/api/appointments?status=scheduled&doctor_id=2" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"</pre>
                </div>
            </div>

            <h3>Create Appointment</h3>
            <div class="endpoint">
                <div class="endpoint-header">
                    <span class="method post">POST</span>
                    <span class="endpoint-url">/appointments</span>
                    <span class="badge auth">Auth Required</span>
                </div>
                <p class="description">Schedule a new appointment for a patient.</p>

                <h4>Request Body:</h4>
                <div class="code-block">
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                    <pre>{
  "patient_id": 1,
  "doctor_id": 2,
  "appointment_date": "2025-01-20",
  "appointment_time": "14:30",
  "reason": "Regular checkup",
  "notes": "Patient prefers morning appointments"
}</pre>
                </div>

                <h4>cURL Example:</h4>
                <div class="code-block">
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                    <pre>curl -X POST http://localhost:8000/api/appointments \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{"patient_id":1,"doctor_id":2,"appointment_date":"2025-01-20","appointment_time":"14:30","reason":"Regular checkup"}'</pre>
                </div>
            </div>

            <h3>Cancel Appointment</h3>
            <div class="endpoint">
                <div class="endpoint-header">
                    <span class="method patch">PATCH</span>
                    <span class="endpoint-url">/appointments/{id}/cancel</span>
                    <span class="badge auth">Auth Required</span>
                </div>
                <p class="description">Cancel an existing appointment.</p>

                <h4>cURL Example:</h4>
                <div class="code-block">
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                    <pre>curl -X PATCH http://localhost:8000/api/appointments/1/cancel \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"</pre>
                </div>
            </div>
        </div>

        <!-- Visits Section -->
        <div class="section" id="visits">
            <h2>🩺 Visits (EMR)</h2>

            <h3>List Visits</h3>
            <div class="endpoint">
                <div class="endpoint-header">
                    <span class="method get">GET</span>
                    <span class="endpoint-url">/visits</span>
                    <span class="badge auth">Auth Required</span>
                </div>
                <p class="description">Retrieve a paginated list of visit records.</p>

                <h4>Query Parameters:</h4>
                <div class="info-box">
                    <code>?patient_id=1</code> - Filter by patient<br>
                    <code>?doctor_id=2</code> - Filter by doctor<br>
                    <code>?per_page=15</code> - Results per page
                </div>

                <h4>cURL Example:</h4>
                <div class="code-block">
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                    <pre>curl "http://localhost:8000/api/visits?patient_id=1&doctor_id=2" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"</pre>
                </div>
            </div>

            <h3>Create Visit Record</h3>
            <div class="endpoint">
                <div class="endpoint-header">
                    <span class="method post">POST</span>
                    <span class="endpoint-url">/visits</span>
                    <span class="badge auth">Auth Required</span>
                </div>
                <p class="description">Create a new visit record (Electronic Medical Record).</p>

                <h4>Request Body:</h4>
                <div class="code-block">
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                    <pre>{
  "patient_id": 1,
  "doctor_id": 2,
  "appointment_id": 1,
  "visit_date": "2025-01-20",
  "chief_complaint": "Chest pain",
  "diagnosis": "Angina pectoris",
  "medical_notes": "Patient reports intermittent chest pain...",
  "vital_signs": {
    "blood_pressure": "120/80",
    "temperature": "98.6",
    "pulse": "72",
    "weight": "70"
  },
  "consultation_fee": 50.00
}</pre>
                </div>

                <h4>cURL Example:</h4>
                <div class="code-block">
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                    <pre>curl -X POST http://localhost:8000/api/visits \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{"patient_id":1,"doctor_id":2,"visit_date":"2025-01-20","chief_complaint":"Chest pain","diagnosis":"Angina pectoris","consultation_fee":50.00}'</pre>
                </div>
            </div>
        </div>

        <!-- Lab Tests Section -->
        <div class="section" id="lab">
            <h2>🧪 Lab Tests</h2>

            <h3>Create Lab Order</h3>
            <div class="endpoint">
                <div class="endpoint-header">
                    <span class="method post">POST</span>
                    <span class="endpoint-url">/labs/orders</span>
                    <span class="badge auth">Auth Required</span>
                </div>
                <p class="description">Create a new laboratory test order.</p>

                <h4>Request Body:</h4>
                <div class="code-block">
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                    <pre>{
  "patient_id": 1,
  "doctor_id": 2,
  "visit_id": 1,
  "test_name": "Complete Blood Count",
  "test_type": "Blood Test",
  "priority": "normal",
  "notes": "Fasting required",
  "order_date": "2025-01-20",
  "cost": 25.00
}</pre>
                </div>

                <h4>cURL Example:</h4>
                <div class="code-block">
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                    <pre>curl -X POST http://localhost:8000/api/labs/orders \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{"patient_id":1,"doctor_id":2,"test_name":"Complete Blood Count","test_type":"Blood Test","priority":"normal","cost":25.00}'</pre>
                </div>
            </div>

            <h3>Upload Lab Result</h3>
            <div class="endpoint">
                <div class="endpoint-header">
                    <span class="method post">POST</span>
                    <span class="endpoint-url">/labs/results</span>
                    <span class="badge auth">Auth Required</span>
                </div>
                <p class="description">Upload laboratory test results.</p>

                <h4>Request Body:</h4>
                <div class="code-block">
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                    <pre>{
  "lab_order_id": 1,
  "results": "All values within normal range",
  "result_file_url": "https://example.com/results/file.pdf",
  "result_date": "2025-01-21",
  "notes": "No abnormalities detected"
}</pre>
                </div>

                <h4>cURL Example:</h4>
                <div class="code-block">
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                    <pre>curl -X POST http://localhost:8000/api/labs/results \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{"lab_order_id":1,"results":"All values within normal range","result_date":"2025-01-21"}'</pre>
                </div>
            </div>
        </div>

        <!-- Pharmacy Section -->
        <div class="section" id="pharmacy">
            <h2> Pharmacy</h2>

            <h3>Create Prescription</h3>
            <div class="endpoint">
                <div class="endpoint-header">
                    <span class="method post">POST</span>
                    <span class="endpoint-url">/pharmacy/prescriptions</span>
                    <span class="badge auth">Auth Required</span>
                </div>
                <p class="description">Create a new prescription for a patient.</p>

                <h4>Request Body:</h4>
                <div class="code-block">
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                    <pre>{
  "patient_id": 1,
  "doctor_id": 2,
  "visit_id": 1,
  "prescription_date": "2025-01-20",
  "medications": [
    {
      "name": "Aspirin",
      "dosage": "500mg",
      "frequency": "Twice daily",
      "duration": "7 days",
      "instructions": "Take with food"
    },
    {
      "name": "Metformin",
      "dosage": "850mg",
      "frequency": "Three times daily",
      "duration": "30 days",
      "instructions": "With meals"
    }
  ],
  "notes": "Follow up after 2 weeks"
}</pre>
                </div>

                <h4>cURL Example:</h4>
                <div class="code-block">
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                    <pre>curl -X POST http://localhost:8000/api/pharmacy/prescriptions \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{"patient_id":1,"doctor_id":2,"prescription_date":"2025-01-20","medications":[{"name":"Aspirin","dosage":"500mg","frequency":"Twice daily","duration":"7 days"}]}'</pre>
                </div>
            </div>

            <h3>List Prescriptions</h3>
            <div class="endpoint">
                <div class="endpoint-header">
                    <span class="method get">GET</span>
                    <span class="endpoint-url">/pharmacy/prescriptions</span>
                    <span class="badge auth">Auth Required</span>
                </div>
                <p class="description">Retrieve a paginated list of prescriptions with optional filtering.</p>

                <h4>Query Parameters:</h4>
                <div class="info-box">
                    <code>?status=pending</code> - Filter by status (pending, dispensed, cancelled)<br>
                    <code>?patient_id=1</code> - Filter by patient<br>
                    <code>?per_page=15</code> - Results per page
                </div>

                <h4>cURL Example:</h4>
                <div class="code-block">
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                    <pre>curl "http://localhost:8000/api/pharmacy/prescriptions?status=pending&per_page=15" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"</pre>
                </div>
            </div>

            <h3>Update Prescription Status</h3>
            <div class="endpoint">
                <div class="endpoint-header">
                    <span class="method put">PUT</span>
                    <span class="endpoint-url">/pharmacy/prescriptions/{id}</span>
                    <span class="badge auth">Auth Required</span>
                </div>
                <p class="description">Update prescription status (mark as dispensed, cancelled, etc).</p>

                <h4>Request Body:</h4>
                <div class="code-block">
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                    <pre>{
  "status": "dispensed",
  "notes": "Dispensed on 2025-01-21"
}</pre>
                </div>

                <h4>cURL Example:</h4>
                <div class="code-block">
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                    <pre>curl -X PUT http://localhost:8000/api/pharmacy/prescriptions/1 \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{"status":"dispensed","notes":"Dispensed on 2025-01-21"}'</pre>
                </div>

                <h4>✉️ Email Notification:</h4>
                <div class="success-box">
                    When status changes to <strong>dispensed</strong>, the system automatically sends a <strong>Prescription Ready</strong> email to the patient with medication details and pickup instructions.
                </div>
            </div>

            <h3>Get Pharmacy Inventory</h3>
            <div class="endpoint">
                <div class="endpoint-header">
                    <span class="method get">GET</span>
                    <span class="endpoint-url">/pharmacy/inventory</span>
                    <span class="badge auth">Auth Required</span>
                </div>
                <p class="description">Retrieve current pharmacy inventory with stock levels.</p>

                <h4>Query Parameters:</h4>
                <div class="info-box">
                    <code>?low_stock=true</code> - Show only low stock items<br>
                    <code>?expired=true</code> - Show expired items<br>
                    <code>?per_page=15</code> - Results per page
                </div>

                <h4>cURL Example:</h4>
                <div class="code-block">
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                    <pre>curl "http://localhost:8000/api/pharmacy/inventory?low_stock=true" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"</pre>
                </div>
            </div>
        </div>

        <!-- Billing Section -->
        <div class="section" id="billing">
            <h2> Billing & Invoices</h2>

            <h3>Create Invoice</h3>
            <div class="endpoint">
                <div class="endpoint-header">
                    <span class="method post">POST</span>
                    <span class="endpoint-url">/invoices</span>
                    <span class="badge auth">Auth Required</span>
                </div>
                <p class="description">Create a new invoice for a patient visit or services.</p>

                <h4>Request Body:</h4>
                <div class="code-block">
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                    <pre>{
  "patient_id": 1,
  "visit_id": 1,
  "invoice_date": "2025-01-20",
  "items": [
    {
      "description": "Doctor Consultation",
      "quantity": 1,
      "unit_price": 50.00,
      "amount": 50.00
    },
    {
      "description": "Blood Test (CBC)",
      "quantity": 1,
      "unit_price": 25.00,
      "amount": 25.00
    },
    {
      "description": "Medications",
      "quantity": 1,
      "unit_price": 35.00,
      "amount": 35.00
    }
  ],
  "subtotal": 110.00,
  "tax": 11.00,
  "discount": 0.00,
  "total": 121.00
}</pre>
                </div>

                <h4>cURL Example:</h4>
                <div class="code-block">
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                    <pre>curl -X POST http://localhost:8000/api/invoices \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{"patient_id":1,"visit_id":1,"invoice_date":"2025-01-20","subtotal":110.00,"tax":11.00,"total":121.00}'</pre>
                </div>

                <h4>Email Notification:</h4>
                <div class="success-box">
                    When an invoice is created, the system automatically sends an <strong>Invoice Created</strong> email to the patient with itemized details, total amount due, and payment methods.
                </div>
            </div>

            <h3>List Invoices</h3>
            <div class="endpoint">
                <div class="endpoint-header">
                    <span class="method get">GET</span>
                    <span class="endpoint-url">/invoices</span>
                    <span class="badge auth">Auth Required</span>
                </div>
                <p class="description">Retrieve a paginated list of invoices with optional filtering.</p>

                <h4>Query Parameters:</h4>
                <div class="info-box">
                    <code>?status=pending</code> - Filter by status (pending, paid, cancelled)<br>
                    <code>?patient_id=1</code> - Filter by patient<br>
                    <code>?per_page=15</code> - Results per page
                </div>

                <h4>cURL Example:</h4>
                <div class="code-block">
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                    <pre>curl "http://localhost:8000/api/invoices?status=pending&per_page=15" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"</pre>
                </div>
            </div>

            <h3>Mark Invoice as Paid</h3>
            <div class="endpoint">
                <div class="endpoint-header">
                    <span class="method patch">PATCH</span>
                    <span class="endpoint-url">/invoices/{id}/pay</span>
                    <span class="badge auth">Auth Required</span>
                </div>
                <p class="description">Mark an invoice as paid and record payment details.</p>

                <h4>Request Body:</h4>
                <div class="code-block">
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                    <pre>{
  "payment_method": "card",
  "amount_paid": 121.00,
  "payment_date": "2025-01-20"
}</pre>
                </div>

                <h4>Payment Methods:</h4>
                <div class="info-box">
                    <code>cash</code> | <code>card</code> | <code>insurance</code> | <code>other</code>
                </div>

                <h4>cURL Example:</h4>
                <div class="code-block">
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                    <pre>curl -X PATCH http://localhost:8000/api/invoices/1/pay \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{"payment_method":"card","amount_paid":121.00,"payment_date":"2025-01-20"}'</pre>
                </div>

                <h4> Email Notification:</h4>
                <div class="success-box">
                    When payment is received, the system automatically sends an <strong>Invoice Paid</strong> email to the patient with payment confirmation, date, and receipt reference number.
                </div>
            </div>
        </div>

        <!-- Response Formats Section -->
        <div class="section" id="responses">
            <h2> Response Formats</h2>

            <h3>Success Response Format</h3>
            <div class="code-block">
                <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                <pre>{
  "success": true,
  "message": "Operation completed successfully",
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    ...
  }
}</pre>
            </div>

            <h3>Paginated Response Format</h3>
            <div class="code-block">
                <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                <pre>{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "John Doe",
      ...
    }
  ],
  "meta": {
    "total": 100,
    "per_page": 15,
    "current_page": 1,
    "last_page": 7,
    "from": 1,
    "to": 15
  },
  "links": {
    "first": "http://localhost:8000/api/patients?page=1",
    "last": "http://localhost:8000/api/patients?page=7",
    "next": "http://localhost:8000/api/patients?page=2"
  }
}</pre>
            </div>

            <h3>Error Response Format</h3>
            <div class="code-block">
                <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                <pre>{
  "success": false,
  "message": "Validation error",
  "errors": {
    "email": ["The email field is required"],
    "password": ["The password must be at least 8 characters"]
  }
}</pre>
            </div>

            <h3>HTTP Status Codes</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Meaning</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>200</strong></td>
                            <td>OK</td>
                            <td>Request successful</td>
                        </tr>
                        <tr>
                            <td><strong>201</strong></td>
                            <td>Created</td>
                            <td>Resource created successfully</td>
                        </tr>
                        <tr>
                            <td><strong>400</strong></td>
                            <td>Bad Request</td>
                            <td>Invalid request parameters</td>
                        </tr>
                        <tr>
                            <td><strong>401</strong></td>
                            <td>Unauthorized</td>
                            <td>Missing or invalid authentication token</td>
                        </tr>
                        <tr>
                            <td><strong>403</strong></td>
                            <td>Forbidden</td>
                            <td>User lacks required permissions</td>
                        </tr>
                        <tr>
                            <td><strong>404</strong></td>
                            <td>Not Found</td>
                            <td>Resource does not exist</td>
                        </tr>
                        <tr>
                            <td><strong>422</strong></td>
                            <td>Unprocessable Entity</td>
                            <td>Validation failed</td>
                        </tr>
                        <tr>
                            <td><strong>500</strong></td>
                            <td>Server Error</td>
                            <td>Internal server error</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Roles & Permissions Section -->
        <div class="section" id="roles">
            <h2> Roles & Permissions</h2>

            <h3>Available Roles</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Role</th>
                            <th>Description</th>
                            <th>Key Permissions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Admin</strong></td>
                            <td>Full system access</td>
                            <td>All operations, user management, system configuration</td>
                        </tr>
                        <tr>
                            <td><strong>Doctor</strong></td>
                            <td>Medical professional</td>
                            <td>Create visits, prescriptions, lab orders, view patients</td>
                        </tr>
                        <tr>
                            <td><strong>Receptionist</strong></td>
                            <td>Front desk staff</td>
                            <td>Manage appointments, patient registration</td>
                        </tr>
                        <tr>
                            <td><strong>Cashier</strong></td>
                            <td>Billing staff</td>
                            <td>Create invoices, process payments, view billing</td>
                        </tr>
                        <tr>
                            <td><strong>Nurse</strong></td>
                            <td>Nursing staff</td>
                            <td>Record vital signs, assist doctors, view patient info</td>
                        </tr>
                        <tr>
                            <td><strong>Lab Technician</strong></td>
                            <td>Laboratory staff</td>
                            <td>Create lab orders, upload results, manage tests</td>
                        </tr>
                        <tr>
                            <td><strong>Pharmacist</strong></td>
                            <td>Pharmacy staff</td>
                            <td>Dispense prescriptions, manage inventory</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <h3>Role-Based Access Control</h3>
            <div class="info-box">
                <strong>Note:</strong> All endpoints (except login and invitation acceptance) require authentication. 
                Access is restricted based on user role. Some endpoints require specific roles as documented in each endpoint section.
            </div>

            <h3>Bearer Token Usage</h3>
            <p class="description">Include your authentication token in the Authorization header for all requests:</p>
            
            <div class="code-block">
                <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                <pre>Authorization: Bearer YOUR_TOKEN_HERE</pre>
            </div>

            <h4>Example with Headers:</h4>
            <div class="code-block">
                <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                <pre>curl http://localhost:8000/api/patients \
  -H "Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz123456" \
  -H "Accept: application/json"</pre>
            </div>
        </div>

        <!-- Additional Resources Section -->
        <div class="section">
            <h2> Additional Resources</h2>

            <h3>Error Handling Best Practices</h3>
            <div class="warning-box">
                <strong>Always check the response status:</strong><br>
                • Check the <code>success</code> field in JSON response<br>
                • Inspect HTTP status codes<br>
                • Read error messages for debugging<br>
                • Implement proper error handling in your client application
            </div>

            <h3>Email Notifications</h3>
            <div class="success-box">
                <strong> Automatic Email System:</strong><br>
                The system automatically sends professional emails for:<br>
                • <strong>Appointments:</strong> Confirmation and cancellation notices<br>
                • <strong>Lab Results:</strong> Notifications when results are ready<br>
                • <strong>Prescriptions:</strong> Alerts when ready for pickup<br>
                • <strong>Invoices:</strong> Billing notifications and payment confirmations<br>
                <br>
                Emails are sent to the patient's registered email address with professional HTML templates.
            </div>

            <h3>Rate Limiting</h3>
            <div class="info-box">
                <strong>Current Configuration:</strong><br>
                • Rate limit: 60 requests per minute per user<br>
                • Burst limit: 100 requests per 5 minutes<br>
                • Reset: Automatic reset every minute<br>
                Contact administrator for higher limits if needed.
            </div>

            <h3>Data Validation Rules</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Field Type</th>
                            <th>Rules</th>
                            <th>Example</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Email</strong></td>
                            <td>Valid email format</td>
                            <td>user@example.com</td>
                        </tr>
                        <tr>
                            <td><strong>Phone</strong></td>
                            <td>10-15 digits, optional +</td>
                            <td>+1234567890</td>
                        </tr>
                        <tr>
                            <td><strong>Date</strong></td>
                            <td>YYYY-MM-DD format</td>
                            <td>2025-01-20</td>
                        </tr>
                        <tr>
                            <td><strong>Time</strong></td>
                            <td>HH:MM format (24hr)</td>
                            <td>14:30</td>
                        </tr>
                        <tr>
                            <td><strong>Money</strong></td>
                            <td>Decimal, 2 places max</td>
                            <td>99.99</td>
                        </tr>
                        <tr>
                            <td><strong>Password</strong></td>
                            <td>Min 8 chars, mixed case, special char</td>
                            <td>SecurePass123!@</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <h3>Common Issues & Solutions</h3>
            <div class="warning-box">
                <strong>401 Unauthorized:</strong> Token expired or invalid. Re-authenticate with login endpoint.<br>
                <br>
                <strong>403 Forbidden:</strong> User role lacks permission for this endpoint. Use appropriate user role.<br>
                <br>
                <strong>422 Validation Error:</strong> Check request data against schema. Review error messages in response.<br>
                <br>
                <strong>500 Server Error:</strong> Contact system administrator. Check server logs.
            </div>
        </div>

        <!-- Support Section -->
        <div class="section">
            <h2> Support & Contact</h2>

            <div class="info-box">
                <strong>Documentation Version:</strong> 1.0<br>
                <strong>Last Updated:</strong> January 2026<br>
                <strong>API Status:</strong> Fully Operational<br>
                <strong>Server:</strong> http://localhost:8000<br>
                <br>
                <strong>For Support:</strong><br>
                Email: support@afyamed.com<br>
                 Website: <a href="https://devconnect.site" target="_blank">DevConnectTz</a><br>
                 Phone: +255 (0)XXX XXX XXX
            </div>

            <h3>Getting Started Checklist</h3>
            <div class="success-box">
                 Review this documentation<br>
                 Set up your development environment<br>
                 Get API credentials from administrator<br>
                 Authenticate to get token via login endpoint<br>
                 Test endpoints with provided cURL examples<br>
                 Implement error handling in your application<br>
                 Monitor email notifications<br>
                 Deploy to production<br>
            </div>
        </div>
    </div>

    <div class="footer">
        &copy; <?php echo date('Y'); ?> Hospital Management System. Developed by <a href="https://devconnect.site" target="_blank">DevConnectTz</a>.
    </div>

    <script>
        function copyCode(button) {
            const codeBlock = button.nextElementSibling;
            const codeText = codeBlock.innerText;

            navigator.clipboard.writeText(codeText).then(() => {
                button.classList.add('copied');
                button.innerText = 'Copied!';
                setTimeout(() => {
                    button.classList.remove('copied');
                    button.innerText = 'Copy';
                }, 2000);
            });
        }
    </script>
</body>
</html>
                