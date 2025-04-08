<?php
require_once 'includes/header.php';
require_once 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$test_id = (int)$_GET['id'];
$test = $db->fetchOne("SELECT * FROM tests WHERE id = ? AND user_id = ?", [$test_id, $_SESSION['user_id']]);

if (!$test) {
    header("Location: dashboard.php");
    exit();
}

if ($test['status'] !== 'completed') {
    header("Location: process.php?id=$test_id");
    exit();
}

// Decode JSON fields with error handling
$disc_profile = json_decode($test['disc_profile'] ?? '{"D":50,"I":50,"S":50,"C":50}', true) ?? [];
$swot_analysis = json_decode($test['swot_analysis'] ?? '{"strengths":[],"weaknesses":[],"opportunities":[],"threats":[]}', true) ?? [];
$mi_distribution = json_decode($test['mi_distribution'] ?? '{}', true) ?? [];
$career_recommendations = json_decode($test['career_recommendations'] ?? '{"Primary":[],"Secondary":[],"Tertiary":[]}', true) ?? [];

// Handle MI distribution - ensure it's always an array
if (empty($mi_distribution) || !is_array($mi_distribution)) {
    $mi_distribution = [
        'Linguistic' => $test['verbal_linguistic'] ?? 50,
        'Logical' => $test['logical_mathematical'] ?? 50,
        'Spatial' => $test['visual_spatial'] ?? 50,
        'Musical' => $test['musical'] ?? 50,
        'Bodily' => $test['bodily_kinesthetic'] ?? 50,
        'Interpersonal' => $test['interpersonal'] ?? 50,
        'Intrapersonal' => $test['intrapersonal'] ?? 50,
        'Naturalist' => $test['naturalistic'] ?? 50
    ];
}

// Only attempt to sort if we have an array with values
if (is_array($mi_distribution) && !empty($mi_distribution)) {
    arsort($mi_distribution);
    $mi_keys = array_keys($mi_distribution);
    $test['primary_intelligence'] = $mi_keys[0] ?? 'Unknown';
    $test['secondary_intelligence'] = $mi_keys[1] ?? 'Unknown';
} else {
    $test['primary_intelligence'] = 'Unknown';
    $test['secondary_intelligence'] = 'Unknown';
}
// Check if PDF generation is requested
if (isset($_GET['pdf'])) {
    require_once MPDF_DIR . '/mpdf.php';
    
    // Generate chart images before creating PDF
  
    $disc_chart_path = generateDiscChartImage($disc_profile);
    $mi_chart_path = generateMiChartImage($mi_distribution);
    
    ob_start();
    include 'report_template.php';
    $html = ob_get_clean();
    
    try {
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 15,
            'margin_bottom' => 15,
            'margin_header' => 10,
            'margin_footer' => 10
        ]);
        
        $mpdf->SetTitle("DMIT Report - {$test['personality_type']}");
        $mpdf->SetAuthor("DMIT Analysis System");
        $mpdf->SetCreator("DMIT Web Application");
        $mpdf->SetDisplayMode('fullpage');
        
        $mpdf->WriteHTML($html);
        
        $filename = "DMIT_Report_{$test_id}_{$_SESSION['user_name']}.pdf";
        $mpdf->Output($filename, 'D');
        
        // Clean up temporary chart images
        if (file_exists($disc_chart_path)) unlink($disc_chart_path);
        if (file_exists($mi_chart_path)) unlink($mi_chart_path);
        
        exit();
    } catch (Exception $e) {
        error_log("PDF generation failed: " . $e->getMessage());
        $_SESSION['error'] = "Failed to generate PDF report. Please try again.";
        header("Location: report.php?id=$test_id");
        exit();
    }
}

function generateDiscChartImage($disc_profile) {
    require_once 'includes/chart_generator.php';
    return ChartGenerator::createDiscChart($disc_profile);
}

function generateMiChartImage($mi_distribution) {
    require_once 'includes/chart_generator.php';
    return ChartGenerator::createMiChart($mi_distribution);
}
?>

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>DMIT Test Report</h2>
                <div>
                    <a href="report.php?id=<?php echo $test_id; ?>&pdf=1" class="btn btn-primary">
                        <i class="fas fa-file-pdf"></i> Download PDF
                    </a>
                    <a href="dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h4>Test Information</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <p><strong>Test ID:</strong> <?php echo $test_id; ?></p>
                            <p><strong>Date Completed:</strong> <?php echo date('F j, Y', strtotime($test['completed_at'])); ?></p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['user_email']); ?></p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Analysis Accuracy:</strong> <?php echo $test['accuracy']; ?>%</p>
                            <p><strong>Personality Type:</strong> <?php echo htmlspecialchars($test['personality_type']); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Personality Type -->
            <div class="card mb-4">
                <div class="card-header">
                    <h4>Personality Type</h4>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <h3><?php echo htmlspecialchars($test['personality_type']); ?></h3>
                        <p class="lead">Based on Howard Gardner's Multiple Intelligence Theory</p>
                    </div>
                    
                    <p><?php echo getPersonalityDescription($test['personality_type']); ?></p>
                    
                    <div class="mt-4">
                        <h5>Key Characteristics:</h5>
                        <ul>
                            <?php foreach (getPersonalityTraits($test['personality_type']) as $trait): ?>
                                <li><?php echo htmlspecialchars($trait); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- DISC Profile -->
            <div class="card mb-4">
                <div class="card-header">
                    <h4>DISC Personality Profile</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 text-center">
                            <div class="disc-score">
                                <div class="disc-letter">D</div>
                                <div class="disc-value"><?php echo $disc_profile['D']; ?>%</div>
                            </div>
                            <h5>Dominance</h5>
                            <p><?php echo getDiscDescription('D', $disc_profile['D']); ?></p>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="disc-score">
                                <div class="disc-letter">I</div>
                                <div class="disc-value"><?php echo $disc_profile['I']; ?>%</div>
                            </div>
                            <h5>Influence</h5>
                            <p><?php echo getDiscDescription('I', $disc_profile['I']); ?></p>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="disc-score">
                                <div class="disc-letter">S</div>
                                <div class="disc-value"><?php echo $disc_profile['S']; ?>%</div>
                            </div>
                            <h5>Steadiness</h5>
                            <p><?php echo getDiscDescription('S', $disc_profile['S']); ?></p>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="disc-score">
                                <div class="disc-letter">C</div>
                                <div class="disc-value"><?php echo $disc_profile['C']; ?>%</div>
                            </div>
                            <h5>Conscientiousness</h5>
                            <p><?php echo getDiscDescription('C', $disc_profile['C']); ?></p>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <canvas id="discChart" height="150"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- SWOT Analysis -->
            <div class="card mb-4">
                <div class="card-header">
                    <h4>SWOT Analysis</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="swot-box swot-strengths">
                                <h5>Strengths</h5>
                                <ul>
                                    <?php foreach ($swot_analysis['strengths'] as $strength): ?>
                                        <li><?php echo htmlspecialchars($strength); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="swot-box swot-weaknesses">
                                <h5>Weaknesses</h5>
                                <ul>
                                    <?php foreach ($swot_analysis['weaknesses'] as $weakness): ?>
                                        <li><?php echo htmlspecialchars($weakness); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="swot-box swot-opportunities">
                                <h5>Opportunities</h5>
                                <ul>
                                    <?php foreach ($swot_analysis['opportunities'] as $opportunity): ?>
                                        <li><?php echo htmlspecialchars($opportunity); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="swot-box swot-threats">
                                <h5>Threats</h5>
                                <ul>
                                    <?php foreach ($swot_analysis['threats'] as $threat): ?>
                                        <li><?php echo htmlspecialchars($threat); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Learning Style and Brain Dominance -->
            <div class="card mb-4">
                <div class="card-header">
                    <h4>Learning Style & Brain Dominance</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Learning Style: <?php echo htmlspecialchars($test['learning_style']); ?></h5>
                            <p><?php echo getLearningStyleDescription($test['learning_style']); ?></p>
                            
                            <div class="mt-3">
                                <h6>Optimal Learning Strategies:</h6>
                                <ul>
                                    <?php foreach (getLearningStrategies($test['learning_style']) as $strategy): ?>
                                        <li><?php echo htmlspecialchars($strategy); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h5>Brain Dominance: <?php echo htmlspecialchars($test['brain_dominance']); ?></h5>
                            <p><?php echo getBrainDominanceDescription($test['brain_dominance']); ?></p>
                            
                            <div class="mt-3">
                                <h6>Characteristics:</h6>
                                <ul>
                                    <?php foreach (getBrainDominanceTraits($test['brain_dominance']) as $trait): ?>
                                        <li><?php echo htmlspecialchars($trait); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Multiple Intelligences -->
            <div class="card mb-4">
                <div class="card-header">
                    <h4>Multiple Intelligences Distribution</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <canvas id="miChart" height="250"></canvas>
                        </div>
                        <div class="col-md-4">
                            <div class="mi-legend">
                                <?php foreach ($mi_distribution as $mi => $score): ?>
                                    <div class="mi-item">
                                        <div class="mi-label"><?php echo htmlspecialchars($mi); ?></div>
                                        <div class="mi-bar-container">
                                            <div class="mi-bar" style="width: <?php echo $score; ?>%"></div>
                                            <div class="mi-value"><?php echo $score; ?>%</div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <h5>Primary Intelligence: <?php echo htmlspecialchars($test['primary_intelligence']); ?></h5>
                            <p><?php echo getMIDescription($test['primary_intelligence']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <h5>Secondary Intelligence: <?php echo htmlspecialchars($test['secondary_intelligence']); ?></h5>
                            <p><?php echo getMIDescription($test['secondary_intelligence']); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Fingerprint Analysis -->
            <div class="card mb-4">
                <div class="card-header">
                    <h4>Fingerprint Analysis</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <h5>Total Finger Ridge Count (TFRC)</h5>
                            <div class="big-number"><?php echo $test['tfrc']; ?></div>
                            <p><?php echo getTFRCInterpretation($test['tfrc']); ?></p>
                        </div>
                        <div class="col-md-4">
                            <h5>ATD Angle</h5>
                            <div class="big-number"><?php echo $test['atd_angle']; ?>°</div>
                            <p><?php echo getATDAngleInterpretation($test['atd_angle']); ?></p>
                        </div>
                        <div class="col-md-4">
                            <h5>Learning Sensibility</h5>
                            <div class="big-number"><?php echo $test['learning_sensibility']; ?></div>
                            <p><?php echo getLearningSensibilityInterpretation($test['learning_sensibility']); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Career Recommendations -->
            <div class="card mb-4">
                <div class="card-header">
                    <h4>Career Recommendations</h4>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <h5>Holland Code: <?php echo htmlspecialchars($test['holland_code']); ?></h5>
                        <p><?php echo getHollandCodeDescription($test['holland_code']); ?></p>
                    </div>
                    
                    <div class="row">
                        <?php foreach ($career_recommendations as $category => $careers): ?>
                            <div class="col-md-4">
                                <div class="career-category">
                                    <h6><?php echo htmlspecialchars($category); ?></h6>
                                    <ul>
                                        <?php foreach ($careers as $career): ?>
                                            <li><?php echo htmlspecialchars($career); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Final Recommendations -->
            <div class="card">
                <div class="card-header">
                    <h4>Final Recommendations</h4>
                </div>
                <div class="card-body">
                    <h5>Personal Development Suggestions:</h5>
                    <ul>
                        <?php foreach (getDevelopmentSuggestions($test) as $suggestion): ?>
                            <li><?php echo htmlspecialchars($suggestion); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    
                    <h5 class="mt-4">Educational Guidance:</h5>
                    <p><?php echo getEducationalGuidance($test); ?></p>
                    
                    <h5 class="mt-4">Career Path Suggestions:</h5>
                    <p><?php echo getCareerPathGuidance($test); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Doughnut Chart
const discCtx = document.getElementById('discChart')?.getContext('2d');
if (discCtx) {
    const discChart = new Chart(discCtx, {
        type: 'doughnut',
        data: {
            labels: ['Dominance', 'Influence', 'Steadiness', 'Conscientiousness'],
            datasets: [{
                data: [
                    <?php echo $disc_profile['D'] ?? 50; ?>,
                    <?php echo $disc_profile['I'] ?? 50; ?>,
                    <?php echo $disc_profile['S'] ?? 50; ?>,
                    <?php echo $disc_profile['C'] ?? 50; ?>
                ],
                backgroundColor: [
                    'rgba(255, 99, 132, 0.7)',
                    'rgba(54, 162, 235, 0.7)',
                    'rgba(255, 206, 86, 0.7)',
                    'rgba(75, 192, 192, 0.7)'
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'right',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `${context.label}: ${context.raw}%`;
                        }
                    }
                }
            },
            cutout: '70%'
        }
    });
}

// Multiple Intelligences Chart
const miCtx = document.getElementById('miChart')?.getContext('2d');
if (miCtx) {
    const miChart = new Chart(miCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_keys($mi_distribution)); ?>,
            datasets: [{
                label: 'Score (%)',
                data: <?php echo json_encode(array_values($mi_distribution)); ?>,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.7)',
                    'rgba(54, 162, 235, 0.7)',
                    'rgba(255, 206, 86, 0.7)',
                    'rgba(75, 192, 192, 0.7)',
                    'rgba(153, 102, 255, 0.7)',
                    'rgba(255, 159, 64, 0.7)',
                    'rgba(199, 199, 199, 0.7)',
                    'rgba(83, 102, 255, 0.7)'
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)',
                    'rgba(255, 159, 64, 1)',
                    'rgba(199, 199, 199, 1)',
                    'rgba(83, 102, 255, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            }
        }
    });
}
</script>

<?php
require_once 'includes/footer.php';

// Helper functions for report content
function getPersonalityDescription($type) {
    $descriptions = [
        'Linguistic' => 'You have highly developed verbal skills and sensitivity to the sounds, meanings and rhythms of words. You think in words rather than pictures.',
        'Logical-Mathematical' => 'You have the ability to think conceptually and abstractly, and capacity to discern logical or numerical patterns. You tend to think in numbers and patterns.',
        'Spatial' => 'You have the capacity to think in images and pictures, to visualize accurately and abstractly. You tend to think in pictures and images.',
        'Musical' => 'You have the ability to produce and appreciate rhythm, pitch and timbre. You think in sounds, rhythms and melodies.',
        'Bodily-Kinesthetic' => 'You have the ability to control one\'s body movements and to handle objects skillfully. You tend to think in movements and physical sensations.',
        'Interpersonal' => 'You have the capacity to detect and respond appropriately to the moods, motivations and desires of others. You think in interactions with others.',
        'Intrapersonal' => 'You have the capacity to be self-aware and in tune with inner feelings, values, beliefs and thinking processes. You think in reflections and self-analysis.',
        'Naturalistic' => 'You have the ability to recognize and categorize plants, animals and other objects in nature. You think in natural patterns and ecosystems.'
    ];
    
    return $descriptions[$type] ?? 'This personality type combines several intelligence strengths.';
}

function getPersonalityTraits($type) {
    $traits = [
        'Linguistic' => [
            'Excellent at reading, writing and telling stories',
            'Good at memorizing names, places, dates and trivia',
            'Learns best by saying, hearing and seeing words'
        ],
        'Logical-Mathematical' => [
            'Excellent at math, reasoning, logic and problem solving',
            'Good at scientific thinking and investigation',
            'Learns best by categorizing, classifying and working with patterns'
        ],
        'Spatial' => [
            'Excellent at visualizing and mentally manipulating objects',
            'Good at reading maps, charts and solving puzzles',
            'Learns best by visualizing, dreaming and working with colors/pictures'
        ],
        'Musical' => [
            'Excellent at singing, picking up sounds and remembering melodies',
            'Good at noticing pitches, rhythms and musical patterns',
            'Learns best by rhythm, melody and music'
        ],
        'Bodily-Kinesthetic' => [
            'Excellent at physical activities like sports, dance and acting',
            'Good at hands-on learning and crafts',
            'Learns best by touching, moving and interacting with space'
        ],
        'Interpersonal' => [
            'Excellent at understanding people and communication',
            'Good at mediating conflicts and building relationships',
            'Learns best by sharing, comparing and cooperating'
        ],
        'Intrapersonal' => [
            'Excellent at self-reflection and awareness of inner feelings',
            'Good at setting goals and understanding self',
            'Learns best by working alone and self-paced instruction'
        ],
        'Naturalistic' => [
            'Excellent at understanding nature and making distinctions',
            'Good at identifying flora and fauna',
            'Learns best by working in nature and exploring things'
        ]
    ];
    
    return $traits[$type] ?? ['Combination of multiple intelligence traits'];
}

function getDiscDescription($type, $score) {
    $descriptions = [
        'D' => [
            'low' => 'You tend to be cooperative, patient and accommodating rather than assertive.',
            'medium' => 'You have a balanced approach between being assertive and cooperative.',
            'high' => 'You are direct, decisive and like to take charge of situations.'
        ],
        'I' => [
            'low' => 'You tend to be more reserved and task-focused rather than outgoing.',
            'medium' => 'You balance social interaction with periods of reflection.',
            'high' => 'You are outgoing, enthusiastic and enjoy social interactions.'
        ],
        'S' => [
            'low' => 'You prefer change and variety over stability and routine.',
            'medium' => 'You balance a need for stability with adaptability.',
            'high' => 'You value stability, cooperation and consistent routines.'
        ],
        'C' => [
            'low' => 'You are more flexible and tolerant of imperfections.',
            'medium' => 'You balance attention to detail with big-picture thinking.',
            'high' => 'You value accuracy, quality and attention to details.'
        ]
    ];
    
    $level = ($score < 40) ? 'low' : (($score < 70) ? 'medium' : 'high');
    return $descriptions[$type][$level];
}

function getLearningStyleDescription($style) {
    $descriptions = [
        'Visual' => 'You learn best through seeing. Charts, diagrams, illustrations, and other visual tools help you understand and remember information.',
        'Auditory' => 'You learn best through listening. Lectures, discussions, and other auditory methods are most effective for your learning.',
        'Kinesthetic' => 'You learn best through moving, doing, and touching. Hands-on activities and physical experiences help you understand concepts.',
        'Reading/Writing' => 'You learn best through reading and writing. You prefer to see information in written form and benefit from taking notes.'
    ];
    
    return $descriptions[$style] ?? 'You have a balanced combination of learning style preferences.';
}

function getLearningStrategies($style) {
    $strategies = [
        'Visual' => [
            'Use color coding to organize notes and ideas',
            'Create mind maps or diagrams to visualize concepts',
            'Watch videos or demonstrations of the material'
        ],
        'Auditory' => [
            'Record lectures and listen to them again',
            'Participate in group discussions about the material',
            'Explain concepts out loud to yourself or others'
        ],
        'Kinesthetic' => [
            'Use physical objects or models to learn concepts',
            'Take frequent breaks to move around while studying',
            'Create hands-on projects to demonstrate learning'
        ],
        'Reading/Writing' => [
            'Rewrite notes in your own words',
            'Create outlines and written summaries of material',
            'Read textbooks and write responses or analyses'
        ]
    ];
    
    return $strategies[$style] ?? [
        'Combine different learning methods to find what works best',
        'Experiment with visual, auditory, and kinesthetic approaches',
        'Use a variety of study techniques'
    ];
}

function getBrainDominanceDescription($dominance) {
    $descriptions = [
        'Left Brain' => 'You tend to be more logical, analytical, and detail-oriented. You prefer structured, sequential learning and are good with numbers and language.',
        'Right Brain' => 'You tend to be more creative, intuitive, and big-picture oriented. You prefer holistic, visual learning and are good with patterns and spatial reasoning.',
        'Balanced' => 'You have a good balance between logical and creative thinking. You can adapt your thinking style to different situations.'
    ];
    
    return $descriptions[$dominance] ?? 'Your brain dominance shows a unique combination of characteristics.';
}

function getBrainDominanceTraits($dominance) {
    $traits = [
        'Left Brain' => [
            'Logical and analytical thinking',
            'Detail-oriented',
            'Good with numbers and language',
            'Prefers structured, sequential learning',
            'Strong in mathematics and science'
        ],
        'Right Brain' => [
            'Creative and intuitive thinking',
            'Big-picture oriented',
            'Good with patterns and spatial reasoning',
            'Prefers holistic, visual learning',
            'Strong in arts and creativity'
        ],
        'Balanced' => [
            'Combination of logical and creative thinking',
            'Can adapt thinking style to situation',
            'Good at both details and big picture',
            'Flexible learning approach',
            'Strong in integrating different perspectives'
        ]
    ];
    
    return $traits[$dominance] ?? ['Unique combination of left and right brain traits'];
}

function getMIDescription($mi) {
    $descriptions = [
        'Linguistic' => 'You have a strong ability to use words effectively, both orally and in writing. You enjoy reading, writing, telling stories and playing word games.',
        'Logical-Mathematical' => 'You have a strong ability to reason, calculate, and handle logical thinking. You enjoy working with numbers, experiments and solving problems.',
        'Spatial' => 'You have a strong ability to perceive the visual-spatial world accurately. You enjoy maps, charts, pictures and visualizing information.',
        'Musical' => 'You have a strong sensitivity to rhythm, melody and sound. You enjoy singing, playing instruments, composing music and recognizing tonal patterns.',
        'Bodily-Kinesthetic' => 'You have a strong ability to control body movements and handle objects skillfully. You enjoy dancing, sports, crafts and hands-on activities.',
        'Interpersonal' => 'You have a strong ability to understand and interact effectively with others. You enjoy leading, organizing, communicating and mediating conflicts.',
        'Intrapersonal' => 'You have a strong ability to understand yourself and your own thoughts/feelings. You enjoy self-reflection, introspection and working independently.',
        'Naturalistic' => 'You have a strong ability to recognize and classify plants, animals and natural phenomena. You enjoy nature, animals and observing ecological patterns.'
    ];
    
    return $descriptions[$mi] ?? 'This intelligence represents a significant strength in your profile.';
}

function getTFRCInterpretation($tfrc) {
    if ($tfrc < 100) return 'Low ridge count indicates potential for abstract thinking and conceptual abilities.';
    if ($tfrc < 150) return 'Moderate ridge count suggests balanced cognitive abilities.';
    return 'High ridge count indicates strong potential for detailed, concrete thinking and memory capacity.';
}

function getATDAngleInterpretation($angle) {
    if ($angle < 35) return 'Fast information processing speed and quick reflexes.';
    if ($angle < 45) return 'Average information processing speed and reaction time.';
    return 'More deliberate information processing style with careful consideration.';
}

function getLearningSensibilityInterpretation($score) {
    if ($score < 3) return 'Preference for structured, step-by-step learning with clear instructions.';
    if ($score < 7) return 'Balanced learning approach that adapts to different teaching methods.';
    return 'Preference for exploratory, self-directed learning with minimal guidance.';
}

function getHollandCodeDescription($code) {
    $descriptions = [
        'Realistic' => 'You prefer work activities that include practical, hands-on problems and solutions. You enjoy working with plants, animals, real-world materials or machinery.',
        'Investigative' => 'You prefer work activities that include observation and systematic investigation of physical, biological or cultural phenomena. You enjoy scientific and intellectual pursuits.',
        'Artistic' => 'You prefer work activities that deal with the artistic side of things, such as forms, designs and patterns. You enjoy creative expression and unstructured environments.',
        'Social' => 'You prefer work activities that assist others and promote learning and personal development. You enjoy teaching, helping, healing and serving others.',
        'Enterprising' => 'You prefer work activities that involve starting up and carrying out projects, especially business ventures. You enjoy leadership, influencing and persuading others.',
        'Conventional' => 'You prefer work activities that follow set procedures and routines. You enjoy organizing and working with data in structured environments.'
    ];
    
    // For multi-letter codes (like "RIS", "AES", etc.)
    if (strlen($code) > 1) {
        $primary = substr($code, 0, 1);
        $desc = "Your primary interest is " . strtolower($descriptions[$primary] ?? 'a combination of areas') . " ";
        $desc .= "with secondary interests in ";
        
        $secondary = [];
        for ($i = 1; $i < strlen($code); $i++) {
            $letter = substr($code, $i, 1);
            if (isset($descriptions[$letter])) {
                $secondary[] = strtolower($descriptions[$letter]);
            }
        }
        
        if (empty($secondary)) {
            $desc .= "other areas.";
        } else {
            $desc .= implode(", ", $secondary) . ".";
        }
        
        return $desc;
    }
    
    return $descriptions[$code] ?? 'Your career interests represent a unique combination of preferences.';
}

function getDevelopmentSuggestions($test) {
    $suggestions = [];
    
    // Based on personality type
    $personality = $test['personality_type'];
    if (in_array($personality, ['Linguistic', 'Interpersonal'])) {
        $suggestions[] = 'Develop your natural communication skills through public speaking or writing opportunities.';
    } elseif (in_array($personality, ['Logical-Mathematical', 'Spatial'])) {
        $suggestions[] = 'Challenge yourself with puzzles, strategy games or coding projects to strengthen your analytical skills.';
    }
    
    // Based on DISC profile
    $disc = json_decode($test['disc_profile'], true);
    if ($disc['D'] < 40) {
        $suggestions[] = 'Practice being more assertive in expressing your needs and opinions.';
    }
    if ($disc['S'] > 70) {
        $suggestions[] = 'Try stepping out of your comfort zone occasionally to build adaptability.';
    }
    
    // Based on SWOT weaknesses
    $swot = json_decode($test['swot_analysis'], true);
    foreach ($swot['weaknesses'] as $weakness) {
        $suggestions[] = "Work on improving your $weakness through targeted practice and learning.";
    }
    
    // Based on learning style
    $learningStyle = $test['learning_style'];
    if ($learningStyle === 'Visual') {
        $suggestions[] = 'Experiment with auditory learning techniques to expand your learning flexibility.';
    } elseif ($learningStyle === 'Auditory') {
        $suggestions[] = 'Try incorporating more visual aids into your study routine.';
    }
    
    return array_slice($suggestions, 0, 5); // Return top 5 suggestions
}

function getEducationalGuidance($test) {
    $guidance = "Based on your profile, you would likely excel in ";
    
    $mi = json_decode($test['mi_distribution'], true);
    arsort($mi);
    $top_mi = array_keys(array_slice($mi, 0, 2));
    
   // var_dump($top_mi);

    $fields = [];
    foreach ($top_mi as $intelligence) {
        switch ($intelligence) {
            case 'naturalist':
                $fields[] = 'biology, environmental science, agriculture, or veterinary medicine';
                break;
            case 'intrapersonal':
                $fields[] = 'philosophy, theology, entrepreneurship, or writing';
                break;
            case 'interpersonal':
                $fields[] = 'psychology, counseling, teaching, or human resources';
                break;
            case 'bodily':
                $fields[] = 'athletics, dance, physical therapy, or carpentry';
                break;
            case 'musical':
                $fields[] = 'music performance, composition, audio engineering, or music therapy';
                break;
            case 'spatial':
                $fields[] = 'architecture, graphic design, photography, or surgery';
                break;
            case 'logical':
                $fields[] = 'mathematics, computer science, engineering, or physics';
                break;
            case 'linguistic':
                $fields[] = 'language arts, literature, journalism, or law';
                break;
        }
    }
    

    if (count($fields) > 1) {
        $last = array_pop($fields);
        $fields = [implode(', ', $fields) . ' and ' . $last];
    }
    
    $guidance .= $fields[0] . ". ";
    
    $learningStyle = $test['learning_style'];
    $guidance .= "Given your " . strtolower($learningStyle) . " learning style, you may benefit from ";
    
    switch ($learningStyle) {
        case 'Visual':
            $guidance .= "programs that emphasize diagrams, charts, and visual demonstrations.";
            break;
        case 'Auditory':
            $guidance .= "lecture-based programs or those with strong discussion components.";
            break;
        case 'Kinesthetic':
            $guidance .= "hands-on programs with labs, fieldwork, or practical applications.";
            break;
        case 'Reading/Writing':
            $guidance .= "text-based programs with extensive reading and writing requirements.";
            break;
        default:
            $guidance .= "a variety of educational approaches that play to your strengths.";
    }
    
    return $guidance;
}

function getCareerPathGuidance($test) {
    $holland = $test['holland_code'];
    $personality = $test['personality_type'];
    
    $guidance = "Your Holland Code of $holland suggests you would be most satisfied in careers that align with these interests. ";
    
    // Combine Holland code with personality type for more specific guidance
    if (strpos($holland, 'I') !== false && in_array($personality, ['Logical-Mathematical', 'Spatial'])) {
        $guidance .= "Your investigative nature combined with analytical strengths make you well-suited for research, data analysis, or technical fields. ";
    }
    
    if (strpos($holland, 'A') !== false && in_array($personality, ['Spatial', 'Musical'])) {
        $guidance .= "Your artistic interests and creative intelligence suggest you would thrive in design, arts, or innovative fields. ";
    }
    
    if (strpos($holland, 'S') !== false && in_array($personality, ['Interpersonal', 'Intrapersonal'])) {
        $guidance .= "Your social orientation and people skills indicate helping professions would be rewarding for you. ";
    }
    
    $guidance .= "Consider exploring careers that combine these interests with your natural abilities for maximum fulfillment.";
    
    return $guidance;
}
?>