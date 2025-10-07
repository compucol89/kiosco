@echo off
echo 🚀 CONFIGURANDO GIT PARA TAYRONA KIOSCO POS
echo.

REM Verificar si Git está instalado
git --version >nul 2>&1
if errorlevel 1 (
    echo ❌ Git no está instalado. Descárgalo de: https://git-scm.com/
    pause
    exit /b 1
)

echo ✅ Git encontrado

REM Inicializar repositorio si no existe
if not exist .git (
    echo 📁 Inicializando repositorio Git...
    git init
    echo ✅ Repositorio inicializado
) else (
    echo 📁 Repositorio Git ya existe
)

REM Configurar usuario (cambiar por tus datos)
echo 👤 Configurando usuario Git...
set /p username="Ingresa tu nombre de usuario GitHub: "
set /p email="Ingresa tu email de GitHub: "

git config user.name "%username%"
git config user.email "%email%"

echo ✅ Usuario configurado: %username% (%email%)

REM Crear .gitignore si no existe
if not exist .gitignore (
    echo 📝 Creando .gitignore...
    (
        echo # Dependencias
        echo node_modules/
        echo vendor/
        echo.
        echo # Archivos de configuración sensibles
        echo config_production.php
        echo api/bd_conexion.php
        echo .env
        echo .env.local
        echo .env.production
        echo.
        echo # Logs
        echo api/logs/*.log
        echo *.log
        echo.
        echo # Cache
        echo api/cache/*
        echo !api/cache/.gitkeep
        echo.
        echo # Uploads y archivos temporales
        echo uploads/*
        echo !uploads/.gitkeep
        echo temp/
        echo tmp/
        echo.
        echo # Archivos del sistema
        echo .DS_Store
        echo Thumbs.db
        echo desktop.ini
        echo.
        echo # Build
        echo build/
        echo dist/
        echo.
        echo # IDE
        echo .vscode/
        echo .idea/
        echo *.swp
        echo *.swo
        echo.
        echo # Certificados y claves
        echo certificados/
        echo *.key
        echo *.crt
        echo *.pem
    ) > .gitignore
    echo ✅ .gitignore creado
)

REM Agregar archivos al staging
echo 📦 Agregando archivos al repositorio...
git add .

REM Hacer commit inicial
echo 💾 Haciendo commit inicial...
git commit -m "🚀 Initial commit - Tayrona Kiosco POS v1.0.1

- Sistema de punto de venta completo
- Frontend React + Backend PHP
- Base de datos MySQL optimizada
- Integración AFIP para Argentina
- Módulos: Ventas, Inventario, Caja, Reportes
- Performance optimizada <25ms"

echo.
echo ✅ Commit inicial completado
echo.
echo 📋 PRÓXIMOS PASOS:
echo 1. Crea un repositorio en GitHub
echo 2. Copia la URL del repositorio
echo 3. Ejecuta: git remote add origin [URL_DEL_REPO]
echo 4. Ejecuta: git push -u origin main
echo.
echo 🎉 ¡Listo para hacer push a GitHub!
pause
