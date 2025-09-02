<?php
// submit_survey.php - Fixed Version
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Database configuration
$servername = "localhost";
$username = "vxjtgclw_nairobi_survey";
$password = "FB=4x?80r=]wK;03";
$dbname = "vxjtgclw_nairobi_survey";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}

// Set charset
$conn->set_charset("utf8mb4");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Debug: Log received POST data
    error_log("POST Data: " . print_r($_POST, true));
    
    // Check if table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'survey_responses'");
    if ($table_check->num_rows == 0) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database table not found']);
        exit;
    }
    
    // Validate required fields
    $required_fields = ['enumerator_name', 'gender', 'age'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $missing_fields[] = $field;
        }
    }
    
    if (!empty($missing_fields)) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error', 
            'message' => 'Required fields missing: ' . implode(', ', $missing_fields),
            'missing_fields' => $missing_fields
        ]);
        exit;
    }
    
    // Sanitize and prepare data with proper defaults
    $data = array();
    
    // Helper function to get POST value with default
    function getPostValue($key, $default = '') {
        return isset($_POST[$key]) && $_POST[$key] !== '' ? $_POST[$key] : $default;
    }
    
    // Helper function to get float value
    function getPostFloat($key) {
        return !empty($_POST[$key]) ? (float)$_POST[$key] : null;
    }
    
    // Enumerator Information
    $data['enumerator_name'] = getPostValue('enumerator_name');
    $data['enumerator_location'] = getPostValue('enumerator_location');
    
    // Basic Information
    $data['gender'] = getPostValue('gender');
    $data['age'] = getPostValue('age');
    $data['education'] = getPostValue('education');
    $data['occupation'] = getPostValue('occupation');
    $data['occupation_other'] = getPostValue('occupation_other');
    $data['income'] = getPostValue('income');
    $data['sub_county'] = getPostValue('sub_county');
    $data['ward'] = getPostValue('ward');
    $data['estate'] = getPostValue('estate');
    
    // Location fields
    $data['survey_location'] = getPostValue('survey_location');
    $data['latitude'] = getPostFloat('latitude');
    $data['longitude'] = getPostFloat('longitude');
    $data['location_accuracy'] = getPostFloat('location_accuracy');
    $data['location_method'] = getPostValue('location_method', 'manual');
    
    $data['residence_privacy'] = isset($_POST['residence_privacy']) ? 1 : 0;
    $data['car_ownership'] = getPostValue('car_ownership');
    $data['bus_usage'] = getPostValue('bus_usage');
    $data['walking_usage'] = getPostValue('walking_usage');
    $data['trip_origin'] = getPostValue('trip_origin');
    $data['trip_destination'] = getPostValue('trip_destination');
    
    // Transport modes
    $data['transport_mode'] = getPostValue('transport_mode');
    $data['transport_mode_other'] = getPostValue('transport_mode_other');
    $data['transport_mode_first_mile'] = getPostValue('transport_mode_first_mile');
    $data['transport_mode_main_mile'] = getPostValue('transport_mode_main_mile');
    $data['transport_mode_last_mile'] = getPostValue('transport_mode_last_mile');
    
    // Section A: Pedestrian Safety Perception
    $data['general_safety'] = getPostValue('general_safety');
    $data['accident_concern'] = getPostValue('accident_concern');
    $data['driver_yield'] = getPostValue('driver_yield');
    $data['night_safety'] = getPostValue('night_safety');
    
    // Section B: Walkability of Urban Environment
    $data['walkway_importance'] = getPostValue('walkway_importance');
    $data['obstacles_frequency'] = getPostValue('obstacles_frequency');
    $data['path_connectivity'] = getPostValue('path_connectivity');
    $data['comfort_satisfaction'] = getPostValue('comfort_satisfaction');
    
    // Section C: Infrastructure Quality
    $data['walkway_obstruction'] = getPostValue('walkway_obstruction');
    $data['street_lighting'] = getPostValue('street_lighting');
    $data['road_surface_safety'] = getPostValue('road_surface_safety');
    $data['traffic_calming'] = getPostValue('traffic_calming');
    
    // Section D: Socioeconomic Context
    $data['income_limitation'] = getPostValue('income_limitation');
    $data['affordability_influence'] = getPostValue('affordability_influence');
    $data['cost_effect'] = getPostValue('cost_effect');
    
    // Section E: Pedestrian Mobility Patterns
    $data['walk_frequency'] = getPostValue('walk_frequency');
    $data['safety_route_choice'] = getPostValue('safety_route_choice');
    $data['leisure_walk'] = getPostValue('leisure_walk');
    
    // Section F: Traffic-Related Safety Risks
    $data['vehicle_speed_risk'] = getPostValue('vehicle_speed_risk');
    $data['witness_accidents'] = getPostValue('witness_accidents');
    $data['crossing_danger'] = getPostValue('crossing_danger');
    
    // Section G: Last Mile Accessibility
    $data['bus_stop_convenience'] = getPostValue('bus_stop_convenience');
    $data['path_friendliness'] = getPostValue('path_friendliness');
    $data['job_accessibility'] = getPostValue('job_accessibility');
    
    // Section H: Accessibility for Vulnerable Groups
    $data['vulnerable_accommodation'] = getPostValue('vulnerable_accommodation');
    $data['children_school_safety'] = getPostValue('children_school_safety');
    $data['wheelchair_accessibility'] = getPostValue('wheelchair_accessibility');
    $data['equal_access_effectiveness'] = getPostValue('equal_access_effectiveness');
    
    // Barriers (array)
    $barriers = isset($_POST['barriers']) && is_array($_POST['barriers']) ? implode(',', $_POST['barriers']) : '';
    $data['barriers'] = $barriers;
    $data['barriers_other'] = getPostValue('barriers_other');
    
    // Additional comments
    $data['additional_comments'] = getPostValue('additional_comments');
    
    // Metadata
    $data['submission_time'] = date('Y-m-d H:i:s');
    $data['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';
    $data['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    try {
        // Prepare the insert statement
        $sql = "INSERT INTO survey_responses (
            enumerator_name, enumerator_location,
            gender, age, education, occupation, occupation_other, income, 
            sub_county, ward, estate, survey_location, latitude, longitude, 
            location_accuracy, location_method, residence_privacy, car_ownership, 
            bus_usage, walking_usage, trip_origin, trip_destination, 
            transport_mode, transport_mode_other, 
            transport_mode_first_mile, transport_mode_main_mile, transport_mode_last_mile,
            general_safety, accident_concern, driver_yield, night_safety, 
            walkway_importance, obstacles_frequency, path_connectivity, comfort_satisfaction, 
            walkway_obstruction, street_lighting, road_surface_safety, traffic_calming,
            income_limitation, affordability_influence, cost_effect, 
            walk_frequency, safety_route_choice, leisure_walk, 
            vehicle_speed_risk, witness_accidents, crossing_danger,
            bus_stop_convenience, path_friendliness, job_accessibility, 
            vulnerable_accommodation, children_school_safety, wheelchair_accessibility, equal_access_effectiveness,
            barriers, barriers_other, additional_comments, submission_time, 
            ip_address, user_agent
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        // Bind all 60 parameters
        $stmt->bind_param("sssssssssssddssissssssssssssssssssssssssssssssssssssssssssss",
            $data['enumerator_name'], $data['enumerator_location'],
            $data['gender'], $data['age'], $data['education'], $data['occupation'], 
            $data['occupation_other'], $data['income'], $data['sub_county'], 
            $data['ward'], $data['estate'], $data['survey_location'], 
            $data['latitude'], $data['longitude'], $data['location_accuracy'], 
            $data['location_method'], $data['residence_privacy'], 
            $data['car_ownership'], $data['bus_usage'], $data['walking_usage'], 
            $data['trip_origin'], $data['trip_destination'], $data['transport_mode'], 
            $data['transport_mode_other'], 
            $data['transport_mode_first_mile'], $data['transport_mode_main_mile'], $data['transport_mode_last_mile'],
            $data['general_safety'], $data['accident_concern'], 
            $data['driver_yield'], $data['night_safety'], $data['walkway_importance'], 
            $data['obstacles_frequency'], $data['path_connectivity'], $data['comfort_satisfaction'], 
            $data['walkway_obstruction'], $data['street_lighting'], $data['road_surface_safety'], $data['traffic_calming'],
            $data['income_limitation'], $data['affordability_influence'], $data['cost_effect'],
            $data['walk_frequency'], $data['safety_route_choice'], $data['leisure_walk'],
            $data['vehicle_speed_risk'], $data['witness_accidents'], $data['crossing_danger'],
            $data['bus_stop_convenience'], $data['path_friendliness'], $data['job_accessibility'],
            $data['vulnerable_accommodation'], $data['children_school_safety'], $data['wheelchair_accessibility'], $data['equal_access_effectiveness'],
            $data['barriers'], $data['barriers_other'], $data['additional_comments'], 
            $data['submission_time'], $data['ip_address'], $data['user_agent']
        );
        
        if ($stmt->execute()) {
            $insert_id = $conn->insert_id;
            
            http_response_code(200);
            echo json_encode([
                'status' => 'success', 
                'message' => 'Survey submitted successfully!',
                'id' => $insert_id,
                'timestamp' => $data['submission_time']
            ]);
        } else {
            throw new Exception('Execute failed: ' . $stmt->error);
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        error_log("Survey submission error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'status' => 'error', 
            'message' => 'Failed to save survey. Please try again.',
            'error_details' => $e->getMessage()
        ]);
    }
    
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Only POST method allowed']);
}

$conn->close();
?>