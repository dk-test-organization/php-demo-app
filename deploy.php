<?php
/**
 * GitHub Webhook Auto-Deploy Script
 *
 * Place this file on your server in /test/github_cc_autodeploy/
 * Set webhook URL: https://your-domain.com/test/github_cc_autodeploy/deploy.php
 */

// Security token - change this to a secure random string
define('WEBHOOK_SECRET', 'your_secure_webhook_secret_here');

// Path to your project directory
define('PROJECT_DIR', __DIR__);

// Log file
define('LOG_FILE', PROJECT_DIR . '/deploy.log');

/**
 * Log message to file
 */
function logMessage($message) {
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents(LOG_FILE, "[$timestamp] $message\n", FILE_APPEND);
}

/**
 * Verify GitHub signature
 */
function verifySignature($payload, $signature) {
    $hash = 'sha256=' . hash_hmac('sha256', $payload, WEBHOOK_SECRET);
    return hash_equals($hash, $signature);
}

// Get the payload
$payload = file_get_contents('php://input');
$headers = getallheaders();

// Verify signature if secret is set
if (WEBHOOK_SECRET !== 'your_secure_webhook_secret_here') {
    $signature = $headers['X-Hub-Signature-256'] ?? '';
    if (!verifySignature($payload, $signature)) {
        logMessage('ERROR: Invalid signature');
        http_response_code(403);
        die('Invalid signature');
    }
}

// Parse payload
$data = json_decode($payload, true);

// Check if this is a push event to master branch
if (isset($data['ref']) && $data['ref'] === 'refs/heads/master') {
    logMessage('Webhook received for master branch');

    // Change to project directory
    chdir(PROJECT_DIR);

    // Pull latest changes
    $output = [];
    $returnVar = 0;

    exec('git pull origin master 2>&1', $output, $returnVar);

    $outputStr = implode("\n", $output);
    logMessage("Git pull output:\n$outputStr");

    if ($returnVar === 0) {
        logMessage('Deploy successful');
        echo json_encode(['status' => 'success', 'message' => 'Deploy completed']);
    } else {
        logMessage('Deploy failed');
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Deploy failed']);
    }
} else {
    logMessage('Webhook received but not for master branch');
    echo json_encode(['status' => 'skipped', 'message' => 'Not master branch']);
}
