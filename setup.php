<?php

use Glpi\Plugin\Hooks;

define('PLUGIN_HELPXORA_VERSION', '1.0.0');
define('PLUGIN_HELPXORA_MIN_GLPI_VERSION', '10.0.0');
define('PLUGIN_HELPXORA_MAX_GLPI_VERSION', '10.0.99');

function plugin_init_helpxora() {
   global $PLUGIN_HOOKS;

   $PLUGIN_HOOKS['csrf_compliant']['helpxora'] = true;

   Plugin::registerClass(PluginHelpxoraConfig::class, ['addtabon' => [PluginHelpxoraConfig::class]]);
   Plugin::registerClass(PluginHelpxoraConsulta::class, ['addtabon' => [PluginHelpxoraConfig::class]]);
   Plugin::registerClass(PluginHelpxoraRequerimiento::class, ['addtabon' => [PluginHelpxoraConfig::class]]);
   Plugin::registerClass(PluginHelpxoraLog::class, ['addtabon' => [PluginHelpxoraConfig::class]]);
   Plugin::registerClass(PluginHelpxoraChat::class);

   if (Session::getLoginUserID()) {
      $PLUGIN_HOOKS[Hooks::MENU_TOADD]['helpxora'] = [
         'config' => PluginHelpxoraConfig::class
      ];
   }

   $PLUGIN_HOOKS[Hooks::ADD_CSS]['helpxora'] = 'css/helpxora.css';
   $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['helpxora'] = 'js/helpxora.js';
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
            'min' => '7.4',
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
