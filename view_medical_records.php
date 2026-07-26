<?php
session_start();
if (!isset($_SESSION['patient_id'])) {
    header("Location: index.php");
    exit();
}
include 'db.php';
$patient_id = $_SESSION['patient_id'];

// Fetch patient name
$query = $conn->prepare("SELECT full_name FROM patients WHERE id = ?");
$query->bind_param("i", $patient_id);
$query->execute();
$query->bind_result($full_name);
$query->fetch();
$query->close();

// Handle record deletion
if (isset($_POST['delete_record'])) {
    $record_id = intval($_POST['record_id']);
    
    // Check if record belongs to this patient
    $check = $conn->prepare("SELECT file FROM medical_records WHERE id = ? AND patient_id = ?");
    $check->bind_param("ii", $record_id, $patient_id);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $file_path = $row['file'];
        
        // Delete file if exists
        if (!empty($file_path)) {
            $full_path = "uploads/" . $file_path;
            if (file_exists($full_path)) {
                unlink($full_path);
            }
        }
        
        // Delete record from database
        $delete = $conn->prepare("DELETE FROM medical_records WHERE id = ? AND patient_id = ?");
        $delete->bind_param("ii", $record_id, $patient_id);
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

// Fetch medical records
$sql = "SELECT mr.*, d.name AS doctor_name FROM medical_records mr LEFT JOIN doctors d ON mr.doctor_id = d.id WHERE mr.patient_id = ? ORDER BY mr.record_date DESC, mr.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Medical Records</title>
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
    .sidebar h2 { font-size: 22px; margin-bottom: 30px; }
    .sidebar a { color: white; display: block; margin: 10px 0; text-decoration: none; padding: 10px; border-radius: 6px; }
    .sidebar a:hover { background: #34495e; }
    .main { margin-left: 240px; padding: 30px; }
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
    td { background-color: #fafafa; }
    tr:hover td { background-color: #f5f5f5; }
    .logout { margin-top: 30px; display: inline-block; color: red; font-weight: bold; }
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
<div class="sidebar">
    <h2>Patient Dashboard</h2>
    <p>👤 <strong><?= htmlspecialchars($full_name) ?></strong></p>
    <a href="patient_dashboard.php">🏠 Dashboard Home</a>
    <a href="book_appointment.php">📅 Book Appointment</a>
    <a href="appointment_history.php">📖 Appointment History</a>
    <a href="view_medical_records.php">🩺 Medical Records</a>
    <a href="rate_doctor.php">⭐ Rate Doctor</a>
    <a href="manage_account.php">⚙️ Manage Account</a>
    <a href="feedback.php">💬 Feedback</a>
    <a class="logout" href="logout.php">🚪 Logout</a>
  </div>
  <div class="main">
    <div class="card">
      <div class="records-header">
        <h3>🩺 Your Medical Records</h3>
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
          <p>Your medical records will appear here once your doctors add them after appointments.</p>
        </div>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>📅 Date</th>
              <th>👨‍⚕️ Doctor</th>
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
                <td><?= htmlspecialchars($row['doctor_name'] ?? 'N/A') ?></td>
                <td><?= nl2br(htmlspecialchars($row['description'])) ?></td>
                <td>
                  <?php if (!empty($row['file'])): ?>
                    <a class="download-link" href="uploads/<?= htmlspecialchars($row['file']) ?>" target="_blank">
                      📥 Download
                    </a>
                  <?php else: ?>
                    <span style="color: #999;">-</span>
                  <?php endif; ?>
                </td>
                <td><?= date('M d, Y H:i', strtotime($row['created_at'])) ?></td>
                <td>
                  <button class="btn-delete" onclick="confirmDelete(<?= $row['id'] ?>)">
                    🗑️ Delete
                  </button>
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
<?php $stmt->close(); $conn->close(); ?> 