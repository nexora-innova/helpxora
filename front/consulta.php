<?php

include('../../../inc/includes.php');

Session::checkLoginUser();
Session::checkRight('config', READ);

Html::header('Chatbot', $_SERVER['PHP_SELF'], "plugins", "chatbot");

$consulta = new PluginChatbotConsulta();
$consulta->display($_GET);

Html::footer();
