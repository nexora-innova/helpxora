# Herramientas - HelpXora

Scripts para desarrollo y publicación del plugin.

## validate_plugin.php

Comprueba que el plugin cumpla los requisitos para el directorio de plugins de GLPI.

```bash
php tools/validate_plugin.php
```

Verifica: setup.php, hook.php, plugin.xml, README.md, LICENSE, directorios, logo (pics/logo.png o helpxora.png), archivos .mo en locales/.

## po2mo.php

Compila archivos .po a .mo (traducciones) sin necesidad de msgfmt.

```bash
php tools/po2mo.php
```

Genera `locales/en_GB.mo` y `locales/es_ES.mo` a partir de los .po.

## compile_translations.sh

Alternativa con gettext (msgfmt). Ejecutar desde la raíz del plugin o desde tools/:

```bash
bash tools/compile_translations.sh
```

Requiere gettext instalado. En Windows usar `php tools/po2mo.php` en su lugar.
