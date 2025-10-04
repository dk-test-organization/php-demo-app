# Настройка Webhook деплоя (альтернатива FTP)

## Проблема
GitHub Actions нестабильно блокирует FTP/SFTP соединения, деплой работает через раз.

## Решение
Использовать GitHub Webhook + PHP скрипт на сервере для автоматического деплоя.

## Установка

### 1. Загрузите deploy-webhook.php на сервер

Загрузите файл `deploy-webhook.php` в директорию `/home/c/co89321/public_html/test/github_cc_autodeploy/`

### 2. Настройте GitHub Webhook

1. Откройте репозиторий: https://github.com/dk-test-organization/php-demo-app
2. Перейдите в **Settings** → **Webhooks** → **Add webhook**
3. Заполните:
   - **Payload URL**: `https://ваш-домен.ru/test/github_cc_autodeploy/deploy-webhook.php`
   - **Content type**: `application/json`
   - **Secret**: `gh_webhook_2024_secure_key`
   - **Which events**: Выберите "Just the push event"
   - Отметьте **Active**
4. Нажмите **Add webhook**

### 3. Проверьте права доступа

Убедитесь что у PHP есть права на запись в директорию:
```bash
chmod 755 /home/c/co89321/public_html/test/github_cc_autodeploy/
```

### 4. Тестирование

Сделайте любой коммит и push в master:
```bash
git commit --allow-empty -m "Test webhook deploy"
git push origin master
```

Проверьте:
- В GitHub: Settings → Webhooks → Recent Deliveries (должна быть зеленая галочка)
- На сервере: файл `deploy.log` должен содержать записи о деплое

## Как это работает

1. Вы делаете `git push origin master`
2. GitHub отправляет webhook на ваш сервер
3. PHP скрипт:
   - Проверяет подпись для безопасности
   - Загружает ZIP архив репозитория через GitHub API
   - Извлекает файлы
   - Копирует их в директорию сервера
   - Удаляет временные файлы
4. Деплой завершен!

## Преимущества

- ✅ Не зависит от блокировок GitHub Actions
- ✅ Работает стабильно и быстро
- ✅ Не требует Git на сервере
- ✅ Безопасно (проверка подписи)
- ✅ Логирование всех деплоев

## Отладка

Проверьте лог: `/test/github_cc_autodeploy/deploy.log`

В логе будут записи о всех попытках деплоя и ошибках.
