@echo off
REM ================================================================
REM  BACKUP.BAT - Sistema de Copias de Seguridad
REM  Proyecto: Campo Fresco - Tienda Ecologica
REM  Proyecto Intermodular 2025-2026
REM ================================================================
REM  Que hace este script:
REM   1. Comprime el proyecto en un ZIP con fecha y hora
REM   2. Exporta la base de datos con mysqldump
REM   3. Genera un hash SHA256 para verificar integridad
REM   4. Registra todo en un archivo de log
REM   5. Elimina automaticamente copias con mas de 7 dias
REM ================================================================

REM ---- CONFIGURACION (cambiar segun el equipo) ----
set PROYECTO=C:\xampp\htdocs\CampoFresco
set DESTINO=D:\Backups\CampoFresco
set MYSQL_BIN=C:\xampp\mysql\bin
set DB_NOMBRE=campofrescodb
set DB_USUARIO=root
set DB_PASSWORD=
set DIAS_RETENER=7

REM ---- Generar timestamp para nombre de archivo ----
for /f "tokens=2 delims==" %%I in ('wmic os get localdatetime /value') do set DT=%%I
set FECHA=%DT:~0,4%-%DT:~4,2%-%DT:~6,2%
set HORA=%DT:~8,2%-%DT:~10,2%
set TIMESTAMP=%FECHA%_%HORA%

set ZIP=%DESTINO%\backup_%TIMESTAMP%.zip
set SQL=%DESTINO%\db_%TIMESTAMP%.sql
set HASH=%DESTINO%\backup_%TIMESTAMP%.sha256
set LOG=%DESTINO%\backup.log

REM ---- Crear carpeta destino si no existe ----
if not exist "%DESTINO%" mkdir "%DESTINO%"

echo.
echo  ================================================
echo   Campo Fresco - Copia de Seguridad Automatica
echo   Fecha: %FECHA%  Hora: %HORA%
echo  ================================================

REM ---- Escribir cabecera en el log ----
echo. >> "%LOG%"
echo [%TIMESTAMP%] ===== INICIO BACKUP ===== >> "%LOG%"
echo [%TIMESTAMP%] Proyecto: %PROYECTO% >> "%LOG%"
echo [%TIMESTAMP%] Destino:  %DESTINO% >> "%LOG%"

REM ================================================================
REM  PASO 1: Comprimir el proyecto en ZIP
REM ================================================================
echo.
echo [1/4] Comprimiendo proyecto...

if not exist "%PROYECTO%" (
    echo       ERROR: La carpeta del proyecto no existe: %PROYECTO%
    echo [%TIMESTAMP%] ERROR - La carpeta del proyecto no existe: %PROYECTO% >> "%LOG%"
    goto :ERROR
)

powershell -command "Compress-Archive -Path '%PROYECTO%\*' -DestinationPath '%ZIP%' -Force" >nul 2>&1

if %errorlevel% neq 0 (
    echo       ERROR: No se pudo crear el ZIP.
    echo [%TIMESTAMP%] ERROR - Fallo al comprimir el proyecto >> "%LOG%"
    goto :ERROR
)

echo       OK - ZIP creado: backup_%TIMESTAMP%.zip
echo [%TIMESTAMP%] OK - ZIP creado correctamente >> "%LOG%"

REM ================================================================
REM  PASO 2: Exportar base de datos MySQL (si XAMPP esta activo)
REM ================================================================
echo.
echo [2/4] Exportando base de datos...

"%MYSQL_BIN%\mysqldump.exe" -u%DB_USUARIO% %DB_NOMBRE% > "%SQL%" 2>nul

if %errorlevel% equ 0 (
    echo       OK - Base de datos exportada: db_%TIMESTAMP%.sql
    echo [%TIMESTAMP%] OK - mysqldump exitoso: db_%TIMESTAMP%.sql >> "%LOG%"
) else (
    echo       AVISO: No se exporto la BD (XAMPP inactivo o BD inexistente)
    echo       (Normal si el proyecto solo usa localStorage)
    echo [%TIMESTAMP%] AVISO - mysqldump fallo (puede que no haya BD activa) >> "%LOG%"
    del "%SQL%" 2>nul
)

REM ================================================================
REM  PASO 3: Verificacion de integridad con hash SHA256
REM ================================================================
echo.
echo [3/4] Generando hash de verificacion (SHA256)...

certutil -hashfile "%ZIP%" SHA256 > "%HASH%" 2>nul

if %errorlevel% equ 0 (
    REM Extraer solo el hash (segunda linea del output de certutil)
    for /f "skip=1 tokens=*" %%H in ('certutil -hashfile "%ZIP%" SHA256 2^>nul') do (
        if not defined HASHVALUE set HASHVALUE=%%H
    )
    echo       OK - Hash SHA256: %HASHVALUE%
    echo       Guardado en: backup_%TIMESTAMP%.sha256
    echo [%TIMESTAMP%] OK - Hash SHA256 generado: %HASHVALUE% >> "%LOG%"
) else (
    echo       AVISO: No se pudo generar el hash (certutil no disponible)
    echo [%TIMESTAMP%] AVISO - No se pudo generar hash SHA256 >> "%LOG%"
)

REM ================================================================
REM  PASO 4: Eliminar backups de mas de DIAS_RETENER dias
REM ================================================================
echo.
echo [4/4] Limpiando copias antiguas (mas de %DIAS_RETENER% dias)...

set ELIMINADOS=0
forfiles /p "%DESTINO%" /m "backup_*.zip" /d -%DIAS_RETENER% /c "cmd /c del @path && set /a ELIMINADOS+=1" 2>nul
forfiles /p "%DESTINO%" /m "db_*.sql" /d -%DIAS_RETENER% /c "cmd /c del @path" 2>nul
forfiles /p "%DESTINO%" /m "backup_*.sha256" /d -%DIAS_RETENER% /c "cmd /c del @path" 2>nul

echo       OK - Limpieza completada.
echo [%TIMESTAMP%] OK - Limpieza de archivos antiguos completada >> "%LOG%"

REM ================================================================
REM  RESUMEN FINAL
REM ================================================================
echo.
echo  ================================================
echo   BACKUP COMPLETADO CON EXITO
echo   Archivo: backup_%TIMESTAMP%.zip
echo   Ubicacion: %DESTINO%
echo  ================================================
echo [%TIMESTAMP%] ===== FIN BACKUP (EXITOSO) ===== >> "%LOG%"
echo. >> "%LOG%"
goto :FIN

:ERROR
echo.
echo  ================================================
echo   ERROR: El backup NO se completo correctamente.
echo   Revisa el archivo de log: %LOG%
echo  ================================================
echo [%TIMESTAMP%] ===== FIN BACKUP (CON ERRORES) ===== >> "%LOG%"
echo. >> "%LOG%"

:FIN
pause
