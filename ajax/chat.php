<?php

if (!defined('GLPI_KEEP_CSRF_TOKEN')) {
   define('GLPI_KEEP_CSRF_TOKEN', true);
}

include('../../../inc/includes.php');

header("Content-Type: application/json; charset=UTF-8");

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
   case 'init':
      $config = PluginHelpxoraChat::getBotConfig();
      $isLoggedIn = Session::getLoginUserID() > 0;

      $val = trim(ini_get('upload_max_filesize'));
      $last = strtolower($val[strlen($val) - 1]);
      $max_size = (int)$val;
      switch ($last) {
         case 'g':
            $max_size *= 1024;
         case 'm':
            $max_size *= 1024;
         case 'k':
            $max_size *= 1024;
      }

      $allowed_exts = '';
      if (class_exists('DocumentType')) {
         $allowed_exts = DocumentType::getUploadableFilePattern();
      }

      echo json_encode([
         'status' => 'ok',
         'config' => [
            'name' => $config['helpxora_name'] ?? 'Asistente',
            'avatar' => $config['avatar'] ?? '',
            'bubble_icon' => $config['bubble_icon'] ?? '💬',
            'bubble_size' => $config['bubble_size'] ?? 60,
            'welcome' => $config['welcome_message'] ?? 'Hola',
            'intro' => $config['intro_message'] ?? 'Seleccione una opción:',
            'close' => $config['close_message'] ?? 'Gracias',
            'reason' => $config['reason_message'] ?? '¿Cuál es el motivo de tu requerimiento?',
            'color_button_float' => $config['color_button_float'] ?? '#007bff',
            'color_header' => $config['color_header'] ?? '#007bff',
            'color_user_bubble' => $config['color_user_bubble'] ?? '#007bff',
            'color_bot_buttons' => $config['color_bot_buttons'] ?? '#007bff',
            'color_hover' => $config['color_hover'] ?? '#0056b3',
            'color_send_button' => $config['color_send_button'] ?? '#ffffff',
            'send_button_label' => $config['send_button_label'] ?? '➢',
            'color_send_button_bg' => $config['color_send_button_bg'] ?? '#28a745',
            'color_chat_background' => $config['color_chat_background'] ?? '#ffffff',
            'color_bot_bubble' => $config['color_bot_bubble'] ?? '#e6e6e6',
            'color_bot_text' => $config['color_bot_text'] ?? '#000000',
            'color_user_text' => $config['color_user_text'] ?? '#ffffff',
            'color_input_background' => $config['color_input_background'] ?? '#f8f9fa',
            'color_input_text' => $config['color_input_text'] ?? '#000000',
            'error_msg' => $config['upload_error_message'] ?? 'Error al subir archivo.',
            'max_upload_size' => $max_size,
            'allowed_extensions' => $allowed_exts
         ]
      ]);
      break;

   case 'get_menu':
      $consultas = PluginHelpxoraChat::getConsultas();
      $reqs = PluginHelpxoraChat::getRequerimientos();

      $options = [];
      if (!empty($consultas)) {
         $options[] = ['id' => 'menu_consultas', 'text' => '1️⃣ Tengo una consulta'];
      }
      if (!empty($reqs)) {
         if (Session::getLoginUserID()) {
            $options[] = ['id' => 'menu_reqs', 'text' => '2️⃣ Quiero generar un requerimiento'];
         }
         else {
            $options[] = ['id' => 'login_required', 'text' => '2️⃣ Quiero generar un requerimiento (Requiere login)'];
         }
      }

      echo json_encode(['options' => $options]);
      break;

   case 'get_consultas':
      $consultas = PluginHelpxoraChat::getConsultas();
      echo json_encode(['options' => $consultas]);
      break;

   case 'get_answer':
      $id = (int)($_POST['id'] ?? 0);
      $answer = PluginHelpxoraChat::getAnswer($id);
      echo json_encode($answer);
      break;

   case 'get_reqs':
      $reqs = PluginHelpxoraChat::getRequerimientos();
      echo json_encode(['options' => $reqs]);
      break;

   case 'create_ticket':
      if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
         header('HTTP/1.1 405 Method Not Allowed');
         echo json_encode(['status' => 'error', 'message' => __('The action you have requested is not allowed.')]);
         exit;
      }
      if (!Session::getLoginUserID()) {
         header('HTTP/1.1 403 Forbidden');
         echo json_encode(['status' => 'error', 'message' => __('You must be logged in.')]);
         exit;
      }
      if (empty($_POST['req_id'])) {
         header('HTTP/1.1 400 Bad Request');
         echo json_encode(['status' => 'error', 'message' => __('Missing mandatory fields')]);
         exit;
      }
      $csrf_data = $_POST;
      if (!isset($csrf_data['_glpi_csrf_token']) && isset($_SERVER['HTTP_X_GLPI_CSRF_TOKEN'])) {
         $csrf_data['_glpi_csrf_token'] = $_SERVER['HTTP_X_GLPI_CSRF_TOKEN'];
      }
      if (!Session::validateCSRF($csrf_data)) {
         header('HTTP/1.1 403 Forbidden');
         echo json_encode(['status' => 'error', 'message' => __('The action you have requested is not allowed.')]);
         exit;
      }
      $tickets_id = PluginHelpxoraChat::createTicket($_POST);
      if (is_array($tickets_id) && isset($tickets_id['error'])) {
         $config = PluginHelpxoraChat::getBotConfig();
         $ctx = $tickets_id['context'] ?? [];
         if (($tickets_id['message'] ?? '') === 'invalid_requirement') {
            $msg = __('Invalid or inactive requirement. Please choose again from the menu.', 'helpxora');
         } else if (($tickets_id['message'] ?? '') === 'description_required_empty') {
            $msg = __('Description is required for this requirement.', 'helpxora');
         } else if (($tickets_id['message'] ?? '') === 'description_too_short') {
            $msg = sprintf(
               __('The description must be at least %d characters for this requirement.', 'helpxora'),
               (int)($ctx['min_chars'] ?? 1)
            );
         } else if (($tickets_id['message'] ?? '') === 'description_too_long') {
            $msg = sprintf(
               __('The description must not exceed %d characters for this requirement.', 'helpxora'),
               (int)($ctx['max_chars'] ?? 500)
            );
         } else if (($tickets_id['message'] ?? '') === 'suspicious_input') {
            $msg = __('Description contains invalid patterns and cannot be processed.', 'helpxora');
         } else if (($tickets_id['message'] ?? '') === 'requirement_no_input_channel') {
            $msg = __('This requirement is not configured for chat: enable attachments or description.', 'helpxora');
         } else if (($tickets_id['message'] ?? '') === 'invalid_format') {
            $msg = __('The text does not look valid.', 'helpxora');
         } else if (($tickets_id['message'] ?? '') === 'gibberish_detected') {
            $custom = trim((string)($config['gibberish_error_message'] ?? ''));
            $msg = ($custom !== '') ? $custom : __('Description appears to be invalid or meaningless.', 'helpxora');
         } else if (($tickets_id['message'] ?? '') === 'file_not_allowed') {
            $msg = __('Attachments are not allowed for this requirement.', 'helpxora');
         } else if (($tickets_id['message'] ?? '') === 'invalid_extension') {
            $msg = __('File extension is not allowed for this requirement.', 'helpxora');
         } else if (($tickets_id['message'] ?? '') === 'too_many_files') {
            $msg = sprintf(
               __('Too many files attached (maximum %d).', 'helpxora'),
               (int)($ctx['max_files'] ?? 1)
            );
         } else if (($tickets_id['message'] ?? '') === 'too_few_files') {
            $msg = sprintf(
               __('You must attach exactly %d file(s) for this requirement.', 'helpxora'),
               (int)($ctx['max_files'] ?? 1)
            );
         } else if (($tickets_id['message'] ?? '') === 'upload_error') {
            $msg = $config['upload_error_message'] ?? __('File could not be attached due to system restrictions.', 'helpxora');
         } else {
            $msg = $config['upload_error_message'] ?? __('File could not be attached due to system restrictions.', 'helpxora');
         }
         echo json_encode(['status' => 'error', 'message' => $msg]);
         exit;
      }

      if ($tickets_id) {
         echo json_encode(['status' => 'success', 'ticket_id' => $tickets_id]);
      }
      else {
         $msg = '';
         if (isset($_SESSION['MESSAGE_AFTER_REDIRECT'][ERROR])) {
            $errors = $_SESSION['MESSAGE_AFTER_REDIRECT'][ERROR];
            if (is_array($errors) && count($errors) > 0) {
               $msg = (string)end($errors);
            } else {
               $msg = (string)$errors;
            }
            unset($_SESSION['MESSAGE_AFTER_REDIRECT'][ERROR]);
            if (empty($_SESSION['MESSAGE_AFTER_REDIRECT'])) {
               unset($_SESSION['MESSAGE_AFTER_REDIRECT']);
            }
         }
         if ($msg === '') {
            $msg = __('The ticket could not be created.', 'helpxora');
         }
         echo json_encode(['status' => 'error', 'message' => $msg]);
      }
      break;

   default:
      echo json_encode(['status' => 'invalid_action']);
}
