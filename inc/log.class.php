<?php
if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginHelpxoraLog extends CommonDBTM
{
   static $rightname = 'config';

   static function getTypeName($nb = 0)
   {
      return _n('Histórico de acciones', 'Histórico de acciones', $nb, 'helpxora');
   }

   public static function canCreate()
   {
      return Session::haveRight(self::$rightname, UPDATE);
   }

   public static function canView()
   {
      return Session::haveRight(self::$rightname, READ);
   }

   public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
   {
      if ($item->getType() == PluginHelpxoraConfig::class) {
         $ong = [];
         $ong[1] = self::getTypeName(2);
         return $ong;
      }
      return '';
   }

   public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
   {
      if ($item->getType() == PluginHelpxoraConfig::class) {
         $self = new self();
         $self->showList();
      }
      return true;
   }

   function showList()
   {
      global $DB;
      $result = $DB->request([
         'FROM' => 'glpi_plugin_helpxora_logs',
         'ORDER' => 'id DESC',
         'LIMIT' => 500
      ]);

      echo "<div class='center'>";
      echo "<table class='tab_cadre_fixehov'>";
      echo "<tr class='tab_bg_1'>";
      echo "<th>ID</th>";
      echo "<th>Fecha</th>";
      echo "<th>Usuario</th>";
      echo "<th>Acción</th>";
      echo "<th>Elemento</th>";
      echo "<th>Campo modificado</th>";
      echo "<th>Valor anterior</th>";
      echo "<th>Nuevo valor</th>";
      echo "</tr>";

      foreach ($result as $data) {
         echo "<tr class='tab_bg_1'>";
         echo "<td class='center'>" . $data['id'] . "</td>";
         echo "<td class='center'>" . Html::convDateTime($data['date']) . "</td>";
         
         $user = "Sistema";
         if ($data['users_id'] > 0) {
             $userObj = new User();
             if ($userObj->getFromDB($data['users_id'])) {
                 $user = $userObj->fields['name'];
             }
         }
         
         echo "<td class='center'>" . htmlspecialchars($user) . "</td>";
         echo "<td class='center'>" . htmlspecialchars($data['action']) . "</td>";
         echo "<td class='center'>" . htmlspecialchars($data['itemtype'] . ' (' . $data['items_id'] . ')') . "</td>";
         echo "<td class='center'>" . htmlspecialchars($data['field'] ?? '') . "</td>";
         
         $old_val = $data['old_value'] ? htmlspecialchars(strip_tags($data['old_value'])) : '';
         $new_val = $data['new_value'] ? htmlspecialchars(strip_tags($data['new_value'])) : '';
         
         if (strlen($old_val) > 50) $old_val = substr($old_val, 0, 50) . '...';
         if (strlen($new_val) > 50) $new_val = substr($new_val, 0, 50) . '...';
         
         echo "<td class='center' title='" . htmlspecialchars($data['old_value']) . "'>" . $old_val . "</td>";
         echo "<td class='center' title='" . htmlspecialchars($data['new_value']) . "'>" . $new_val . "</td>";
         echo "</tr>";
      }
      echo "</table>";
      echo "</div>";
   }

   public static function logAction($action, $itemtype, $items_id, $field = '', $old_value = '', $new_value = '')
   {
       global $DB;
       $log = new self();
       $input = [
           'users_id' => Session::getLoginUserID() ?? 0,
           'action' => $action,
           'itemtype' => $itemtype,
           'items_id' => $items_id,
           'field' => $field,
           'old_value' => $old_value,
           'new_value' => $new_value
       ];
       $log->add($input);
   }
}
