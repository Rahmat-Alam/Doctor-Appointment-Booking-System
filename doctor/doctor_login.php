<?php
session_start();
include '../db.php';

if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Doctor verification
    $sql = "SELECT * FROM doctors WHERE email = '$email' AND password = '$password'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) == 1) {
        $doctor = mysqli_fetch_assoc($result);
        $_SESSION['doctor_id'] = $doctor['id'];
        $_SESSION['doctor_name'] = $doctor['name'];
        header("Location: doctor_dashboard.php");
        exit();
    } else {
        $error = "Invalid Email or Password!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Doctor Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(120deg, #f0f8ff 0%, #dff3ec 100%);
            margin: 0;
            min-height: 100vh;
        }
        
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #0077b6;
            padding: 10px 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            position: relative;
        }
        .logo img {
            height: 48px;
        }
        .menu {
            display: flex;
            align-items: center;
        }
        .menu a {
            color: white;
            text-decoration: none;
            margin: 0 15px;
            font-size: 16px;
            transition: 0.3s;
        }
        .menu a:hover,
        .menu a.active {
            text-decoration: underline;
            font-weight: bold;
        }
        
        .main-content {
            display: flex;
            height: calc(100vh - 80px);
            justify-content: center;
            align-items: center;
        }

        .login-box {
            background: white;
            padding: 40px;
            box-shadow: 0px 0px 10px #aaa;
            border-radius: 10px;
        }

        input {
            padding: 10px;
            width: 100%;
            margin: 10px 0;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        button {
            padding: 10px;
            background: #28a745;
            color: white;
            font-weight: bold;
            width: 100%;
            border: none;
            border-radius: 5px;
        }

        .error {
            color: red;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo">
            <img src="../images/hospital_logo.svg" alt="Hospital Appointment System Logo">
        </div>
        <div class="menu">
            <a href="../index.php">Home</a>
            <a href="../about.html">About</a>
        </div>
    </nav>
    
    <div class="main-content">
        <div class="login-box">
    <h2>Doctor Login</h2>
    <form method="POST">
        <input type="email" name="email" placeholder="Enter Email" required />
        <input type="password" name="password" placeholder="Enter Password" required />
        <button type="submit" name="login">Login</button>
        <?php if (isset($error)) echo "<div class='error'>$error</div>"; ?>
    </form>
</div>
    </div>
</body>
</html>
