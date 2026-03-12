#!/bin/bash

# Script para compilar traducciones del plugin HelpXora
# Requiere: gettext (msgfmt) o usar alternativamente: php tools/po2mo.php

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
LOCALES_DIR="$PLUGIN_DIR/locales"

echo "Compilando traducciones de HelpXora..."
echo ""

if ! command -v msgfmt &> /dev/null; then
    echo "msgfmt no encontrado. Ejecutar en su lugar: php tools/po2mo.php"
    exit 1
fi

if [ -f "$LOCALES_DIR/en_GB.po" ]; then
    echo "Compilando en_GB.po..."
    msgfmt "$LOCALES_DIR/en_GB.po" -o "$LOCALES_DIR/en_GB.mo" && echo "  en_GB.mo OK" || exit 1
fi

if [ -f "$LOCALES_DIR/es_ES.po" ]; then
    echo "Compilando es_ES.po..."
    msgfmt "$LOCALES_DIR/es_ES.po" -o "$LOCALES_DIR/es_ES.mo" && echo "  es_ES.mo OK" || exit 1
fi

echo ""
echo "Compilación completada."
ls -la "$LOCALES_DIR"/*.mo 2>/dev/null || true
