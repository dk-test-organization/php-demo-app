# Конфигурация автодеплоя - Готовые значения

## 📄 Файл `.github/workflows/deploy.yml`

Создайте файл `.github/workflows/deploy.yml` со следующим содержимым:

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

---

## 🔐 GitHub Secrets - Значения для копирования

Откройте: `https://github.com/dk-test-organization/php-demo-app/settings/secrets/actions`

Нажмите **"New repository secret"** и добавьте каждый секрет:

### Secret 1: FTP_USERNAME

**Name:**
```
FTP_USERNAME
```

**Value:**
```
co89321_wdc
```

---

### Secret 2: FTP_PASSWORD

**Name:**
```
FTP_PASSWORD
```

**Value:**
```
Rd220911
```

---

### Secret 3: DEPLOY_PATH

**Name:**
```
DEPLOY_PATH
```

**Value:**
```
test/github_cc_autodeploy
```

---

## ✅ Проверка настройки

После добавления всех секретов вы должны увидеть на странице:

```
Repository secrets

FTP_USERNAME        Updated X minutes ago    Update  Remove
FTP_PASSWORD        Updated X minutes ago    Update  Remove
DEPLOY_PATH         Updated X minutes ago    Update  Remove
```

⚠️ **Важно:** Значения секретов не отображаются в веб-интерфейсе! Это нормально и сделано для безопасности.

---

## 🚀 Альтернатива: Установка через командную строку

Если у вас установлен GitHub CLI (`gh`), можно добавить секреты командами:

```bash
gh secret set FTP_USERNAME --body "co89321_wdc" --repo dk-test-organization/php-demo-app
gh secret set FTP_PASSWORD --body "Rd220911" --repo dk-test-organization/php-demo-app
gh secret set DEPLOY_PATH --body "test/github_cc_autodeploy" --repo dk-test-organization/php-demo-app
```

---

## 📋 Краткая инструкция по применению

### Шаг 1: Создайте workflow файл
```bash
mkdir -p .github/workflows
```

Создайте файл `.github/workflows/deploy.yml` с содержимым из раздела выше.

### Шаг 2: Добавьте секреты
Откройте настройки репозитория и добавьте 3 секрета с точными значениями.

### Шаг 3: Отправьте код на GitHub
```bash
git add .github/workflows/deploy.yml
git commit -m "Add deployment workflow"
git push origin master
```

### Шаг 4: Проверьте деплой
Откройте: `https://github.com/dk-test-organization/php-demo-app/actions`

---

## 🎯 Итоговая структура

```
ваш-репозиторий/
├── .github/
│   └── workflows/
│       └── deploy.yml          ← Workflow конфигурация
├── index.php
├── pages/
└── assets/

GitHub Secrets (невидимы в коде):
├── FTP_USERNAME = co89321_wdc
├── FTP_PASSWORD = Rd220911
└── DEPLOY_PATH = test/github_cc_autodeploy
```

---

## ⚙️ Как это работает

1. Вы делаете `git push origin master`
2. GitHub Actions запускает workflow из `deploy.yml`
3. Workflow подключается к `vh314.timeweb.ru` по FTP
4. Использует логин/пароль из secrets
5. Загружает файлы в директорию `test/github_cc_autodeploy`
6. Деплой готов! 🎉

---

## 🔒 Безопасность

- **Секреты зашифрованы** на стороне GitHub
- **Не отображаются** в логах (заменяются на `***`)
- **Не видны** в веб-интерфейсе после сохранения
- **Доступны только** во время выполнения workflow

Никогда не коммитьте пароли в код! Всегда используйте GitHub Secrets.
