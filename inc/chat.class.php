<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginHelpxoraChat extends CommonGLPI
{

   static function getMenuName()
   {
      return "";
   }

   public static function getBotConfig()
   {
      global $DB;
      $iterator = $DB->request([
         'FROM' => 'glpi_plugin_helpxora_configs',
         'WHERE' => ['id' => 1],
         'LIMIT' => 1
      ]);
      $row = $iterator->current();
      if ($row) {
         $row['welcome_message'] = html_entity_decode($row['welcome_message'] ?? '', ENT_QUOTES, 'UTF-8');
         $row['intro_message'] = html_entity_decode($row['intro_message'] ?? '', ENT_QUOTES, 'UTF-8');
         $row['close_message'] = html_entity_decode($row['close_message'] ?? '', ENT_QUOTES, 'UTF-8');
         $row['reason_message'] = html_entity_decode($row['reason_message'] ?? '', ENT_QUOTES, 'UTF-8');
         return $row;
      }
      return [];
   }

   public static function getConsultas()
   {
      global $DB;
      $consultas = [];
      $iterator = $DB->request([
         'SELECT' => ['id', 'question'],
         'FROM' => 'glpi_plugin_helpxora_consultas',
         'WHERE' => ['is_active' => 1],
         'ORDER' => 'question ASC'
      ]);
      foreach ($iterator as $row) {
         $consultas[] = [
            'id' => $row['id'],
            'text' => $row['question']
         ];
      }
      return $consultas;
   }

   public static function getAnswer($id)
   {
      global $DB;
      $iterator = $DB->request([
         'SELECT' => ['answer', 'images'],
         'FROM' => 'glpi_plugin_helpxora_consultas',
         'WHERE' => [
            'id' => (int)$id,
            'is_active' => 1
         ]
      ]);
      $row = $iterator->current();
      if ($row) {
         $row['answer'] = html_entity_decode($row['answer'] ?? '', ENT_QUOTES, 'UTF-8');
         $raw_images = json_decode($row['images'] ?? '[]', true) ?: [];
         $plugin_web = Plugin::getWebDir('helpxora');
         $row['images'] = array_map(function ($path) use ($plugin_web) {
            return $plugin_web . '/' . ltrim($path, '/');
         }, $raw_images);
         return $row;
      }
      return null;
   }

   public static function getRequerimientos()
   {
      global $DB;
      $reqs = [];
      $iterator = $DB->request([
         'SELECT' => ['id', 'question', 'type', 'custom_response'],
         'FROM' => 'glpi_plugin_helpxora_requerimientos',
         'WHERE' => ['is_active' => 1],
         'ORDER' => 'question ASC'
      ]);
      foreach ($iterator as $row) {
         $reqs[] = [
            'id' => $row['id'],
            'text' => $row['question'],
            'type' => $row['type'],
            'custom_response' => html_entity_decode($row['custom_response'] ?? '', ENT_QUOTES, 'UTF-8')
         ];
      }
      return $reqs;
   }

   public static function createTicket($data)
   {
      if (!Session::getLoginUserID()) {
         return false;
      }

      global $DB;
      $req_id = (int)($data['req_id'] ?? 0);
      $description = $data['description'] ?? '';

      $iterator = $DB->request([
         'FROM' => 'glpi_plugin_helpxora_requerimientos',
         'WHERE' => [
            'id' => $req_id,
            'is_active' => 1
         ]
      ]);
      $req = $iterator->current();
      if (!$req) {
         return ['error' => true, 'message' => 'invalid_requirement'];
      }

      if (isset($_FILES['file']) && !empty($_FILES['file']['name'])) {
         $filename = $_FILES['file']['name'];
         $tmp_name = $_FILES['file']['tmp_name'];
         $filesize = (int)($_FILES['file']['size'] ?? 0);

         $val = trim(ini_get('upload_max_filesize'));
         $last = strtolower($val[strlen($val) - 1] ?? '');
         $max_size = (int)$val;
         switch ($last) {
            case 'g':
               $max_size *= 1024;
            case 'm':
               $max_size *= 1024;
            case 'k':
               $max_size *= 1024;
         }

         if ($filesize > $max_size) {
            return ['error' => true, 'message' => 'too_large'];
         }

         if (empty(Document::isValidDoc($filename))) {
            return ['error' => true, 'message' => 'invalid_type'];
         }
      }

      $ticket = new Ticket();
      $input = [
         'name' => html_entity_decode(strip_tags($req['question']), ENT_QUOTES, 'UTF-8'),
         'content' => html_entity_decode(strip_tags($description), ENT_QUOTES, 'UTF-8'),
         'type' => (int)$req['type'],
         'itilcategories_id' => (int)$req['itilcategories_id'],
         'requesttypes_id' => (int)$req['requesttypes_id'],
         '_groups_id_assign' => (int)$req['groups_id'],
         '_users_id_requester' => Session::getLoginUserID(),
         'locations_id' => (int)($_SESSION['glpilocation_id'] ?? 0),
         'entities_id' => (int)($_SESSION['glpiactive_entity'] ?? 0)
      ];

      $tickets_id = $ticket->add($input);
      if (!$tickets_id) {
         return false;
      }

      if (isset($_FILES['file']) && !empty($_FILES['file']['name'])) {
         $filename = $_FILES['file']['name'];
         $tmp_name = $_FILES['file']['tmp_name'];
         $uniqid = uniqid('chat_') . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
         $dest = GLPI_TMP_DIR . '/' . $uniqid;

         if (move_uploaded_file($tmp_name, $dest)) {
            $doc = new Document();
            $docinput = [
               'name' => $filename,
               'tickets_id' => $tickets_id,
               '_auto_update_extension' => 1,
               '_auto_import' => 1,
               'itemtype' => 'Ticket',
               'items_id' => $tickets_id,
               '_filename' => [$uniqid]
            ];
            $doc->add($docinput);
         }
      }

      return $tickets_id;
   }
}
