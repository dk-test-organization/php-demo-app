<?php
/**
 * Простое PHP приложение для демонстрации деплоя через GitHub
 */

// Настройки
define('APP_NAME', 'Demo PHP App');
define('VERSION', '1.0.0');

// Получаем текущее время
$currentTime = date('Y-m-d H:i:s');

// Простая маршрутизация
$action = $_GET['action'] ?? 'home';

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1><?= APP_NAME ?></h1>
            <p class="version">Version: <?= VERSION ?></p>
        </header>

        <nav>
            <a href="?action=home">Главная</a>
            <a href="?action=about">О приложении</a>
            <a href="?action=info">PHP Info</a>
        </nav>

        <main>
            <?php
            switch ($action) {
                case 'about':
                    include 'pages/about.php';
                    break;

                case 'info':
                    include 'pages/info.php';
                    break;

                case 'home':
                default:
                    include 'pages/home.php';
                    break;
            }
            ?>
        </main>

        <footer>
            <p>Текущее время на сервере: <?= $currentTime ?></p>
            <p>&copy; 2025 Demo PHP App. Deployed via GitHub Actions.</p>
        </footer>
    </div>
</body>
</html>
