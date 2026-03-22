<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginHelpxoraRequirementValidator extends CommonGLPI
{
   public const ATTACHMENTS_NONE = 0;

   public const ATTACHMENTS_MANDATORY = 1;

   public const ATTACHMENTS_OPTIONAL = 2;

   public const DESCRIPTION_NONE = 0;

   public const DESCRIPTION_MANDATORY = 1;

   public const DESCRIPTION_OPTIONAL = 2;

   public static function hasSuspiciousSqlPattern($text)
   {
      $value = mb_strtolower((string)$text, 'UTF-8');
      $patterns = [
         '/\bunion\b\s+\bselect\b/i',
         '/\bselect\b.+\bfrom\b/i',
         '/\binsert\b\s+\binto\b/i',
         '/\bupdate\b.+\bset\b/i',
         '/\bdelete\b\s+\bfrom\b/i',
         '/\bdrop\b\s+\btable\b/i',
         '/\btruncate\b\s+\btable\b/i',
         '/\bor\b\s+1\s*=\s*1\b/i',
         '/--/',
         '/\/\*/',
         '/;\s*(select|insert|update|delete|drop|truncate)\b/i'
      ];
      foreach ($patterns as $pattern) {
         if (preg_match($pattern, $value)) {
            return true;
         }
      }
      return false;
   }

   public static function matchesCustomRegex($text, $regex)
   {
      $regex = trim((string)$regex);
      if ($regex === '') {
         return true;
      }
      if (@preg_match($regex, '') === false) {
         return true;
      }
      return preg_match($regex, (string)$text) === 1;
   }

   public static function isRegexPatternValid($regex)
   {
      $regex = trim((string)$regex);
      if ($regex === '') {
         return true;
      }
      return @preg_match($regex, '') !== false;
   }

   public static function isGibberishUserInput($text)
   {
      $normalized = trim((string)$text);
      if ($normalized === '') {
         return false;
      }

      $lettersOnly = preg_replace('/[^a-zA-ZáéíóúüñÁÉÍÓÚÜÑ]/u', '', $normalized);
      $letterLen = Toolbox::strlen($lettersOnly);
      if ($letterLen < 4) {
         return false;
      }

      $words = preg_split('/[^\p{L}\p{N}]+/u', $normalized, -1, PREG_SPLIT_NO_EMPTY);
      if (count($words) < 2) {
         return true;
      }

      if (preg_match('/asdf|qwer|zxcv|1234/i', $normalized)) {
         return true;
      }

      if (preg_match('/(.)\1{4,}/us', $normalized)) {
         return true;
      }

      $lower = mb_strtolower($lettersOnly, 'UTF-8');
      if (!preg_match('/[aeiouáéíóúü]/u', $lower)) {
         return true;
      }

      return false;
   }

   public static function abortTicketAddWithCode(Ticket $item, $code, array $ctx = [])
   {
      $item->input = false;
      $config = PluginHelpxoraChat::getBotConfig();
      switch ($code) {
         case 'description_required_empty':
            Session::addMessageAfterRedirect(__('Description is required for this requirement.', 'helpxora'), false, ERROR);
            return;
         case 'description_too_short':
            Session::addMessageAfterRedirect(
               sprintf(__('The description must be at least %d characters for this requirement.', 'helpxora'), (int)($ctx['min_chars'] ?? 1)),
               false,
               ERROR
            );
            return;
         case 'description_too_long':
            Session::addMessageAfterRedirect(
               sprintf(__('The description must not exceed %d characters for this requirement.', 'helpxora'), (int)($ctx['max_chars'] ?? 500)),
               false,
               ERROR
            );
            return;
         case 'suspicious_input':
            Session::addMessageAfterRedirect(__('Description contains invalid patterns and cannot be processed.', 'helpxora'), false, ERROR);
            return;
         case 'requirement_no_input_channel':
            Session::addMessageAfterRedirect(__('This requirement is not configured for chat: enable attachments or description.', 'helpxora'), false, ERROR);
            return;
         case 'invalid_format':
            Session::addMessageAfterRedirect(__('The text does not look valid.', 'helpxora'), false, ERROR);
            return;
         case 'gibberish_detected':
            $msg = trim((string)($config['gibberish_error_message'] ?? ''));
            if ($msg === '') {
               $msg = __('Description appears to be invalid or meaningless.', 'helpxora');
            }
            Session::addMessageAfterRedirect($msg, false, ERROR);
            return;
         case 'file_not_allowed':
            Session::addMessageAfterRedirect(__('Attachments are not allowed for this requirement.', 'helpxora'), false, ERROR);
            return;
         case 'too_many_files':
            Session::addMessageAfterRedirect(
               sprintf(__('Too many files attached (maximum %d).', 'helpxora'), (int)($ctx['max_files'] ?? 1)),
               false,
               ERROR
            );
            return;
         case 'too_few_files':
            Session::addMessageAfterRedirect(
               sprintf(__('You must attach exactly %d file(s) for this requirement.', 'helpxora'), (int)($ctx['max_files'] ?? 1)),
               false,
               ERROR
            );
            return;
         case 'too_large':
            Session::addMessageAfterRedirect(
               $config['upload_error_message'] ?? __('File could not be attached due to system restrictions.', 'helpxora'),
               false,
               ERROR
            );
            return;
         case 'invalid_type':
            Session::addMessageAfterRedirect(
               $config['upload_error_message'] ?? __('File could not be attached due to system restrictions.', 'helpxora'),
               false,
               ERROR
            );
            return;
         case 'invalid_extension':
            Session::addMessageAfterRedirect(__('File extension is not allowed for this requirement.', 'helpxora'), false, ERROR);
            return;
         case 'upload_error':
            Session::addMessageAfterRedirect(
               $config['upload_error_message'] ?? __('File could not be attached due to system restrictions.', 'helpxora'),
               false,
               ERROR
            );
            return;
         default:
            Session::addMessageAfterRedirect(__('The ticket could not be created.', 'helpxora'), false, ERROR);
      }
   }

   public static function isAllowedExtension($filename, $allowedExtensions)
   {
      $allowedExtensions = trim((string)$allowedExtensions);
      if ($allowedExtensions === '') {
         return true;
      }
      $ext = strtolower(pathinfo((string)$filename, PATHINFO_EXTENSION));
      if ($ext === '') {
         return false;
      }
      $allowed = array_filter(array_map('trim', explode(',', strtolower($allowedExtensions))));
      return in_array($ext, $allowed, true);
   }

   public static function collectUploadedFilesFromRequest()
   {
      $out = [];
      if (!empty($_FILES['files']['name']) && is_array($_FILES['files']['name'])) {
         $n = count($_FILES['files']['name']);
         for ($i = 0; $i < $n; $i++) {
            $name = $_FILES['files']['name'][$i] ?? '';
            if ($name === '' || (int)($_FILES['files']['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
               continue;
            }
            $out[] = [
               'name'     => $name,
               'size'     => (int)($_FILES['files']['size'][$i] ?? 0),
               'tmp_name' => $_FILES['files']['tmp_name'][$i] ?? '',
               'error'    => (int)($_FILES['files']['error'][$i] ?? 0),
            ];
         }
      }
      if (count($out) === 0 && !empty($_FILES['file']['name']) && is_string($_FILES['file']['name']) && (int)($_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
         $out[] = [
            'name'     => $_FILES['file']['name'],
            'size'     => (int)($_FILES['file']['size'] ?? 0),
            'tmp_name' => $_FILES['file']['tmp_name'] ?? '',
            'error'    => (int)($_FILES['file']['error'] ?? 0),
         ];
      }
      return $out;
   }

   public static function getMaxUploadSizeBytes()
   {
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
      return $max_size;
   }

   public static function normalizeRequirementRow(array $req)
   {
      $min = (int)($req['min_chars'] ?? 10);
      $max = (int)($req['max_chars'] ?? 500);
      if ($min < 0) {
         $min = 0;
      }
      if ($max < 1) {
         $max = 1;
      }
      if ($max < $min && $min > 0) {
         $max = $min;
      }
      $mf = (int)($req['max_files'] ?? 1);
      if ($mf < 1) {
         $mf = 1;
      }
      if ($mf > 10) {
         $mf = 10;
      }
      $am = (int)($req['attachments_mode'] ?? self::ATTACHMENTS_NONE);
      if ($am < self::ATTACHMENTS_NONE || $am > self::ATTACHMENTS_OPTIONAL) {
         $am = self::ATTACHMENTS_NONE;
      }
      $dm = (int)($req['description_mode'] ?? self::DESCRIPTION_MANDATORY);
      if ($dm < self::DESCRIPTION_NONE || $dm > self::DESCRIPTION_OPTIONAL) {
         $dm = self::DESCRIPTION_MANDATORY;
      }
      return [
         'attachments_mode'   => $am,
         'max_files'          => $mf,
         'allowed_extensions' => (string)($req['allowed_extensions'] ?? ''),
         'min_chars'          => $min,
         'max_chars'          => $max,
         'validation_regex'   => (string)($req['validation_regex'] ?? ''),
         'restrict_gibberish' => (int)($req['restrict_gibberish'] ?? 0),
         'description_mode'   => $dm,
      ];
   }

   public static function validateTicketAgainstRequirement(array $req, $plainDescription, &$errorContext = [])
   {
      $errorContext = [];
      $r = self::normalizeRequirementRow($req);
      if ($r['attachments_mode'] === self::ATTACHMENTS_NONE && $r['description_mode'] === self::DESCRIPTION_NONE) {
         return 'requirement_no_input_channel';
      }
      $content = trim((string)$plainDescription);
      $len = Toolbox::strlen($content);

      $dm = $r['description_mode'];
      if ($dm === self::DESCRIPTION_MANDATORY && $content === '') {
         return 'description_required_empty';
      }

      if ($dm === self::DESCRIPTION_NONE) {
         if ($content !== '' && self::hasSuspiciousSqlPattern($content)) {
            return 'suspicious_input';
         }
      } elseif ($content !== '') {
         if ($len < $r['min_chars']) {
            $errorContext['min_chars'] = $r['min_chars'];
            return 'description_too_short';
         }
         if ($len > $r['max_chars']) {
            $errorContext['max_chars'] = $r['max_chars'];
            return 'description_too_long';
         }
         if (self::hasSuspiciousSqlPattern($content)) {
            return 'suspicious_input';
         }
         if (!self::matchesCustomRegex($content, $r['validation_regex'])) {
            return 'invalid_format';
         }
         if ($r['restrict_gibberish'] === 1 && self::isGibberishUserInput($content)) {
            return 'gibberish_detected';
         }
      } elseif ($len > $r['max_chars']) {
         $errorContext['max_chars'] = $r['max_chars'];
         return 'description_too_long';
      }

      $files = self::collectUploadedFilesFromRequest();
      $n = count($files);
      $am = $r['attachments_mode'];

      if ($am === self::ATTACHMENTS_NONE) {
         if ($n > 0) {
            return 'file_not_allowed';
         }
         return null;
      }

      if ($n > $r['max_files']) {
         $errorContext['max_files'] = $r['max_files'];
         return 'too_many_files';
      }

      if ($am === self::ATTACHMENTS_MANDATORY && $n !== $r['max_files']) {
         $errorContext['max_files'] = $r['max_files'];
         return 'too_few_files';
      }

      $max_size = self::getMaxUploadSizeBytes();
      foreach ($files as $f) {
         if ($f['error'] !== UPLOAD_ERR_OK) {
            return 'upload_error';
         }
         if ($f['size'] > $max_size) {
            return 'too_large';
         }
         if (empty(Document::isValidDoc($f['name']))) {
            return 'invalid_type';
         }
         if (!self::isAllowedExtension($f['name'], $r['allowed_extensions'])) {
            return 'invalid_extension';
         }
      }

      return null;
   }

   public static function validateRequerimientoItem(CommonDBTM $item)
   {
      if (!($item instanceof PluginHelpxoraRequerimiento)) {
         return;
      }
      $delta = $item->input;
      if (!is_array($delta) || count($delta) === 0) {
         return;
      }

      $in = array_merge($item->fields, $delta);

      $min = (int)($in['min_chars'] ?? 10);
      $max = (int)($in['max_chars'] ?? 500);
      $am = (int)($in['attachments_mode'] ?? PluginHelpxoraRequirementValidator::ATTACHMENTS_NONE);
      $dm = (int)($in['description_mode'] ?? PluginHelpxoraRequirementValidator::DESCRIPTION_MANDATORY);
      $mf = (int)($in['max_files'] ?? 1);
      $regex = (string)($in['validation_regex'] ?? '');

      if ($min < 0) {
         $item->input = false;
         Session::addMessageAfterRedirect(__('Minimum character count cannot be negative.', 'helpxora'), false, ERROR);
         return;
      }
      if ($max < 1) {
         $item->input = false;
         Session::addMessageAfterRedirect(__('Maximum character count must be at least 1.', 'helpxora'), false, ERROR);
         return;
      }
      if ($min > $max) {
         $item->input = false;
         Session::addMessageAfterRedirect(__('Minimum characters cannot exceed maximum characters.', 'helpxora'), false, ERROR);
         return;
      }
      if ($mf < 1 || $mf > 10) {
         $item->input = false;
         Session::addMessageAfterRedirect(__('Maximum files must be between 1 and 10.', 'helpxora'), false, ERROR);
         return;
      }
      if (!self::isRegexPatternValid($regex)) {
         $item->input = false;
         Session::addMessageAfterRedirect(__('The validation regular expression is not valid.', 'helpxora'), false, ERROR);
         return;
      }
      if ($am < self::ATTACHMENTS_NONE || $am > self::ATTACHMENTS_OPTIONAL) {
         $item->input = false;
         Session::addMessageAfterRedirect(__('Invalid attachments mode for this requirement.', 'helpxora'), false, ERROR);
         return;
      }
      if ($dm < self::DESCRIPTION_NONE || $dm > self::DESCRIPTION_OPTIONAL) {
         $item->input = false;
         Session::addMessageAfterRedirect(__('Invalid description mode for this requirement.', 'helpxora'), false, ERROR);
         return;
      }
      if ($am === self::ATTACHMENTS_NONE && $dm === self::DESCRIPTION_NONE) {
         $item->input = false;
         Session::addMessageAfterRedirect(
            __('Enable at least one input channel (attachments or description) so users can send a response from the chat.', 'helpxora'),
            false,
            ERROR
         );
         return;
      }
   }
}
