#!/bin/bash

# =============================================
# CAMPO FRESCO - Script de Backup (Linux)
# Servidor: Nginx + PHP + MariaDB
# Autor: Alex Valls - Proyecto Intermodular 2025-2026
# =============================================

FECHA=$(date +"%Y-%m-%d_%H-%M")
WEB="/var/www/campofreso"
DB_NOMBRE="campofrescodb"
DB_USUARIO="campofreso"
DB_PASS="CampoFreso2026!"
DESTINO="/home/valle/backup/backups"
LOGS="/home/valle/backup/logs"
TAR="${DESTINO}/backup_${FECHA}.tar.gz"
SQL="${DESTINO}/backup_${FECHA}.sql"
HASH="${DESTINO}/backup_${FECHA}.sha256"
LOG="${LOGS}/backup.log"

mkdir -p "${DESTINO}" "${LOGS}"

echo "[${FECHA}] ===== INICIO BACKUP =====" >> "${LOG}"
echo "[${FECHA}] Web: ${WEB}" >> "${LOG}"
echo "[${FECHA}] Destino: ${DESTINO}" >> "${LOG}"

# 1. Comprimir carpeta del proyecto
if tar -czf "${TAR}" -C /var/www campofreso 2>/dev/null; then
    TAMANO=$(du -sh "${TAR}" | cut -f1)
    echo "[${FECHA}] OK - Proyecto comprimido en tar.gz (${TAMANO})" >> "${LOG}"
else
    echo "[${FECHA}] ERROR - No se pudo comprimir el proyecto" >> "${LOG}"
    exit 1
fi

# 2. Exportar base de datos con mysqldump
if mysqldump -u"${DB_USUARIO}" -p"${DB_PASS}" "${DB_NOMBRE}" > "${SQL}" 2>/dev/null; then
    LINEAS=$(wc -l < "${SQL}")
    echo "[${FECHA}] OK - Base de datos exportada (${LINEAS} lineas SQL)" >> "${LOG}"
else
    echo "[${FECHA}] AVISO - Error al exportar base de datos" >> "${LOG}"
fi

# 3. Generar hash SHA256 para verificacion de integridad
sha256sum "${TAR}" > "${HASH}"
HASH_VAL=$(awk '{print $1}' "${HASH}")
echo "[${FECHA}] OK - Hash SHA256 generado: ${HASH_VAL:0:20}..." >> "${LOG}"

# 4. Eliminar backups de mas de 7 dias automaticamente
find "${DESTINO}" -name "backup_*.tar.gz" -mtime +7 -delete 2>/dev/null
find "${DESTINO}" -name "backup_*.sql"    -mtime +7 -delete 2>/dev/null
find "${DESTINO}" -name "backup_*.sha256" -mtime +7 -delete 2>/dev/null
echo "[${FECHA}] OK - Limpieza de copias antiguas (mas de 7 dias) completada" >> "${LOG}"
echo "[${FECHA}] ===== FIN BACKUP (EXITOSO) =====" >> "${LOG}"
echo "" >> "${LOG}"

exit 0
