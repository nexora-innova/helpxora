<?php

include('../../../inc/includes.php');

Session::checkLoginUser();
Session::checkRight('config', UPDATE);

$consulta = new PluginHelpxoraConsulta();

function helpxoraProcessImages($existing_json = '[]') {
   $upload_dir = GLPI_ROOT . '/plugins/helpxora/pics/uploads/';
   if (!is_dir($upload_dir)) {
      @mkdir($upload_dir, 0755, true);
   }

   $images = json_decode($existing_json, true) ?: [];
   $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

   if (!empty($_FILES['consulta_images']['name'][0])) {
      foreach ($_FILES['consulta_images']['name'] as $i => $fname) {
         if (empty($fname) || $_FILES['consulta_images']['error'][$i] !== UPLOAD_ERR_OK) continue;
         $ext = strtolower(pathinfo($fname, PATHINFO_EXTENSION));
         if (!in_array($ext, $allowed_exts)) continue;
         $safe_name = uniqid('img_') . '.' . $ext;
         $dest = $upload_dir . $safe_name;
         if (move_uploaded_file($_FILES['consulta_images']['tmp_name'][$i], $dest)) {
            $images[] = 'pics/uploads/' . $safe_name;
         }
      }
   }

   return json_encode($images);
}

if (isset($_POST["add"])) {
   $_POST['images'] = helpxoraProcessImages('[]');
   $consulta->check(-1, CREATE, $_POST);
   $consulta->add($_POST);
   if (isset($_POST['_ajax_modal'])) {
       die("1");
   }
   Html::redirect(Plugin::getWebDir('helpxora') . '/front/config.php');
} else if (isset($_POST["update"])) {
   $_POST['images'] = helpxoraProcessImages($_POST['existing_images'] ?? '[]');
   $consulta->check($_POST["id"], UPDATE);
   $consulta->update($_POST);
   if (isset($_POST['_ajax_modal'])) {
       die("1");
   }
   Html::back();
} else if (isset($_POST["purge"])) {
   $consulta->check($_POST["id"], PURGE);
   $consulta->delete($_POST, 1);
   $consulta->redirectToList();
}

$is_ajax = isset($_REQUEST['_ajax_modal']);
if (!$is_ajax) {
    Html::header('HelpXora', $_SERVER['PHP_SELF'], "plugins", "helpxora");
}

$id = isset($_GET['id']) ? $_GET['id'] : -1;
$consulta->showForm($id, ['is_modal' => $is_ajax]);

if (!$is_ajax) {
    Html::footer();
}
