<?php
// Centralized departments configuration
// This file contains all available departments in the system
// Include this file in any page that needs department lists

$DEPARTMENTS = [
    'Cardiology',
    'Dermatology',
    'Neurology',
    'Orthopedics',
    'Pediatrics',
    'General Medicine',
    'ENT',
    'Gynecology',
    'Psychiatry',
    'Urology',
    'TB & Respiratory Diseases',
    'Ophthalmology',
    'Radiology',
];

// Function to get departments array
function getDepartments() {
    global $DEPARTMENTS;
    return $DEPARTMENTS;
}

// Function to validate if a department exists
function isValidDepartment($department) {
    global $DEPARTMENTS;
    return in_array($department, $DEPARTMENTS);
}

// Function to get departments as HTML options
function getDepartmentsAsOptions($selected = '') {
    global $DEPARTMENTS;
    $options = '<option value="">Select Department</option>';
    foreach ($DEPARTMENTS as $dept) {
        $selected_attr = ($selected == $dept) ? ' selected' : '';
        $options .= '<option value="' . htmlspecialchars($dept) . '"' . $selected_attr . '>' . htmlspecialchars($dept) . '</option>';
    }
    return $options;
}
?>