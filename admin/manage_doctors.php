<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../admin_login.php");
    exit;
}

include '../db.php';

// Fetch all doctors
$result = mysqli_query($conn, "SELECT * FROM doctors ORDER BY created_at DESC");

// Delete Doctor
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    mysqli_query($conn, "DELETE FROM doctors WHERE id = $id");
    header("Location: manage_doctors.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Doctors</title>
    <link rel="stylesheet" href="admin_style.css">
</head>
<body>

<div class="header">
    <h1>Manage Doctors</h1>
    <div class="logout">
        <a href="../logout.php">Logout</a>
    </div>
</div>

<div class="nav">
    <a href="admin_dashboard.php">Dashboard</a>
    <a href="add_admin.php">Add Admin</a>
    <a href="add_doctor.php">Add Doctor</a>
    <a href="add_patient.php">Add Patient</a>
    <a href="manage_doctors.php" class="active">Manage Doctors</a>
    <a href="manage_patients.php">Manage Patients</a>
    <a href="manage_appointments.php">Manage Appointments</a>
    <a href="manage_departments.php">Manage Departments</a>
    <a href="view_medical_records.php">View Medical Records</a>
</div>

<div class="content">
    <h2>Manage Doctors</h2>
    
    <div class="text-center">
        <a href="add_doctor.php" class="btn btn-success">+ Add New Doctor</a>
    </div>

            <div class="table-container">
            <table>
                <tr>
                    <th>ID</th>
                    <th>Photo</th>
                    <th>Full Name</th>
                    <th>Email Address</th>
                    <th>Contact Number</th>
                    <th>Department</th>
                    <th>Qualification</th>
                    <th>Experience (In Years)</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>

                <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                    <tr>
                        <td><?= $row['id']; ?></td>
                        <td>
                            <?php if ($row['profile_pic']) { ?>
                                <img src="../images/<?= $row['profile_pic']; ?>" alt="Profile" style="width: 60px; height: 60px; border-radius: 50%;">
                            <?php } else { echo "N/A"; } ?>
                        </td>
                        <td><?= htmlspecialchars($row['name']); ?></td>
                        <td><?= htmlspecialchars($row['email']); ?></td>
                        <td><?= htmlspecialchars($row['contact']); ?></td>
                        <td><?= htmlspecialchars($row['specialization']); ?></td>
                        <td><?= htmlspecialchars($row['qualification']); ?></td>
                        <td><?= $row['experience']; ?> yrs</td>
                        <td><?= date("d-m-Y", strtotime($row['created_at'])); ?></td>
                        <td>
                            <a class="btn btn-primary btn-sm" href="edit_doctor.php?id=<?= $row['id']; ?>">Edit</a>
                            <a class="btn btn-danger btn-sm" href="?delete=<?= $row['id']; ?>" onclick="return confirm('Are you sure to delete this doctor?')">Delete</a>
                        </td>
                    </tr>
                <?php } ?>
            </table>
        </div>
    </div>

</body>
</html>
