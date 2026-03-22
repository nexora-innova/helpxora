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
         $q = (string)($row['question'] ?? '');
         $consultas[] = [
            'id'   => $row['id'],
            'text' => html_entity_decode(strip_tags($q), ENT_QUOTES, 'UTF-8'),
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
      $table = 'glpi_plugin_helpxora_requerimientos';
      $select = ['id', 'question', 'type', 'custom_response'];
      $optionalFields = [
         'attachments_mode', 'max_files', 'allowed_extensions', 'min_chars', 'max_chars',
         'validation_regex', 'restrict_gibberish', 'description_mode',
      ];
      foreach ($optionalFields as $field) {
         if ($DB->fieldExists($table, $field)) {
            $select[] = $field;
         }
      }
      $iterator = $DB->request([
         'SELECT' => $select,
         'FROM' => $table,
         'WHERE' => ['is_active' => 1],
         'ORDER' => 'question ASC'
      ]);
      foreach ($iterator as $row) {
         $am = (int)($row['attachments_mode'] ?? 0);
         $dm = (int)($row['description_mode'] ?? 1);
         if ($am === 0 && $dm === 0) {
            continue;
         }
         $reqs[] = [
            'id' => $row['id'],
            'text' => $row['question'],
            'type' => $row['type'],
            'custom_response' => html_entity_decode($row['custom_response'] ?? '', ENT_QUOTES, 'UTF-8'),
            'attachments_mode' => $am,
            'max_files' => max(1, (int)($row['max_files'] ?? 1)),
            'allowed_extensions' => (string)($row['allowed_extensions'] ?? ''),
            'min_chars' => (int)($row['min_chars'] ?? 10),
            'max_chars' => max(1, (int)($row['max_chars'] ?? 500)),
            'validation_regex' => (string)($row['validation_regex'] ?? ''),
            'restrict_gibberish' => (int)($row['restrict_gibberish'] ?? 0),
            'description_mode' => $dm,
         ];
      }
      return $reqs;
   }

   public static function preTicketAddValidate(CommonDBTM $item)
   {
      if (!($item instanceof Ticket)) {
         return;
      }
      $req_id = (int)($item->input['_helpxora_req_id'] ?? 0);
      unset($item->input['_helpxora_req_id']);
      if ($req_id <= 0) {
         return;
      }

      global $DB;
      $iterator = $DB->request([
         'FROM' => 'glpi_plugin_helpxora_requerimientos',
         'WHERE' => [
            'id'        => $req_id,
            'is_active' => 1
         ]
      ]);
      $req = $iterator->current();
      if (!$req) {
         $item->input = false;
         Session::addMessageAfterRedirect(__('Invalid or inactive requirement. Please choose again from the menu.', 'helpxora'), false, ERROR);
         return;
      }

      $content = strip_tags(html_entity_decode((string)($item->input['content'] ?? ''), ENT_QUOTES, 'UTF-8'));
      $ctx = [];
      $code = PluginHelpxoraRequirementValidator::validateTicketAgainstRequirement($req, $content, $ctx);
      if ($code !== null) {
         PluginHelpxoraRequirementValidator::abortTicketAddWithCode($item, $code, $ctx);
         return;
      }
      $nr = PluginHelpxoraRequirementValidator::normalizeRequirementRow($req);
      if ($nr['description_mode'] === PluginHelpxoraRequirementValidator::DESCRIPTION_NONE) {
         $item->input['content'] = '';
      }
   }

   public static function createTicket($data)
   {
      if (!Session::getLoginUserID()) {
         return false;
      }

      global $DB;
      $req_id = (int)($data['req_id'] ?? 0);
      $description = trim((string)($data['description'] ?? ''));
      if ($req_id <= 0) {
         return ['error' => true, 'message' => 'invalid_requirement'];
      }

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

      $ctx = [];
      $code = PluginHelpxoraRequirementValidator::validateTicketAgainstRequirement($req, $description, $ctx);
      if ($code !== null) {
         return ['error' => true, 'message' => $code, 'context' => $ctx];
      }

      $nr = PluginHelpxoraRequirementValidator::normalizeRequirementRow($req);
      $ticket_body = $description;
      if ($nr['description_mode'] === PluginHelpxoraRequirementValidator::DESCRIPTION_NONE) {
         $ticket_body = '';
      }

      $ticket = new Ticket();
      $input = [
         'name' => html_entity_decode(strip_tags($req['question']), ENT_QUOTES, 'UTF-8'),
         'content' => html_entity_decode(strip_tags($ticket_body), ENT_QUOTES, 'UTF-8'),
         'type' => (int)$req['type'],
         'itilcategories_id' => (int)$req['itilcategories_id'],
         'requesttypes_id' => (int)$req['requesttypes_id'],
         '_groups_id_assign' => (int)$req['groups_id'],
         '_users_id_requester' => Session::getLoginUserID(),
         'locations_id' => (int)($_SESSION['glpilocation_id'] ?? 0),
         'entities_id' => (int)($_SESSION['glpiactive_entity'] ?? 0),
         '_helpxora_req_id' => $req_id,
      ];

      $tickets_id = $ticket->add($input);
      if (!$tickets_id) {
         return false;
      }

      $files = PluginHelpxoraRequirementValidator::collectUploadedFilesFromRequest();
      foreach ($files as $f) {
         $filename = $f['name'];
         $tmp_name = $f['tmp_name'];
         $uniqid = uniqid('chat_') . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
         $dest = GLPI_TMP_DIR . '/' . $uniqid;

         if ($tmp_name !== '' && is_uploaded_file($tmp_name) && move_uploaded_file($tmp_name, $dest)) {
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
