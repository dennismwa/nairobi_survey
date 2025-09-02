<?php
// admin/export_excel.php - Updated with All WOD Fields
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

// Database connection
$servername = "localhost";
$username = "vxjtgclw_nairobi_survey";
$password = "FB=4x?80r=]wK;03";
$dbname = "vxjtgclw_nairobi_survey";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Determine export type and build query
$where_clause = "";
$filename_suffix = "";

if (isset($_POST['selected_ids'])) {
    // Export selected responses
    $selected_ids = json_decode($_POST['selected_ids'], true);
    if (!empty($selected_ids)) {
        $ids_string = implode(',', array_map('intval', $selected_ids));
        $where_clause = "WHERE id IN ($ids_string)";
        $filename_suffix = "_selected";
    }
} elseif (isset($_GET['export']) && $_GET['export'] === 'current') {
    // Export current view with search
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    if ($search) {
        $search_escaped = $conn->real_escape_string($search);
        $where_clause = "WHERE id LIKE '%$search_escaped%' 
                         OR gender LIKE '%$search_escaped%' 
                         OR age LIKE '%$search_escaped%' 
                         OR education LIKE '%$search_escaped%' 
                         OR occupation LIKE '%$search_escaped%'
                         OR sub_county LIKE '%$search_escaped%'
                         OR ward LIKE '%$search_escaped%'
                         OR estate LIKE '%$search_escaped%'
                         OR survey_location LIKE '%$search_escaped%'";
        $filename_suffix = "_filtered";
    }
}

// Get all survey responses
$query = "SELECT * FROM survey_responses $where_clause ORDER BY submission_time DESC";
$result = $conn->query($query);

if (!$result) {
    die("Query failed: " . $conn->error);
}

// Create CSV content
$csv_data = [];

// Complete headers array with all WOD fields
$headers = [
    'Response ID',
    'Submission Time',
    'IP Address',
    'Enumerator Name',
    'Enumerator Location',
    
    // Basic Information
    'Gender',
    'Age Group',
    'Education Level',
    'Occupation',
    'Occupation Other',
    'Monthly Income',
    'Sub County',
    'Ward',
    'Estate',
    'Survey Location',
    'Latitude',
    'Longitude',
    'Location Accuracy',
    'Location Method',
    'Residence Privacy',
    'Car Ownership',
    'Bus Usage Frequency',
    'Walking Usage Frequency',
    'Recent Trip Origin',
    'Recent Trip Destination',
    
    // Transport Mode Fields
    'Transport Mode',
    'Transport Mode Other',
    'Transport Mode First Mile',
    'Transport Mode Main Mile',  
    'Transport Mode Last Mile',
    
    // Section A: Pedestrian Safety Perception
    'General Safety Feeling',
    'Accident Concern Level',
    'Driver Yield Frequency',
    'Night Safety Feeling',
    
    // Section B: Walkability of Urban Environment
    'Walkway Maintenance Importance',
    'Obstacles Frequency',
    'Path Connectivity Agreement',
    'Comfort Satisfaction',
    
    // Section C: Infrastructure Quality
    'Walkway Obstruction Freedom',
    'Street Lighting Adequacy',
    'Road Surface Safety',
    'Traffic Calming Effectiveness',
    
    // Section D: Socioeconomic Context
    'Income Transportation Limitation',
    'Affordability Walking Influence',
    'Transport Cost Effect',
    
    // Section E: Pedestrian Mobility Patterns
    'Walking Frequency to Facilities',
    'Safety Concerns Route Choice',
    'Leisure Walking Frequency',
    
    // Section F: Traffic-Related Safety Risks
    'Vehicle Speed Risk Frequency',
    'Accident Witness Frequency',
    'Road Crossing Danger Level',
    
    // Section G: Last Mile Accessibility
    'Bus Stop Walking Convenience',
    'Path to Transport Friendliness',
    'Job Accessibility Walking Distance',
    
    // Section H: Accessibility for Vulnerable Groups
    'Vulnerable Group Accommodation',
    'Children School Walking Safety',
    'Wheelchair Accessibility',
    'Equal Access Effectiveness',
    
    // Additional Information
    'Walking Barriers',
    'Other Barriers',
    'Additional Comments'
];

// Add headers as first row
$csv_data[] = $headers;

// Process data rows with all fields
while ($row = $result->fetch_assoc()) {
    $data_row = [
        $row['id'],
        $row['submission_time'],
        $row['ip_address'],
        $row['enumerator_name'] ?? '',
        $row['enumerator_location'] ?? '',
        
        // Basic Information
        ucfirst(str_replace('_', ' ', $row['gender'] ?? '')),
        str_replace('_', '-', $row['age'] ?? ''),
        ucfirst(str_replace('_', ' ', $row['education'] ?? '')),
        ucfirst(str_replace('_', ' ', $row['occupation'] ?? '')),
        $row['occupation_other'] ?? '',
        $row['income'] ?? '',
        $row['sub_county'] ?? '',
        $row['ward'] ?? '',
        $row['estate'] ?? '',
        $row['survey_location'] ?? '',
        $row['latitude'] ?? '',
        $row['longitude'] ?? '',
        $row['location_accuracy'] ?? '',
        ucfirst($row['location_method'] ?? ''),
        $row['residence_privacy'] ? 'Yes' : 'No',
        ucfirst($row['car_ownership'] ?? ''),
        ucfirst($row['bus_usage'] ?? ''),
        ucfirst($row['walking_usage'] ?? ''),
        $row['trip_origin'] ?? '',
        $row['trip_destination'] ?? '',
        
        // Transport Mode Fields
        str_replace('_', '/', ucfirst($row['transport_mode'] ?? '')),
        $row['transport_mode_other'] ?? '',
        ucfirst(str_replace('_', '/', $row['transport_mode_first_mile'] ?? '')),
        ucfirst(str_replace('_', '/', $row['transport_mode_main_mile'] ?? '')),
        ucfirst(str_replace('_', '/', $row['transport_mode_last_mile'] ?? '')),
        
        // Section A: Pedestrian Safety Perception
        ucfirst(str_replace('_', ' ', $row['general_safety'] ?? '')),
        ucfirst(str_replace('_', ' ', $row['accident_concern'] ?? '')),
        ucfirst($row['driver_yield'] ?? ''),
        ucfirst(str_replace('_', ' ', $row['night_safety'] ?? '')),
        
        // Section B: Walkability of Urban Environment
        ucfirst(str_replace('_', ' ', $row['walkway_importance'] ?? '')),
        ucfirst($row['obstacles_frequency'] ?? ''),
        ucfirst(str_replace('_', ' ', $row['path_connectivity'] ?? '')),
        ucfirst(str_replace('_', ' ', $row['comfort_satisfaction'] ?? '')),
        
        // Section C: Infrastructure Quality
        ucfirst($row['walkway_obstruction'] ?? ''),
        ucfirst(str_replace('_', ' ', $row['street_lighting'] ?? '')),
        ucfirst(str_replace('_', ' ', $row['road_surface_safety'] ?? '')),
        ucfirst(str_replace('_', ' ', $row['traffic_calming'] ?? '')),
        
        // Section D: Socioeconomic Context
        ucfirst($row['income_limitation'] ?? ''),
        ucfirst(str_replace('_', ' ', $row['affordability_influence'] ?? '')),
        ucfirst(str_replace('_', ' ', $row['cost_effect'] ?? '')),
        
        // Section E: Pedestrian Mobility Patterns
        ucfirst($row['walk_frequency'] ?? ''),
        ucfirst($row['safety_route_choice'] ?? ''),
        ucfirst($row['leisure_walk'] ?? ''),
        
        // Section F: Traffic-Related Safety Risks
        ucfirst($row['vehicle_speed_risk'] ?? ''),
        ucfirst($row['witness_accidents'] ?? ''),
        ucfirst(str_replace('_', ' ', $row['crossing_danger'] ?? '')),
        
        // Section G: Last Mile Accessibility
        ucfirst(str_replace('_', ' ', $row['bus_stop_convenience'] ?? '')),
        ucfirst(str_replace('_', ' ', $row['path_friendliness'] ?? '')),
        ucfirst(str_replace('_', ' ', $row['job_accessibility'] ?? '')),
        
        // Section H: Accessibility for Vulnerable Groups
        ucfirst($row['vulnerable_accommodation'] ?? ''),
        ucfirst(str_replace('_', ' ', $row['children_school_safety'] ?? '')),
        ucfirst(str_replace('_', ' ', $row['wheelchair_accessibility'] ?? '')),
        ucfirst(str_replace('_', ' ', $row['equal_access_effectiveness'] ?? '')),
        
        // Additional Information
        str_replace(',', '; ', $row['barriers'] ?? ''),
        $row['barriers_other'] ?? '',
        $row['additional_comments'] ?? ''
    ];
    
    $csv_data[] = $data_row;
}

// Generate filename
$timestamp = date('Y-m-d_H-i-s');
$filename = "nairobi_survey_responses{$filename_suffix}_{$timestamp}.csv";

// Set headers for file download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

// Output CSV
$output = fopen('php://output', 'w');

// Add BOM for proper UTF-8 handling in Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

foreach ($csv_data as $row) {
    fputcsv($output, $row);
}

fclose($output);
$conn->close();
exit;
?>