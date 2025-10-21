@echo off
REM File: deploy_to_server.bat
REM Script de despliegue automático al servidor de producción (Windows)
REM Exists to upload all necessary files to production server via WinSCP
REM Related files: build/, api/, CORS_PRODUCCION_ACTUALIZADO.md

echo ====================================
echo DESPLIEGUE A PRODUCCION
echo ====================================
echo.

REM ====================================
REM CONFIGURACION DEL SERVIDOR
REM ====================================
set SERVER_IP=148.230.72.12
set SERVER_USER=tu_usuario_ftp
set SERVER_PASSWORD=tu_password_ftp
set SERVER_PATH=/public_html/kiosco
set FTP_PORT=21

echo Configuracion:
echo   Servidor: %SERVER_IP%
echo   Usuario: %SERVER_USER%
echo   Ruta: %SERVER_PATH%
echo.

REM ====================================
REM OPCION 1: USAR WINSCP (RECOMENDADO)
REM ====================================
echo [OPCION 1] Usando WinSCP (si esta instalado)
echo.

REM Crear script temporal de WinSCP
echo option batch abort > winscp_script.txt
echo option confirm off >> winscp_script.txt
echo open ftp://%SERVER_USER%:%SERVER_PASSWORD%@%SERVER_IP%:%FTP_PORT% >> winscp_script.txt
echo cd %SERVER_PATH% >> winscp_script.txt
echo synchronize remote ./api %SERVER_PATH%/api -delete -mirror >> winscp_script.txt
echo synchronize remote ./build %SERVER_PATH% -delete -mirror >> winscp_script.txt
echo exit >> winscp_script.txt

REM Ejecutar WinSCP (si esta en PATH)
"C:\Program Files (x86)\WinSCP\WinSCP.com" /script=winscp_script.txt

if %errorlevel% equ 0 (
    echo.
    echo ====================================
    echo DESPLIEGUE COMPLETADO
    echo ====================================
    echo.
    echo URL: http://%SERVER_IP%/kiosco
    echo.
    echo Proximos pasos:
    echo 1. Probar login en: http://%SERVER_IP%/kiosco
    echo 2. Usuario: admin
    echo 3. Verificar dynamic pricing
    echo.
) else (
    echo.
    echo ERROR: WinSCP no esta instalado o fallo la conexion
    echo.
    echo ====================================
    echo OPCION 2: SUBIR MANUALMENTE
    echo ====================================
    echo.
    echo 1. Abre FileZilla o tu cliente FTP
    echo 2. Conecta a: %SERVER_IP%:%FTP_PORT%
    echo 3. Usuario: %SERVER_USER%
    echo 4. Sube carpeta ./api a %SERVER_PATH%/api
    echo 5. Sube contenido de ./build a %SERVER_PATH%
    echo.
)

REM Limpiar script temporal
if exist winscp_script.txt del winscp_script.txt

pause

