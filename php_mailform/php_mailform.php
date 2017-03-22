<?php
require_once "./phplib/mailform/ff_mailform.php";
$controller = new ff_mailform("config.yml");
$controller->run();
