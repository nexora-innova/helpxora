<?php

include('../../../inc/includes.php');

Session::checkLoginUser();
Session::checkRight('config', READ);

Html::requireJs('tinymce');

Html::header('HelpXora', $_SERVER['PHP_SELF'], "plugins", "helpxora");

$config = new PluginHelpxoraConfig();
$config->display(['id' => 1]);

echo Html::scriptBlock(<<<'JS'
document.addEventListener('focusin', function (e) {
   var t = e.target;
   if (!t || typeof t.closest !== 'function') {
      return;
   }
   if (t.closest('.tox-tinymce-aux, .tox-dialog, .tox-dialog-wrap, .moxman-window, .tam-assetmanager-root')) {
      e.stopImmediatePropagation();
   }
}, true);
JS
);

echo '<style id="helpxora-admin-modal-tinymce">
body.modal-open .tox-tinymce-aux,
body.modal-open .tox-dialog-wrap,
body.modal-open .tox-dialog { z-index: 10060 !important; }
</style>';

Html::footer();
