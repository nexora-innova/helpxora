<?php

define('PLUGIN_HELPXORA_VERSION', '1.0.2');
define('PLUGIN_HELPXORA_MIN_GLPI_VERSION', '10.0.0');
define('PLUGIN_HELPXORA_MAX_GLPI_VERSION', '10.0.99');

require_once __DIR__ . '/hook.php';

function plugin_init_helpxora() {
   global $PLUGIN_HOOKS;

   $PLUGIN_HOOKS['csrf_compliant']['helpxora'] = true;

   Plugin::registerClass(PluginHelpxoraConfig::class, ['addtabon' => [PluginHelpxoraConfig::class]]);
   Plugin::registerClass(PluginHelpxoraConsulta::class, ['addtabon' => [PluginHelpxoraConfig::class]]);
   Plugin::registerClass(PluginHelpxoraRequerimiento::class, ['addtabon' => [PluginHelpxoraConfig::class]]);
   Plugin::registerClass(PluginHelpxoraLog::class, ['addtabon' => [PluginHelpxoraConfig::class]]);
   Plugin::registerClass(PluginHelpxoraChat::class);

   if (Session::getLoginUserID()) {
      $PLUGIN_HOOKS['menu_toadd']['helpxora'] = [
         'config' => PluginHelpxoraConfig::class
      ];
   }

   $PLUGIN_HOOKS['add_css']['helpxora'] = 'css/helpxora.css';
   $PLUGIN_HOOKS['add_javascript']['helpxora'] = 'js/helpxora.js';

   $PLUGIN_HOOKS['pre_item_add']['helpxora'] = [
      'Ticket'                      => 'plugin_helpxora_pre_item_add',
      'PluginHelpxoraRequerimiento' => 'plugin_helpxora_pre_item_add',
   ];
   $PLUGIN_HOOKS['pre_item_update']['helpxora'] = [
      'Ticket'                      => 'plugin_helpxora_pre_item_update',
      'PluginHelpxoraRequerimiento' => 'plugin_helpxora_pre_item_update',
   ];
}

function plugin_version_helpxora() {
   return [
      'name' => 'HelpXora',
      'version' => PLUGIN_HELPXORA_VERSION,
      'author' => 'NexoraInnova',
      'license' => 'GPLv3',
      'homepage' => 'https://github.com/nexora-innova/helpxora',
      'requirements' => [
         'glpi' => [
            'min' => PLUGIN_HELPXORA_MIN_GLPI_VERSION,
            'max' => PLUGIN_HELPXORA_MAX_GLPI_VERSION,
         ],
         'php' => [
            'min' => '8.1',
         ],
      ],
   ];
}

function plugin_helpxora_check_prerequisites() {
   if (version_compare(GLPI_VERSION, PLUGIN_HELPXORA_MIN_GLPI_VERSION, 'lt')) {
      if (method_exists('Toolbox', 'logError')) {
         Toolbox::logError('This plugin requires GLPI >= ' . PLUGIN_HELPXORA_MIN_GLPI_VERSION);
      }
      return false;
   }
   if (version_compare(GLPI_VERSION, PLUGIN_HELPXORA_MAX_GLPI_VERSION, 'ge')) {
      if (method_exists('Toolbox', 'logError')) {
         Toolbox::logError('This plugin requires GLPI < ' . PLUGIN_HELPXORA_MAX_GLPI_VERSION);
      }
      return false;
   }
   return true;
}

function plugin_helpxora_check_config() {
   return true;
}
