<?php
// admin/generate_report.php - Enhanced WOD Analysis Report
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

$conn = null; // Initialize connection variable

try {
    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Get comprehensive statistics
    $stats = [
        'total_responses' => 0,
        'this_month' => 0,
        'male_count' => 0,
        'female_count' => 0,
        'no_car' => 0,
        'daily_walkers' => 0,
        'feel_safe' => 0,
        'walkway_extremely_important' => 0,
        'very_dissatisfied_comfort' => 0,
        'vulnerable_poorly_accommodated' => 0
    ];

    // Basic statistics queries
    $result = $conn->query("SELECT COUNT(*) as count FROM survey_responses");
    if ($result) {
        $stats['total_responses'] = $result->fetch_assoc()['count'];
    }

    $result = $conn->query("SELECT COUNT(*) as count FROM survey_responses WHERE MONTH(submission_time) = MONTH(NOW()) AND YEAR(submission_time) = YEAR(NOW())");
    if ($result) {
        $stats['this_month'] = $result->fetch_assoc()['count'];
    }

    // Gender distribution
    $result = $conn->query("SELECT COUNT(*) as count FROM survey_responses WHERE gender = 'male'");
    if ($result) {
        $stats['male_count'] = $result->fetch_assoc()['count'];
    }
    
    $result = $conn->query("SELECT COUNT(*) as count FROM survey_responses WHERE gender = 'female'");
    if ($result) {
        $stats['female_count'] = $result->fetch_assoc()['count'];
    }

    // Transportation patterns
    $result = $conn->query("SELECT COUNT(*) as count FROM survey_responses WHERE car_ownership = 'no'");
    if ($result) {
        $stats['no_car'] = $result->fetch_assoc()['count'];
    }

    $result = $conn->query("SELECT COUNT(*) as count FROM survey_responses WHERE walking_usage = 'daily'");
    if ($result) {
        $stats['daily_walkers'] = $result->fetch_assoc()['count'];
    }

    // Safety perception
    $result = $conn->query("SELECT COUNT(*) as count FROM survey_responses WHERE general_safety IN ('safe', 'very_safe')");
    if ($result) {
        $stats['feel_safe'] = $result->fetch_assoc()['count'];
    }

    // WOD-specific metrics
    $result = $conn->query("SELECT COUNT(*) as count FROM survey_responses WHERE walkway_importance = 'extremely_important'");
    if ($result) {
        $stats['walkway_extremely_important'] = $result->fetch_assoc()['count'];
    }

    $result = $conn->query("SELECT COUNT(*) as count FROM survey_responses WHERE comfort_satisfaction IN ('very_dissatisfied', 'dissatisfied')");
    if ($result) {
        $stats['very_dissatisfied_comfort'] = $result->fetch_assoc()['count'];
    }

    $result = $conn->query("SELECT COUNT(*) as count FROM survey_responses WHERE vulnerable_accommodation IN ('not_at_all', 'slightly')");
    if ($result) {
        $stats['vulnerable_poorly_accommodated'] = $result->fetch_assoc()['count'];
    }

    // Get detailed WOD analysis data
    $wod_metrics = [];
    
    // Safety perception distribution
    $safety_data = [];
    $result = $conn->query("SELECT general_safety, COUNT(*) as count FROM survey_responses WHERE general_safety IS NOT NULL GROUP BY general_safety");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $safety_data[$row['general_safety']] = $row['count'];
        }
    }

    // Walkway importance analysis
    $walkway_importance_data = [];
    $result = $conn->query("SELECT walkway_importance, COUNT(*) as count FROM survey_responses WHERE walkway_importance IS NOT NULL GROUP BY walkway_importance");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $walkway_importance_data[$row['walkway_importance']] = $row['count'];
        }
    }

    // Infrastructure quality ratings
    $infrastructure_data = [];
    $infrastructure_fields = ['street_lighting', 'road_surface_safety', 'traffic_calming'];
    foreach ($infrastructure_fields as $field) {
        $result = $conn->query("SELECT $field, COUNT(*) as count FROM survey_responses WHERE $field IS NOT NULL GROUP BY $field");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $infrastructure_data[$field][$row[$field]] = $row['count'];
            }
        }
    }

    // Vulnerable groups accommodation
    $vulnerable_data = [];
    $result = $conn->query("SELECT vulnerable_accommodation, COUNT(*) as count FROM survey_responses WHERE vulnerable_accommodation IS NOT NULL GROUP BY vulnerable_accommodation");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $vulnerable_data[$row['vulnerable_accommodation']] = $row['count'];
        }
    }

    // Barriers analysis (enhanced)
    $barriers_data = [];
    $result = $conn->query("SELECT barriers FROM survey_responses WHERE barriers IS NOT NULL AND barriers != ''");
    if ($result) {
        $barrier_counts = [];
        while ($row = $result->fetch_assoc()) {
            $barriers = explode(',', $row['barriers']);
            foreach ($barriers as $barrier) {
                $barrier = trim($barrier);
                if (!empty($barrier)) {
                    $barrier_counts[$barrier] = ($barrier_counts[$barrier] ?? 0) + 1;
                }
            }
        }
        arsort($barrier_counts);
        $barriers_data = array_slice($barrier_counts, 0, 10, true);
    }

    // Transport mode analysis
    $transport_modes = [];
    $modes = ['transport_mode_first_mile', 'transport_mode_main_mile', 'transport_mode_last_mile'];
    foreach ($modes as $mode_field) {
        $result = $conn->query("SELECT $mode_field, COUNT(*) as count FROM survey_responses WHERE $mode_field IS NOT NULL GROUP BY $mode_field");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $transport_modes[$mode_field][$row[$mode_field]] = $row['count'];
            }
        }
    }

    // Monthly trends
    $monthly_trends = [];
    $result = $conn->query("SELECT DATE_FORMAT(submission_time, '%Y-%m') as month, COUNT(*) as count FROM survey_responses WHERE submission_time >= DATE_SUB(NOW(), INTERVAL 12 MONTH) GROUP BY DATE_FORMAT(submission_time, '%Y-%m') ORDER BY month");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $monthly_trends[$row['month']] = $row['count'];
        }
    }

} catch (Exception $e) {
    die("System Error: Unable to generate report. Please contact administrator.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WOD Analysis Report - Nairobi Walkability Study</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; }
            .gradient-bg { background: #667eea !important; }
            .chart-container { break-inside: avoid; }
        }
        
        .chart-container {
            position: relative;
            height: 400px;
            margin: 20px 0;
        }

        .chart-small {
            position: relative;
            height: 300px;
            margin: 20px 0;
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Navigation -->
    <nav class="gradient-bg shadow-lg no-print">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="dashboard.php" class="flex-shrink-0 flex items-center text-white hover:text-gray-200">
                        <i class="fas fa-arrow-left text-lg mr-3"></i>
                        <i class="fas fa-chart-pie text-2xl mr-3"></i>
                        <span class="text-xl font-bold">WOD Analysis Report</span>
                    </a>
                </div>
                
                <div class="flex items-center space-x-4">
                    <button onclick="window.print()" class="bg-white bg-opacity-20 text-white px-4 py-2 rounded-lg hover:bg-opacity-30 transition-all duration-200">
                        <i class="fas fa-print mr-2"></i>Print Report
                    </button>
                    <div class="text-white">
                        <i class="fas fa-user-circle mr-2"></i>
                        <?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?>
                    </div>
                    <a href="logout.php" class="bg-white bg-opacity-20 text-white px-4 py-2 rounded-lg hover:bg-opacity-30 transition-all duration-200">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        
        <!-- Report Header -->
        <div class="bg-white rounded-xl shadow-lg p-8 mb-8 text-center">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">
                Nairobi Walkable-Oriented Development (WOD) Survey
            </h1>
            <h2 class="text-2xl text-gray-600 mb-6">
                Comprehensive Analysis Report
            </h2>
            <div class="flex justify-center items-center space-x-8 text-sm text-gray-500">
                <div>
                    <i class="fas fa-calendar mr-2"></i>
                    Generated: <?php echo date('F j, Y'); ?>
                </div>
                <div>
                    <i class="fas fa-database mr-2"></i>
                    Total Responses: <?php echo number_format($stats['total_responses']); ?>
                </div>
                <div>
                    <i class="fas fa-chart-line mr-2"></i>
                    This Month: <?php echo number_format($stats['this_month']); ?>
                </div>
            </div>
        </div>

        <!-- Executive Summary -->
        <div class="bg-white rounded-xl shadow-lg p-8 mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                <i class="fas fa-clipboard-list text-blue-600 mr-3"></i>
                Executive Summary
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-blue-50 p-6 rounded-lg border border-blue-200">
                    <div class="text-3xl font-bold text-blue-600"><?php echo number_format($stats['total_responses']); ?></div>
                    <div class="text-blue-800 font-medium">Total Survey Responses</div>
                </div>
                
                <div class="bg-green-50 p-6 rounded-lg border border-green-200">
                    <div class="text-3xl font-bold text-green-600">
                        <?php 
                        echo $stats['total_responses'] > 0 ? 
                            round(($stats['feel_safe'] / $stats['total_responses']) * 100, 1) . '%' : '0%';
                        ?>
                    </div>
                    <div class="text-green-800 font-medium">Feel Safe Walking</div>
                </div>
                
                <div class="bg-purple-50 p-6 rounded-lg border border-purple-200">
                    <div class="text-3xl font-bold text-purple-600">
                        <?php 
                        echo $stats['total_responses'] > 0 ? 
                            round(($stats['walkway_extremely_important'] / $stats['total_responses']) * 100, 1) . '%' : '0%';
                        ?>
                    </div>
                    <div class="text-purple-800 font-medium">Rate Walkways as Extremely Important</div>
                </div>
                
                <div class="bg-red-50 p-6 rounded-lg border border-red-200">
                    <div class="text-3xl font-bold text-red-600">
                        <?php 
                        echo $stats['total_responses'] > 0 ? 
                            round(($stats['vulnerable_poorly_accommodated'] / $stats['total_responses']) * 100, 1) . '%' : '0%';
                        ?>
                    </div>
                    <div class="text-red-800 font-medium">Say Vulnerable Groups Poorly Accommodated</div>
                </div>
            </div>
            
            <div class="prose max-w-none text-gray-700">
                <p class="mb-4">
                    This comprehensive analysis of <?php echo number_format($stats['total_responses']); ?> survey responses provides crucial insights into the state of walkable-oriented development in Nairobi. The study examines pedestrian safety perceptions, infrastructure quality, accessibility for vulnerable groups, and barriers to walking in urban areas around BRT Line 3.
                </p>
                
                <?php if ($stats['total_responses'] > 0): ?>
                <p class="mb-4">
                    <strong>Key WOD Findings:</strong> While <?php echo round(($stats['walkway_extremely_important'] / $stats['total_responses']) * 100, 1); ?>% of respondents consider well-maintained walkways extremely important, only <?php echo round(($stats['feel_safe'] / $stats['total_responses']) * 100, 1); ?>% feel safe walking in Nairobi. This gap highlights the urgent need for walkable-oriented development interventions.
                </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- WOD-Specific Analysis -->
        <div class="bg-white rounded-xl shadow-lg p-8 mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-8 flex items-center">
                <i class="fas fa-road text-indigo-600 mr-3"></i>
                Walkable-Oriented Development Analysis
            </h2>

            <!-- Safety vs. Infrastructure Importance -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <?php if (!empty($safety_data) && !empty($walkway_importance_data)): ?>
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Safety Perception vs. Infrastructure Importance</h3>
                    <div class="chart-small">
                        <canvas id="safetyVsImportanceChart"></canvas>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($vulnerable_data)): ?>
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Vulnerable Groups Accommodation</h3>
                    <div class="chart-small">
                        <canvas id="vulnerableChart"></canvas>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Infrastructure Quality Analysis -->
            <?php if (!empty($infrastructure_data)): ?>
            <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-6">Infrastructure Quality Assessment</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <?php foreach ($infrastructure_data as $field => $data): ?>
                        <div class="bg-gray-50 p-6 rounded-lg">
                            <h4 class="font-semibold text-gray-800 mb-4 capitalize">
                                <?php echo str_replace('_', ' ', $field); ?>
                            </h4>
                            <div class="space-y-3">
                                <?php foreach ($data as $rating => $count): ?>
                                    <?php $percentage = array_sum($data) > 0 ? round(($count / array_sum($data)) * 100, 1) : 0; ?>
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-600 capitalize">
                                            <?php echo str_replace('_', ' ', $rating); ?>
                                        </span>
                                        <div class="flex items-center space-x-2">
                                            <div class="w-20 bg-gray-200 rounded-full h-2">
                                                <div class="bg-indigo-600 h-2 rounded-full" style="width: <?php echo $percentage; ?>%"></div>
                                            </div>
                                            <span class="text-sm font-medium text-gray-900 w-12 text-right">
                                                <?php echo $percentage; ?>%
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Transport Mode Analysis -->
        <?php if (!empty($transport_modes)): ?>
        <div class="bg-white rounded-xl shadow-lg p-8 mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                <i class="fas fa-route text-green-600 mr-3"></i>
                Multi-Modal Transport Analysis
            </h2>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <?php 
                $mode_labels = [
                    'transport_mode_first_mile' => 'First Mile',
                    'transport_mode_main_mile' => 'Main Mile', 
                    'transport_mode_last_mile' => 'Last Mile'
                ];
                
                foreach ($transport_modes as $mode_field => $mode_data): 
                ?>
                <div class="text-center">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4"><?php echo $mode_labels[$mode_field]; ?></h3>
                    <div class="chart-small">
                        <canvas id="<?php echo $mode_field; ?>Chart"></canvas>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="mt-8 bg-blue-50 p-6 rounded-lg border border-blue-200">
                <h3 class="text-lg font-semibold text-blue-800 mb-4">Multi-Modal Transport Insights</h3>
                <div class="text-blue-700 text-sm space-y-2">
                    <p>• <strong>First Mile:</strong> How people get from home to main transport</p>
                    <p>• <strong>Main Mile:</strong> Primary long-distance transport mode</p>
                    <p>• <strong>Last Mile:</strong> How people reach final destination</p>
                    <p class="mt-4 font-medium">Walking plays a crucial role in connecting people to public transport, highlighting the importance of walkable infrastructure around transit hubs.</p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Enhanced Barriers Analysis -->
        <?php if (!empty($barriers_data)): ?>
        <div class="bg-white rounded-xl shadow-lg p-8 mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                <i class="fas fa-exclamation-triangle text-red-600 mr-3"></i>
                Critical Walking Barriers Analysis
            </h2>
            
            <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Top Walking Barriers</h3>
                <div class="chart-container">
                    <canvas id="barriersChart"></canvas>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="bg-red-50 p-6 rounded-lg border border-red-200">
                    <h3 class="text-lg font-semibold text-red-800 mb-4">Priority Intervention Areas</h3>
                    <div class="space-y-3">
                        <?php 
                        $top_barriers = array_slice($barriers_data, 0, 5, true);
                        foreach ($top_barriers as $barrier => $count): 
                            $barrier_labels = [
                                'poor_sidewalk' => 'Poor sidewalk conditions',
                                'unsafe_crossings' => 'Unsafe road crossings',
                                'crime_concerns' => 'Security and crime concerns',
                                'poor_lighting' => 'Inadequate street lighting',
                                'vehicle_speeds' => 'High vehicle speeds',
                                'narrow_sidewalks' => 'Narrow/crowded walkways'
                            ];
                            $label = $barrier_labels[$barrier] ?? ucfirst(str_replace('_', ' ', $barrier));
                        ?>
                            <div class="flex items-center justify-between">
                                <span class="text-red-700 font-medium"><?php echo $label; ?></span>
                                <span class="bg-red-200 text-red-800 px-3 py-1 rounded-full text-sm font-bold">
                                    <?php echo $count; ?> reports
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="bg-green-50 p-6 rounded-lg border border-green-200">
                    <h3 class="text-lg font-semibold text-green-800 mb-4">WOD Implementation Priorities</h3>
                    <div class="space-y-3 text-green-700 text-sm">
                        <div class="flex items-start">
                            <i class="fas fa-check-circle text-green-600 mr-2 mt-0.5"></i>
                            <span><strong>Infrastructure:</strong> Sidewalk rehabilitation and expansion</span>
                        </div>
                        <div class="flex items-start">
                            <i class="fas fa-check-circle text-green-600 mr-2 mt-0.5"></i>
                            <span><strong>Safety:</strong> Improved lighting and secure crossing points</span>
                        </div>
                        <div class="flex items-start">
                            <i class="fas fa-check-circle text-green-600 mr-2 mt-0.5"></i>
                            <span><strong>Traffic:</strong> Speed reduction and calming measures</span>
                        </div>
                        <div class="flex items-start">
                            <i class="fas fa-check-circle text-green-600 mr-2 mt-0.5"></i>
                            <span><strong>Accessibility:</strong> Universal design for all users</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- WOD Recommendations -->
        <div class="bg-white rounded-xl shadow-lg p-8 mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                <i class="fas fa-lightbulb text-yellow-600 mr-3"></i>
                Walkable-Oriented Development Recommendations
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="space-y-6">
                    <div class="bg-blue-50 p-6 rounded-lg border border-blue-200">
                        <h3 class="text-lg font-semibold text-blue-800 mb-3">
                            <i class="fas fa-road mr-2"></i>Infrastructure Development
                        </h3>
                        <ul class="text-blue-700 space-y-2 text-sm">
                            <li>• Complete sidewalk networks around BRT stations</li>
                            <li>• Install adequate street lighting for 24/7 safety</li>
                            <li>• Create covered waiting areas and weather protection</li>
                            <li>• Implement proper stormwater drainage systems</li>
                        </ul>
                    </div>
                    
                    <div class="bg-green-50 p-6 rounded-lg border border-green-200">
                        <h3 class="text-lg font-semibold text-green-800 mb-3">
                            <i class="fas fa-shield-alt mr-2"></i>Safety & Security
                        </h3>
                        <ul class="text-green-700 space-y-2 text-sm">
                            <li>• Traffic calming measures in pedestrian areas</li>
                            <li>• Safe crossing facilities with proper signalization</li>
                            <li>• Community policing and CCTV installation</li>
                            <li>• Clear sightlines and natural surveillance</li>
                        </ul>
                    </div>
                </div>
                
                <div class="space-y-6">
                    <div class="bg-purple-50 p-6 rounded-lg border border-purple-200">
                        <h3 class="text-lg font-semibold text-purple-800 mb-3">
                            <i class="fas fa-universal-access mr-2"></i>Universal Access
                        </h3>
                        <ul class="text-purple-700 space-y-2 text-sm">
                            <li>• Wheelchair accessible ramps and crossings</li>
                            <li>• Safe routes to schools with proper signage</li>
                            <li>• Elderly-friendly infrastructure and rest areas</li>
                            <li>• Gender-sensitive lighting and design</li>
                        </ul>
                    </div>
                    
                    <div class="bg-orange-50 p-6 rounded-lg border border-orange-200">
                        <h3 class="text-lg font-semibold text-orange-800 mb-3">
                            <i class="fas fa-users mr-2"></i>Policy & Governance
                        </h3>
                        <ul class="text-orange-700 space-y-2 text-sm">
                            <li>• Integrate WOD principles in urban planning</li>
                            <li>• Enforce sidewalk obstruction regulations</li>
                            <li>• Create pedestrian-priority zones</li>
                            <li>• Regular maintenance and monitoring systems</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Report Footer -->
        <div class="bg-gray-50 rounded-xl shadow-lg p-8 text-center">
            <div class="text-sm text-gray-600 space-y-2">
                <p><strong>Nairobi Walkable-Oriented Development Study</strong></p>
                <p><strong>Research Conducted by:</strong> Alex Ngamau, Waseda University</p>
                <p><strong>Report Generated:</strong> <?php echo date('F j, Y \a\t g:i A'); ?></p>
                <?php if ($stats['total_responses'] > 0): ?>
                <p><strong>Data Summary:</strong> 
                    <?php echo number_format($stats['total_responses']); ?> total responses, 
                    <?php echo number_format($stats['this_month']); ?> this month
                </p>
                <p class="text-xs text-gray-500 mt-4">
                    This report contributes to evidence-based walkable-oriented development planning for sustainable urban mobility in Nairobi.
                </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <!-- Chart.js Initialization Scripts -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Chart.defaults.font.family = 'Inter, sans-serif';
            Chart.defaults.color = '#374151';

            // Safety vs Importance Comparison Chart
            <?php if (!empty($safety_data) && !empty($walkway_importance_data)): ?>
            const safetyCtx = document.getElementById('safetyVsImportanceChart');
            if (safetyCtx) {
                new Chart(safetyCtx, {
                    type: 'bar',
                    data: {
                        labels: ['Very Unsafe', 'Unsafe', 'Neutral', 'Safe', 'Very Safe'],
                        datasets: [{
                            label: 'Safety Perception',
                            data: [
                                <?php echo $safety_data['very_unsafe'] ?? 0; ?>,
                                <?php echo $safety_data['unsafe'] ?? 0; ?>,
                                <?php echo $safety_data['neutral'] ?? 0; ?>,
                                <?php echo $safety_data['safe'] ?? 0; ?>,
                                <?php echo $safety_data['very_safe'] ?? 0; ?>
                            ],
                            backgroundColor: ['#EF4444', '#F97316', '#EAB308', '#22C55E', '#16A34A'],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { stepSize: 1 }
                            }
                        }
                    }
                });
            }
            <?php endif; ?>

            // Vulnerable Groups Chart
            <?php if (!empty($vulnerable_data)): ?>
            const vulnerableCtx = document.getElementById('vulnerableChart');
            if (vulnerableCtx) {
                new Chart(vulnerableCtx, {
                    type: 'doughnut',
                    data: {
                        labels: [<?php 
                            $labels = [];
                            foreach (array_keys($vulnerable_data) as $key) {
                                $labels[] = '"' . ucfirst(str_replace('_', ' ', $key)) . '"';
                            }
                            echo implode(', ', $labels);
                        ?>],
                        datasets: [{
                            data: [<?php echo implode(', ', array_values($vulnerable_data)); ?>],
                            backgroundColor: ['#EF4444', '#F97316', '#EAB308', '#22C55E', '#16A34A'],
                            borderWidth: 2,
                            borderColor: '#ffffff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: { padding: 10, usePointStyle: true }
                            }
                        }
                    }
                });
            }
            <?php endif; ?>

            // Transport Mode Charts
            <?php if (!empty($transport_modes)): ?>
            <?php foreach ($transport_modes as $mode_field => $mode_data): ?>
            const <?php echo $mode_field; ?>Ctx = document.getElementById('<?php echo $mode_field; ?>Chart');
            if (<?php echo $mode_field; ?>Ctx) {
                new Chart(<?php echo $mode_field; ?>Ctx, {
                    type: 'pie',
                    data: {
                        labels: [<?php 
                            $labels = [];
                            foreach (array_keys($mode_data) as $key) {
                                $labels[] = '"' . ucfirst(str_replace('_', '/', $key)) . '"';
                            }
                            echo implode(', ', $labels);
                        ?>],
                        datasets: [{
                            data: [<?php echo implode(', ', array_values($mode_data)); ?>],
                            backgroundColor: [
                                '#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#06B6D4'
                            ],
                            borderWidth: 2,
                            borderColor: '#ffffff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: { padding: 8, usePointStyle: true, font: { size: 10 } }
                            }
                        }
                    }
                });
            }
            <?php endforeach; ?>
            <?php endif; ?>

            // Barriers Chart
            <?php if (!empty($barriers_data)): ?>
            const barriersCtx = document.getElementById('barriersChart');
            if (barriersCtx) {
                const barrier_labels = {
                    'poor_sidewalk': 'Poor sidewalk conditions',
                    'unsafe_crossings': 'Unsafe road crossings',
                    'crime_concerns': 'Security concerns',
                    'poor_lighting': 'Poor lighting',
                    'vehicle_speeds': 'High vehicle speeds',
                    'narrow_sidewalks': 'Narrow walkways',
                    'lack_shade': 'Lack of shade',
                    'long_distance': 'Long distances',
                    'lack_signals': 'No crossing signals',
                    'no_amenities': 'No amenities'
                };

                new Chart(barriersCtx, {
                    type: 'bar',
                    data: {
                        labels: [<?php 
                            $barrier_chart_labels = [];
                            foreach (array_keys($barriers_data) as $key) {
                                $label = isset($barrier_labels[$key]) ? $barrier_labels[$key] : ucfirst(str_replace('_', ' ', $key));
                                $barrier_chart_labels[] = '"' . $label . '"';
                            }
                            echo implode(', ', $barrier_chart_labels);
                        ?>],
                        datasets: [{
                            label: 'Number of Reports',
                            data: [<?php echo implode(', ', array_values($barriers_data)); ?>],
                            backgroundColor: '#EF4444',
                            borderColor: '#DC2626',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        indexAxis: 'y',
                        plugins: {
                            legend: { display: false },
                            title: {
                                display: true,
                                text: 'Most Critical Walking Barriers'
                            }
                        },
                        scales: {
                            x: {
                                beginAtZero: true,
                                ticks: { stepSize: 1 }
                            }
                        }
                    }
                });
            }
            <?php endif; ?>
        });
    </script>
</body>
</html>


    