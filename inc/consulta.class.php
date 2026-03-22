<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginHelpxoraConsulta extends CommonDBTM
{
   static $rightname = 'config';

   static function getTypeName($nb = 0)
   {
      return _n('Consulta', 'Consultas', $nb, 'helpxora');
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
         'FROM' => 'glpi_plugin_helpxora_consultas',
         'ORDER' => 'id ASC'
      ]);

      $modal_id = "add_consulta_modal";

      echo "<div class='center'>";
      echo "<table class='tab_cadre_fixehov'>";

      echo "<tr class='noHover'><th colspan='5'>";
      echo "<button type='button' class='btn btn-primary' data-bs-toggle='modal' data-bs-target='#$modal_id' onclick='loadConsultaModal(-1)'>Añadir una Consulta</button>";
      echo "</th></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<th>ID</th>";
      echo "<th>Pregunta</th>";
      echo "<th>Activo</th>";
      echo "<th>Acciones</th>";
      echo "</tr>";
      foreach ($result as $data) {
         echo "<tr class='tab_bg_1'>";
         echo "<td class='center'>" . $data['id'] . "</td>";
         $qplain = trim(html_entity_decode(strip_tags((string)($data['question'] ?? '')), ENT_QUOTES, 'UTF-8'));
         if (Toolbox::strlen($qplain) > 120) {
            $qplain = Toolbox::substr($qplain, 0, 117) . '…';
         }
         echo "<td>" . htmlspecialchars($qplain, ENT_QUOTES, 'UTF-8') . "</td>";
         echo "<td class='center'>" . ($data['is_active'] ? 'Sí' : 'No') . "</td>";

         echo "<td class='center'>";
         echo "<button type='button' class='btn btn-sm btn-warning' title='Editar' data-bs-toggle='modal' data-bs-target='#$modal_id' onclick='loadConsultaModal(" . $data['id'] . ")'><i class='fas fa-edit'></i></button>";
         echo "</td>";
         echo "</tr>";
      }
      echo "</table>";
      echo "</div>";

      echo "<div class='modal fade' id='$modal_id' tabindex='-1' aria-labelledby='{$modal_id}Label' aria-hidden='true'>
        <div class='modal-dialog modal-lg modal-dialog-scrollable'>
          <div class='modal-content'>
            <div class='modal-header'>
              <h5 class='modal-title' id='{$modal_id}Label'>Añadir una Consulta</h5>
              <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
            </div>
            <div class='modal-body'>";

      echo "      </div>
          </div>
        </div>
      </div>";

      $form_url = Plugin::getWebDir('helpxora') . "/front/consulta.form.php";
      echo "<script>
      function loadConsultaModal(id) {
          var \$body = $('#" . $modal_id . " .modal-body');
          if (typeof tinymce !== 'undefined' && tinymce.get('helpxora_consulta_answer_modal')) {
              tinymce.get('helpxora_consulta_answer_modal').remove();
          }
          $('#" . $modal_id . "Label').text(id > 0 ? 'Editar Consulta' : 'Añadir una Consulta');
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
         echo "<form id='form_add_consulta'>";
         if ($ID > 0) {
            echo "<input type='hidden' name='id' value='$ID'>";
         }
         echo "<input type='hidden' name='_glpi_csrf_token' value='" . Session::getNewCSRFToken() . "'>";
         echo "<table class='tab_cadre_fixe'>";
      }
      else {
         $this->showFormHeader($options);
      }

      echo "<tr class='tab_bg_1'>";
      echo "<td>Pregunta</td>";
      echo "<td>";
      $q_val = htmlspecialchars(strip_tags($this->fields['question'] ?? ''), ENT_QUOTES);
      echo "<input type='text' name='question' value='{$q_val}' class='form-control' style='width:100%;'>";
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>Respuesta (Soporta HTML básico)</td>";
      echo "<td>";
      Html::textarea([
         'name' => 'answer',
         'value' => $this->fields['answer'] ?? '',
         'enable_richtext' => true,
         'editor_id' => 'helpxora_consulta_answer_' . ($is_modal ? 'modal' : mt_rand()),
         'rows' => 6
      ]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>Imágenes (Opcional)</td>";
      echo "<td>";

      $existing_images = [];
      if ($ID > 0 && !empty($this->fields['images'])) {
         $existing_images = json_decode($this->fields['images'], true) ?: [];
      }

      if (!empty($existing_images)) {
         $plugin_web = Plugin::getWebDir('helpxora');
         echo "<div id='helpxora_images_preview' style='display:flex;flex-wrap:wrap;gap:8px;margin-bottom:10px;'>";
         foreach ($existing_images as $img_path) {
            $web_url = $plugin_web . '/' . ltrim($img_path, '/');
            echo "<div class='helpxora-img-thumb' style='position:relative;display:inline-block;'>";
            echo "<img src='" . htmlspecialchars($web_url) . "' style='width:80px;height:80px;object-fit:cover;border-radius:4px;border:1px solid #ddd;'>";
            echo "<button type='button' onclick='helpxoraRemoveImage(this)' data-path='" . htmlspecialchars($img_path) . "' style='position:absolute;top:-6px;right:-6px;background:#dc3545;color:white;border:none;border-radius:50%;width:18px;height:18px;font-size:10px;padding:0;line-height:18px;cursor:pointer;'>✕</button>";
            echo "</div>";
         }
         echo "</div>";
      }

      echo "<input type='hidden' id='helpxora_existing_images' name='existing_images' value='" . htmlspecialchars(json_encode($existing_images)) . "'>";
      echo "<input type='file' name='consulta_images[]' multiple accept='image/jpeg,image/png,image/gif,image/webp' class='form-control' style='margin-top:4px;'>";
      echo "<small class='text-muted' style='display:block;margin-top:4px;'>Formatos aceptados: JPG, PNG, GIF, WebP. Puede seleccionar varias imágenes.</small>";
      echo "<script>
      function helpxoraRemoveImage(btn) {
          var path = btn.getAttribute('data-path');
          var inp = document.getElementById('helpxora_existing_images');
          var imgs = JSON.parse(inp.value || '[]');
          imgs = imgs.filter(function(p) { return p !== path; });
          inp.value = JSON.stringify(imgs);
          btn.closest('.helpxora-img-thumb').remove();
      }
      </script>";
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>Activo</td>";
      echo "<td>";
      Dropdown::showYesNo('is_active', $this->fields['is_active'] ?? 1);
      echo "</td>";
      echo "</tr>";

      if ($is_modal) {
         echo "<tr><td colspan='2' class='center'>";
         $submit_name = ($ID > 0) ? 'update' : 'add';
         echo "<input type='submit' class='btn btn-primary' name='$submit_name' value='Guardar'>";
         echo "</td></tr>";
         echo "</table>";
         echo "</form>";

         echo "<script>
         $(function() {
            var formTarget = $('#form_add_consulta');

            formTarget.off('submit').on('submit', function(e) {
                e.preventDefault();
                if (typeof tinymce !== 'undefined') { tinymce.triggerSave(); }
                var btn = \$(this).find('input[type=\"submit\"]');
                btn.prop('disabled', true);
                var fd = new FormData(this);
                fd.append('_ajax_modal', '1');
                fd.append(btn.attr('name'), '1');

                $.ajax({
                    type: 'POST',
                    url: '" . Plugin::getWebDir('helpxora') . "/front/consulta.form.php',
                    data: fd,
                    processData: false,
                    contentType: false,
                    success: function(res) {
                        var m = document.getElementById('add_consulta_modal');
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
