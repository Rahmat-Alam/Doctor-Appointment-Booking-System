<?php
include '../db.php';
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../admin_login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: manage_patients.php");
    exit();
}

$id = $_GET['id'];

// Fetch existing data
$query = "SELECT * FROM patients WHERE id = $id";
$result = mysqli_query($conn, $query);
$patient = mysqli_fetch_assoc($result);

if (!$patient) {
    echo "Patient not found.";
    exit();
}

// Update logic
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $gender = $_POST['gender'];
    $age = $_POST['age'];
    $address = $_POST['address'];
    $disease = $_POST['disease'];

    $sql = "UPDATE patients SET 
            full_name='$full_name', email='$email', phone='$phone', 
            gender='$gender', age='$age', address='$address', disease='$disease'
            WHERE id=$id";

    if (mysqli_query($conn, $sql)) {
        header("Location: manage_patients.php");
        exit();
    } else {
        echo "Error updating patient.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Patient</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            margin: 0;
        }

        .header {
            background: #1f2937;
            color: white;
            padding: 20px;
            text-align: center;
            position: relative;
        }

        .logout {
            position: absolute;
            right: 20px;
            top: 20px;
        }

        .logout a {
            background-color: red;
            color: white;
            padding: 8px 12px;
            border-radius: 5px;
            text-decoration: none;
        }

        .nav {
            background: #374151;
            padding: 15px;
            display: flex;
            justify-content: center;
        }

        .nav a {
            color: white;
            margin: 0 15px;
            text-decoration: none;
            font-weight: bold;
        }

        .container {
            max-width: 600px;
            margin: 30px auto;
            background: white;
            padding: 30px;
            box-shadow: 0 0 10px #ccc;
            border-radius: 8px;
        }

        h2 {
            text-align: center;
            margin-bottom: 30px;
        }

        input, select, textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        button {
            background: #3b82f6;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        button:hover {
            background: #2563eb;
        }
    </style>
</head>
<body>

<div class="header">
    <h1>Edit Patient</h1>
    <div class="logout">
        <a href="../logout.php">Logout</a>
    </div>
</div>

<div class="nav">
    <a href="admin_dashboard.php">Dashboard</a>
    <a href="manage_doctors.php">Manage Doctors</a>
    <a href="manage_patients.php">Manage Patients</a>
    <a href="manage_appointments.php">Manage Appointments</a>
</div>

<div class="container">
    <h2>Update Patient Details</h2>
    <form method="post">
        <label>Full Name</label>
        <input type="text" name="full_name" value="<?= $patient['full_name']; ?>" required>

        <label>Email Address</label>
        <input type="email" name="email" value="<?= $patient['email']; ?>" required>

        <label>Phone Number</label>
        <input type="text" name="phone" value="<?= $patient['phone']; ?>" required>

        <label>Gender</label>
        <select name="gender" required>
            <option value="Male" <?= $patient['gender'] == 'Male' ? 'selected' : '' ?>>Male</option>
            <option value="Female" <?= $patient['gender'] == 'Female' ? 'selected' : '' ?>>Female</option>
            <option value="Other" <?= $patient['gender'] == 'Other' ? 'selected' : '' ?>>Other</option>
        </select>

        <label>Age</label>
        <input type="number" name="age" value="<?= $patient['age']; ?>" required>

        <label>Address</label>
        <textarea name="address" required><?= $patient['address']; ?></textarea>

        <label>Disease</label>
        <input type="text" name="disease" value="<?= $patient['disease']; ?>" required>

        <button type="submit">Update Patient</button>
    </form>
</div>

</body>
</html>
