# Changelog

Todos los cambios notables del plugin HelpXora se documentan en este archivo.

## [1.0.2] - 2026-03-21

### Añadido

- `PluginHelpxoraRequirementValidator`: clase centralizada de validación con soporte de anti-gibberish (palabras mínimas, vocales, repeticiones y patrones de teclado), regex personalizada, límites de caracteres y archivos.
- Requerimientos: campos `attachments_mode` y `description_mode` (0 = ninguno, 1 = obligatorio, 2 = opcional) que reemplazan las columnas legacy `allow_files`, `description_required` y `require_all_attachments`; migración automática al actualizar desde cualquier versión anterior.
- Requerimientos: campos `max_files`, `allowed_extensions`, `min_chars`, `max_chars`, `validation_regex` y `restrict_gibberish`; validación `pre_item_add`/`pre_item_update` que bloquea configuraciones sin canal de entrada activo.
- Chat: cola de archivos acumulativa (los archivos se añaden uno a uno sin reemplazar los anteriores), contador de caracteres en vivo, botón de envío con color dinámico según estado de validación, mensajes de estado unificados en el pie del compositor.
- Chat: los requerimientos con `attachments_mode = 0` y `description_mode = 0` se filtran del menú.
- Configuración: campo `gibberish_error_message` para personalizar el mensaje de texto sin sentido.
- Hooks `pre_item_add`/`pre_item_update` para `Ticket` y `PluginHelpxoraRequerimiento`.
- Localización: archivos `.mo` compilados para todas las variantes de español de GLPI (`es_ES`, `es_AR`, `es_MX`, `es_CO`, `es_CL`, `es_EC`, `es_VE`, `es_419`).

### Cambiado

- Formulario de requerimiento: las secciones de adjuntos y descripción se ocultan automáticamente cuando su política está en «ninguno»; políticas con tres opciones traducibles.
- Consultas: campo «Pregunta» cambiado a `<input type="text">` (sin editor enriquecido); columna `question` migrada a `TEXT` para aceptar más contenido; listas y menú del chat aplican `strip_tags`.
- Configuración: eliminada la pestaña duplicada «General» que provocaba conflictos de IDs con TinyMCE en la vista «Todo».
- Página de configuración: listener `focusin` con `stopImmediatePropagation` y `z-index` elevado para diálogos TinyMCE, resolviendo el foco atrapado dentro de modales Bootstrap.

### Corregido

- Migración desde esquemas sin columna `require_all_attachments`: el backfill detecta qué columnas existen antes de construir el `UPDATE`, eliminando el error `Unknown column`.
- TinyMCE: IDs de editor con sufijo aleatorio para evitar colisiones al renderizar el mismo formulario en múltiples pestañas simultáneamente.

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
