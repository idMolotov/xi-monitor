<?php

namespace XiMonitor;

interface XiMonitorScriptInterface
{

    public function setConfig($config);

    public function getConfig(): array;

    public function __construct($config);

    /**
     * @return void
     */
    public function run(): void;
}
