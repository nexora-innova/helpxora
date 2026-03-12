<?php

use DBConnection;
use Migration;

function plugin_helpxora_install() {
   global $DB;

   $default_charset   = DBConnection::getDefaultCharset();
   $default_collation = DBConnection::getDefaultCollation();
   $migration = new Migration(PLUGIN_HELPXORA_VERSION);

   $table_consultas = 'glpi_plugin_helpxora_consultas';
   if (!$DB->tableExists($table_consultas)) {
      $migration->addPreQuery(
         "CREATE TABLE `$table_consultas` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `question` VARCHAR(255) COLLATE $default_collation NOT NULL,
            `answer` TEXT COLLATE $default_collation NOT NULL,
            `images` TEXT COLLATE $default_collation DEFAULT NULL,
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `date_creation` TIMESTAMP NULL DEFAULT NULL,
            `date_mod` TIMESTAMP NULL DEFAULT NULL,
            PRIMARY KEY (`id`)
         ) ENGINE=InnoDB DEFAULT CHARSET=$default_charset COLLATE=$default_collation",
         "Create table $table_consultas"
      );
   }

   $table_requerimientos = 'glpi_plugin_helpxora_requerimientos';
   if (!$DB->tableExists($table_requerimientos)) {
      $migration->addPreQuery(
         "CREATE TABLE `$table_requerimientos` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `question` VARCHAR(255) COLLATE $default_collation NOT NULL,
            `type` INT(11) NOT NULL DEFAULT 1,
            `itilcategories_id` INT(11) NOT NULL DEFAULT 0,
            `requesttypes_id` INT(11) NOT NULL DEFAULT 0,
            `groups_id` INT(11) NOT NULL DEFAULT 0,
            `custom_response` TEXT COLLATE $default_collation DEFAULT NULL,
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `date_creation` TIMESTAMP NULL DEFAULT NULL,
            `date_mod` TIMESTAMP NULL DEFAULT NULL,
            PRIMARY KEY (`id`)
         ) ENGINE=InnoDB DEFAULT CHARSET=$default_charset COLLATE=$default_collation",
         "Create table $table_requerimientos"
      );
   }

   $table_configs = 'glpi_plugin_helpxora_configs';
   if (!$DB->tableExists($table_configs)) {
      $migration->addPreQuery(
         "CREATE TABLE `$table_configs` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `helpxora_name` VARCHAR(255) COLLATE $default_collation NOT NULL DEFAULT 'Asistente Virtual',
            `welcome_message` TEXT COLLATE $default_collation NOT NULL,
            `intro_message` TEXT COLLATE $default_collation NOT NULL,
            `close_message` TEXT COLLATE $default_collation NOT NULL,
            `reason_message` TEXT COLLATE $default_collation DEFAULT NULL,
            `avatar` VARCHAR(255) COLLATE $default_collation DEFAULT NULL,
            `upload_error_message` TEXT COLLATE $default_collation DEFAULT NULL,
            `bubble_icon` VARCHAR(255) COLLATE $default_collation DEFAULT '💬',
            `bubble_size` INT(11) DEFAULT 60,
            `color_button_float` VARCHAR(7) DEFAULT '#007bff',
            `color_header` VARCHAR(7) DEFAULT '#007bff',
            `color_user_bubble` VARCHAR(7) DEFAULT '#007bff',
            `color_bot_buttons` VARCHAR(7) DEFAULT '#007bff',
            `color_hover` VARCHAR(7) DEFAULT '#0056b3',
            `color_send_button` VARCHAR(7) DEFAULT '#ffffff',
            `send_button_label` VARCHAR(50) DEFAULT '➢',
            `color_send_button_bg` VARCHAR(7) DEFAULT '#28a745',
            `date_creation` TIMESTAMP NULL DEFAULT NULL,
            `date_mod` TIMESTAMP NULL DEFAULT NULL,
            PRIMARY KEY (`id`)
         ) ENGINE=InnoDB DEFAULT CHARSET=$default_charset COLLATE=$default_collation",
         "Create table $table_configs"
      );
      $migration->addPreQuery(
         "INSERT IGNORE INTO `$table_configs` (`id`, `helpxora_name`, `welcome_message`, `intro_message`, `close_message`, `reason_message`, `upload_error_message`) VALUES
         (1, 'Asistente Virtual', 'Hola 👋<br>Soy el asistente virtual de la mesa de ayuda.', 'Seleccione una opción:', 'Gracias por contactarnos. Su requerimiento ha sido registrado.', '¿Cuál es el motivo de tu requerimiento?', 'Error al adjuntar archivo. Verifica que el formato sea válido y el peso no exceda el límite del sistema.')",
         "Insert default config"
      );
   }

   $table_logs = 'glpi_plugin_helpxora_logs';
   if (!$DB->tableExists($table_logs)) {
      $migration->addPreQuery(
         "CREATE TABLE `$table_logs` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `users_id` INT(11) NOT NULL DEFAULT 0,
            `date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `action` VARCHAR(50) COLLATE $default_collation NOT NULL,
            `itemtype` VARCHAR(100) COLLATE $default_collation NOT NULL,
            `items_id` INT(11) NOT NULL DEFAULT 0,
            `field` VARCHAR(100) COLLATE $default_collation DEFAULT NULL,
            `old_value` TEXT COLLATE $default_collation DEFAULT NULL,
            `new_value` TEXT COLLATE $default_collation DEFAULT NULL,
            PRIMARY KEY (`id`)
         ) ENGINE=InnoDB DEFAULT CHARSET=$default_charset COLLATE=$default_collation",
         "Create table $table_logs"
      );
   }

   if ($DB->tableExists($table_configs)) {
      $migration->addField($table_configs, 'avatar', 'string', ['null' => true, 'after' => 'close_message']);
      $migration->addField($table_configs, 'upload_error_message', 'text', ['null' => true, 'after' => 'avatar']);
      $migration->addField($table_configs, 'color_button_float', 'string', ['value' => '#007bff']);
      $migration->addField($table_configs, 'color_header', 'string', ['value' => '#007bff']);
      $migration->addField($table_configs, 'color_user_bubble', 'string', ['value' => '#007bff']);
      $migration->addField($table_configs, 'color_bot_buttons', 'string', ['value' => '#007bff']);
      $migration->addField($table_configs, 'color_hover', 'string', ['value' => '#0056b3']);
      $migration->addField($table_configs, 'color_send_button', 'string', ['value' => '#ffffff']);
      $migration->addField($table_configs, 'send_button_label', 'string', ['value' => '➢', 'after' => 'color_send_button']);
      $migration->addField($table_configs, 'color_send_button_bg', 'string', ['value' => '#28a745', 'after' => 'send_button_label']);
      $migration->addField($table_configs, 'reason_message', 'text', ['null' => true]);
      $migration->addField($table_configs, 'bubble_icon', 'string', ['value' => '💬', 'after' => 'reason_message']);
      $migration->addField($table_configs, 'bubble_size', 'integer', ['value' => 60, 'after' => 'bubble_icon']);
      $migration->dropField($table_configs, 'show_on_login');
   }

   if ($DB->tableExists($table_consultas)) {
      if ($DB->fieldExists($table_consultas, 'image') && !$DB->fieldExists($table_consultas, 'images')) {
         $migration->changeField($table_consultas, 'image', 'images', 'text', []);
      } elseif (!$DB->fieldExists($table_consultas, 'images')) {
         $migration->addField($table_consultas, 'images', 'text', ['null' => true]);
      }
   }

   if ($DB->tableExists($table_requerimientos)) {
      $migration->addField($table_requerimientos, 'custom_response', 'text', ['null' => true, 'after' => 'itilcategories_id']);
      $migration->addField($table_requerimientos, 'requesttypes_id', 'integer', ['value' => 0, 'after' => 'custom_response']);
      $migration->addField($table_requerimientos, 'groups_id', 'integer', ['value' => 0, 'after' => 'requesttypes_id']);
   }

   $migration->executeMigration();

   return true;
}

function plugin_helpxora_uninstall() {
   global $DB;

   $tables = [
      'glpi_plugin_helpxora_consultas',
      'glpi_plugin_helpxora_requerimientos',
      'glpi_plugin_helpxora_logs',
      'glpi_plugin_helpxora_configs'
   ];

   foreach ($tables as $table) {
      if ($DB->tableExists($table)) {
         $DB->queryOrDie("DROP TABLE `$table`", "Error dropping table: $table");
      }
   }

   return true;
}
