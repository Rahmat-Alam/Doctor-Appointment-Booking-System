<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

include '../db.php';
include '../departments_config.php';

$success = "";
$error = "";

// Handle department operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $new_dept = trim($_POST['department_name']);
                if (!empty($new_dept) && !in_array($new_dept, $DEPARTMENTS)) {
                    $DEPARTMENTS[] = $new_dept;
                    // Update the config file
                    updateDepartmentsConfig($DEPARTMENTS);
                    $success = "Department '$new_dept' added successfully!";
                } else {
                    $error = "Department name is empty or already exists.";
                }
                break;
                
            case 'edit':
                $old_name = $_POST['old_name'];
                $new_name = trim($_POST['new_name']);
                if (!empty($new_name) && in_array($old_name, $DEPARTMENTS)) {
                    $key = array_search($old_name, $DEPARTMENTS);
                    $DEPARTMENTS[$key] = $new_name;
                    updateDepartmentsConfig($DEPARTMENTS);
                    $success = "Department updated successfully!";
                } else {
                    $error = "Invalid department name or name already exists.";
                }
                break;
                
            case 'delete':
                $dept_name = $_POST['department_name'];
                if (in_array($dept_name, $DEPARTMENTS)) {
                    $key = array_search($dept_name, $DEPARTMENTS);
                    unset($DEPARTMENTS[$key]);
                    $DEPARTMENTS = array_values($DEPARTMENTS); // Re-index array
                    updateDepartmentsConfig($DEPARTMENTS);
                    $success = "Department '$dept_name' deleted successfully!";
                } else {
                    $error = "Department not found.";
                }
                break;
        }
    }
}

// Function to update the departments config file
function updateDepartmentsConfig($departments) {
    $config_content = "<?php\n";
    $config_content .= "// Centralized departments configuration\n";
    $config_content .= "// This file contains all available departments in the system\n";
    $config_content .= "// Include this file in any page that needs department lists\n\n";
    $config_content .= "\$DEPARTMENTS = [\n";
    foreach ($departments as $dept) {
        $config_content .= "    '" . addslashes($dept) . "',\n";
    }
    $config_content .= "];\n\n";
    $config_content .= "// Function to get departments array\n";
    $config_content .= "function getDepartments() {\n";
    $config_content .= "    global \$DEPARTMENTS;\n";
    $config_content .= "    return \$DEPARTMENTS;\n";
    $config_content .= "}\n\n";
    $config_content .= "// Function to validate if a department exists\n";
    $config_content .= "function isValidDepartment(\$department) {\n";
    $config_content .= "    global \$DEPARTMENTS;\n";
    $config_content .= "    return in_array(\$department, \$DEPARTMENTS);\n";
    $config_content .= "}\n\n";
    $config_content .= "// Function to get departments as HTML options\n";
    $config_content .= "function getDepartmentsAsOptions(\$selected = '') {\n";
    $config_content .= "    global \$DEPARTMENTS;\n";
    $config_content .= "    \$options = '<option value=\"\">Select Department</option>';\n";
    $config_content .= "    foreach (\$DEPARTMENTS as \$dept) {\n";
    $config_content .= "        \$selected_attr = (\$selected == \$dept) ? ' selected' : '';\n";
    $config_content .= "        \$options .= '<option value=\"' . htmlspecialchars(\$dept) . '\"' . \$selected_attr . '>' . htmlspecialchars(\$dept) . '</option>';\n";
    $config_content .= "    }\n";
    $config_content .= "    return \$options;\n";
    $config_content .= "}\n";
    $config_content .= "?>";
    
    file_put_contents('../departments_config.php', $config_content);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Departments</title>
    <link rel="stylesheet" href="admin_style.css">
    <style>
        .departments-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .department-card {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 20px;
            position: relative;
        }

        .department-name {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 15px;
        }

        .department-actions {
            display: flex;
            gap: 10px;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>Manage Departments</h1>
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
        <a href="manage_departments.php" class="active">Manage Departments</a>
        <a href="view_medical_records.php">View Medical Records</a>
    </div>

    <div class="content">
        <h2>Manage Departments</h2>
        
        <?php if ($success): ?>
            <div class="message success"><?= $success ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?= $error ?></div>
        <?php endif; ?>

        <div class="card">
            <h3>Add New Department</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label for="department_name">Department Name:</label>
                    <input type="text" id="department_name" name="department_name" required placeholder="Enter department name">
                </div>
                <button type="submit" class="btn btn-success">Add Department</button>
            </form>
        </div>

        <div class="card">
            <h3>Current Departments</h3>
            <div class="departments-grid">
                <?php foreach ($DEPARTMENTS as $dept): ?>
                    <div class="department-card">
                        <div class="department-name"><?= htmlspecialchars($dept) ?></div>
                        <div class="department-actions">
                            <button class="btn btn-warning btn-sm" onclick="editDepartment('<?= htmlspecialchars($dept) ?>')">Edit</button>
                            <button class="btn btn-danger btn-sm" onclick="deleteDepartment('<?= htmlspecialchars($dept) ?>')">Delete</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h3>Edit Department</h3>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="old_name" id="old_name">
                <div class="form-group">
                    <label for="new_name">Department Name:</label>
                    <input type="text" id="new_name" name="new_name" required>
                </div>
                <button type="submit" class="btn btn-primary">Update Department</button>
            </form>
        </div>
    </div>

    <!-- Delete Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h3>Delete Department</h3>
            <p>Are you sure you want to delete this department?</p>
            <form method="POST">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="department_name" id="delete_dept_name">
                <button type="submit" class="btn btn-danger">Delete</button>
                <button type="button" class="btn btn-primary" onclick="closeModal()">Cancel</button>
            </form>
        </div>
    </div>

    <script>
    function editDepartment(name) {
        document.getElementById('old_name').value = name;
        document.getElementById('new_name').value = name;
        document.getElementById('editModal').style.display = 'block';
    }

    function deleteDepartment(name) {
        document.getElementById('delete_dept_name').value = name;
        document.getElementById('deleteModal').style.display = 'block';
    }

    function closeModal() {
        document.getElementById('editModal').style.display = 'none';
        document.getElementById('deleteModal').style.display = 'none';
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        const editModal = document.getElementById('editModal');
        const deleteModal = document.getElementById('deleteModal');
        if (event.target == editModal) {
            editModal.style.display = 'none';
        }
        if (event.target == deleteModal) {
            deleteModal.style.display = 'none';
        }
    }
    </script>

</body>
</html> 