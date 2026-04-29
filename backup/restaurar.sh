#!/bin/bash

# =============================================
# CAMPO FRESCO - Script de Restauracion (Linux)
# Autor: Alex Valls - Proyecto Intermodular 2025-2026
# =============================================

DESTINO="/home/valle/backup/backups"
WEB_DIR="/var/www/campofreso"
DB_NOMBRE="campofrescodb"
DB_USUARIO="campofreso"
DB_PASS="CampoFreso2026!"
LOG="/home/valle/backup/logs/restaurar.log"
FECHA=$(date +"%Y-%m-%d_%H-%M")

mkdir -p "$(dirname ${LOG})"

echo ""
echo "========================================"
echo " CAMPO FRESCO - Sistema de Restauracion "
echo "========================================"
echo ""
echo "Backups disponibles:"
echo ""

if ! ls "${DESTINO}"/*.tar.gz > /dev/null 2>&1; then
    echo "  No hay backups disponibles en ${DESTINO}"
    exit 1
fi

ls -1 "${DESTINO}"/*.tar.gz | while read f; do
    FECHA_F=$(basename "${f}" .tar.gz | sed 's/backup_//')
    TAMANO=$(du -sh "${f}" 2>/dev/null | cut -f1)
    echo "  $(basename ${f} .tar.gz)  [${TAMANO}]"
done

echo ""
read -p "Nombre del backup a restaurar (sin .tar.gz): " NOMBRE

TAR="${DESTINO}/${NOMBRE}.tar.gz"
SQL="${DESTINO}/${NOMBRE}.sql"
HASH="${DESTINO}/${NOMBRE}.sha256"

if [ ! -f "${TAR}" ]; then
    echo ""
    echo "ERROR: No se encontro el archivo ${TAR}"
    exit 1
fi

# Verificar integridad SHA256
if [ -f "${HASH}" ]; then
    echo ""
    echo "Verificando integridad (SHA256)..."
    if sha256sum --check "${HASH}" > /dev/null 2>&1; then
        echo "OK - El backup es integro, los hashes coinciden."
    else
        echo "AVISO: Los hashes NO coinciden. El backup puede estar corrupto."
        read -p "¿Restaurar igualmente? (s/N): " FORZAR
        if [ "${FORZAR,,}" != "s" ]; then
            echo "Restauracion cancelada."
            exit 1
        fi
    fi
fi

echo ""
read -p "¿Confirmas la restauracion desde '${NOMBRE}'? Esto reemplazara la web actual. (s/N): " CONFIRM
if [ "${CONFIRM,,}" != "s" ]; then
    echo "Restauracion cancelada."
    exit 0
fi

echo "[${FECHA}] ===== INICIO RESTAURACION: ${NOMBRE} =====" >> "${LOG}"

# Restaurar proyecto web
echo ""
echo "Restaurando archivos web..."
rm -rf "${WEB_DIR}"
if tar -xzf "${TAR}" -C /var/www 2>/dev/null; then
    chown -R www-data:www-data "${WEB_DIR}" 2>/dev/null
    echo "OK - Proyecto restaurado en ${WEB_DIR}"
    echo "[${FECHA}] OK - Proyecto restaurado desde ${NOMBRE}.tar.gz" >> "${LOG}"
else
    echo "ERROR - No se pudo restaurar el proyecto"
    echo "[${FECHA}] ERROR - No se pudo restaurar el proyecto" >> "${LOG}"
    exit 1
fi

# Restaurar base de datos
if [ -f "${SQL}" ]; then
    echo "Restaurando base de datos..."
    if mysql -u"${DB_USUARIO}" -p"${DB_PASS}" "${DB_NOMBRE}" < "${SQL}" 2>/dev/null; then
        echo "OK - Base de datos restaurada correctamente"
        echo "[${FECHA}] OK - Base de datos restaurada" >> "${LOG}"
    else
        echo "AVISO - No se pudo restaurar la base de datos automaticamente"
        echo "[${FECHA}] AVISO - Error al restaurar BD" >> "${LOG}"
    fi
else
    echo "INFO - No habia archivo SQL en este backup"
    echo "[${FECHA}] INFO - No habia archivo SQL" >> "${LOG}"
fi

# Reiniciar nginx para aplicar cambios
sudo systemctl restart nginx 2>/dev/null && echo "OK - Nginx reiniciado"

echo "[${FECHA}] ===== FIN RESTAURACION (EXITOSO) =====" >> "${LOG}"
echo ""
echo "========================================"
echo " Restauracion completada con exito!"
echo " Comprueba: http://192.168.0.20:8082/Fase6/"
echo "========================================"
