# Современные практики деплоя: от FTP до CI/CD

## Ваш текущий процесс (устаревший)

```
Локальная разработка → Ручной FTP → Сервер
```

**Проблемы:**
- ❌ Нет версионирования кода
- ❌ Невозможно откатить изменения
- ❌ Нет истории изменений
- ❌ Сложно работать в команде
- ❌ Легко перезаписать чужие файлы
- ❌ Нет бэкапов
- ❌ Нет тестирования перед деплоем
- ❌ Человеческий фактор (забыть файл, залить не туда)

---

## Современный процесс (best practices)

### Уровень 1: Базовый (Git + GitHub)

```
Локальная разработка → Git → GitHub → Ручной деплой
```

**Минимальный набор:**
1. Используете Git для версионирования
2. Пушите в GitHub (приватный репозиторий)
3. На сервере делаете `git pull`

**Преимущества:**
- ✅ История всех изменений
- ✅ Легко откатить
- ✅ Можно работать в команде
- ✅ Бесплатные бэкапы на GitHub

**Для Битрикс24:**
```bash
# На локальной машине
git init
git add .
git commit -m "Initial commit"
git remote add origin https://github.com/username/bitrix-project.git
git push -u origin main

# На сервере (через SSH)
cd /var/www/html
git clone https://github.com/username/bitrix-project.git .
# или если уже клонирован
git pull origin main
```

---

### Уровень 2: Git + Deploy Script

```
Локальная разработка → Git → GitHub → Webhook → Auto Deploy
```

**Автоматизация через GitHub Webhook:**

Создаете скрипт на сервере `deploy.php`:
```php
<?php
// /var/www/deploy.php

// Секретный токен для безопасности
$secret = 'ваш_секретный_токен';

// Проверка токена из GitHub webhook
$headers = getallheaders();
$signature = $headers['X-Hub-Signature-256'] ?? '';

$payload = file_get_contents('php://input');
$hash = 'sha256=' . hash_hmac('sha256', $payload, $secret);

if (!hash_equals($hash, $signature)) {
    http_response_code(403);
    die('Invalid signature');
}

// Логирование
file_put_contents('/var/log/deploy.log', date('Y-m-d H:i:s') . " - Deploy started\n", FILE_APPEND);

// Путь к проекту
$project_path = '/var/www/html/bitrix-project';

// Команды деплоя
$commands = [
    "cd $project_path",
    "git fetch --all",
    "git reset --hard origin/main", // или origin/production
    "composer install --no-dev", // если используете Composer
    "php bitrix/console.php cache:clear", // очистка кеша Битрикс
];

foreach ($commands as $command) {
    exec($command . ' 2>&1', $output, $return);
    file_put_contents('/var/log/deploy.log',
        "Command: $command\n" . implode("\n", $output) . "\n",
        FILE_APPEND
    );
}

file_put_contents('/var/log/deploy.log', "Deploy completed\n\n", FILE_APPEND);

http_response_code(200);
echo "Deploy successful";
?>
```

**Настройка GitHub Webhook:**
1. GitHub → Repository → Settings → Webhooks → Add webhook
2. Payload URL: `https://ваш-домен.ru/deploy.php`
3. Content type: `application/json`
4. Secret: `ваш_секретный_токен`
5. Events: `Just the push event`

**Результат:** При `git push` → автоматический деплой

---

### Уровень 3: GitHub Actions CI/CD (рекомендуется)

```
Локальная разработка → Git → GitHub → GitHub Actions → Auto Deploy + Tests
```

**Создаете `.github/workflows/deploy.yml`:**

```yaml
name: Deploy to Production

on:
  push:
    branches: [main]
  # или вручную
  workflow_dispatch:

jobs:
  deploy:
    runs-on: ubuntu-latest

    steps:
      # 1. Получаем код
      - name: Checkout code
        uses: actions/checkout@v3

      # 2. Проверки перед деплоем (опционально)
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'

      - name: Validate PHP syntax
        run: find . -name "*.php" -exec php -l {} \;

      # 3. Деплой через FTP (если нет SSH)
      - name: FTP Deploy
        uses: SamKirkland/FTP-Deploy-Action@4.3.0
        with:
          server: ftp.ваш-хостинг.ru
          username: ${{ secrets.FTP_USERNAME }}
          password: ${{ secrets.FTP_PASSWORD }}
          server-dir: /public_html/
          exclude: |
            **/.git*
            **/.git*/**
            **/node_modules/**
            .github/**

      # 4. Деплой через SSH (если есть доступ)
      - name: SSH Deploy
        uses: appleboy/ssh-action@master
        with:
          host: ${{ secrets.SSH_HOST }}
          username: ${{ secrets.SSH_USERNAME }}
          key: ${{ secrets.SSH_PRIVATE_KEY }}
          script: |
            cd /var/www/html/bitrix-project
            git pull origin main
            composer install --no-dev
            php bitrix/console.php cache:clear

      # 5. Уведомление
      - name: Notify success
        if: success()
        run: echo "Deploy successful!"
```

**Настройка секретов в GitHub:**
1. GitHub → Repository → Settings → Secrets and variables → Actions
2. Добавляете:
   - `FTP_USERNAME`
   - `FTP_PASSWORD`
   - `SSH_HOST`
   - `SSH_USERNAME`
   - `SSH_PRIVATE_KEY`

**Преимущества:**
- ✅ Автоматический деплой при push
- ✅ Проверка синтаксиса перед деплоем
- ✅ История всех деплоев
- ✅ Уведомления об ошибках
- ✅ Можно добавить тесты

---

## Специфика для Битрикс24

### Структура .gitignore для Битрикс

```gitignore
# .gitignore для Битрикс24

# Bitrix core (не коммитим ядро)
/bitrix/
!/bitrix/.settings.php
!/bitrix/php_interface/

# Загрузки пользователей
/upload/

# Кеш
/bitrix/cache/
/bitrix/managed_cache/
/bitrix/stack_cache/

# Логи
/bitrix/backup/
*.log

# Локальные настройки
/bitrix/.settings.php
/bitrix/php_interface/dbconn.php

# IDE
.idea/
.vscode/
*.sublime-*

# Composer
/vendor/
composer.lock

# Node
node_modules/
package-lock.json

# OS
.DS_Store
Thumbs.db
```

### Что коммитить в Git для Битрикс:

**✅ Коммитим:**
- `/local/` — ваши компоненты, модули, шаблоны
- `/upload/.htaccess` — правила для upload
- `/.htaccess` — настройки Apache
- Конфигурационные файлы
- Кастомные скрипты

**❌ НЕ коммитим:**
- `/bitrix/` — ядро Битрикс (кроме настроек)
- `/upload/` — файлы пользователей
- Кеши, логи, бэкапы
- Секретные данные (пароли БД)

### Деплой Битрикс: безопасный скрипт

```bash
#!/bin/bash
# deploy.sh

set -e # Остановка при ошибке

PROJECT_PATH="/var/www/html/bitrix-project"
BACKUP_PATH="/var/backups/bitrix"
DATE=$(date +%Y%m%d_%H%M%S)

echo "=== Bitrix Deploy Script ==="
echo "Started at: $(date)"

# 1. Бэкап текущей версии
echo "Creating backup..."
mkdir -p $BACKUP_PATH
tar -czf $BACKUP_PATH/backup_$DATE.tar.gz -C $PROJECT_PATH .

# 2. Переходим в проект
cd $PROJECT_PATH

# 3. Сохраняем текущие файлы настроек
echo "Backing up settings..."
cp bitrix/.settings.php /tmp/.settings.php.bak
cp bitrix/php_interface/dbconn.php /tmp/dbconn.php.bak

# 4. Получаем код из Git
echo "Pulling from Git..."
git fetch --all
git reset --hard origin/main

# 5. Восстанавливаем настройки
echo "Restoring settings..."
cp /tmp/.settings.php.bak bitrix/.settings.php
cp /tmp/dbconn.php.bak bitrix/php_interface/dbconn.php

# 6. Права доступа
echo "Setting permissions..."
chown -R www-data:www-data $PROJECT_PATH
find $PROJECT_PATH -type d -exec chmod 755 {} \;
find $PROJECT_PATH -type f -exec chmod 644 {} \;

# 7. Очистка кеша Битрикс
echo "Clearing Bitrix cache..."
php $PROJECT_PATH/bitrix/console.php cache:clear

# 8. Composer (если используется)
if [ -f "composer.json" ]; then
    echo "Running composer install..."
    composer install --no-dev --optimize-autoloader
fi

# 9. Проверка здоровья
echo "Health check..."
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" https://ваш-домен.ru)
if [ $HTTP_CODE -ne 200 ]; then
    echo "ERROR: Site returned $HTTP_CODE"
    echo "Rolling back..."
    tar -xzf $BACKUP_PATH/backup_$DATE.tar.gz -C $PROJECT_PATH
    exit 1
fi

echo "Deploy successful!"
echo "Completed at: $(date)"
```

---

## Можете ли вы (Claude Code) деплоить на сервер?

### ✅ Да, я могу:

1. **Создать деплой скрипты**
   - GitHub Actions workflows
   - Bash скрипты для деплоя
   - PHP скрипты для webhook

2. **Настроить Git и GitHub**
   - Инициализировать репозиторий
   - Создать .gitignore
   - Настроить ветки
   - Создать PR и мерджить

3. **Работать с FTP через скрипты**
   ```bash
   # Я могу создать и запустить
   lftp -u username,password ftp.server.com <<EOF
   cd /public_html
   mirror -R local_folder remote_folder
   bye
   EOF
   ```

4. **Работать с SSH (если есть доступ)**
   ```bash
   ssh user@server "cd /var/www && git pull"
   ```

5. **Создать автоматизацию**
   - GitHub Actions
   - Webhooks
   - Deploy скрипты

### ❌ Я НЕ могу напрямую:

1. **Заливать файлы на ваш сервер напрямую**
   - У меня нет доступа к вашим FTP/SSH credentials
   - Я не имею прямого доступа к интернету для FTP

2. **Выполнять команды на удаленном сервере**
   - Только через скрипты, которые вы запустите

### 🤝 Как мы можем работать вместе:

**Вариант 1: Я создаю скрипты, вы запускаете**
```
Вы: "Создай деплой скрипт для Битрикс через FTP"
Я: [создаю deploy.sh с вашими настройками]
Вы: [запускаете ./deploy.sh на своей машине]
```

**Вариант 2: Я настраиваю GitHub Actions**
```
Вы: "Настрой автодеплой через GitHub Actions"
Я: [создаю .github/workflows/deploy.yml]
Вы: [добавляете секреты в GitHub]
Результат: автоматический деплой при git push
```

**Вариант 3: Я создаю webhook**
```
Я: [создаю deploy.php скрипт]
Вы: [заливаете deploy.php на сервер]
Вы: [настраиваете webhook в GitHub]
Результат: автодеплой при push
```

---

## Рекомендуемый план миграции для вас

### Этап 1: Внедрение Git (1 день)

```bash
# На вашей локальной машине
cd ваш-битрикс-проект
git init
# Я создам правильный .gitignore для Битрикс
git add .
git commit -m "Initial commit"
```

### Этап 2: GitHub Setup (30 минут)

```bash
# Создаете приватный репозиторий на GitHub
# Я помогу настроить и запушить
git remote add origin https://github.com/username/bitrix-project.git
git push -u origin main
```

### Этап 3: Простой деплой через SSH (1 час)

```bash
# На сервере (первый раз)
cd /var/www/html
git clone https://github.com/username/bitrix-project.git .

# Далее при каждом обновлении
git pull origin main
```

### Этап 4: Автоматизация через GitHub Actions (2 часа)

- Я создам workflow для автодеплоя
- Вы добавите секреты (FTP/SSH credentials)
- Результат: git push → автоматический деплой

---

## Практический пример: Ваш первый деплой

### Сценарий: Вы изменили файл в VSCode

**Старый способ (FTP):**
```
1. Сохраняете файл в VSCode
2. Открываете FileZilla
3. Подключаетесь к FTP
4. Находите файл
5. Заливаете файл
6. Проверяете на сайте
```
**Время:** 5-10 минут

**Новый способ (Git + GitHub Actions):**
```
1. Сохраняете файл в VSCode
2. В терминале VSCode:
   git add .
   git commit -m "Исправил баг в корзине"
   git push
3. GitHub Actions автоматически деплоит
4. Получаете уведомление об успехе
```
**Время:** 1 минута

**Еще лучше (через VSCode Git UI):**
```
1. Сохраняете файл (Ctrl+S)
2. Клик на иконку Git в VSCode
3. Вводите сообщение коммита
4. Нажимаете Commit & Push
5. Автодеплой
```
**Время:** 30 секунд

---

## Что я могу сделать прямо сейчас

Скажите мне, и я:

1. **Создам правильный .gitignore для Битрикс**
2. **Инициализирую Git репозиторий**
3. **Создам GitHub Actions workflow для автодеплоя**
4. **Создам деплой скрипт для SSH**
5. **Создам webhook скрипт для PHP**
6. **Настрою VSCode для работы с Git**

Просто скажите:
- У вас есть SSH доступ к серверу или только FTP?
- Хотите полную автоматизацию или пока ручной деплой через git pull?
- Какой хостинг используете?

---

## Сравнительная таблица

| Критерий | FTP (старое) | Git + ручной pull | Git + GitHub Actions |
|----------|--------------|-------------------|----------------------|
| **Скорость деплоя** | 5-10 мин | 2 мин | 30 сек |
| **Версионирование** | ❌ | ✅ | ✅ |
| **Откат изменений** | ❌ | ✅ (легко) | ✅ (1 клик) |
| **История** | ❌ | ✅ | ✅ + автолог |
| **Работа в команде** | ⚠️ конфликты | ✅ | ✅ |
| **Автоматизация** | ❌ | ❌ | ✅ |
| **Тестирование** | ❌ | ❌ | ✅ |
| **Бэкапы** | ⚠️ вручную | ✅ GitHub | ✅ + авто |
| **Уведомления** | ❌ | ❌ | ✅ |
| **Цена** | Бесплатно | Бесплатно | Бесплатно |

---

## Заключение

**Рекомендация:** Переходите на Git + GitHub Actions

**План действий:**
1. Я помогу настроить Git и GitHub
2. Создам автодеплой через GitHub Actions
3. Вы добавите секреты (пароли)
4. Работаете как обычно в VSCode
5. При git push → автоматический деплой

**Результат:**
- ⏱️ Деплой с 10 минут до 30 секунд
- 🛡️ Безопасность и версионирование
- 🔄 Легкий откат при ошибках
- 🤝 Готовность к командной работе

Готов начать прямо сейчас! 🚀
