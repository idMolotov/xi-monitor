<?php
/**
 * Script to execute monitoring tasks
 */

require_once __DIR__ . "/../vendor/autoload.php";

$config = file_get_contents(__DIR__ . '/../config/xi-monitor.conf.json');

$xiMonitor = new XiMonitor\XiMonitor($config);
$xiMonitor->run();
