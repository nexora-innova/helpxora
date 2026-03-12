<?php

include('../../../inc/includes.php');

Session::checkLoginUser();
Session::checkRight('config', READ);

Html::requireJs('tinymce');

Html::header('HelpXora', $_SERVER['PHP_SELF'], "plugins", "helpxora");

$config = new PluginHelpxoraConfig();
$config->display(['id' => 1]);

echo Html::scriptBlock("
   \$(function() {
      \$('#tabspanel a[data-bs-toggle=\"tab\"], #tabspanel a[data-toggle=\"tab\"]').filter(function() {
         return \$.trim(\$(this).text()) === 'General';
      }).closest('li, .list-group-item').hide();
   });
");

Html::footer();
