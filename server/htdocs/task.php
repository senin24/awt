<?php
require_once '../web_construction_set/autoload.php';
header('Content-Type: application/json');
\Config::$rootPath = __DIR__ . '/';
$t = new \AdvancedWebTesting\Task();
$t->run();