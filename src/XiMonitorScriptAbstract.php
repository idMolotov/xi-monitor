<?php

namespace XiMonitor;

abstract class XiMonitorScriptAbstract implements XiMonitorScriptInterface
{
    /** @var array configuration values */
    private array $config;

    public function setConfig($config)
    {
        $this->config = $config;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function __construct($config)
    {
        $this->setConfig($config);
    }

    /**
     * @inheritDoc
     */
    abstract public function run(): void;
}
