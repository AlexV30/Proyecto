#!/bin/bash

# =============================================
# CAMPO FRESCO - Verificacion de Integridad
# Autor: Alex Valls - Proyecto Intermodular 2025-2026
# =============================================

DESTINO="/home/valle/backup/backups"

echo ""
echo "=============================="
echo " Verificacion de Backups      "
echo "=============================="
echo ""

ERRORES=0
TOTAL=0

for HASH_FILE in "${DESTINO}"/*.sha256; do
    [ -f "${HASH_FILE}" ] || continue
    TOTAL=$((TOTAL+1))
    NOMBRE=$(basename "${HASH_FILE}" .sha256)
    TAR="${DESTINO}/${NOMBRE}.tar.gz"
    if [ ! -f "${TAR}" ]; then
        echo "  [FALTA]  ${NOMBRE} - archivo .tar.gz no encontrado"
        ERRORES=$((ERRORES+1))
    elif sha256sum --check "${HASH_FILE}" > /dev/null 2>&1; then
        TAMANO=$(du -sh "${TAR}" | cut -f1)
        echo "  [OK]     ${NOMBRE}  (${TAMANO})"
    else
        echo "  [ERROR]  ${NOMBRE} - Hash NO coincide (archivo corrupto?)"
        ERRORES=$((ERRORES+1))
    fi
done

echo ""
if [ ${TOTAL} -eq 0 ]; then
    echo "No hay backups para verificar."
elif [ ${ERRORES} -eq 0 ]; then
    echo "Resultado: ${TOTAL}/${TOTAL} backups integros. Todo correcto."
else
    echo "Resultado: ${ERRORES} errores de ${TOTAL} backups verificados."
fi
echo ""
