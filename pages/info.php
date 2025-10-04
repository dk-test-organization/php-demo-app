<div class="page-content">
    <h2>Информация о PHP</h2>

    <div class="info-box">
        <h3>Версия PHP: <?= phpversion() ?></h3>
        <p>Сервер: <?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?></p>
        <p>ОС: <?= PHP_OS ?></p>
    </div>

    <div class="php-info">
        <h3>Основные настройки:</h3>
        <table>
            <tr>
                <td><strong>PHP Version:</strong></td>
                <td><?= phpversion() ?></td>
            </tr>
            <tr>
                <td><strong>Memory Limit:</strong></td>
                <td><?= ini_get('memory_limit') ?></td>
            </tr>
            <tr>
                <td><strong>Max Execution Time:</strong></td>
                <td><?= ini_get('max_execution_time') ?> секунд</td>
            </tr>
            <tr>
                <td><strong>Upload Max Filesize:</strong></td>
                <td><?= ini_get('upload_max_filesize') ?></td>
            </tr>
            <tr>
                <td><strong>Post Max Size:</strong></td>
                <td><?= ini_get('post_max_size') ?></td>
            </tr>
        </table>
    </div>

    <div class="extensions">
        <h3>Загруженные расширения:</h3>
        <div class="extensions-grid">
            <?php
            $extensions = get_loaded_extensions();
            sort($extensions);
            foreach ($extensions as $ext) {
                echo "<span class='extension-badge'>$ext</span>";
            }
            ?>
        </div>
    </div>

    <div class="full-phpinfo">
        <h3>Полная информация:</h3>
        <details>
            <summary>Показать phpinfo() (нажмите для раскрытия)</summary>
            <div class="phpinfo-container">
                <?php phpinfo(); ?>
            </div>
        </details>
    </div>
</div>
