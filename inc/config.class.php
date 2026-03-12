<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginHelpxoraConfig extends CommonDBTM
{
   static $rightname = 'config';

   static function getTypeName($nb = 0)
   {
      return _n('Configuración de HelpXora', 'Configuraciones de HelpXora', $nb, 'helpxora');
   }

   static function getIcon()
   {
      return "fas fa-robot";
   }

   static function getMenuName()
   {
      return __('HelpXora', 'helpxora');
   }

   static function getMenuContent()
   {
      return [
         'title' => self::getMenuName(),
         'page' => Plugin::getWebDir('helpxora') . '/front/config.php',
         'icon' => self::getIcon()
      ];
   }

   public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
   {
      if ($item->getType() == __CLASS__) {
         $ong = [];
         $ong[1] = __('General', 'helpxora');
         return $ong;
      }
      return '';
   }

   public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
   {
      if ($item->getType() == __CLASS__) {
         switch ($tabnum) {
            case 1:
               $item->showForm(1);
               break;
         }
      }
      return true;
   }

   function showForm($ID, $options = [])
   {
      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'><th colspan='4'>Configuración General del Asistente</th></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>Nombre del asistente</td>";
      echo "<td><input type='text' name='helpxora_name' value='" . Html::cleanInputText($this->fields['helpxora_name'] ?? '') . "'></td>";
      echo "<td>Avatar del asistente (URL o path)</td>";
      echo "<td><input type='text' name='avatar' value='" . Html::cleanInputText($this->fields['avatar'] ?? '') . "'></td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>Icono de la burbuja (URL, path o emoji)</td>";
      echo "<td><input type='text' name='bubble_icon' value='" . Html::cleanInputText($this->fields['bubble_icon'] ?? '💬') . "'></td>";
      echo "<td>Tamaño de la burbuja (px)</td>";
      echo "<td><input type='number' name='bubble_size' value='" . ($this->fields['bubble_size'] ?? 60) . "' min='20' max='200'></td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>Mensaje de bienvenida</td>";
      echo "<td colspan='3'>";
      Html::textarea(['name' => 'welcome_message', 'value' => $this->fields['welcome_message'] ?? '', 'enable_richtext' => true, 'editor_id' => 'helpxora_welcome_message']);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>Mensaje de introducción</td>";
      echo "<td colspan='3'>";
      Html::textarea(['name' => 'intro_message', 'value' => $this->fields['intro_message'] ?? '', 'enable_richtext' => true, 'editor_id' => 'helpxora_intro_message']);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>Mensaje de motivo de requerimiento</td>";
      echo "<td colspan='3'>";
      Html::textarea(['name' => 'reason_message', 'value' => $this->fields['reason_message'] ?? '', 'enable_richtext' => true, 'editor_id' => 'helpxora_reason_message']);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>Mensaje de cierre</td>";
      echo "<td colspan='3'>";
      Html::textarea(['name' => 'close_message', 'value' => $this->fields['close_message'] ?? '', 'enable_richtext' => true, 'editor_id' => 'helpxora_close_message']);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>Mensaje de error (Carga de archivos)</td>";
      echo "<td colspan='3'><input type='text' name='upload_error_message' value='" . Html::cleanInputText($this->fields['upload_error_message'] ?? '') . "' size='80'></td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>Color: Botón flotante</td>";
      echo "<td><input type='color' name='color_button_float' value='" . Html::cleanInputText($this->fields['color_button_float'] ?? '#007bff') . "'></td>";
      echo "<td>Color: Encabezado del asistente</td>";
      echo "<td><input type='color' name='color_header' value='" . Html::cleanInputText($this->fields['color_header'] ?? '#007bff') . "'></td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>Color: Burbujas de mensajes (Usuario)</td>";
      echo "<td><input type='color' name='color_user_bubble' value='" . Html::cleanInputText($this->fields['color_user_bubble'] ?? '#007bff') . "'></td>";
      echo "<td>Color: Botones de opciones</td>";
      echo "<td><input type='color' name='color_bot_buttons' value='" . Html::cleanInputText($this->fields['color_bot_buttons'] ?? '#007bff') . "'></td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>Color: Efecto hover en botones</td>";
      echo "<td><input type='color' name='color_hover' value='" . Html::cleanInputText($this->fields['color_hover'] ?? '#0056b3') . "'></td>";
      echo "<td>Color: Icono del botón de envío</td>";
      echo "<td><input type='color' name='color_send_button' value='" . Html::cleanInputText($this->fields['color_send_button'] ?? '#ffffff') . "'></td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'><th colspan='4'>Botón de Envío del Asistente</th></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>Etiqueta del botón de envío (texto o emoji)</td>";
      echo "<td><input type='text' name='send_button_label' value='" . Html::cleanInputText($this->fields['send_button_label'] ?? '➢') . "'></td>";
      echo "<td>Color de fondo botón de envío</td>";
      echo "<td><input type='color' name='color_send_button_bg' value='" . Html::cleanInputText($this->fields['color_send_button_bg'] ?? '#28a745') . "'></td>";
      echo "</tr>";

      $options['candel'] = false;
      $this->showFormButtons($options);

      return true;
   }

   function post_updateItem($history = 1)
   {
      if (isset($this->oldvalues) && count($this->oldvalues) > 0) {
         foreach ($this->oldvalues as $field => $old_val) {
            $new_val = $this->fields[$field];
            PluginHelpxoraLog::logAction('update', __CLASS__, $this->fields['id'], $field, $old_val, $new_val);
         }
      }
   }
}
