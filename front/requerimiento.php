<?php

include('../../../inc/includes.php');

Session::checkLoginUser();
Session::checkRight('config', READ);

Html::header('Chatbot', $_SERVER['PHP_SELF'], "plugins", "chatbot");

$req = new PluginChatbotRequerimiento();
$req->display($_GET);

Html::footer();
