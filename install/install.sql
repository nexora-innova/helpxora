CREATE TABLE IF NOT EXISTS `glpi_plugin_helpxora_consultas` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `question` TEXT COLLATE utf8mb4_unicode_ci NOT NULL,
  `answer` TEXT COLLATE utf8mb4_unicode_ci NOT NULL,
  `images` TEXT COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `date_creation` TIMESTAMP NULL DEFAULT NULL,
  `date_mod` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `glpi_plugin_helpxora_requerimientos` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `question` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` INT(11) NOT NULL DEFAULT 1 COMMENT '1: Incident, 2: Request',
  `itilcategories_id` INT(11) NOT NULL DEFAULT 0,
  `requesttypes_id` INT(11) NOT NULL DEFAULT 0,
  `groups_id` INT(11) NOT NULL DEFAULT 0,
  `custom_response` TEXT COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attachments_mode` TINYINT NOT NULL DEFAULT 0 COMMENT '0 none, 1 mandatory exact max, 2 optional',
  `max_files` INT(11) NOT NULL DEFAULT 1,
  `allowed_extensions` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `min_chars` INT(11) NOT NULL DEFAULT 10,
  `max_chars` INT(11) NOT NULL DEFAULT 500,
  `validation_regex` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `restrict_gibberish` TINYINT(1) NOT NULL DEFAULT 0,
  `description_mode` TINYINT NOT NULL DEFAULT 1 COMMENT '0 none, 1 mandatory, 2 optional',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `date_creation` TIMESTAMP NULL DEFAULT NULL,
  `date_mod` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `glpi_plugin_helpxora_configs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `helpxora_name` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Asistente Virtual',
  `welcome_message` TEXT COLLATE utf8mb4_unicode_ci NOT NULL,
  `intro_message` TEXT COLLATE utf8mb4_unicode_ci NOT NULL,
  `close_message` TEXT COLLATE utf8mb4_unicode_ci NOT NULL,
  `reason_message` TEXT COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `avatar` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `upload_error_message` TEXT COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bubble_icon` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT '💬',
  `bubble_size` INT(11) DEFAULT 60,
  `color_button_float` VARCHAR(7) DEFAULT '#007bff',
  `color_header` VARCHAR(7) DEFAULT '#007bff',
  `color_user_bubble` VARCHAR(7) DEFAULT '#007bff',
  `color_bot_buttons` VARCHAR(7) DEFAULT '#007bff',
  `color_hover` VARCHAR(7) DEFAULT '#0056b3',
  `color_send_button` VARCHAR(7) DEFAULT '#ffffff',
  `send_button_label` VARCHAR(50) DEFAULT '➢',
  `color_send_button_bg` VARCHAR(7) DEFAULT '#28a745',
  `gibberish_error_message` TEXT COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_creation` TIMESTAMP NULL DEFAULT NULL,
  `date_mod` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `glpi_plugin_helpxora_configs` (`id`, `helpxora_name`, `welcome_message`, `intro_message`, `close_message`, `reason_message`, `upload_error_message`) VALUES
(1, 'Asistente Virtual', 'Hola 👋<br>Soy el asistente virtual de la mesa de ayuda.', 'Seleccione una opción:', 'Gracias por contactarnos. Su requerimiento ha sido registrado.', '¿Cuál es el motivo de tu requerimiento?', 'Error al adjuntar archivo. Verifica que el formato sea válido y el peso no exceda el límite del sistema.');

CREATE TABLE IF NOT EXISTS `glpi_plugin_helpxora_logs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `users_id` INT(11) NOT NULL DEFAULT 0,
  `date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `action` VARCHAR(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `itemtype` VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `items_id` INT(11) NOT NULL DEFAULT 0,
  `field` VARCHAR(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `old_value` TEXT COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `new_value` TEXT COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
