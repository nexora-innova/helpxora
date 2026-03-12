<?php

include('../../../inc/includes.php');

Session::checkLoginUser();
Session::checkRight('config', UPDATE);

$config = new PluginHelpxoraConfig();

if (isset($_POST["update"])) {
   $config->check($_POST["id"], UPDATE);
   $config->update($_POST);
   Html::back();
} else if (isset($_POST["add"])) {
   $config->check(-1, CREATE, $_POST);
   $config->add($_POST);
   Html::back();
}

Html::displayErrorAndDie("Lost");
