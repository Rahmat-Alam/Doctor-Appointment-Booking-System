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

// Handle review submission
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doctor_id = intval($_POST['doctor_id']);
    $rating = intval($_POST['rating']);
    $review = trim($_POST['review']);
    if ($doctor_id && $rating >= 1 && $rating <= 5) {
        // Check if already reviewed
        $check = $conn->prepare("SELECT id FROM doctor_reviews WHERE doctor_id = ? AND patient_id = ?");
        $check->bind_param("ii", $doctor_id, $patient_id);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            // Update
            $stmt = $conn->prepare("UPDATE doctor_reviews SET rating = ?, review = ?, created_at = NOW() WHERE doctor_id = ? AND patient_id = ?");
            $stmt->bind_param("isii", $rating, $review, $doctor_id, $patient_id);
            $stmt->execute();
            $success = "Review updated.";
            $stmt->close();
        } else {
            // Insert
            $stmt = $conn->prepare("INSERT INTO doctor_reviews (doctor_id, patient_id, rating, review) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiis", $doctor_id, $patient_id, $rating, $review);
            $stmt->execute();
            $success = "Review submitted.";
            $stmt->close();
        }
        $check->close();
    } else {
        $error = "Please select a doctor and rating.";
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    $conn->query("DELETE FROM doctor_reviews WHERE id = $del_id AND patient_id = $patient_id");
    header("Location: rate_doctor.php");
    exit();
}

// Fetch previous reviews by this patient
$my_reviews = $conn->query("SELECT dr.*, d.name AS doctor_name, p.full_name AS patient_name 
                            FROM doctor_reviews dr 
                            JOIN doctors d ON dr.doctor_id = d.id 
                            JOIN patients p ON dr.patient_id = p.id 
                            WHERE dr.patient_id = $patient_id 
                            ORDER BY dr.created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Rate & Review Doctor</title>
  <style>
    body { font-family: Arial, sans-serif; background: #f1f1f1; margin: 0; padding: 0; }
    .sidebar { width: 220px; height: 100vh; background: #2c3e50; padding: 20px; color: white; position: fixed; }
    .sidebar h2 { font-size: 22px; margin-bottom: 30px; }
    .sidebar a { color: white; display: block; margin: 10px 0; text-decoration: none; padding: 10px; border-radius: 6px; }
    .sidebar a:hover { background: #34495e; }
    .main { margin-left: 240px; padding: 30px; }
    .card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 600px; }
    .card h3 { margin-bottom: 15px; }
    form label { display: block; margin-top: 10px; font-weight: bold; }
    select, textarea { width: 100%; padding: 10px; margin-top: 5px; border-radius: 6px; border: 1px solid #ccc; font-size: 16px; }
    .stars { display: flex; flex-direction: row-reverse; justify-content: flex-end; margin-top: 5px; }
    .stars input { display: none; }
    .stars label { font-size: 24px; color: #ccc; cursor: pointer; transition: color 0.2s; }
    .stars input:checked ~ label, .stars label:hover, .stars label:hover ~ label { color: #f5b301; }
    button { margin-top: 15px; padding: 10px 15px; background-color: #3498db; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; }
    button:hover { background-color: #2980b9; }
    .success { color: green; margin-top: 10px; }
    .error { color: red; margin-top: 10px; }
    .logout { margin-top: 30px; display: inline-block; color: red; font-weight: bold; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    th, td { padding: 12px; text-align: center; border-bottom: 1px solid #ddd; }
    th { background-color: #3498db; color: white; }
    td { background-color: #fafafa; }
    a.delete-link { color: #e74c3c; text-decoration: underline; }
    a.delete-link:hover { color: #c0392b; }
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
      <h3>⭐ Rate & Review Doctor</h3>
      <?php if ($doctors->num_rows == 0): ?>
        <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
          <p style="margin: 0; color: #856404;">📝 <strong>Note:</strong> You can only rate doctors you have booked appointments with. 
          <a href="book_appointment.php" style="color: #0077b6;">Book an appointment first</a> to rate a doctor.</p>
        </div>
      <?php endif; ?>
      <form method="POST">
        <label for="doctor_id">Select Doctor</label>
        <select name="doctor_id" id="doctor_id" required>
          <option value="">Select Doctor</option>
          <?php if ($doctors->num_rows > 0): ?>
            <?php while ($doc = $doctors->fetch_assoc()): ?>
              <option value="<?= $doc['id'] ?>">Dr. <?= htmlspecialchars($doc['name']) ?> - <?= htmlspecialchars($doc['specialization']) ?></option>
            <?php endwhile; ?>
          <?php else: ?>
            <option value="" disabled>No doctors available (book an appointment first)</option>
          <?php endif; ?>
        </select>
        <label>Rating</label>
        <div class="stars">
          <?php for ($i = 5; $i >= 1; $i--): ?>
            <input type="radio" id="star<?= $i ?>" name="rating" value="<?= $i ?>">
            <label for="star<?= $i ?>">★</label>
          <?php endfor; ?>
        </div>
        <label for="review">Review (Optional)</label>
        <textarea name="review" id="review" placeholder="Write your review..."></textarea>
        <button type="submit">Submit</button>
      </form>
      <?php if ($success): ?><p class="success"><?= $success ?></p><?php endif; ?>
      <?php if ($error): ?><p class="error"><?= $error ?></p><?php endif; ?>
    </div>
    <div class="card" style="margin-top:30px;">
      <h3>📝 Your Reviews</h3>
      <table>
        <thead>
          <tr>
            <th>Doctor</th>
            <th>Patient</th>
            <th>Rating</th>
            <th>Review</th>
            <th>Date</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($my_reviews->num_rows === 0): ?>
            <tr><td colspan="6">No reviews yet.</td></tr>
          <?php else: while ($row = $my_reviews->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($row['doctor_name']) ?></td>
              <td><?= htmlspecialchars($row['patient_name']) ?></td>
              <td><?= str_repeat('★', $row['rating']) . str_repeat('☆', 5 - $row['rating']) ?></td>
              <td><?= nl2br(htmlspecialchars($row['review'])) ?></td>
              <td><?= date('d-m-Y', strtotime($row['created_at'])) ?></td>
              <td><a class="delete-link" href="?delete=<?= $row['id'] ?>" onclick="return confirm('Delete this review?');">Delete</a></td>
            </tr>
          <?php endwhile; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</body>
</html> 