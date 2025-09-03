<?php
// submit_survey.php - FINAL FIXED VERSION
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
    
    // Validate required fields - minimal validation
    $required_fields = ['enumerator_name'];
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
    
    // Helper function to get POST value with default
    function getPostValue($key, $default = '') {
        return isset($_POST[$key]) && $_POST[$key] !== '' ? $_POST[$key] : $default;
    }
    
    // Helper function to get float value  
    function getPostFloat($key) {
        return !empty($_POST[$key]) && $_POST[$key] !== '' ? (float)$_POST[$key] : null;
    }
    
    // Prepare data in EXACT order matching database columns
    $enumerator_name = getPostValue('enumerator_name');
    $enumerator_location = getPostValue('enumerator_location');
    $gender = getPostValue('gender');
    $age = getPostValue('age');
    $education = getPostValue('education');
    $occupation = getPostValue('occupation');
    $occupation_other = getPostValue('occupation_other');
    $income = getPostValue('income');
    $sub_county = getPostValue('sub_county');
    $ward = getPostValue('ward');
    $estate = getPostValue('estate');
    $survey_location = getPostValue('survey_location');
    $latitude = getPostFloat('latitude');
    $longitude = getPostFloat('longitude');
    $location_accuracy = getPostFloat('location_accuracy');
    $location_method = getPostValue('location_method', 'manual');
    $residence_privacy = isset($_POST['residence_privacy']) ? 1 : 0;
    $car_ownership = getPostValue('car_ownership');
    $bus_usage = getPostValue('bus_usage');
    $walking_usage = getPostValue('walking_usage');
    $trip_origin = getPostValue('trip_origin');
    $trip_destination = getPostValue('trip_destination');
    $transport_mode = getPostValue('transport_mode');
    $transport_mode_other = getPostValue('transport_mode_other');
    $transport_mode_first_mile = null; // Set to null since form doesn't have this field
    $transport_mode_main_mile = null;  // Set to null since form doesn't have this field
    $transport_mode_last_mile = null;  // Set to null since form doesn't have this field
    $general_safety = getPostValue('general_safety');
    $accident_concern = getPostValue('accident_concern');
    $driver_yield = getPostValue('driver_yield');
    $night_safety = getPostValue('night_safety');
    $walkway_importance = getPostValue('walkway_importance');
    $obstacles_frequency = getPostValue('obstacles_frequency');
    $path_connectivity = getPostValue('path_connectivity');
    $comfort_satisfaction = getPostValue('comfort_satisfaction');
    $walkway_obstruction = getPostValue('walkway_obstruction');
    $street_lighting = getPostValue('street_lighting');
    $road_surface_safety = getPostValue('road_surface_safety');
    $traffic_calming = getPostValue('traffic_calming');
    $income_limitation = getPostValue('income_limitation');
    $affordability_influence = getPostValue('affordability_influence');
    $cost_effect = getPostValue('cost_effect');
    $walk_frequency = getPostValue('walk_frequency');
    $safety_route_choice = getPostValue('safety_route_choice');
    $leisure_walk = getPostValue('leisure_walk');
    $vehicle_speed_risk = getPostValue('vehicle_speed_risk');
    $witness_accidents = getPostValue('witness_accidents');
    $crossing_danger = getPostValue('crossing_danger');
    $bus_stop_convenience = getPostValue('bus_stop_convenience');
    $path_friendliness = getPostValue('path_friendliness');
    $job_accessibility = getPostValue('job_accessibility');
    $vulnerable_accommodation = getPostValue('vulnerable_accommodation');
    $children_school_safety = getPostValue('children_school_safety');
    $wheelchair_accessibility = getPostValue('wheelchair_accessibility');
    $equal_access_effectiveness = getPostValue('equal_access_effectiveness');
    
    // Barriers (array)
    $barriers = isset($_POST['barriers']) && is_array($_POST['barriers']) ? implode(',', $_POST['barriers']) : '';
    $barriers_other = getPostValue('barriers_other');
    $additional_comments = getPostValue('additional_comments');
    $submission_time = date('Y-m-d H:i:s');
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    try {
        // FIXED: Prepare statement with EXACTLY 61 columns and 61 question marks
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
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        // FIXED: Bind EXACTLY 61 parameters with correct types
        $stmt->bind_param("ssssssssssssdddississssssssssssssssssssssssssssssssssssssssss",
            $enumerator_name, $enumerator_location,
            $gender, $age, $education, $occupation, 
            $occupation_other, $income, $sub_county, 
            $ward, $estate, $survey_location, 
            $latitude, $longitude, $location_accuracy, 
            $location_method, $residence_privacy, 
            $car_ownership, $bus_usage, $walking_usage, 
            $trip_origin, $trip_destination, $transport_mode, 
            $transport_mode_other, 
            $transport_mode_first_mile, $transport_mode_main_mile, $transport_mode_last_mile,
            $general_safety, $accident_concern, 
            $driver_yield, $night_safety, $walkway_importance, 
            $obstacles_frequency, $path_connectivity, $comfort_satisfaction, 
            $walkway_obstruction, $street_lighting, $road_surface_safety, $traffic_calming,
            $income_limitation, $affordability_influence, $cost_effect,
            $walk_frequency, $safety_route_choice, $leisure_walk,
            $vehicle_speed_risk, $witness_accidents, $crossing_danger,
            $bus_stop_convenience, $path_friendliness, $job_accessibility,
            $vulnerable_accommodation, $children_school_safety, $wheelchair_accessibility, $equal_access_effectiveness,
            $barriers, $barriers_other, $additional_comments, 
            $submission_time, $ip_address, $user_agent
        );
        
        if ($stmt->execute()) {
            $insert_id = $conn->insert_id;
            
            http_response_code(200);
            echo json_encode([
                'status' => 'success', 
                'message' => 'Survey submitted successfully!',
                'id' => $insert_id,
                'timestamp' => $submission_time
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
