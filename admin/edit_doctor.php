<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../admin_login.php");
    exit;
}

include '../db.php';

if (!isset($_GET['id'])) {
    header("Location: manage_doctors.php");
    exit;
}

$id = $_GET['id'];
$msg = "";

// Fetch doctor info
$sql = "SELECT * FROM doctors WHERE id = $id";
$result = mysqli_query($conn, $sql);
$doctor = mysqli_fetch_assoc($result);

if (!$doctor) {
    echo "Doctor not found!";
    exit;
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $specialization = mysqli_real_escape_string($conn, $_POST['specialization']);
    $experience = mysqli_real_escape_string($conn, $_POST['experience']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $profile_pic = $doctor['profile_pic'];

    // If password provided, update it
    if (!empty($_POST['password'])) {
        $password = $_POST['password'];
        $update_pass = ", password='$password'";
    } else {
        $update_pass = "";
    }

    // Profile picture update
    if (!empty($_FILES['profile_pic']['name'])) {
        $filename = time() . '_' . basename($_FILES['profile_pic']['name']);
        $target = "../images/" . $filename;
        if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $target)) {
            $profile_pic = $filename;
        }
    }

    $update_sql = "UPDATE doctors SET 
                    name = '$name', 
                    specialization = '$specialization',
                    experience = '$experience',
                    email = '$email',
                    profile_pic = '$profile_pic'
                    $update_pass
                   WHERE id = $id";

    if (mysqli_query($conn, $update_sql)) {
        $msg = "Doctor updated successfully.";
        // Refresh info
        $result = mysqli_query($conn, "SELECT * FROM doctors WHERE id = $id");
        $doctor = mysqli_fetch_assoc($result);
    } else {
        $msg = "Error: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Doctor</title>
    <style>
        body {
            font-family: Arial;
            background: #f0f0f0;
        }

        .form-container {
            width: 500px;
            margin: 30px auto;
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px #ccc;
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }

        label {
            display: block;
            margin-top: 10px;
            font-weight: bold;
        }

        input[type="text"], input[type="email"], input[type="password"], input[type="file"] {
            width: 100%;
            padding: 10px;
            margin-top: 6px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        img {
            margin-top: 10px;
            width: 80px;
            border-radius: 50%;
        }

        button {
            margin-top: 20px;
            padding: 12px;
            width: 100%;
            border: none;
            background: #1f2937;
            color: white;
            font-size: 16px;
            border-radius: 5px;
        }

        button:hover {
            background: #111827;
        }

        .msg {
            text-align: center;
            color: green;
            font-weight: bold;
        }

        a.back {
            display: block;
            text-align: center;
            margin-top: 15px;
            text-decoration: none;
            color: #1f2937;
        }
    </style>
</head>
<body>

<div class="form-container">
    <h2>Edit Doctor</h2>

    <?php if ($msg): ?>
        <p class="msg"><?= $msg; ?></p>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <label>Name</label>
        <input type="text" name="name" value="<?= $doctor['name']; ?>" required>

        <label>Specialization</label>
        <input type="text" name="specialization" value="<?= $doctor['specialization']; ?>" required>

        <label>Experience (in years)</label>
        <input type="text" name="experience" value="<?= $doctor['experience']; ?>" required>

        <label>Email</label>
        <input type="email" name="email" value="<?= $doctor['email']; ?>" required>

        <label>Password (leave blank to keep unchanged)</label>
        <input type="password" name="password">

        <label>Current Profile Picture</label><br>
        <?php if ($doctor['profile_pic']): ?>
            <img src="../images/<?= $doctor['profile_pic']; ?>" alt="Profile">
        <?php else: ?>
            <img src="../images/default.png" alt="Default">
        <?php endif; ?>

        <label>Change Profile Picture</label>
        <input type="file" name="profile_pic">

        <button type="submit">Update Doctor</button>
    </form>

    <a class="back" href="manage_doctors.php">← Back to Manage Doctors</a>
</div>

</body>
</html>
