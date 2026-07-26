<?php
include '../db.php';
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../admin_login.php");
    exit;
}

// Update status
if (isset($_GET['approve'])) {
    $id = $_GET['approve'];
    $conn->query("UPDATE appointments SET status = 'Approved' WHERE id = $id");
} elseif (isset($_GET['cancel'])) {
    $id = $_GET['cancel'];
    $conn->query("UPDATE appointments SET status = 'Cancelled' WHERE id = $id");
}

// Handle reschedule
if (isset($_POST['reschedule_id'], $_POST['new_date'], $_POST['new_time'])) {
    $id = intval($_POST['reschedule_id']);
    $new_date = $_POST['new_date'];
    $new_time = $_POST['new_time'];
    // Get doctor_id for this appointment
    $stmt = $conn->prepare("SELECT doctor_id FROM appointments WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($doctor_id);
    $stmt->fetch();
    $stmt->close();
    // Check if slot is available
    $stmt = $conn->prepare("SELECT id FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? AND id != ?");
    $stmt->bind_param("issi", $doctor_id, $new_date, $new_time, $id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $reschedule_error = "Slot already booked.";
    } else {
        $conn->query("UPDATE appointments SET appointment_date = '".$conn->real_escape_string($new_date)."', appointment_time = '".$conn->real_escape_string($new_time)."' WHERE id = $id");
        $reschedule_success = "Appointment rescheduled.";
    }
    $stmt->close();
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM appointments WHERE id = $id");
}

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

// AJAX: Get doctor_id for appointment
if (isset($_GET['get_doctor_id']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT doctor_id FROM appointments WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($doctor_id);
    $stmt->fetch();
    $stmt->close();
    header('Content-Type: application/json');
    echo json_encode(['doctor_id' => $doctor_id]);
    exit();
}

// Fetch Appointments with JOIN
$sql = "SELECT a.id, p.full_name AS patient_name, d.name AS doctor_name, a.appointment_date, a.appointment_time, a.status 
        FROM appointments a
        JOIN patients p ON a.patient_id = p.id
        JOIN doctors d ON a.doctor_id = d.id
        ORDER BY a.appointment_date DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Appointments</title>
    <link rel="stylesheet" href="admin_style.css">
</head>
<body>

    <div class="header">
        <h1>Manage Appointments</h1>
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
    <a href="manage_appointments.php" class="active">Manage Appointments</a>
    <a href="manage_departments.php">Manage Departments</a>
    <a href="view_medical_records.php">View Medical Records</a>
</div>

<div class="content">
    <h2>Manage Appointments</h2>

    <table>
        <tr>
            <th>ID</th>
            <th>Patient Name</th>
            <th>Doctor Name</th>
            <th>Appointment Date</th>
            <th>Time Slot</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>

        <?php while ($row = $result->fetch_assoc()) { ?>
            <tr>
                <td><?= $row['id']; ?></td>
                <td><?= $row['patient_name']; ?></td>
                <td><?= $row['doctor_name']; ?></td>
                <td><?= $row['appointment_date']; ?></td>
                <td><?= $row['appointment_time']; ?></td>
                <td>
                    <?php
                        if ($row['status'] == 'Approved') {
                            echo "<span class='approved'>Approved</span>";
                        } elseif ($row['status'] == 'Cancelled') {
                            echo "<span class='cancelled'>Cancelled</span>";
                        } else {
                            echo "<span class='pending'>Pending</span>";
                        }
                    ?>
                </td>
                <td>
                    <form method="POST" style="display:inline;" class="reschedule-form" data-id="<?= $row['id']; ?>">
                        <input type="hidden" name="reschedule_id" value="<?= $row['id']; ?>">
                        <select name="new_date" class="date-dropdown" required style="width:120px;"></select>
                        <select name="new_time" class="slot-dropdown" required style="width:110px;"></select>
                        <button type="submit" class="btn">Reschedule</button>
                    </form>
                    <a class="btn cancel-btn" href="?cancel=<?= $row['id']; ?>" onclick="return confirm('Are you sure you want to cancel?');">Cancel</a>
                    <a class="btn" style="background:#ef4444;" href="?delete=<?= $row['id']; ?>" onclick="return confirm('Are you sure you want to permanently delete this appointment?');">Delete</a>
                </td>
            </tr>
        <?php } ?>
    </table>

    <script>
    document.querySelectorAll('.reschedule-form').forEach(function(form) {
        const id = form.getAttribute('data-id');
        const doctorName = form.parentElement.parentElement.children[2].textContent;
        // Get doctor_id from a hidden field or via AJAX if needed
        // For simplicity, fetch doctor_id from the server using appointment id
        fetch('manage_appointments.php?get_doctor_id=1&id=' + id)
          .then(res => res.json())
          .then(data => {
            const doctorId = data.doctor_id;
            // Populate date dropdown
            fetch('manage_appointments.php?fetch_dates=1&doctor_id=' + doctorId)
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
                fetch('manage_appointments.php?fetch_slots=1&doctor_id=' + doctorId + '&date=' + this.value)
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
    });
    </script>

</body>
</html>
