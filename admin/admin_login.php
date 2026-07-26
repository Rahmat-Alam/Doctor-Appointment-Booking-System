<?php
session_start();
include '../db.php';

$msg = "";
$showError = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM admin WHERE username = '$username'";
    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) > 0) {
        $admin = mysqli_fetch_assoc($result);

        if ($password === $admin['password']) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];

            header("Location: admin_dashboard.php");
            exit();
        } else {
            $msg = "❌ Invalid password!";
            $showError = true;
        }
    } else {
        $msg = "❌ Admin not found!";
        $showError = true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Login</title>
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
    form {
      background: white;
      padding: 40px;
      border-radius: 10px;
      box-shadow: 0 0 10px #aaa;
    }
    input {
      width: 100%;
      padding: 10px;
      margin: 10px 0;
    }
    button {
      padding: 10px;
      width: 100%;
      background: #4CAF50;
      color: white;
      border: none;
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
    <form method="POST">
  <h2>Admin Login</h2>
  <input type="text" name="username" placeholder="Username" required />
  <input type="password" name="password" placeholder="Password" required />
  <button type="submit">Login</button>

  <?php if ($showError): ?>
    <div class="error"><?php echo $msg; ?></div>
  <?php endif; ?>
</form>
  </div>
</body>
</html>
