# Инструкция по настройке автодеплоя

## Шаг 1: Загрузка файлов на сервер

1. Подключитесь к серверу через FileZilla (vh314.timeweb.ru, co89321_wdc, Rd220911)
2. Перейдите в директорию `/test/github_cc_autodeploy`
3. Загрузите все файлы проекта в эту директорию через FTP

## Шаг 2: Инициализация Git репозитория на сервере

Подключитесь к серверу по SSH и выполните команды:

```bash
cd /home/c/co89321/public_html/test/github_cc_autodeploy
git init
git remote add origin https://github.com/dk-test-organization/php-demo-app.git
git fetch
git checkout master
git pull origin master
```

## Шаг 3: Настройка прав доступа

```bash
chmod 755 deploy.php
chmod 666 deploy.log
```

## Шаг 4: Настройка GitHub Webhook

1. Откройте https://github.com/dk-test-organization/php-demo-app/settings/hooks
2. Нажмите "Add webhook"
3. Заполните:
   - **Payload URL**: `https://ваш-домен.ru/test/github_cc_autodeploy/deploy.php`
   - **Content type**: `application/json`
   - **Secret**: установите секретный ключ (запомните его)
   - **Events**: выберите "Just the push event"
4. Нажмите "Add webhook"

## Шаг 5: Обновление секрета в deploy.php

Отредактируйте `deploy.php` на сервере и замените строку:
```php
define('WEBHOOK_SECRET', 'your_secure_webhook_secret_here');
```
На:
```php
define('WEBHOOK_SECRET', 'ваш_секретный_ключ_из_шага_4');
```

## Готово!

Теперь при каждом push в ветку master, GitHub отправит webhook на ваш сервер, и файл `deploy.php` автоматически выполнит `git pull`.

Логи деплоя сохраняются в файл `deploy.log`.
