<?php
// Create sample CSV file for download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="student_sample_template.csv"');

// Create the file pointer
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// Add headers
fputcsv($output, ['Full Name', 'Email', 'Phone', 'Date of Birth', 'Gender', 'Address', 'Parent Email']);

// Add sample data rows
$sample_data = [
  ['John Doe', 'john.doe@example.com', '+1234567890', '2010-05-15', 'male', '123 Main Street, City', 'parent@example.com'],
  ['Jane Smith', 'jane.smith@example.com', '+1987654321', '2010-08-20', 'female', '456 Oak Avenue, City', ''],
  ['Alex Johnson', 'alex.johnson@example.com', '', '2011-03-10', 'other', '', 'parent2@example.com'],
  ['Maria Garcia', 'maria.garcia@example.com', '+1122334455', '2010-12-01', 'female', '789 Pine Road, City', 'maria.parent@example.com']
];

foreach ($sample_data as $row) {
  fputcsv($output, $row);
}

fclose($output);
exit();
