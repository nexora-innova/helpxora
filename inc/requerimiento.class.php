<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginHelpxoraRequerimiento extends CommonDBTM
{
   static $rightname = 'config';

   static function getTypeName($nb = 0)
   {
      return _n('Requerimiento', 'Requerimientos', $nb, 'helpxora');
   }

   public static function canCreate()
   {
      return Session::haveRight(self::$rightname, UPDATE);
   }

   public static function canView()
   {
      return Session::haveRight(self::$rightname, READ);
   }

   public static function canUpdate()
   {
      return Session::haveRight(self::$rightname, UPDATE);
   }

   public static function canDelete()
   {
      return Session::haveRight(self::$rightname, PURGE);
   }

   function prepareInputForAdd($input)
   {
      return $this->prepareInputForUpdate($input);
   }

   function prepareInputForUpdate($input)
   {
      if (!is_array($input)) {
         return $input;
      }
      if (array_key_exists('attachments_mode', $input)) {
         $v = (int)$input['attachments_mode'];
         if ($v < 0 || $v > 2) {
            $v = PluginHelpxoraRequirementValidator::ATTACHMENTS_NONE;
         }
         $input['attachments_mode'] = $v;
      }
      if (array_key_exists('description_mode', $input)) {
         $v = (int)$input['description_mode'];
         if ($v < 0 || $v > 2) {
            $v = PluginHelpxoraRequirementValidator::DESCRIPTION_MANDATORY;
         }
         $input['description_mode'] = $v;
      }
      return $input;
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
         $self->showFormList();
      }
      return true;
   }

   function showFormList()
   {
      global $DB;
      $result = $DB->request([
         'FROM' => 'glpi_plugin_helpxora_requerimientos',
         'ORDER' => 'id ASC'
      ]);

      $modal_id = "add_req_modal";

      echo "<div class='center'>";
      echo "<table class='tab_cadre_fixehov'>";

      echo "<tr class='noHover'><th colspan='6'>";
      echo "<button type='button' class='btn btn-primary' data-bs-toggle='modal' data-bs-target='#$modal_id' onclick='loadReqModal(-1)'>Añadir un Requerimiento</button>";
      echo "</th></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<th>ID</th>";
      echo "<th>Pregunta</th>";
      echo "<th>Tipo</th>";
      echo "<th>Categoría ITIL</th>";
      echo "<th>Activo</th>";
      echo "<th>Acciones</th>";
      echo "</tr>";
      foreach ($result as $data) {
         echo "<tr class='tab_bg_1'>";
         echo "<td class='center'>" . $data['id'] . "</td>";
         echo "<td>" . htmlspecialchars($data['question'], ENT_QUOTES, 'UTF-8') . "</td>";
         echo "<td class='center'>" . ($data['type'] == 1 ? 'Incidente' : 'Solicitud') . "</td>";

         $categoryName = "";
         if ($data['itilcategories_id'] > 0) {
            $cat = new ITILCategory();
            if ($cat->getFromDB($data['itilcategories_id'])) {
               $categoryName = $cat->fields['completename'];
            }
         }

         echo "<td class='center'>" . htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8') . "</td>";
         echo "<td class='center'>" . ($data['is_active'] ? 'Sí' : 'No') . "</td>";

         echo "<td class='center'>";
         echo "<button type='button' class='btn btn-sm btn-warning' title='Editar' data-bs-toggle='modal' data-bs-target='#$modal_id' onclick='loadReqModal(" . $data['id'] . ")'><i class='fas fa-edit'></i></button>";
         echo "</td>";
         echo "</tr>";
      }
      echo "</table>";
      echo "</div>";

      echo "<div class='modal fade' id='$modal_id' tabindex='-1' aria-labelledby='{$modal_id}Label' aria-hidden='true'>
        <div class='modal-dialog modal-lg modal-dialog-scrollable'>
          <div class='modal-content'>
            <div class='modal-header'>
              <h5 class='modal-title' id='{$modal_id}Label'>Añadir un Requerimiento</h5>
              <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
            </div>
            <div class='modal-body'>";

      echo "      </div>
          </div>
        </div>
      </div>";

      $form_url = Plugin::getWebDir('helpxora') . '/front/requerimiento.form.php';
      $dropdowns_url = Plugin::getWebDir('helpxora') . '/ajax/dropdowns.php';
      echo "<script>
      function loadReqModal(id) {
          var \$modal = $('#" . $modal_id . "');
          var \$body = \$modal.find('.modal-body');
          var _editorId = 'helpxora_req_custom_response_modal';
          if (typeof tinymce !== 'undefined' && tinymce.get(_editorId)) {
              tinymce.get(_editorId).remove();
          }
          $('#" . $modal_id . "Label').text(id > 0 ? 'Editar Requerimiento' : 'Añadir un Requerimiento');
          \$body.html('<div class=\"center\"><span class=\"spinner-border\"></span> Cargando...</div>');
          $.get('" . $form_url . "?_ajax_modal=1&id=' + id, function(data) {
              var \$wrap = \$('<div>').html(data);
              var scripts = [];
              \$wrap.find('script').each(function() { var t = (this.textContent || this.innerText || '').trim(); if (t) scripts.push(t); \$(this).remove(); });
              \$body.html(\$wrap.html());
              function runScripts(attempt) {
                  attempt = attempt || 0;
                  if (typeof window.tinyMCE !== 'undefined') {
                      scripts.forEach(function(s) { try { \$.globalEval(s); } catch (e) { console.warn(e); } });
                      return;
                  }
                  if (attempt < 100) setTimeout(function() { runScripts(attempt + 1); }, 100);
              }
              runScripts(0);
          });
      }
      </script>";
   }

   function showForm($ID, $options = [])
   {
      $this->initForm($ID, $options);

      $is_modal = isset($options['is_modal']) && $options['is_modal'];

      if ($is_modal) {
         echo "<form id='form_add_req' class='helpxora-modal-form'>";
         if ($ID > 0) {
            echo "<input type='hidden' name='id' value='$ID'>";
         }
         echo "<input type='hidden' name='_glpi_csrf_token' value='" . Session::getNewCSRFToken() . "'>";
         echo "<table class='tab_cadre_fixe' style='width: 100%;'>";
      }
      else {
         $this->showFormHeader($options);
      }

      echo "<tr class='tab_bg_1'>";
      echo "<td>Pregunta (Mostrada al usuario)</td>";
      echo "<td><input type='text' name='question' value='" . Html::cleanInputText($this->fields['question'] ?? '') . "' size='80'></td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>Tipo de Ticket</td>";
      echo "<td>";
      Dropdown::showFromArray('type', [1 => 'Incidente', 2 => 'Solicitud'], ['value' => $this->fields['type'] ?? 1, 'id' => 'dropdown_req_type']);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>Canal de atención</td>";
      echo "<td>";
      RequestType::dropdown(['name' => 'requesttypes_id', 'value' => $this->fields['requesttypes_id'] ?? 0]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>Grupo de GLPI (solo asignables)</td>";
      echo "<td>";
      Group::dropdown(['name' => 'groups_id', 'value' => $this->fields['groups_id'] ?? 0, 'condition' => ['is_assign' => 1]]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>Categoría ITIL</td>";
      echo "<td id='container_itilcategories_id'>";
      
      $condition = [];
      $type_val = $this->fields['type'] ?? 1;
      if ($type_val == 1) {
          $condition['is_incident'] = 1;
      } else {
          $condition['is_request'] = 1;
      }
      
      ITILCategory::dropdown([
          'name' => 'itilcategories_id', 
          'value' => $this->fields['itilcategories_id'] ?? 0,
          'condition' => $condition
      ]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>Respuesta personalizada</td>";
      echo "<td>";
      Html::textarea([
         'name' => 'custom_response',
         'value' => $this->fields['custom_response'] ?? '',
         'enable_richtext' => true,
         'editor_id' => $is_modal ? 'helpxora_req_custom_response_modal' : 'helpxora_req_custom_response_' . mt_rand()
      ]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>Activo</td>";
      echo "<td>";
      Dropdown::showYesNo('is_active', $this->fields['is_active'] ?? 1);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'><th colspan='2'>Validaciones y Control de Entrada</th></tr>";

      $attachments_mode_labels = [
         (string)PluginHelpxoraRequirementValidator::ATTACHMENTS_NONE      => __('Attachments: none', 'helpxora'),
         (string)PluginHelpxoraRequirementValidator::ATTACHMENTS_MANDATORY => __('Attachments: required (exactly the maximum)', 'helpxora'),
         (string)PluginHelpxoraRequirementValidator::ATTACHMENTS_OPTIONAL  => __('Attachments: optional (up to the maximum)', 'helpxora'),
      ];
      $description_mode_labels = [
         (string)PluginHelpxoraRequirementValidator::DESCRIPTION_NONE      => __('Description: none (no text field)', 'helpxora'),
         (string)PluginHelpxoraRequirementValidator::DESCRIPTION_MANDATORY => __('Description: required', 'helpxora'),
         (string)PluginHelpxoraRequirementValidator::DESCRIPTION_OPTIONAL  => __('Description: optional', 'helpxora'),
      ];
      $am_val = (int)($this->fields['attachments_mode'] ?? PluginHelpxoraRequirementValidator::ATTACHMENTS_NONE);
      if ($am_val < 0 || $am_val > 2) {
         $am_val = PluginHelpxoraRequirementValidator::ATTACHMENTS_NONE;
      }
      $dm_val = (int)($this->fields['description_mode'] ?? PluginHelpxoraRequirementValidator::DESCRIPTION_MANDATORY);
      if ($dm_val < 0 || $dm_val > 2) {
         $dm_val = PluginHelpxoraRequirementValidator::DESCRIPTION_MANDATORY;
      }

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Attachments policy', 'helpxora') . "</td>";
      echo "<td>";
      Dropdown::showFromArray('attachments_mode', $attachments_mode_labels, ['value' => $am_val]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1 helpxora-req-row-attachments'>";
      echo "<td>" . __('Maximum number of attachments', 'helpxora') . "</td>";
      echo "<td>";
      Dropdown::showNumber('max_files', ['value' => $this->fields['max_files'] ?? 1, 'min' => 1, 'max' => 10]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1 helpxora-req-row-attachments'>";
      echo "<td>" . __('Extensions (comma-separated)', 'helpxora') . "</td>";
      echo "<td><input type='text' id='helpxora_allowed_extensions_input' class='form-control' name='allowed_extensions' value='" . Html::cleanInputText($this->fields['allowed_extensions'] ?? '') . "' size='80' placeholder='pdf, png, docx…'></td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Description policy', 'helpxora') . "</td>";
      echo "<td>";
      Dropdown::showFromArray('description_mode', $description_mode_labels, ['value' => $dm_val]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1 helpxora-req-row-description'>";
      echo "<td>" . __('Minimum characters (when description is shown)', 'helpxora') . "</td>";
      echo "<td><input type='number' name='min_chars' value='" . (int)($this->fields['min_chars'] ?? 10) . "' min='0'></td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1 helpxora-req-row-description'>";
      echo "<td>" . __('Maximum characters', 'helpxora') . "</td>";
      echo "<td><input type='number' name='max_chars' value='" . (int)($this->fields['max_chars'] ?? 500) . "' min='1'></td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1 helpxora-req-row-description'>";
      echo "<td>Regex personalizada (Opcional)</td>";
      echo "<td><input type='text' name='validation_regex' value='" . Html::cleanInputText($this->fields['validation_regex'] ?? '') . "' size='80' placeholder='/^[a-zA-Z0-9\s]+$/'></td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1 helpxora-req-row-description'>";
      echo "<td>Restringir texto sin sentido (Anti-Gibberish)</td>";
      echo "<td>";
      Dropdown::showYesNo('restrict_gibberish', $this->fields['restrict_gibberish'] ?? 0);
      echo "</td>";
      echo "</tr>";

      if ($is_modal) {
         echo "<tr><td colspan='2' class='center'>";
         $submit_name = ($ID > 0) ? 'update' : 'add';
         echo "<input type='submit' class='btn btn-primary' name='$submit_name' value='Guardar'>";
         echo "</td></tr>";
         echo "</table>";
         echo "</form>";

         $dropdowns_url = Plugin::getWebDir('helpxora') . '/ajax/dropdowns.php';
         echo "<script>
         $(function() {
            var formTarget = $('#form_add_req');
            formTarget.off('change', 'select[name=type]').on('change', 'select[name=type]', function() {
                var newType = $(this).val();
                var currentVal = formTarget.find('select[name=itilcategories_id]').val() || '0';
                $.ajax({
                    url: '" . $dropdowns_url . "',
                    type: 'GET',
                    data: {
                        action: 'get_categories',
                        type: newType,
                        value: currentVal
                    },
                    success: function(html) {
                        formTarget.find('#container_itilcategories_id').html(html);
                    }
                });
            });

            function helpxoraToggleReqValidationRowsHelpXora(\$f) {
                var am = parseInt(\$f.find('select[name=\"attachments_mode\"]').val(), 10) || 0;
                var dm = parseInt(\$f.find('select[name=\"description_mode\"]').val(), 10) || 0;
                \$f.find('.helpxora-req-row-attachments').toggle(am !== 0);
                \$f.find('.helpxora-req-row-description').toggle(dm !== 0);
            }
            formTarget.on('change', 'select[name=attachments_mode], select[name=description_mode]', function() {
                helpxoraToggleReqValidationRowsHelpXora(formTarget);
            });
            helpxoraToggleReqValidationRowsHelpXora(formTarget);

            formTarget.off('submit').on('submit', function(e) {
                e.preventDefault();
                if (typeof tinymce !== 'undefined') { tinymce.triggerSave(); }
                var btn = \$(this).find('input[type=\"submit\"]');
                btn.prop('disabled', true);
                var data = \$(this).serialize() + '&_ajax_modal=1&' + btn.attr('name') + '=1';
                
                $.ajax({
                    type: 'POST',
                    url: '" . Plugin::getWebDir('helpxora') . "/front/requerimiento.form.php',
                    data: data,
                    success: function(res) {
                        var m = document.getElementById('add_req_modal');
                        if (m && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                            var inst = bootstrap.Modal.getInstance(m);
                            if (inst) inst.hide();
                        }
                        $('.modal-backdrop').remove();
                        $('body').removeClass('modal-open');
                        window.location.reload();
                    },
                    error: function() {
                        btn.prop('disabled', false);
                        alert('Error al guardar el registro.');
                    }
                });
            });

         });
         </script>";
      }
      else {
         $this->showFormButtons($options);
         echo "<script>
         $(function() {
            var \$f = \$('select[name=\"attachments_mode\"]').closest('form');
            if (!\$f.length) {
               return;
            }
            function helpxoraToggleReqValidationRowsHelpXora(\$form) {
               var am = parseInt(\$form.find('select[name=\"attachments_mode\"]').val(), 10) || 0;
               var dm = parseInt(\$form.find('select[name=\"description_mode\"]').val(), 10) || 0;
               \$form.find('.helpxora-req-row-attachments').toggle(am !== 0);
               \$form.find('.helpxora-req-row-description').toggle(dm !== 0);
            }
            \$f.on('change', 'select[name=attachments_mode], select[name=description_mode]', function() {
               helpxoraToggleReqValidationRowsHelpXora(\$f);
            });
            helpxoraToggleReqValidationRowsHelpXora(\$f);
         });
         </script>";
      }
      return true;
   }

   function post_addItem()
   {
       PluginHelpxoraLog::logAction('create', __CLASS__, $this->fields['id']);
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
