<?php
session_start();
if (!isset($_SESSION['doctor_id'])) {
    header("Location: doctor_login.php");
    exit();
}
include '../db.php';
$doctor_id = $_SESSION['doctor_id'];

// Fetch doctor name
$query = $conn->prepare("SELECT name FROM doctors WHERE id = ?");
$query->bind_param("i", $doctor_id);
$query->execute();
$query->bind_result($doctor_name);
$query->fetch();
$query->close();

// Fetch patients who have appointments with this doctor
$patients = $conn->query("SELECT DISTINCT p.id, p.full_name, p.email 
                         FROM patients p 
                         JOIN appointments a ON p.id = a.patient_id 
                         WHERE a.doctor_id = $doctor_id 
                         ORDER BY p.full_name ASC");

// Fetch available dates for this doctor
$avail_dates = [];
$date_result = $conn->query("SELECT DISTINCT date FROM doctor_availability WHERE doctor_id = $doctor_id AND date >= CURDATE() ORDER BY date ASC");
while ($row = $date_result->fetch_assoc()) {
    $avail_dates[] = $row['date'];
}

$success = $error = '';

// Handle medical record submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = intval($_POST['patient_id']);
    $record_date = $_POST['record_date'];
    $description = trim($_POST['description']);
    $file_path = '';
    
    // Validate inputs
    if ($patient_id && $record_date && !empty($description)) {
        // Check if patient has appointment with this doctor
        $check = $conn->prepare("SELECT id FROM appointments WHERE patient_id = ? AND doctor_id = ?");
        $check->bind_param("ii", $patient_id, $doctor_id);
        $check->execute();
        $check->store_result();
        
        if ($check->num_rows > 0) {
            // Handle file upload
            if (isset($_FILES['medical_file']) && $_FILES['medical_file']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../uploads/medical_records/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_info = pathinfo($_FILES['medical_file']['name']);
                $file_extension = strtolower($file_info['extension']);
                
                // Allow only specific file types
                $allowed_extensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'txt'];
                
                if (in_array($file_extension, $allowed_extensions)) {
                    $file_name = 'medical_record_' . $patient_id . '_' . time() . '.' . $file_extension;
                    $file_path = $upload_dir . $file_name;
                    
                    if (move_uploaded_file($_FILES['medical_file']['tmp_name'], $file_path)) {
                        $file_path = 'medical_records/' . $file_name;
                    } else {
                        $error = "Failed to upload file.";
                    }
                } else {
                    $error = "Invalid file type. Allowed: PDF, DOC, DOCX, JPG, PNG, TXT";
                }
            }
            
            if (empty($error)) {
                $stmt = $conn->prepare("INSERT INTO medical_records (patient_id, doctor_id, record_date, description, file) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("iisss", $patient_id, $doctor_id, $record_date, $description, $file_path);
                
                if ($stmt->execute()) {
                    $success = "Medical record added successfully!";
                } else {
                    $error = "Failed to add medical record.";
                }
                $stmt->close();
            }
        } else {
            $error = "You can only add medical records for patients you have appointments with.";
        }
        $check->close();
    } else {
        $error = "Please fill all required fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Medical Record</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(120deg, #f0f8ff 0%, #dff3ec 100%);
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 220px;
            height: 100vh;
            background: #2c3e50;
            padding: 20px;
            color: white;
            position: fixed;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar h2 {
            font-size: 22px;
            margin-bottom: 30px;
        }
        
        .sidebar a {
            color: white;
            display: block;
            margin: 10px 0;
            text-decoration: none;
            padding: 10px;
            border-radius: 6px;
        }
        
        .sidebar a:hover {
            background: #34495e;
        }
        
        .main {
            margin-left: 240px;
            padding: 30px;
        }
        
        .card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            max-width: 600px;
        }
        
        .card h3 {
            color: #0077b6;
            font-size: 20px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        form label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
            color: #333;
        }
        
        select, input[type="date"], textarea {
            width: 100%;
            padding: 12px;
            margin-top: 5px;
            border-radius: 8px;
            border: 1px solid #ddd;
            font-size: 16px;
            box-sizing: border-box;
        }
        
        textarea {
            height: 120px;
            resize: vertical;
        }
        
        input[type="file"] {
            padding: 10px;
            border: 2px dashed #ddd;
            border-radius: 8px;
            background: #f9f9f9;
        }
        
        button {
            margin-top: 20px;
            padding: 12px 25px;
            background: linear-gradient(135deg, #0077b6, #023e8a);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,119,182,0.3);
        }
        
        .success {
            background: #d1fae5;
            color: #065f46;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            border-left: 4px solid #10b981;
        }
        
        .error {
            background: #fee2e2;
            color: #991b1b;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            border-left: 4px solid #ef4444;
        }
        
        .logout {
            margin-top: 30px;
            display: inline-block;
            color: red;
            font-weight: bold;
        }
        
        .file-info {
            background: #e3f2fd;
            padding: 10px;
            border-radius: 6px;
            margin-top: 10px;
            font-size: 14px;
            color: #1976d2;
        }
    </style>
</head>
<body>
<div class="sidebar">
    <h2>Doctor Dashboard</h2>
    <p>👨‍⚕️ <strong><?= htmlspecialchars($doctor_name) ?></strong></p>
    <a href="doctor_dashboard.php">🏠 Dashboard Home</a>
    <a href="view_appointments.php">📅 View Appointments</a>
    <a href="manage_availability.php">🗓️ Manage Availability</a>
    <a href="manage_appointment.php">🛠️ Manage Appointments</a>
    <a href="add_medical_record.php">🩺 Add Medical Record</a>
    <a href="generate_medical_record.php">📋 Generate Medical Record</a>
    <a href="view_medical_records.php">📋 My Medical Records</a>
    <a href="manage_account.php">⚙️ Manage Account</a>
    <a class="logout" href="../logout.php">🚪 Logout</a>
  </div>
    
    <div class="main">
        <div class="card">
            <h3>🩺 Add Medical Record</h3>
            
            <?php if ($success): ?>
                <div class="success"><?= $success ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="error"><?= $error ?></div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <label for="patient_id">Select Patient:</label>
                <select name="patient_id" id="patient_id" required>
                    <option value="">-- Select Patient --</option>
                    <?php if ($patients->num_rows > 0): ?>
                        <?php while ($patient = $patients->fetch_assoc()): ?>
                            <option value="<?= $patient['id'] ?>">
                                <?= htmlspecialchars($patient['full_name']) ?> (<?= htmlspecialchars($patient['email']) ?>)
                            </option>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <option value="" disabled>No patients available</option>
                    <?php endif; ?>
                </select>
                
                <label for="record_date">Record Date:</label>
                <select name="record_date" id="record_date" required>
                    <option value="">-- Select Date --</option>
                    <?php foreach ($avail_dates as $date): ?>
                        <option value="<?= $date ?>"><?= date('d M Y', strtotime($date)) ?></option>
                    <?php endforeach; ?>
                </select>
                
                <label for="description">Description:</label>
                <textarea name="description" id="description" placeholder="Enter all medical details here..." required></textarea>
                <label for="medical_file">Medical File (Optional):</label>
                <input type="file" name="medical_file" id="medical_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.txt">
                <div class="file-info">
                    📎 Allowed file types: PDF, DOC, DOCX, JPG, PNG, TXT (Max 5MB)
                </div>
                <button type="submit">Add Record</button>
            </form>
        </div>
    </div>
</body>
</html> 