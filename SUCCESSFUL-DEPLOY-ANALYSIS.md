# Анализ успешного деплоя

## Успешная конфигурация

Коммит: "Switch to FTP deployment for Timeweb"

### Workflow конфигурация (.github/workflows/deploy.yml)

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

### GitHub Secrets

- `FTP_USERNAME`: `co89321_wdc`
- `FTP_PASSWORD`: `Rd220911`
- `DEPLOY_PATH`: `/home/c/co89321/test/github_deploy` (в тот момент)

### Результат деплоя

✅ **Статус**: Успешно (файлы скопированы)

❌ **Проблема**: Файлы скопированы в неправильную директорию

**Фактическая директория**:
```
/home/c/co89321/public_html/C:/Program Files/Git/home/c/co89321/test/github_deploy
```

**Ожидаемая директория**:
```
/home/c/co89321/public_html/test/github_cc_autodeploy
```

### Анализ проблемы

1. **Путь был интерпретирован как относительный** вместо абсолютного
2. **Git Bash на Windows** добавил префикс `C:/Program Files/Git/` к пути
3. **FTP сервер** добавил базовый путь `/home/c/co89321/public_html/` в начало

### Решение

Для правильного деплоя нужно использовать **относительный путь от FTP корня**:

```yaml
server-dir: /test/github_cc_autodeploy/
```

Или **только имя директории** без начального слэша:
```yaml
server-dir: test/github_cc_autodeploy/
```

## Проверенные данные для FileZilla

- **Host**: `vh314.timeweb.ru`
- **Username**: `co89321_wdc`
- **Password**: `Rd220911`
- **FTP путь**: `ftp://co89321_wdc@vh314.timeweb.ru/test/github_cc_autodeploy/`

## Следующий шаг

Протестировать деплой с исправленным путём `DEPLOY_PATH = /test/github_cc_autodeploy`
