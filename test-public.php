<?php

require_once __DIR__ . "/vendor/autoload.php";

$config = file_get_contents(__DIR__ . '/config/xi-monitor.conf.json');

$xiMonitor = new XiMonitor\XiMonitor($config);

$severity = XiMonitor\XiMonitor::SEVERITY_HIGH;

if (
    !empty($_GET['severity'])
    && in_array(
        $_GET['severity'],
        [XiMonitor\XiMonitor::SEVERITY_HIGH, XiMonitor\XiMonitor::SEVERITY_LOW],
        true
    )
) {
    $severity = $_GET['severity'];
}

$xiMonitor->show($severity);

