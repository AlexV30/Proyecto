@echo off
REM ================================================================
REM  RESTAURAR.BAT - Restauracion desde backup
REM  Proyecto: Campo Fresco
REM ================================================================
REM  Restaura el proyecto y/o la base de datos desde un backup ZIP.
REM  IMPORTANTE: Cerrar XAMPP antes de ejecutar.
REM ================================================================

set DESTINO=D:\Backups\CampoFresco
set PROYECTO=C:\xampp\htdocs\CampoFresco
set MYSQL_BIN=C:\xampp\mysql\bin
set DB_NOMBRE=campofrescodb
set DB_USUARIO=root
set LOG=%DESTINO%\restaurar.log

echo.
echo  ================================================
echo   Campo Fresco - RESTAURACION DE BACKUP
echo  ================================================
echo.
echo  ATENCION: Antes de continuar asegurate de que
echo  XAMPP esta CERRADO para evitar conflictos.
echo.
pause

REM ---- Listar backups disponibles ----
echo Backups disponibles en %DESTINO%:
echo.
dir /b /o-d "%DESTINO%\backup_*.zip" 2>nul

if errorlevel 1 (
    echo No hay ningun backup disponible.
    echo Ejecuta primero backup.bat para crear uno.
    pause
    exit /b 1
)

echo.
set /p ZIP_ELEGIDO=Nombre del backup a restaurar (ej: backup_2026-04-20_23-00.zip):

if not exist "%DESTINO%\%ZIP_ELEGIDO%" (
    echo.
    echo ERROR: El archivo "%ZIP_ELEGIDO%" no existe.
    pause
    exit /b 1
)

REM ---- Verificar integridad antes de restaurar ----
echo.
echo Verificando integridad del backup...
set HASH_NOMBRE=%ZIP_ELEGIDO:.zip=.sha256%

if exist "%DESTINO%\%HASH_NOMBRE%" (
    set HASH_ACTUAL=
    set HASH_GUARDADO=
    for /f "skip=1 tokens=*" %%H in ('certutil -hashfile "%DESTINO%\%ZIP_ELEGIDO%" SHA256 2^>nul') do (
        if not defined HASH_ACTUAL set HASH_ACTUAL=%%H
    )
    for /f "skip=1 tokens=*" %%H in ("%DESTINO%\%HASH_NOMBRE%") do (
        if not defined HASH_GUARDADO set HASH_GUARDADO=%%H
    )
    if /i "%HASH_ACTUAL%"=="%HASH_GUARDADO%" (
        echo  [OK] Integridad verificada. El backup es valido.
    ) else (
        echo  [AVISO] El hash no coincide. El backup puede estar corrupto.
        echo  Se recomienda usar otro backup. Continuar de todas formas?
        set /p FORZAR=Continuar igualmente? (S/N):
        if /i not "%FORZAR%"=="S" (
            echo Restauracion cancelada.
            pause
            exit /b 0
        )
    )
) else (
    echo  [AVISO] No hay hash disponible para este backup. Continuando...
)

echo.
echo  Esto sobreescribira la version actual del proyecto en:
echo    %PROYECTO%
echo.
set /p CONFIRMAR=Seguro que quieres continuar? (S/N):

if /i not "%CONFIRMAR%"=="S" (
    echo Restauracion cancelada por el usuario.
    pause
    exit /b 0
)

echo [%date% %time%] ===== INICIO RESTAURACION: %ZIP_ELEGIDO% ===== >> "%LOG%"

REM ================================================================
REM  PASO 1: Eliminar version actual y extraer backup
REM ================================================================
echo.
echo [1/3] Borrando version actual del proyecto...

if exist "%PROYECTO%" (
    rd /s /q "%PROYECTO%"
    echo       OK - Carpeta antigua eliminada.
) else (
    echo       La carpeta no existia, continuando...
)

echo [2/3] Extrayendo backup...
mkdir "%PROYECTO%" 2>nul
powershell -command "Expand-Archive -Path '%DESTINO%\%ZIP_ELEGIDO%' -DestinationPath '%PROYECTO%' -Force" >nul 2>&1

if %errorlevel% equ 0 (
    echo       OK - Proyecto restaurado correctamente.
    echo [%date% %time%] OK - Proyecto restaurado desde %ZIP_ELEGIDO% >> "%LOG%"
) else (
    echo       ERROR: No se pudo extraer el ZIP.
    echo [%date% %time%] ERROR - Fallo al extraer %ZIP_ELEGIDO% >> "%LOG%"
    pause
    exit /b 1
)

REM ================================================================
REM  PASO 2: Restaurar base de datos (si existe el .sql)
REM ================================================================
set SQL_NOMBRE=%ZIP_ELEGIDO:backup_=db_%
set SQL_NOMBRE=%SQL_NOMBRE:.zip=.sql%

echo [3/3] Restaurando base de datos...
if exist "%DESTINO%\%SQL_NOMBRE%" (
    "%MYSQL_BIN%\mysql.exe" -u%DB_USUARIO% %DB_NOMBRE% < "%DESTINO%\%SQL_NOMBRE%" 2>nul
    if %errorlevel% equ 0 (
        echo       OK - Base de datos restaurada desde %SQL_NOMBRE%
        echo [%date% %time%] OK - BD restaurada desde %SQL_NOMBRE% >> "%LOG%"
    ) else (
        echo       ERROR: No se pudo restaurar la BD. Comprueba que XAMPP este activo.
        echo [%date% %time%] ERROR - Fallo al restaurar BD >> "%LOG%"
    )
) else (
    echo       INFO: No hay archivo SQL para este backup.
    echo       (Normal si el proyecto usa solo localStorage)
    echo [%date% %time%] INFO - No habia archivo SQL, proyecto solo usa localStorage >> "%LOG%"
)

echo.
echo  ================================================
echo   RESTAURACION COMPLETADA
echo.
echo   Arranca XAMPP y abre en el navegador:
echo   http://localhost/CampoFresco/
echo  ================================================
echo [%date% %time%] ===== FIN RESTAURACION (EXITOSO) ===== >> "%LOG%"
echo. >> "%LOG%"

pause
