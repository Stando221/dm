<?php
// This is included by report.php when generating PDF
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DMIT Report - <?php echo htmlspecialchars($test['personality_type'] ?? 'Unknown'); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
        .header h1 {
            color: #007bff;
            margin: 10px 0 5px;
            font-size: 24px;
        }
        .header p {
            margin: 0;
            font-size: 16px;
        }
        .section {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }
        .section-title {
            background-color: #007bff;
            color: white;
            padding: 5px 10px;
            margin-bottom: 10px;
            font-size: 18px;
            border-radius: 4px;
        }
        .subsection {
            margin-bottom: 15px;
        }
        .subsection-title {
            font-weight: bold;
            border-bottom: 1px solid #ddd;
            padding-bottom: 3px;
            margin-bottom: 8px;
            color: #007bff;
            font-size: 16px;
        }
        .swot-box {
            border: 1px solid #ddd;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
        }
        .swot-strengths {
            border-left: 4px solid #28a745;
        }
        .swot-weaknesses {
            border-left: 4px solid #dc3545;
        }
        .swot-opportunities {
            border-left: 4px solid #17a2b8;
        }
        .swot-threats {
            border-left: 4px solid #ffc107;
        }
        .mi-item {
            margin-bottom: 8px;
        }
        .mi-label {
            font-weight: bold;
            margin-bottom: 3px;
        }
        .mi-bar-container {
            display: flex;
            align-items: center;
        }
        .mi-bar {
            height: 20px;
            background-color: #007bff;
            border-radius: 3px;
        }
        .mi-value {
            margin-left: 10px;
            font-weight: bold;
        }
        .big-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #007bff;
            text-align: center;
            margin: 10px 0;
        }
        .chart-container {
            height: 300px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #666;
        }
        .page-break {
            page-break-after: always;
        }
        .row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -15px;
        }
        .col {
            flex: 1;
            padding: 0 15px;
            min-width: 200px;
        }
        ul {
            padding-left: 20px;
        }
        li {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Dermatoglyphics Multiple Intelligence Test</h1>
        <p>Comprehensive Analysis Report</p>
    </div>
    
    <div class="section">
        <div class="section-title">Test Information</div>
        <div class="row">
            <div class="col">
                <p><strong>Test ID:</strong> <?php echo $test_id; ?></p>
                <p><strong>Date:</strong> <?php echo date('F j, Y', strtotime($test['completed_at'])); ?></p>
            </div>
            <div class="col">
                <p><strong>Name:</strong> <?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
                <p><strong>Accuracy:</strong> <?php echo $test['accuracy']; ?>%</p>
            </div>
        </div>
    </div>
    
    <div class="section">
        <div class="section-title">Personality Type</div>
        <div style="text-align: center; margin-bottom: 15px;">
            <h3><?php echo htmlspecialchars($test['personality_type']); ?></h3>
            <p>Based on Howard Gardner's Multiple Intelligence Theory</p>
        </div>
        
        <p><?php echo getPersonalityDescription($test['personality_type']); ?></p>
        
        <div class="subsection">
            <div class="subsection-title">Key Characteristics</div>
            <ul>
                <?php foreach (getPersonalityTraits($test['personality_type']) as $trait): ?>
                    <li><?php echo htmlspecialchars($trait); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    
    <div class="page-break"></div>
    
    <div class="section">
        <div class="section-title">DISC Personality Profile</div>
        <div style="display: flex; flex-wrap: wrap; margin-bottom: 20px; text-align: center;">
            <div style="flex: 1; min-width: 200px; padding: 10px;">
                <div style="font-size: 24px; font-weight: bold; color: #007bff;">D</div>
                <div style="font-size: 20px; margin: 5px 0;"><?php echo $disc_profile['D']; ?>%</div>
                <h5>Dominance</h5>
                <p><?php echo getDiscDescription('D', $disc_profile['D']); ?></p>
            </div>
            <div style="flex: 1; min-width: 200px; padding: 10px;">
                <div style="font-size: 24px; font-weight: bold; color: #007bff;">I</div>
                <div style="font-size: 20px; margin: 5px 0;"><?php echo $disc_profile['I']; ?>%</div>
                <h5>Influence</h5>
                <p><?php echo getDiscDescription('I', $disc_profile['I']); ?></p>
            </div>
            <div style="flex: 1; min-width: 200px; padding: 10px;">
                <div style="font-size: 24px; font-weight: bold; color: #007bff;">S</div>
                <div style="font-size: 20px; margin: 5px 0;"><?php echo $disc_profile['S']; ?>%</div>
                <h5>Steadiness</h5>
                <p><?php echo getDiscDescription('S', $disc_profile['S']); ?></p>
            </div>
            <div style="flex: 1; min-width: 200px; padding: 10px;">
                <div style="font-size: 24px; font-weight: bold; color: #007bff;">C</div>
                <div style="font-size: 20px; margin: 5px 0;"><?php echo $disc_profile['C']; ?>%</div>
                <h5>Conscientiousness</h5>
                <p><?php echo getDiscDescription('C', $disc_profile['C']); ?></p>
            </div>
        </div>
        
        <div class="chart-container">
    <img src="<?php echo generateMiChartImage($mi_distribution); ?>" alt="Multiple Intelligences Chart" style="width: 100%; max-width: 800px; display: block; margin: 0 auto;">
</div>
    </div>
    
    <div class="section">
        <div class="section-title">SWOT Analysis</div>
        <div class="row">
            <div class="col">
                <div class="swot-box swot-strengths">
                    <h5>Strengths</h5>
                    <ul>
                        <?php foreach ($swot_analysis['strengths'] as $strength): ?>
                            <li><?php echo htmlspecialchars($strength); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <div class="col">
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
        <div class="row">
            <div class="col">
                <div class="swot-box swot-opportunities">
                    <h5>Opportunities</h5>
                    <ul>
                        <?php foreach ($swot_analysis['opportunities'] as $opportunity): ?>
                            <li><?php echo htmlspecialchars($opportunity); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <div class="col">
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
    
    <div class="page-break"></div>
    
    <div class="section">
        <div class="section-title">Learning Style & Brain Dominance</div>
        <div class="row">
            <div class="col">
                <div class="subsection">
                    <div class="subsection-title">Learning Style: <?php echo htmlspecialchars($test['learning_style']); ?></div>
                    <p><?php echo getLearningStyleDescription($test['learning_style']); ?></p>
                    
                    <div style="margin-top: 10px;">
                        <h6>Optimal Learning Strategies:</h6>
                        <ul>
                            <?php foreach (getLearningStrategies($test['learning_style']) as $strategy): ?>
                                <li><?php echo htmlspecialchars($strategy); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="subsection">
                    <div class="subsection-title">Brain Dominance: <?php echo htmlspecialchars($test['brain_dominance']); ?></div>
                    <p><?php echo getBrainDominanceDescription($test['brain_dominance']); ?></p>
                    
                    <div style="margin-top: 10px;">
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
    
    <div class="section">
        <div class="section-title">Multiple Intelligences Distribution</div>
        
        <div class="chart-container">
    <img src="<?php echo generateDiscChartImage($disc_profile); ?>" alt="DISC Profile Chart" style="width: 100%; max-width: 600px; display: block; margin: 0 auto;">
</div>
        
        <div class="row">
            <div class="col">
                <div class="subsection">
                    <div class="subsection-title">Primary Intelligence: <?php echo htmlspecialchars($test['primary_intelligence']); ?></div>
                    <p><?php echo getMIDescription($test['primary_intelligence']); ?></p>
                </div>
            </div>
            <div class="col">
                <div class="subsection">
                    <div class="subsection-title">Secondary Intelligence: <?php echo htmlspecialchars($test['secondary_intelligence']); ?></div>
                    <p><?php echo getMIDescription($test['secondary_intelligence']); ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="page-break"></div>
    
    <div class="section">
        <div class="section-title">Fingerprint Analysis</div>
        <div style="display: flex; flex-wrap: wrap; text-align: center;">
            <div style="flex: 1; min-width: 200px; padding: 10px;">
                <h5>Total Finger Ridge Count (TFRC)</h5>
                <div class="big-number"><?php echo $test['tfrc']; ?></div>
                <p><?php echo getTFRCInterpretation($test['tfrc']); ?></p>
            </div>
            <div style="flex: 1; min-width: 200px; padding: 10px;">
                <h5>ATD Angle</h5>
                <div class="big-number"><?php echo $test['atd_angle']; ?>°</div>
                <p><?php echo getATDAngleInterpretation($test['atd_angle']); ?></p>
            </div>
            <div style="flex: 1; min-width: 200px; padding: 10px;">
                <h5>Learning Sensibility</h5>
                <div class="big-number"><?php echo $test['learning_sensibility']; ?></div>
                <p><?php echo getLearningSensibilityInterpretation($test['learning_sensibility']); ?></p>
            </div>
        </div>
    </div>
    
    <div class="section">
        <div class="section-title">Career Recommendations</div>
        <div style="text-align: center; margin-bottom: 20px;">
            <h5>Holland Code: <?php echo htmlspecialchars($test['holland_code']); ?></h5>
            <p><?php echo getHollandCodeDescription($test['holland_code']); ?></p>
        </div>
        
        <div class="row">
            <?php foreach ($career_recommendations as $category => $careers): ?>
                <div class="col">
                    <div style="margin-bottom: 15px;">
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
    
    <div class="section">
        <div class="section-title">Final Recommendations</div>
        
        <div class="subsection">
            <div class="subsection-title">Personal Development Suggestions</div>
            <ul>
                <?php foreach (getDevelopmentSuggestions($test) as $suggestion): ?>
                    <li><?php echo htmlspecialchars($suggestion); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <div class="subsection">
            <div class="subsection-title">Educational Guidance</div>
            <p><?php echo getEducationalGuidance($test); ?></p>
        </div>
        
        <div class="subsection">
            <div class="subsection-title">Career Path Suggestions</div>
            <p><?php echo getCareerPathGuidance($test); ?></p>
        </div>
    </div>
    
    <div class="footer">
        <p>Confidential Report - Generated on <?php echo date('F j, Y'); ?></p>
        <p>&copy; <?php echo date('Y'); ?> DMIT Analysis System. All rights reserved.</p>
    </div>
</body>
</html>

<?php
// Helper functions to generate chart images for PDF
function generateDiscChartImage($disc_profile) {
    $tmp_dir = '/var/www/dmittest/public/tmp/';
    if (!file_exists($tmp_dir)) {
        mkdir($tmp_dir, 0755, true);
    }
    
    $filename = 'disc_chart_' . md5(json_encode($disc_profile)) . '.png';
    $filepath = $tmp_dir . $filename;
    
    if (!file_exists($filepath)) {
        require_once 'includes/chart_generator.php';
        ChartGenerator::createDiscChart($disc_profile, $filepath);
    }
    
    return $filepath;
}

function generateMiChartImage($mi_distribution) {
    $tmp_dir = '/var/www/dmittest/public/tmp/';
    if (!file_exists($tmp_dir)) {
        mkdir($tmp_dir, 0755, true);
    }
    
    $filename = 'mi_chart_' . md5(json_encode($mi_distribution)) . '.png';
    $filepath = $tmp_dir . $filename;
    
    if (!file_exists($filepath)) {
        require_once 'includes/chart_generator.php';
        ChartGenerator::createMiChart($mi_distribution, $filepath);
    }
    
    return $filepath;
}
?>