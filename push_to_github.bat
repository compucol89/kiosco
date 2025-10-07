@echo off
echo 🚀 PUSH AUTOMÁTICO A GITHUB - TAYRONA KIOSCO POS
echo.

REM Verificar si hay cambios
git status --porcelain >nul 2>&1
if errorlevel 1 (
    echo ❌ No estás en un repositorio Git
    echo Ejecuta primero: setup_git.bat
    pause
    exit /b 1
)

REM Mostrar estado actual
echo 📊 Estado actual del repositorio:
git status --short

REM Agregar todos los cambios
echo.
echo 📦 Agregando cambios...
git add .

REM Verificar si hay cambios para commit
git diff --cached --quiet
if not errorlevel 1 (
    echo ℹ️  No hay cambios nuevos para hacer commit
    goto :push
)

REM Pedir mensaje de commit
echo.
set /p commit_msg="💬 Mensaje del commit (Enter para mensaje automático): "

if "%commit_msg%"=="" (
    REM Generar mensaje automático con fecha y hora
    for /f "tokens=2 delims==" %%a in ('wmic OS Get localdatetime /value') do set "dt=%%a"
    set "YY=%dt:~2,2%" & set "YYYY=%dt:~0,4%" & set "MM=%dt:~4,2%" & set "DD=%dt:~6,2%"
    set "HH=%dt:~8,2%" & set "Min=%dt:~10,2%" & set "Sec=%dt:~12,2%"
    set "commit_msg=🔄 Actualización automática %DD%/%MM%/%YYYY% %HH%:%Min%"
)

REM Hacer commit
echo.
echo 💾 Haciendo commit: %commit_msg%
git commit -m "%commit_msg%"

:push
REM Hacer push
echo.
echo 🚀 Haciendo push a GitHub...
git push

if errorlevel 1 (
    echo.
    echo ⚠️  Error en push. Posibles causas:
    echo 1. No has configurado el remote origin
    echo 2. Problemas de autenticación
    echo.
    echo 🔧 Solución rápida:
    echo git remote add origin https://github.com/TU_USUARIO/tayrona-kiosco-pos.git
    echo git push -u origin main
    echo.
    pause
    exit /b 1
)

echo.
echo ✅ ¡Push completado exitosamente!
echo 🌐 Tu código ya está en GitHub
echo.

REM Mostrar URL del repositorio si está configurado
for /f "tokens=*" %%i in ('git remote get-url origin 2^>nul') do set "repo_url=%%i"
if defined repo_url (
    echo 🔗 Repositorio: %repo_url%
)

echo.
echo 🎉 ¡Listo! Ahora puedes conectar con Railway para deploy automático
pause
