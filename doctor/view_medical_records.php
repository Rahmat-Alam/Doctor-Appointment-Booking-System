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

// Handle record deletion
if (isset($_POST['delete_record'])) {
    $record_id = intval($_POST['record_id']);
    
    // Check if record belongs to this doctor
    $check = $conn->prepare("SELECT file FROM medical_records WHERE id = ? AND doctor_id = ?");
    $check->bind_param("ii", $record_id, $doctor_id);
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
        $delete = $conn->prepare("DELETE FROM medical_records WHERE id = ? AND doctor_id = ?");
        $delete->bind_param("ii", $record_id, $doctor_id);
        if ($delete->execute()) {
            $success = "Medical record deleted successfully!";
        } else {
            $error = "Failed to delete record.";
        }
        $delete->close();
    } else {
        $error = "You can only delete your own medical records.";
    }
    $check->close();
}

// Fetch medical records created by this doctor
$sql = "SELECT mr.*, p.full_name AS patient_name, p.email AS patient_email 
        FROM medical_records mr 
        LEFT JOIN patients p ON mr.patient_id = p.id 
        WHERE mr.doctor_id = ? 
        ORDER BY mr.record_date DESC, mr.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Medical Records</title>
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
        }
        
        .card h3 {
            color: #0077b6;
            font-size: 24px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
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
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }
        
        th {
            background: linear-gradient(135deg, #0077b6, #023e8a);
            color: white;
            font-weight: 600;
        }
        
        td {
            background-color: #fafafa;
        }
        
        tr:hover td {
            background-color: #f5f5f5;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .btn-edit, .btn-delete, .btn-download {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .btn-edit {
            background: #28a745;
            color: white;
        }
        
        .btn-edit:hover {
            background: #218838;
            transform: translateY(-1px);
        }
        
        .btn-delete {
            background: #dc3545;
            color: white;
        }
        
        .btn-delete:hover {
            background: #c82333;
            transform: translateY(-1px);
        }
        
        .btn-download {
            background: #0077b6;
            color: white;
        }
        
        .btn-download:hover {
            background: #005a8b;
            transform: translateY(-1px);
        }
        
        .logout {
            margin-top: 30px;
            display: inline-block;
            color: red;
            font-weight: bold;
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
            <div class="records-header">
                <h3>🩺 My Medical Records</h3>
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
                    <p>You haven't created any medical records yet. Start by adding records for your patients.</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>📅 Date</th>
                            <th>👤 Patient</th>
                            <th>📝 Description</th>
                            <th>📎 File</th>
                            <th>🕒 Created At</th>
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
                                    <div class="record-description" title="<?= htmlspecialchars($row['description']) ?>">
                                        <?= htmlspecialchars($row['description']) ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($row['file'])): ?>
                                        <a class="btn-download" href="../uploads/<?= htmlspecialchars($row['file']) ?>" target="_blank">
                                            📥 Download
                                        </a>
                                    <?php else: ?>
                                        <span style="color: #999;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('M d, Y H:i', strtotime($row['created_at'])) ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="edit_medical_record.php?id=<?= $row['id'] ?>" class="btn-edit">
                                            ✏️ Edit
                                        </a>
                                        <button class="btn-delete" onclick="confirmDelete(<?= $row['id'] ?>)">
                                            🗑️ Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
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