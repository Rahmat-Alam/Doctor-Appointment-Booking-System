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

// Handle add availability
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $date = $_POST['date'];
    $slot = $_POST['time_slot'];
    $notes = trim($_POST['notes']);
    // Map slot to start and end times
    $slot_times = [
        '6AM-10AM' => ['06:00:00', '10:00:00'],
        '10AM-2PM' => ['10:00:00', '14:00:00'],
        '2PM-6PM' => ['14:00:00', '18:00:00'],
        '6PM-10PM' => ['18:00:00', '22:00:00'],
        '10PM-2AM' => ['22:00:00', '02:00:00'],
        '2AM-6AM' => ['02:00:00', '06:00:00'],
    ];
    if ($date && $slot && isset($slot_times[$slot])) {
        list($start, $end) = $slot_times[$slot];
        // Check for max 6 slots per date
        $stmt = $conn->prepare("SELECT COUNT(*) FROM doctor_availability WHERE doctor_id = ? AND date = ?");
        $stmt->bind_param("is", $doctor_id, $date);
        $stmt->execute();
        $stmt->bind_result($slot_count);
        $stmt->fetch();
        $stmt->close();
        if ($slot_count >= 6) {
            $error = "You can only set up to 6 slots per date.";
        } else {
            // Prevent duplicate slot for same date/slot
            $stmt = $conn->prepare("SELECT COUNT(*) FROM doctor_availability WHERE doctor_id = ? AND date = ? AND start_time = ? AND end_time = ?");
            $stmt->bind_param("isss", $doctor_id, $date, $start, $end);
            $stmt->execute();
            $stmt->bind_result($dup_count);
            $stmt->fetch();
            $stmt->close();
            if ($dup_count > 0) {
                $error = "This time slot is already set for the selected date.";
            } else {
                $day_of_week = date('l', strtotime($date));
                $stmt = $conn->prepare("INSERT INTO doctor_availability (doctor_id, date, day_of_week, start_time, end_time, notes) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isssss", $doctor_id, $date, $day_of_week, $start, $end, $notes);
                if ($stmt->execute()) {
                    $success = "Availability added.";
                } else {
                    $error = "Error adding availability.";
                }
                $stmt->close();
            }
        }
    } else {
        $error = "All fields except notes are required.";
    }
}
// Handle delete
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    $conn->query("DELETE FROM doctor_availability WHERE id = $del_id AND doctor_id = $doctor_id");
    header("Location: manage_availability.php");
    exit();
}
// Fetch all availabilities
$avail = $conn->query("SELECT * FROM doctor_availability WHERE doctor_id = $doctor_id ORDER BY date, start_time");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Manage Availability</title>
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
    select, input[type="time"], input[type="text"] { width: 100%; padding: 10px; margin-top: 5px; border-radius: 6px; border: 1px solid #ccc; font-size: 16px; }
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
      <h3>🗓️ Set Your Availability</h3>
      <form method="POST">
        <label for="date">Date:</label>
        <input type="date" name="date" id="date" required min="<?= date('Y-m-d') ?>">
        <label for="time_slot">Time Slot:</label>
        <select name="time_slot" id="time_slot" required>
          <option value="">-- Select Time Slot --</option>
          <option value="6AM-10AM">6AM - 10AM</option>
          <option value="10AM-2PM">10AM - 2PM</option>
          <option value="2PM-6PM">2PM - 6PM</option>
          <option value="6PM-10PM">6PM - 10PM</option>
          <option value="10PM-2AM">10PM - 2AM</option>
          <option value="2AM-6AM">2AM - 6AM</option>
        </select>
        <label for="notes">Notes (optional):</label>
        <input type="text" name="notes" id="notes" maxlength="255">
        <button type="submit" name="add">Add Availability</button>
      </form>
      <?php if ($success): ?><p class="success"><?= $success ?></p><?php endif; ?>
      <?php if ($error): ?><p class="error"><?= $error ?></p><?php endif; ?>
    </div>
    <div class="card" style="margin-top:30px;">
      <h3>📋 Your Availability</h3>
      <table>
        <thead>
          <tr>
            <th>Date</th>
            <th>Day</th>
            <th>Time Slot</th>
            <th>Notes</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($avail->num_rows === 0): ?>
            <tr><td colspan="5">No availability set.</td></tr>
          <?php else: while ($row = $avail->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($row['date']) ?></td>
              <td><?= htmlspecialchars($row['day_of_week']) ?></td>
              <td>
                <?php
                  $start = $row['start_time'];
                  $end = $row['end_time'];
                  if ($start == '06:00:00' && $end == '10:00:00') echo '6AM - 10AM';
                  elseif ($start == '10:00:00' && $end == '14:00:00') echo '10AM - 2PM';
                  elseif ($start == '14:00:00' && $end == '18:00:00') echo '2PM - 6PM';
                  elseif ($start == '18:00:00' && $end == '22:00:00') echo '6PM - 10PM';
                  elseif ($start == '22:00:00' && $end == '02:00:00') echo '10PM - 2AM';
                  elseif ($start == '02:00:00' && $end == '06:00:00') echo '2AM - 6AM';
                  else echo htmlspecialchars(substr($start,0,5)) . ' - ' . htmlspecialchars(substr($end,0,5));
                ?>
              </td>
              <td><?= htmlspecialchars($row['notes']) ?></td>
              <td><a class="delete-link" href="?delete=<?= $row['id'] ?>" onclick="return confirm('Delete this slot?');">Delete</a></td>
            </tr>
          <?php endwhile; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</body>
</html> 