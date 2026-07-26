<?php
include '../db.php';
session_start();

// AJAX: Return available dates for a given doctor
if (isset($_GET['fetch_dates']) && isset($_GET['doctor_id'])) {
    $doctor_id = intval($_GET['doctor_id']);
    $dates = [];
    $stmt = $conn->prepare("SELECT DISTINCT date FROM doctor_availability WHERE doctor_id = ? AND date >= CURDATE() ORDER BY date ASC");
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $dates[] = $row['date'];
    }
    header('Content-Type: application/json');
    echo json_encode($dates);
    exit();
}
// AJAX: Return available 20-min slots for a doctor/date
if (isset($_GET['fetch_slots']) && isset($_GET['doctor_id']) && isset($_GET['date'])) {
    $doctor_id = intval($_GET['doctor_id']);
    $date = $_GET['date'];
    $slots = [];
    $stmt = $conn->prepare("SELECT start_time, end_time FROM doctor_availability WHERE doctor_id = ? AND date = ?");
    $stmt->bind_param("is", $doctor_id, $date);
    $stmt->execute();
    $result = $stmt->get_result();
    $periods = [];
    while ($row = $result->fetch_assoc()) {
        $periods[] = [
            'start' => $row['start_time'],
            'end' => $row['end_time']
        ];
    }
    // Get already booked slots for this doctor/date
    $booked = [];
    $stmt2 = $conn->prepare("SELECT appointment_time FROM appointments WHERE doctor_id = ? AND appointment_date = ?");
    $stmt2->bind_param("is", $doctor_id, $date);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    while ($row = $res2->fetch_assoc()) {
        $booked[] = $row['appointment_time'];
    }
    $stmt2->close();
    // For each period, generate 20-min slots
    foreach ($periods as $p) {
        $start = new DateTime($p['start']);
        $end = new DateTime($p['end']);
        if ($end <= $start) {
            $end->modify('+1 day');
        }
        while ($start < $end) {
            $slot_start = $start->format('H:i');
            $slot_end = $start->modify('+20 minutes');
            if ($slot_end > $end) break;
            if (!in_array($slot_start . ':00', $booked)) {
                $slots[] = [
                    'start' => $slot_start,
                    'end' => $start->format('H:i')
                ];
            }
        }
    }
    header('Content-Type: application/json');
    echo json_encode($slots);
    exit();
}

// Doctor Login Check
if (!isset($_SESSION['doctor_id'])) {
    header("Location: doctor_login.php");
    exit;
}

$doctor_id = $_SESSION['doctor_id'];

// Fetch Doctor Name
$stmt = $conn->prepare("SELECT name FROM doctors WHERE id = ?");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$stmt->bind_result($doctor_name);
$stmt->fetch();
$stmt->close();

// Update status
if (isset($_GET['approve'])) {
    $id = $_GET['approve'];
    $conn->query("UPDATE appointments SET status = 'Approved' WHERE id = $id AND doctor_id = $doctor_id");
} elseif (isset($_GET['cancel'])) {
    $id = $_GET['cancel'];
    $conn->query("UPDATE appointments SET status = 'Cancelled' WHERE id = $id AND doctor_id = $doctor_id");
} elseif (isset($_GET['complete'])) {
    $id = $_GET['complete'];
    $conn->query("UPDATE appointments SET status = 'Completed' WHERE id = $id AND doctor_id = $doctor_id");
}

// Fetch Appointments
$sql = "SELECT a.id, p.full_name AS patient_name, a.appointment_date, a.appointment_time, a.status 
        FROM appointments a
        JOIN patients p ON a.patient_id = p.id
        WHERE a.doctor_id = $doctor_id
        ORDER BY a.appointment_date DESC";

$result = $conn->query($sql);

// Handle reschedule
$reschedule_msg = '';
if (
    isset($_POST['reschedule_id'], $_POST['new_date'], $_POST['new_time']) &&
    !empty($_POST['reschedule_id']) && !empty($_POST['new_date']) && !empty($_POST['new_time'])
) {
    $id = intval($_POST['reschedule_id']);
    $new_date = $_POST['new_date'];
    $new_time = $_POST['new_time'];
    // Check if slot is available (excluding this appointment)
    $stmt = $conn->prepare("SELECT id FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? AND id != ?");
    $stmt->bind_param("issi", $doctor_id, $new_date, $new_time, $id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $reschedule_msg = "<span style='color:red;'>Slot already booked.</span>";
    } else {
        $update = $conn->prepare("UPDATE appointments SET appointment_date = ?, appointment_time = ? WHERE id = ? AND doctor_id = ?");
        $update->bind_param("ssii", $new_date, $new_time, $id, $doctor_id);
        if ($update->execute()) {
            $reschedule_msg = "<span style='color:green;'>Appointment rescheduled.</span>";
        } else {
            $reschedule_msg = "<span style='color:red;'>Failed to reschedule.</span>";
        }
        $update->close();
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Manage Appointments</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f3f4f6;
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
        }

        .card h3 {
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th, td {
            padding: 12px 15px;
            border: 1px solid #ccc;
            text-align: center;
        }

        th {
            background-color: #f1f5f9;
        }

        a.btn {
            padding: 6px 12px;
            margin: 0 5px;
            background: #3b82f6;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
        }

        a.btn:hover {
            background: #2563eb;
        }

        .cancel-btn {
            background: #ef4444;
        }

        .cancel-btn:hover {
            background: #dc2626;
        }

        .approved {
            color: green;
            font-weight: bold;
        }

        .pending {
            color: orange;
            font-weight: bold;
        }

        .cancelled {
            color: red;
            font-weight: bold;
        }
        
        .completed {
            color: #10b981;
            font-weight: bold;
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
        <h3>My Appointments</h3>
        <?php if ($reschedule_msg) echo '<div style="margin-bottom:10px;">'.$reschedule_msg.'</div>'; ?>
        <table>
            <tr>
                <th>ID</th>
                <th>Patient Name</th>
                <th>Appointment Date</th>
                <th>Time Slot</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>

            <?php while ($row = $result->fetch_assoc()) { ?>
                <tr>
                    <td><?= $row['id']; ?></td>
                    <td><?= htmlspecialchars($row['patient_name']); ?></td>
                    <td><?= $row['appointment_date']; ?></td>
                    <td><?= $row['appointment_time']; ?></td>
                    <td>
                        <?php
                            if ($row['status'] == 'Approved') {
                                echo "<span class='approved'>Approved</span>";
                            } elseif ($row['status'] == 'Cancelled') {
                                echo "<span class='cancelled'>Cancelled</span>";
                            } elseif ($row['status'] == 'Completed') {
                                echo "<span class='completed'>Completed</span>";
                            } else {
                                echo "<span class='pending'>Pending</span>";
                            }
                        ?>
                    </td>
                    <td>
                        <?php if ($row['status'] == 'Pending') { ?>
                            <a class="btn" href="?approve=<?= $row['id']; ?>">Approve</a>
                            <a class="btn cancel-btn" href="?cancel=<?= $row['id']; ?>" onclick="return confirm('Are you sure you want to cancel?');">Cancel</a>
                        <?php } elseif ($row['status'] == 'Approved') { ?>
                            <a class="btn" href="?complete=<?= $row['id']; ?>" onclick="return confirm('Mark appointment as completed?');">Complete</a>
                            <a class="btn cancel-btn" href="?cancel=<?= $row['id']; ?>" onclick="return confirm('Are you sure you want to cancel?');">Cancel</a>
                            <form method="POST" style="display:inline;" class="reschedule-form" data-id="<?= $row['id']; ?>">
                                <input type="hidden" name="reschedule_id" value="<?= $row['id']; ?>">
                                <select name="new_date" class="date-dropdown" required style="width:120px;"></select>
                                <select name="new_time" class="slot-dropdown" required style="width:110px;"></select>
                                <button type="submit" class="btn">Reschedule</button>
                            </form>
                        <?php } elseif ($row['status'] == 'Completed') { ?>
                            <span style="color: #10b981; font-weight: bold;">✓ Completed</span>
                        <?php } else { 
                            echo "-"; 
                        } ?>
                    </td>
                </tr>
            <?php } ?>
        </table>
    </div>
</div>

<script>
document.querySelectorAll('.reschedule-form').forEach(function(form) {
    const id = form.getAttribute('data-id');
    // Get doctor_id from PHP variable
    const doctorId = <?= $doctor_id ?>;
    // Populate date dropdown
    fetch('manage_appointment.php?fetch_dates=1&doctor_id=' + doctorId)
      .then(res => res.json())
      .then(dates => {
        const dateDropdown = form.querySelector('.date-dropdown');
        dateDropdown.innerHTML = '<option value="">Date</option>';
        dates.forEach(date => {
          const opt = document.createElement('option');
          opt.value = date;
          opt.textContent = date;
          dateDropdown.appendChild(opt);
        });
      });
    // When date changes, populate slot dropdown
    const dateDropdown = form.querySelector('.date-dropdown');
    const slotDropdown = form.querySelector('.slot-dropdown');
    dateDropdown.addEventListener('change', function() {
      slotDropdown.innerHTML = '<option value="">Time</option>';
      if (this.value) {
        fetch('manage_appointment.php?fetch_slots=1&doctor_id=' + doctorId + '&date=' + this.value)
          .then(res => res.json())
          .then(slots => {
            slots.forEach(slot => {
              const opt = document.createElement('option');
              opt.value = slot.start + ':00';
              opt.textContent = slot.start + ' - ' + slot.end;
              slotDropdown.appendChild(opt);
            });
          });
      }
    });
});
</script>

</body>
</html>
