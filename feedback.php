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

// Fetch doctors that this patient has booked appointments with
$doctors = $conn->query("SELECT DISTINCT d.id, d.name, d.specialization 
                        FROM doctors d 
                        JOIN appointments a ON d.id = a.doctor_id 
                        WHERE a.patient_id = $patient_id 
                        ORDER BY d.name ASC");

$success = "";
$error = "";

// Handle feedback submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $feedback_text = trim($_POST["feedback_text"]);
    $doctor_id = !empty($_POST['doctor_id']) ? intval($_POST['doctor_id']) : null;
    
    if (!empty($feedback_text)) {
        if ($doctor_id) {
            // Doctor-specific feedback - check if patient has appointment with this doctor
            $check = $conn->prepare("SELECT id FROM appointments WHERE patient_id = ? AND doctor_id = ?");
            $check->bind_param("ii", $patient_id, $doctor_id);
            $check->execute();
            $check->store_result();
            
            if ($check->num_rows > 0) {
                // Insert into doctor_reviews table
                $stmt = $conn->prepare("INSERT INTO doctor_reviews (doctor_id, patient_id, review) VALUES (?, ?, ?)");
                $stmt->bind_param("iis", $doctor_id, $patient_id, $feedback_text);
                if ($stmt->execute()) {
                    $success = "Doctor-specific feedback submitted successfully!";
                } else {
                    $error = "Something went wrong. Please try again.";
                }
                $stmt->close();
            } else {
                $error = "You can only give feedback to doctors you have booked appointments with.";
            }
            $check->close();
        } else {
            // General feedback
            $stmt = $conn->prepare("INSERT INTO feedback (patient_id, message) VALUES (?, ?)");
            $stmt->bind_param("is", $patient_id, $feedback_text);
            if ($stmt->execute()) {
                $success = "General feedback submitted successfully!";
            } else {
                $error = "Something went wrong. Please try again.";
            }
            $stmt->close();
        }
    } else {
        $error = "Please enter your feedback.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Feedback</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f1f1f1;
      margin: 0;
      padding: 0;
    }

    .sidebar {
      width: 220px;
      height: 100vh;
      background: #2c3e50;
      padding: 20px;
      color: white;
      position: fixed;
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
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      max-width: 600px;
    }

    .card h3 {
      margin-bottom: 15px;
    }

    form label {
      display: block;
      margin-top: 10px;
      font-weight: bold;
    }

    textarea {
      width: 100%;
      height: 120px;
      padding: 10px;
      margin-top: 10px;
      border-radius: 6px;
      border: 1px solid #ccc;
      font-size: 14px;
    }

    select, input[type="text"] {
      width: 100%;
      padding: 10px;
      margin-top: 5px;
      border-radius: 6px;
      border: 1px solid #ccc;
      font-size: 16px;
    }

    button {
      margin-top: 15px;
      padding: 10px 15px;
      background-color: #3498db;
      color: white;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-size: 16px;
    }

    button:hover {
      background-color: #2980b9;
    }

    .success {
      color: green;
      margin-top: 10px;
    }

    .error {
      color: red;
      margin-top: 10px;
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
      <h3>💬 Submit Your Feedback</h3>
      <?php if ($doctors->num_rows == 0): ?>
        <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
          <p style="margin: 0; color: #856404;">📝 <strong>Note:</strong> You can only give doctor-specific feedback to doctors you have booked appointments with. 
          <a href="book_appointment.php" style="color: #0077b6;">Book an appointment first</a> to give doctor-specific feedback.</p>
        </div>
      <?php endif; ?>
      <form method="POST">
        <label for="doctor_id">Doctor (Optional)</label>
        <select name="doctor_id" id="doctor_id">
          <option value="">General Feedback</option>
          <?php if ($doctors->num_rows > 0): ?>
            <?php while ($doc = $doctors->fetch_assoc()): ?>
              <option value="<?= $doc['id'] ?>">Dr. <?= htmlspecialchars($doc['name']) ?> - <?= htmlspecialchars($doc['specialization']) ?></option>
            <?php endwhile; ?>
          <?php else: ?>
            <option value="" disabled>No doctors available (book an appointment first)</option>
          <?php endif; ?>
        </select>
        <label for="feedback_text">Your Feedback</label>
        <textarea name="feedback_text" id="feedback_text" placeholder="Write your feedback here..." required></textarea>
        <button type="submit">Submit Feedback</button>
      </form>
      <?php if ($success): ?>
        <p class="success"><?= $success ?></p>
      <?php endif; ?>
      <?php if ($error): ?>
        <p class="error"><?= $error ?></p>
      <?php endif; ?>
    </div>
  </div>

</body>
</html>
