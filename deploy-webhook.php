<?php
/**
 * GitHub Webhook Deploy Script (без Git на сервере)
 *
 * Загружает код напрямую из GitHub используя REST API
 */

// Секретный ключ для безопасности
define('WEBHOOK_SECRET', 'gh_webhook_2024_secure_key');

// Настройки репозитория
define('GITHUB_REPO', 'dk-test-organization/php-demo-app');
define('GITHUB_BRANCH', 'master');
define('DEPLOY_DIR', __DIR__);

// Лог файл
define('LOG_FILE', DEPLOY_DIR . '/deploy.log');

// Функция логирования
function logMessage($message) {
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents(LOG_FILE, "[$timestamp] $message\n", FILE_APPEND);
}

// Проверка подписи GitHub
function verifySignature($payload, $signature) {
    if (empty($signature)) return false;
    $hash = 'sha256=' . hash_hmac('sha256', $payload, WEBHOOK_SECRET);
    return hash_equals($hash, $signature);
}

// Загрузка архива репозитория
function downloadRepository() {
    $url = 'https://api.github.com/repos/' . GITHUB_REPO . '/zipball/' . GITHUB_BRANCH;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'PHP-Webhook-Deploy');

    $zipData = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception("Failed to download repository: HTTP $httpCode");
    }

    return $zipData;
}

// Извлечение архива
function extractAndDeploy($zipData) {
    $tempZip = DEPLOY_DIR . '/temp_deploy.zip';
    $tempDir = DEPLOY_DIR . '/temp_extract';

    // Сохраняем ZIP
    file_put_contents($tempZip, $zipData);

    // Создаем временную директорию
    if (!is_dir($tempDir)) {
        mkdir($tempDir, 0755, true);
    }

    // Извлекаем
    $zip = new ZipArchive;
    if ($zip->open($tempZip) !== true) {
        throw new Exception('Failed to open ZIP archive');
    }

    $zip->extractTo($tempDir);
    $zip->close();

    // Находим извлеченную папку (GitHub создает папку с именем repo-hash)
    $extractedFolder = null;
    $files = scandir($tempDir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && is_dir($tempDir . '/' . $file)) {
            $extractedFolder = $tempDir . '/' . $file;
            break;
        }
    }

    if (!$extractedFolder) {
        throw new Exception('Could not find extracted folder');
    }

    // Копируем файлы (кроме deploy-webhook.php и deploy.log)
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($extractedFolder, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        $targetPath = DEPLOY_DIR . '/' . $iterator->getSubPathname();

        // Пропускаем служебные файлы
        if (basename($targetPath) === 'deploy-webhook.php' ||
            basename($targetPath) === 'deploy.log' ||
            basename($targetPath) === '.git') {
            continue;
        }

        if ($item->isDir()) {
            if (!is_dir($targetPath)) {
                mkdir($targetPath, 0755, true);
            }
        } else {
            copy($item, $targetPath);
        }
    }

    // Очистка
    deleteDirectory($tempDir);
    unlink($tempZip);
}

// Рекурсивное удаление директории
function deleteDirectory($dir) {
    if (!is_dir($dir)) return;

    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        is_dir($path) ? deleteDirectory($path) : unlink($path);
    }
    rmdir($dir);
}

// Основная логика
try {
    $payload = file_get_contents('php://input');
    $headers = getallheaders();

    // Проверяем подпись
    $signature = $headers['X-Hub-Signature-256'] ?? '';
    if (!verifySignature($payload, $signature)) {
        logMessage('ERROR: Invalid signature');
        http_response_code(403);
        die(json_encode(['status' => 'error', 'message' => 'Invalid signature']));
    }

    // Парсим payload
    $data = json_decode($payload, true);

    // Проверяем, что это push в master
    if (!isset($data['ref']) || $data['ref'] !== 'refs/heads/master') {
        logMessage('Webhook received but not for master branch');
        die(json_encode(['status' => 'skipped', 'message' => 'Not master branch']));
    }

    logMessage('Starting deployment from GitHub');

    // Загружаем и разворачиваем
    $zipData = downloadRepository();
    extractAndDeploy($zipData);

    logMessage('Deployment completed successfully');

    echo json_encode([
        'status' => 'success',
        'message' => 'Deployment completed',
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    $error = $e->getMessage();
    logMessage("ERROR: $error");
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $error]);
}
