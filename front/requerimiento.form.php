<?php

include('../../../inc/includes.php');

Session::checkLoginUser();
Session::checkRight('config', UPDATE);

$req = new PluginHelpxoraRequerimiento();

if (isset($_POST["add"])) {
   $req->check(-1, CREATE, $_POST);
   $req->add($_POST);
   if (isset($_POST['_ajax_modal'])) {
       die("1");
   }
   Html::redirect(Plugin::getWebDir('helpxora') . '/front/config.php');
} else if (isset($_POST["update"])) {
   $req->check($_POST["id"], UPDATE);
   $req->update($_POST);
   if (isset($_POST['_ajax_modal'])) {
       die("1");
   }
   Html::back();
} else if (isset($_POST["purge"])) {
   $req->check($_POST["id"], PURGE);
   $req->delete($_POST, 1);
   $req->redirectToList();
}

$is_ajax = isset($_REQUEST['_ajax_modal']);
if (!$is_ajax) {
    Html::header('HelpXora', $_SERVER['PHP_SELF'], "plugins", "helpxora");
}

$id = isset($_GET['id']) ? $_GET['id'] : -1;
$req->showForm($id, ['is_modal' => $is_ajax]);

if (!$is_ajax) {
    Html::footer();
}
