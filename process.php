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

// Check if analysis is already done
if ($test['status'] === 'completed') {
    header("Location: report.php?id=$test_id");
    exit();
}

// Get fingerprint files
$fingerprints = $db->fetch("SELECT * FROM fingerprints WHERE test_id = ?", [$test_id]);

// Check if all fingerprints are uploaded
if (count($fingerprints) !== 10) {
    // Delete incomplete test
    $db->query("DELETE FROM fingerprints WHERE test_id = ?", [$test_id]);
    $db->query("DELETE FROM tests WHERE id = ?", [$test_id]);
    
    $_SESSION['error'] = "Incomplete fingerprint upload. Please try again.";
    header("Location: upload.php");
    exit();
}

// Update test status to processing
$db->query("UPDATE tests SET status = 'processing' WHERE id = ?", [$test_id]);

// Prepare data for Python script
$finger_data = [];
foreach ($fingerprints as $fp) {
    $finger_data[$fp['finger_type']] = UPLOAD_DIR . $fp['file_path'];
}

// Save as JSON for Python script
$json_file = UPLOAD_DIR . "test_{$test_id}_data.json";
file_put_contents($json_file, json_encode([
    'test_id' => $test_id,
    'fingerprints' => $finger_data
]));

// Execute Python script
$command = PYTHON_PATH." " . PYTHON_SCRIPT . " " . escapeshellarg($json_file);
$output = shell_exec($command);
$result = json_decode($output, true);

if ($result && isset($result['success']) && $result['success']) {
    // Save analysis results
    $db->query(
        "UPDATE tests SET 
            personality_type = ?,
            disc_profile = ?,
            swot_analysis = ?,
            learning_style = ?,
            brain_dominance = ?,
            psychological_capability = ?,
            mi_distribution = ?,
            logical_mathematical = ?,
            verbal_linguistic = ?,
            naturalistic = ?,
            visual_spatial = ?,
            bodily_kinesthetic = ?,
            musical = ?,
            sensing_capability = ?,
            thought_process = ?,
            tfrc = ?,
            atd_angle = ?,
            learning_sensibility = ?,
            leadership_style = ?,
            holland_code = ?,
            career_recommendations = ?,
            accuracy = ?,
            status = 'completed',
            completed_at = NOW()
        WHERE id = ?",
        [
            $result['personality_type'],
            trim(stripslashes(json_encode($result['disc_profile'])), '"'),
            trim(stripslashes(json_encode($result['swot_analysis'])), '"'),
            $result['learning_style'],
            $result['brain_dominance'],
            $result['psychological_capability'],
            trim(stripslashes(json_encode($result['mi_distribution'])), '"'),
            $result['logical_mathematical'],
            $result['verbal_linguistic'],
            $result['naturalistic'],
            $result['visual_spatial'],
            $result['bodily_kinesthetic'],
            $result['musical'],
            $result['sensing_capability'],
            $result['thought_process'],
            $result['tfrc'],
            $result['atd_angle'],
            $result['learning_sensibility'],
            $result['leadership_style'],
            $result['holland_code'],
            trim(stripslashes(json_encode($result['career_recommendations'])), '"'),
            $result['accuracy'],
            $test_id
        ]
    );
    
    // Redirect to report
    header("Location: report.php?id=$test_id");
    exit();
} else {
    // Analysis failed
    $db->query("UPDATE tests SET status = 'failed' WHERE id = ?", [$test_id]);
    $error = "Analysis failed. Please try again.";
}
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="text-center">Processing Your DMIT Test</h3>
                </div>
                <div class="card-body text-center">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger">
                            <h5>Analysis Error</h5>
                            <p><?php echo htmlspecialchars($error); ?></p>
                            <a href="upload.php" class="btn btn-warning">Try Again</a>
                        </div>
                    <?php else: ?>
                        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                            <span class="sr-only">Processing...</span>
                        </div>
                        <h4 class="mt-3">Analyzing Your Fingerprints</h4>
                        <p>This may take a few moments. Please don't close this page.</p>
                        
                        <div class="progress mt-4">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%"></div>
                        </div>
                        
                        <script>
                        // Check for results every 5 seconds
                        setTimeout(function() {
                            window.location.href = "report.php?id=<?php echo $test_id; ?>";
                        }, 5000);
                        </script>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>