@echo off
REM ================================================================
REM  VERIFICAR_BACKUP.BAT - Verificacion de integridad
REM  Proyecto: Campo Fresco
REM ================================================================
REM  Comprueba que un archivo de backup no esta corrupto
REM  comparando su hash SHA256 actual con el guardado al crearlo.
REM ================================================================

set DESTINO=D:\Backups\CampoFresco
set LOG=%DESTINO%\backup.log

echo.
echo  ================================================
echo   Campo Fresco - Verificacion de Integridad
echo  ================================================
echo.

REM Listar backups disponibles
echo Backups disponibles:
echo.
dir /b "%DESTINO%\backup_*.zip" 2>nul

if errorlevel 1 (
    echo No hay ningun backup disponible en %DESTINO%
    pause
    exit /b 1
)

echo.
set /p ZIP_NOMBRE=Nombre del ZIP a verificar (ej: backup_2026-04-20_23-00.zip):

if not exist "%DESTINO%\%ZIP_NOMBRE%" (
    echo ERROR: El archivo no existe.
    pause
    exit /b 1
)

REM Buscar el .sha256 correspondiente
set HASH_NOMBRE=%ZIP_NOMBRE:.zip=.sha256%

if not exist "%DESTINO%\%HASH_NOMBRE%" (
    echo.
    echo AVISO: No se encontro el archivo de hash para este backup.
    echo No es posible verificar la integridad.
    pause
    exit /b 1
)

echo.
echo Calculando hash del archivo...

REM Calcular el hash actual del ZIP
for /f "skip=1 tokens=*" %%H in ('certutil -hashfile "%DESTINO%\%ZIP_NOMBRE%" SHA256 2^>nul') do (
    if not defined HASH_ACTUAL set HASH_ACTUAL=%%H
)

REM Leer el hash guardado (segunda linea del .sha256)
for /f "skip=1 tokens=*" %%H in ("%DESTINO%\%HASH_NOMBRE%") do (
    if not defined HASH_GUARDADO set HASH_GUARDADO=%%H
)

echo.
echo Hash calculado ahora:  %HASH_ACTUAL%
echo Hash guardado al crear: %HASH_GUARDADO%
echo.

REM Comparar los dos hashes
if /i "%HASH_ACTUAL%"=="%HASH_GUARDADO%" (
    echo  [OK] El backup es INTEGRO. Los hashes coinciden.
    echo  El archivo no ha sido modificado ni corrompido.
    echo [%date% %time%] VERIFICACION OK - %ZIP_NOMBRE% integro >> "%LOG%"
) else (
    echo  [ERROR] Los hashes NO coinciden.
    echo  El backup puede estar CORRUPTO o haber sido modificado.
    echo  Recomendacion: usar otro backup mas reciente.
    echo [%date% %time%] VERIFICACION ERROR - %ZIP_NOMBRE% posiblemente corrupto >> "%LOG%"
)

echo.
pause
