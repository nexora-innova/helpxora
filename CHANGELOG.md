# Changelog

Todos los cambios notables del plugin HelpXora se documentan en este archivo.

## [1.0.1] - 2026-03-08

### Corregido

- TinyMCE no se reiniciaba al reabrir los modales "Añadir/Editar Requerimiento" y "Añadir/Editar Consulta": se destruye la instancia previa (`tinymce.get(id).remove()`) antes de reinyectar el contenido del modal.
- El widget de chat no reseteaba su estado al cerrar (área de input y vista previa de archivos seguían visibles al reabrir): `closeChat()` ahora limpia estado y oculta el input-area.
- Error en consola `hotkeys is not defined` en la página de configuración del plugin: se carga `fuzzy.js` en el head mediante el hook `ADD_HEADER_TAG` para que exista antes del script del layout de GLPI.

## [1.0.0] - 2025-01-01

### Añadido

- Versión inicial del plugin HelpXora (asistente virtual para mesa de ayuda en GLPI).
- Widget de chat con consultas frecuentes (FAQ) y múltiples imágenes por consulta.
- Creación de requerimientos/tickets desde el asistente con adjuntos.
- Configuración de mensajes (bienvenida, introducción, motivo, cierre, error de archivo).
- Personalización de colores, iconos, avatar y botón de envío.
- Pestañas: Consultas, Requerimientos, Histórico de acciones.
- Internacionalización (en_GB, es_ES).
- Publicación: plugin.xml, LICENSE GPL v3, README, index.php de seguridad, locales, herramientas de validación y compilación de traducciones.
