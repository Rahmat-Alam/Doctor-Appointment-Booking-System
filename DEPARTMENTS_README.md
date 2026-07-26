# Department Management System

## Overview
The Hospital Appointment Booking System now includes a centralized department management system that allows administrators to add, edit, and delete departments dynamically.

## New Departments Added
The following new departments have been added to the system:
- **TB & Respiratory Diseases** - For tuberculosis and respiratory condition specialists
- **Ophthalmology** - For eye care and vision specialists  
- **Radiology** - For diagnostic imaging and radiology specialists

## Complete Department List
The system now includes the following departments:
1. Cardiology
2. Dermatology
3. Neurology
4. Orthopedics
5. Pediatrics
6. General Medicine
7. ENT
8. Gynecology
9. Psychiatry
10. Urology
11. **TB & Respiratory Diseases** (NEW)
12. **Ophthalmology** (NEW)
13. **Radiology** (NEW)

## Files Updated

### Core Configuration
- `departments_config.php` - Centralized department configuration file
- `DEPARTMENTS_README.md` - This documentation file

### Admin Pages
- `admin/add_doctor.php` - Updated to use centralized department list
- `admin/manage_departments.php` - New department management interface
- `admin/admin_dashboard.php` - Added link to department management
- `admin/manage_doctors.php` - Added navigation link
- `admin/manage_patients.php` - Added navigation link
- `admin/manage_appointments.php` - Added navigation link

### Doctor Pages
- `doctor/manage_account.php` - Updated to use centralized department list

## Features

### Centralized Management
- All departments are managed from a single configuration file
- Consistent department lists across all pages
- Easy to add, edit, or remove departments

### Admin Interface
- **Add Departments**: Add new departments through the admin interface
- **Edit Departments**: Modify existing department names
- **Delete Departments**: Remove departments (with confirmation)
- **Visual Grid Layout**: Departments displayed in an organized grid

### Validation
- Prevents duplicate department names
- Validates department names before adding/editing
- Maintains data integrity across the system

## Usage

### For Administrators
1. Login to admin dashboard
2. Navigate to "Manage Departments" 
3. Add new departments using the form
4. Edit or delete existing departments using the action buttons

### For Developers
1. Include `departments_config.php` in any file that needs department lists
2. Use `getDepartments()` function to get the current department array
3. Use `isValidDepartment($dept)` to validate department names
4. Use `getDepartmentsAsOptions($selected)` to generate HTML select options

## Technical Implementation

### Configuration File Structure
```php
$DEPARTMENTS = [
    'Cardiology',
    'Dermatology',
    // ... all departments
];

function getDepartments() {
    global $DEPARTMENTS;
    return $DEPARTMENTS;
}

function isValidDepartment($department) {
    global $DEPARTMENTS;
    return in_array($department, $DEPARTMENTS);
}
```

### Dynamic Updates
- The `manage_departments.php` file can dynamically update the configuration
- Changes are immediately reflected across the entire system
- No database changes required - all managed through PHP configuration

## Benefits
1. **Consistency**: All pages use the same department list
2. **Flexibility**: Easy to add new departments without code changes
3. **Maintainability**: Centralized management reduces errors
4. **User Experience**: Better organization and easier navigation
5. **Scalability**: System can easily accommodate more departments

## Future Enhancements
- Department-specific doctor availability
- Department-wise appointment statistics
- Department-specific medical record categories
- Department icons and descriptions 