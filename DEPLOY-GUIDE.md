# Руководство по настройке автодеплоя на Timeweb через GitHub Actions

## ✅ Рабочая конфигурация

### Файл `.github/workflows/deploy.yml`

```yaml
name: Deploy to Timeweb

on:
  push:
    branches:
      - master

jobs:
  deploy:
    runs-on: ubuntu-latest

    steps:
    - name: Checkout code
      uses: actions/checkout@v3

    - name: Deploy to server via FTP
      uses: SamKirkland/FTP-Deploy-Action@4.3.0
      with:
        server: vh314.timeweb.ru
        username: ${{ secrets.FTP_USERNAME }}
        password: ${{ secrets.FTP_PASSWORD }}
        server-dir: ${{ secrets.DEPLOY_PATH }}/
```

### GitHub Secrets (Settings → Secrets and variables → Actions)

| Секрет | Значение |
|--------|----------|
| `FTP_USERNAME` | `co89321_wdc` |
| `FTP_PASSWORD` | `Rd220911` |
| `DEPLOY_PATH` | `test/github_cc_autodeploy` |

### ⚠️ Критически важно!

**Путь должен быть относительным, БЕЗ начального слэша `/`**

✅ **Правильно**: `test/github_cc_autodeploy`
❌ **Неправильно**: `/test/github_cc_autodeploy`
❌ **Неправильно**: `/home/c/co89321/test/github_cc_autodeploy`

## 📊 История проблем и решений

### Проблема #1: SSH/SFTP аутентификация

**Попытка**: Использование `appleboy/scp-action` и `wlixcc/SFTP-Deploy-Action` с пользователем `co89321_wdc`

**Ошибка**:
```
ssh: handshake failed: ssh: unable to authenticate
Permission denied, please try again
```

**Причина**:
- Пользователь `co89321_wdc` не имеет SSH доступа
- Только основной пользователь `co89321` имеет SSH доступ
- У нас нет пароля от `co89321`

**Решение**: Переключились на FTP вместо SSH/SFTP

---

### Проблема #2: FTP Connection Timeout

**Попытка**: Использование `SamKirkland/FTP-Deploy-Action` на порту 21

**Ошибка**:
```
Error: connect ETIMEDOUT 176.57.210.144:21
Failed to connect, are you sure your server works via FTP or FTPS?
```

**Причина**:
- GitHub Actions блокирует исходящие соединения на FTP порт 21
- Это периодическая проблема, связанная с сетевыми ограничениями GitHub

**Решение**:
- Проблема решилась сама при повторных попытках
- FTP порт 21 иногда доступен, иногда нет из GitHub Actions

---

### Проблема #3: Неправильная директория деплоя

**Попытка**: Путь `DEPLOY_PATH = /home/c/co89321/test/github_deploy`

**Результат**: Файлы оказались в `/home/c/co89321/public_html/C:/Program Files/Git/home/c/co89321/test/github_deploy`

**Причина**:
1. GitHub Actions runner работает на Linux
2. Путь начинающийся с `/` интерпретируется как абсолютный POSIX путь
3. FTP сервер Timeweb работает в контексте пользователя `co89321_wdc`
4. Корень FTP для этого пользователя: `/home/c/co89321/public_html/`
5. Git Bash на сервере добавил Windows-префикс `C:/Program Files/Git/` к пути

**Решение**: Использовать относительный путь без начального `/`

---

### Проблема #4: FTP vs SFTP путаница

**Попытка**: Различные варианты с FTP, FTPS, SFTP

**Ошибки**:
- `airvzxf/ftp-deployment-action` - бесконечное зависание
- Различные timeout'ы и connection refused

**Причина**:
- Некоторые FTP actions требуют специфические настройки для Timeweb
- Не все actions корректно работают с российскими хостингами
- SFTP требует SSH доступ, который недоступен для `co89321_wdc`

**Решение**: `SamKirkland/FTP-Deploy-Action@4.3.0` оказался наиболее стабильным

---

## 🎯 Финальное решение

### Что сработало:

1. **Протокол**: FTP (не SFTP, не SSH)
2. **Action**: `SamKirkland/FTP-Deploy-Action@4.3.0`
3. **Пользователь**: `co89321_wdc` (FTP пользователь)
4. **Путь**: `test/github_cc_autodeploy` (относительный, без `/`)
5. **Сервер**: `vh314.timeweb.ru`

### Как работает путь:

```
FTP корень пользователя co89321_wdc:
/home/c/co89321/public_html/

Относительный путь в DEPLOY_PATH:
test/github_cc_autodeploy

Итоговый полный путь на сервере:
/home/c/co89321/public_html/test/github_cc_autodeploy/
```

### Почему относительный путь:

- **Абсолютный путь** (`/test/...`) → обрабатывается как POSIX путь → добавляется Windows префикс → файлы в неправильном месте
- **Относительный путь** (`test/...`) → обрабатывается от FTP корня → файлы в правильном месте

---

## 📝 Инструкция по применению

### 1. Создайте workflow файл

```bash
mkdir -p .github/workflows
```

Создайте файл `.github/workflows/deploy.yml` с содержимым из раздела "Рабочая конфигурация"

### 2. Настройте GitHub Secrets

1. Откройте репозиторий на GitHub
2. Перейдите в **Settings** → **Secrets and variables** → **Actions**
3. Нажмите **New repository secret** и добавьте три секрета:
   - `FTP_USERNAME` = `co89321_wdc`
   - `FTP_PASSWORD` = `Rd220911`
   - `DEPLOY_PATH` = `test/github_cc_autodeploy`

### 3. Создайте целевую директорию на сервере

Через FileZilla создайте структуру папок:
```
/test/
  └─ github_cc_autodeploy/
```

### 4. Протестируйте деплой

```bash
git add .github/workflows/deploy.yml
git commit -m "Add auto-deployment workflow"
git push origin master
```

### 5. Проверьте результат

1. Откройте **Actions** в репозитории
2. Дождитесь завершения workflow
3. Проверьте файлы через FileZilla в `/test/github_cc_autodeploy/`

---

## 🔍 Проверка и отладка

### Проверка успешного деплоя

В логах GitHub Actions должно быть:
```
🎉 Sync complete. Saving current server state
Time spent deploying: XX seconds
```

### Если деплой не работает

1. **Проверьте секреты**: Settings → Secrets → убедитесь что все 3 секрета созданы
2. **Проверьте путь**: `DEPLOY_PATH` должен быть БЕЗ начального `/`
3. **Проверьте FTP доступ**: подключитесь через FileZilla с теми же учетными данными
4. **Проверьте логи**: Actions → выберите запуск → смотрите детали ошибки

### Типичные ошибки

| Ошибка | Причина | Решение |
|--------|---------|---------|
| `ETIMEDOUT port 21` | GitHub блокирует FTP | Подождите и попробуйте снова |
| `Permission denied` | SSH недоступен для пользователя | Используйте FTP, не SFTP |
| Файлы в неправильной папке | Абсолютный путь с `/` | Уберите `/` из начала пути |
| `Authentication failed` | Неправильные учетные данные | Проверьте секреты |

---

## 💡 Важные замечания

1. **Первый деплой занимает больше времени** - загружаются все файлы
2. **Последующие деплои быстрее** - загружаются только изменения
3. **Файл `.ftp-deploy-sync-state.json`** создается автоматически для отслеживания изменений
4. **Деплой срабатывает только при push в master** - другие ветки игнорируются
5. **Пароли в логах скрыты** - GitHub автоматически маскирует секреты как `***`

---

## 🎉 Результат

После правильной настройки каждый `git push origin master` автоматически:
1. Запускает GitHub Actions workflow
2. Подключается к серверу через FTP
3. Загружает измененные файлы
4. Синхронизирует состояние
5. Деплой завершается за ~15-20 секунд

Теперь ваш проект автоматически деплоится на Timeweb! 🚀
