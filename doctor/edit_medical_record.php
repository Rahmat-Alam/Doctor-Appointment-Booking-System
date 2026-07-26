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

$success = $error = '';
$record = null;

// Get record ID from URL
if (isset($_GET['id'])) {
    $record_id = intval($_GET['id']);
    
    // Fetch record details
    $stmt = $conn->prepare("SELECT mr.*, p.full_name AS patient_name 
                           FROM medical_records mr 
                           LEFT JOIN patients p ON mr.patient_id = p.id 
                           WHERE mr.id = ? AND mr.doctor_id = ?");
    $stmt->bind_param("ii", $record_id, $doctor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $record = $result->fetch_assoc();
    } else {
        header("Location: view_medical_records.php");
        exit();
    }
    $stmt->close();
} else {
    header("Location: view_medical_records.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $record_date = $_POST['record_date'];
    $description = trim($_POST['description']);
    $file_path = $record['file']; // Keep existing file by default
    
    // Validate inputs
    if ($record_date && !empty($description)) {
        // Handle file upload if new file is provided
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
                // Delete old file if exists
                if (!empty($record['file'])) {
                    $old_file = '../uploads/' . $record['file'];
                    if (file_exists($old_file)) {
                        unlink($old_file);
                    }
                }
                
                $file_name = 'medical_record_' . $record['patient_id'] . '_' . time() . '.' . $file_extension;
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
            $stmt = $conn->prepare("UPDATE medical_records SET record_date = ?, description = ?, file = ? WHERE id = ? AND doctor_id = ?");
            $stmt->bind_param("sssii", $record_date, $description, $file_path, $record_id, $doctor_id);
            
            if ($stmt->execute()) {
                $success = "Medical record updated successfully!";
                // Refresh record data
                $stmt = $conn->prepare("SELECT mr.*, p.full_name AS patient_name 
                                       FROM medical_records mr 
                                       LEFT JOIN patients p ON mr.patient_id = p.id 
                                       WHERE mr.id = ? AND mr.doctor_id = ?");
                $stmt->bind_param("ii", $record_id, $doctor_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $record = $result->fetch_assoc();
            } else {
                $error = "Failed to update medical record.";
            }
            $stmt->close();
        }
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
    <title>Edit Medical Record</title>
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
            max-width: 800px;
        }
        
        .card h3 {
            color: #0077b6;
            font-size: 24px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .patient-info {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .patient-info h4 {
            color: #1976d2;
            margin: 0 0 10px 0;
        }
        
        .patient-info p {
            margin: 5px 0;
            color: #333;
        }
        
        form label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
            color: #333;
        }
        
        input[type="date"], textarea {
            width: 100%;
            padding: 12px;
            margin-top: 5px;
            border-radius: 8px;
            border: 1px solid #ddd;
            font-size: 16px;
            box-sizing: border-box;
        }
        
        textarea {
            height: 150px;
            resize: vertical;
        }
        
        input[type="file"] {
            padding: 10px;
            border: 2px dashed #ddd;
            border-radius: 8px;
            background: #f9f9f9;
            margin-top: 5px;
        }
        
        .current-file {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 8px;
            margin: 10px 0;
            border-left: 4px solid #0077b6;
        }
        
        .current-file a {
            color: #0077b6;
            text-decoration: none;
            font-weight: 500;
        }
        
        .current-file a:hover {
            text-decoration: underline;
        }
        
        .form-buttons {
            display: flex;
            gap: 15px;
            margin-top: 25px;
        }
        
        .btn-save, .btn-cancel {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-save {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40,167,69,0.3);
        }
        
        .btn-cancel {
            background: #6c757d;
            color: white;
        }
        
        .btn-cancel:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        
        .alert {
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .logout {
            margin-top: 30px;
            display: inline-block;
            color: red;
            font-weight: bold;
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
            <h3>✏️ Edit Medical Record</h3>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            
            <div class="patient-info">
                <h4>👤 Patient Information</h4>
                <p><strong>Name:</strong> <?= htmlspecialchars($record['patient_name'] ?? 'N/A') ?></p>
                <p><strong>Record ID:</strong> #<?= $record['id'] ?></p>
            </div>
            
            <form method="POST" enctype="multipart/form-data">
                <label for="record_date">📅 Record Date:</label>
                <input type="date" id="record_date" name="record_date" value="<?= $record['record_date'] ?>" required>
                
                <label for="description">📝 Description:</label>
                <textarea id="description" name="description" placeholder="Enter medical record description..." required><?= htmlspecialchars($record['description']) ?></textarea>
                
                <label for="medical_file">📎 Medical File (Optional):</label>
                <?php if (!empty($record['file'])): ?>
                    <div class="current-file">
                        <strong>Current File:</strong> 
                        <a href="../uploads/<?= htmlspecialchars($record['file']) ?>" target="_blank">
                            📥 <?= basename($record['file']) ?>
                        </a>
                        <br><small>Upload a new file to replace the current one</small>
                    </div>
                <?php endif; ?>
                <input type="file" id="medical_file" name="medical_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.txt">
                
                <div class="form-buttons">
                    <button type="submit" class="btn-save">💾 Save Changes</button>
                    <a href="view_medical_records.php" class="btn-cancel">❌ Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html> 