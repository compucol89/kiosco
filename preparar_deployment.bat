@echo off
echo ========================================
echo PREPARANDO ARCHIVOS PARA HOSTINGER
echo ========================================
echo.

REM Crear carpeta temporal para deployment
if exist "deploy_hostinger" rmdir /s /q deploy_hostinger
mkdir deploy_hostinger

echo [1/6] Copiando archivos del BUILD (frontend compilado)...
xcopy build\* deploy_hostinger\ /E /I /Y >nul
echo      OK - Frontend copiado

echo [2/6] Copiando API (backend PHP)...
xcopy api deploy_hostinger\api\ /E /I /Y >nul
echo      OK - API copiado

echo [3/6] Copiando vendor (composer dependencies)...
xcopy vendor deploy_hostinger\vendor\ /E /I /Y >nul
echo      OK - Vendor copiado

echo [4/6] Copiando uploads...
if not exist "deploy_hostinger\uploads" mkdir deploy_hostinger\uploads
if exist "uploads" xcopy uploads deploy_hostinger\uploads\ /E /I /Y >nul
echo      OK - Uploads copiado

echo [5/6] Copiando certificados AFIP...
if not exist "deploy_hostinger\api\certificados" mkdir deploy_hostinger\api\certificados
xcopy api\certificados deploy_hostinger\api\certificados\ /E /I /Y >nul
echo      OK - Certificados copiados

echo [6/6] Creando .htaccess...
(
echo ^<IfModule mod_rewrite.c^>
echo   RewriteEngine On
echo   RewriteBase /
echo   RewriteCond %%{REQUEST_FILENAME} !-f
echo   RewriteCond %%{REQUEST_FILENAME} !-d
echo   RewriteCond %%{REQUEST_URI} !^/api/
echo   RewriteRule . /index.html [L]
echo ^</IfModule^>
echo.
echo php_value upload_max_filesize 20M
echo php_value post_max_size 50M
echo php_value memory_limit 256M
) > deploy_hostinger\.htaccess
echo      OK - .htaccess creado

echo.
echo ========================================
echo ARCHIVOS LISTOS PARA HOSTINGER
echo ========================================
echo.
echo Carpeta: deploy_hostinger\
echo Archivos preparados: OK
echo.
echo SIGUIENTE PASO:
echo 1. Comprimir carpeta 'deploy_hostinger' en ZIP
echo 2. Subir a Hostinger cPanel
echo 3. Extraer en public_html
echo.
pause





