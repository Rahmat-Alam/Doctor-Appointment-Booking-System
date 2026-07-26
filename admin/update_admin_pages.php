<?php
// Script to update all admin pages with the new design
// This will replace the old styling with the new shared CSS

$admin_files = [
    'manage_appointments.php',
    'add_patient.php',
    'add_admin.php',
    'edit_doctor.php',
    'edit_patient.php',
    'view_medical_records.php'
];

foreach ($admin_files as $file) {
    if (file_exists($file)) {
        echo "Updating $file...\n";
        
        // Read the file content
        $content = file_get_contents($file);
        
        // Replace old style tags with CSS link
        $content = preg_replace('/<style>.*?<\/style>/s', '<link rel="stylesheet" href="admin_style.css">', $content);
        
        // Add header and navigation if not present
        if (!strpos($content, '<div class="header">')) {
            $content = preg_replace('/<body>/', '<body>
    <div class="header">
        <h1>' . ucfirst(str_replace(['.php', '_'], ['', ' '], $file)) . '</h1>
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
        <a href="manage_appointments.php">Manage Appointments</a>
        <a href="manage_departments.php">Manage Departments</a>
        <a href="view_medical_records.php">View Medical Records</a>
    </div>

    <div class="content">', $content);
        }
        
        // Write back the updated content
        file_put_contents($file, $content);
        echo "Updated $file successfully!\n";
    } else {
        echo "File $file not found.\n";
    }
}

echo "All admin pages updated with new design!\n";
?> 