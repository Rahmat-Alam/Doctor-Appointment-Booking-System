<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}
include '../db.php';

// Handle record deletion
if (isset($_POST['delete_record'])) {
    $record_id = intval($_POST['record_id']);
    
    // Check if record exists
    $check = $conn->prepare("SELECT file FROM medical_records WHERE id = ?");
    $check->bind_param("i", $record_id);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $file_path = $row['file'];
        
        // Delete file if exists
        if (!empty($file_path)) {
            $full_path = "../uploads/" . $file_path;
            if (file_exists($full_path)) {
                unlink($full_path);
            }
        }
        
        // Delete record from database
        $delete = $conn->prepare("DELETE FROM medical_records WHERE id = ?");
        $delete->bind_param("i", $record_id);
        if ($delete->execute()) {
            $success = "Medical record deleted successfully!";
        } else {
            $error = "Failed to delete record.";
        }
        $delete->close();
    } else {
        $error = "Record not found.";
    }
    $check->close();
}

// Fetch all medical records with patient and doctor information
$sql = "SELECT mr.*, p.full_name AS patient_name, p.email AS patient_email, 
               d.name AS doctor_name, d.specialization 
        FROM medical_records mr 
        LEFT JOIN patients p ON mr.patient_id = p.id 
        LEFT JOIN doctors d ON mr.doctor_id = d.id 
        ORDER BY mr.record_date DESC, mr.created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Medical Records</title>
    <link rel="stylesheet" href="admin_style.css">
    <style>
        .records-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .records-count {
            background: #e3f2fd;
            color: #1976d2;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }
        
        a.download-link {
            color: #0077b6;
            text-decoration: none;
            background: #e3f2fd;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        a.download-link:hover {
            color: white;
            background: #0077b6;
            transform: translateY(-1px);
        }
        
        .record-description {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .record-description:hover {
            white-space: normal;
            overflow: visible;
            background: white;
            position: relative;
            z-index: 10;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-radius: 6px;
            padding: 10px;
        }
        
        .no-records {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .no-records-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        .btn-delete {
            background: #dc3545;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-delete:hover {
            background: #c82333;
            transform: translateY(-1px);
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
        
        .delete-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .delete-modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 30px;
            border-radius: 15px;
            width: 400px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        }
        
        .delete-modal h3 {
            color: #dc3545;
            margin-bottom: 20px;
        }
        
        .delete-modal-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 25px;
        }
        
        .btn-cancel {
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }
        
        .btn-confirm-delete {
            background: #dc3545;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Medical Records</h1>
        <div class="logout">
            <a href="../logout.php">Logout</a>
        </div>
    </div>

    <div class="nav">
        <a href="admin_dashboard.php">Dashboard</a>
        <a href="add_admin.php">Add Admin</a>
        <a href="add_doctor.php">Add Doctor</a>
        <a href="add_patient.php">Add Patient</a>
        <a href="manage_doctors.php">Manage Doctors</a>
        <a href="manage_patients.php">Manage Patients</a>
        <a href="manage_appointments.php">Manage Appointments</a>
        <a href="manage_departments.php">Manage Departments</a>
        <a href="view_medical_records.php" class="active">View Medical Records</a>
    </div>

    <div class="content">
        <h2>Medical Records</h2>
        
        <div class="card">
            <div class="records-header">
                <h3>🩺 All Medical Records</h3>
                <div class="records-count">
                    📋 <?= $result->num_rows ?> Record<?= $result->num_rows != 1 ? 's' : '' ?>
                </div>
            </div>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            
            <?php if ($result->num_rows === 0): ?>
                <div class="no-records">
                    <div class="no-records-icon">📋</div>
                    <h4>No Medical Records Found</h4>
                    <p>Medical records will appear here once doctors add them.</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>📅 Date</th>
                                <th>👤 Patient</th>
                                <th>👨‍⚕️ Doctor</th>
                                <th>🏥 Specialization</th>
                                <th>📝 Description</th>
                                <th>📎 File</th>
                                <th>🛠️ Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= date('M d, Y', strtotime($row['record_date'])) ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($row['patient_name'] ?? 'N/A') ?></strong><br>
                                        <small><?= htmlspecialchars($row['patient_email'] ?? 'N/A') ?></small>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($row['doctor_name'] ?? 'N/A') ?></strong>
                                    </td>
                                    <td><?= htmlspecialchars($row['specialization'] ?? 'N/A') ?></td>
                                    <td>
                                        <div class="record-description" title="<?= htmlspecialchars($row['description']) ?>">
                                            <?= htmlspecialchars($row['description']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['file'])): ?>
                                            <a class="download-link" href="../uploads/<?= htmlspecialchars($row['file']) ?>" target="_blank">
                                                📥 Download
                                            </a>
                                        <?php else: ?>
                                            <span style="color: #999;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn-delete" onclick="confirmDelete(<?= $row['id'] ?>)">
                                            🗑️ Delete
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="delete-modal">
        <div class="delete-modal-content">
            <h3>🗑️ Delete Medical Record</h3>
            <p>Are you sure you want to delete this medical record? This action cannot be undone.</p>
            <form method="POST" id="deleteForm">
                <input type="hidden" name="record_id" id="recordId">
                <input type="hidden" name="delete_record" value="1">
                <div class="delete-modal-buttons">
                    <button type="button" class="btn-cancel" onclick="closeDeleteModal()">Cancel</button>
                    <button type="submit" class="btn-confirm-delete">Delete Record</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function confirmDelete(recordId) {
            document.getElementById('recordId').value = recordId;
            document.getElementById('deleteModal').style.display = 'block';
        }
        
        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            var modal = document.getElementById('deleteModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html> 