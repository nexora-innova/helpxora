# HelpXora - Asistente virtual para GLPI

Plugin de asistente virtual (chatbot) para mesa de ayuda en GLPI. Desarrollado por **NexoraInnova**.

## Descripción

HelpXora añade un widget de chat en GLPI que permite a los usuarios:

- Consultar respuestas frecuentes (FAQ) configuradas por el administrador
- Crear requerimientos/tickets desde el asistente con adjuntos
- Personalizar mensajes, colores e iconos del asistente

## Requisitos

- GLPI >= 10.0
- PHP >= 8.1

## Instalación

1. Copiar la carpeta `helpxora` dentro de `plugins/` de tu instalación de GLPI
2. Acceder a GLPI como administrador
3. Ir a **Configuración > Plugins**
4. Buscar "HelpXora" y hacer clic en **Instalar**
5. Una vez instalado, hacer clic en **Habilitar**

## Ubicación en el menú

Tras activar el plugin:

**Configuración > HelpXora**

Desde ahí se configuran:

- **Consultas**: preguntas y respuestas frecuentes (con soporte para varias imágenes)
- **Requerimientos**: plantillas de tickets y categorías
- **Histórico de acciones**: registro de cambios de configuración

El widget del asistente aparece en las páginas de GLPI (según la configuración) y permite a los usuarios chatear, consultar respuestas y abrir requerimientos.

## Configuración

En **Configuración > HelpXora** puedes ajustar:

- Nombre y avatar del asistente
- Icono y tamaño de la burbuja flotante
- Mensajes de bienvenida, introducción, motivo de requerimiento y cierre
- Colores (burbuja, botones, envío, etc.)
- Etiqueta y color del botón de envío

## Estructura del plugin

```
helpxora/
├── ajax/           # Endpoints AJAX (chat, dropdowns)
├── css/            # Estilos del widget
├── front/          # Páginas de configuración y formularios
├── inc/            # Clases PHP (Config, Consulta, Requerimiento, Log, Chat)
├── install/        # Esquema SQL de instalación
├── js/             # JavaScript del widget
├── hook.php        # Instalación/desinstalación
├── setup.php       # Definición y hooks del plugin
├── LICENSE         # GPL v3
└── README.md       # Este archivo
```

## Licencia

Este plugin está licenciado bajo **GPL v3**. Ver archivo [LICENSE](LICENSE).

## Soporte

Para reportar errores o solicitar funcionalidades, utiliza el sistema de issues del repositorio del proyecto.
