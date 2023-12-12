<?php

namespace XiMonitor;

use Exception;
use GuzzleHttp\Client;
use InvalidArgumentException;
use PDO;
use RuntimeException;
use function class_exists;
use function disk_free_space;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function is_array;
use function is_string;
use function is_writable;
use function json_decode;
use function method_exists;
use function rtrim;
use function strlen;
use function ucfirst;
use const JSON_THROW_ON_ERROR;
use const PHP_EOL;

/**
 *
 * @author isMolotov <web.id.molotov@gmail.com>
 */
class XiMonitor
{
    public const SEVERITY_HIGH = 'high';
    public const SEVERITY_LOW = 'low';

    /** @var string Initial log content for the OK state */
    private const LOG_RESPONSE_OK = 'OK';

    /**
     * Default `severityHighFilename` value
     */
    public const DEFAULT_SEVERITY_HIGH_LOG_FILENAME = 'xi-monitor-high.log';

    /**
     * Default `severityLowFilename` value
     */
    public const DEFAULT_SEVERITY_LOW_LOG_FILENAME = 'xi-monitor-low.log';

    private array $config;

    private function getMonitors()
    {
        return $this->config['monitors'];
    }

    private function getParamLogIsConcatErrors()
    {
        return $this->config['log']['concatErrors'] ?? false;
    }

    private function getParamLogSeverityHighFilename()
    {
        return $this->config['log']['severityHighFilename'] ?? self::DEFAULT_SEVERITY_HIGH_LOG_FILENAME;
    }

    private function getParamLogSeverityLowFilename()
    {
        return $this->config['log']['severityLowFilename'] ?? self::DEFAULT_SEVERITY_LOW_LOG_FILENAME;
    }

    private function generateLogFilePathBySeverity($severity)
    {
        $filename = $severity === self::SEVERITY_LOW
            ? $this->getParamLogSeverityLowFilename()
            : $this->getParamLogSeverityHighFilename();


        return $this->getParamLogDir() . '/' . $filename;
    }

    /**
     * Return directory path without right '/'
     * @return string|null
     */
    private function getParamLogDir()
    {
        $logDir = $this->config['log']['dir'] ?? null;

        if (!$logDir) {
            throw new InvalidArgumentException(
                '`Log` directory parameter is not set.'
            );
        }


        return rtrim($logDir, '/');
    }

    /**
     * @throws \JsonException
     */
    public function __construct($config)
    {
        if (is_string($config)) {
            $this->config = json_decode($config, true, 512, JSON_THROW_ON_ERROR);
        } elseif (is_array($config)) {
            $this->config = $config;
        } else {
            throw new InvalidArgumentException(
                'Provided `config` value is not expected json string or associative array.'
            );
        }

        $logDir = $this->getParamLogDir();

        $this->processMonitors([
            [
                'isDirWritable' => [
                    'severity' => 'high',
                    'dirs' => [
                        $logDir,
                    ],
                ],
            ]
        ]);
    }

    /**
     * Execute monitors tasks
     * @param $monitors
     * @return void
     */
    private function processMonitors($monitors): void
    {
        foreach ($monitors as $monitorTask) {
            foreach ($monitorTask as $taskId => $taskConfig) {
                try {
                    $taskMethodName = 'task' . ucfirst($taskId);
                    if (method_exists($this, $taskMethodName)) {
                        $this->$taskMethodName($taskConfig);
                    } else {
                        throw new InvalidArgumentException(
                            "Monitoring task with `$taskId` name was not found."
                        );
                    }
                } catch (Exception $e) {
                    $this->writeLogForSeverity($e->getMessage(), $taskConfig['severity']);
                }
            }
        }
    }

    /**
     * Run `monitors` tasks setup in the provided config file.
     * @return void
     */
    public function run()
    {
        $this->resetLogFilesContent();
        $this->processMonitors($this->getMonitors());
    }

    /**
     * @param string $severity
     */
    public function show(string $severity)
    {
        $filePath = $this->generateLogFilePathBySeverity($severity);
        if (file_exists($filePath)) {
            echo file_get_contents($filePath);
        } else {
            echo 'File not exists for severity ' . $severity;
        }
    }

    /**
     * @param string $message
     * @param string $severity
     */
    private function writeLogForSeverity(string $message, string $severity): void
    {
        $fileContent = @file_get_contents($this->generateLogFilePathBySeverity($severity));
        if ($fileContent !== self::LOG_RESPONSE_OK) {
            @file_put_contents($this->generateLogFilePathBySeverity($severity), $fileContent . PHP_EOL . $message);
        } else {
            @file_put_contents($this->generateLogFilePathBySeverity($severity), $message);
        }
    }

    /**
     * Reset state files content to the 'OK'.
     * @return void
     */
    private function resetLogFilesContent(): void
    {
        @file_put_contents($this->generateLogFilePathBySeverity(self::SEVERITY_HIGH), self::LOG_RESPONSE_OK);
        @file_put_contents($this->generateLogFilePathBySeverity(self::SEVERITY_LOW), self::LOG_RESPONSE_OK);
    }

    /**
     * Check is directory exists.
     * @param $monitorTask
     * @throws \Exception
     */
    private function taskIsDirExists($monitorTask): void
    {
        foreach ($monitorTask['dirs'] as $dir) {
            if (!file_exists($dir)) {
                throw new RuntimeException("Directory `$dir` doesn't exists.");
            }
        }
    }

    /**
     * Check is directory writable. Also, pre-check is directory exists.
     * @param $monitorTask
     * @throws \Exception
     */
    private function taskIsDirWritable($monitorTask): void
    {
        foreach ($monitorTask['dirs'] as $dir) {
            if (!file_exists($dir) || !is_writable($dir)) {
                throw new RuntimeException("Directory `$dir` is not writable.");
            }
        }
    }

    /**
     * Check is file exists.
     * @param $monitorTask
     * @throws \Exception
     */
    private function taskIsFileExists($monitorTask): void
    {
        foreach ($monitorTask['files'] as $file) {
            if (!file_exists($file)) {
                throw new RuntimeException("File `$file` doesn't exists.");
            }
        }
    }

    /**
     * Check is monitoring can connect to the DB by provided connection.
     * @param $monitorTask
     * @return void
     */
    private function taskIsDbConnectionAlive($monitorTask): void
    {
        $isLogDSNEnable = $monitorTask['logDSN'] ?? false;
        try {
            new PDO($monitorTask['dsn']);
        } catch (Exception $e) {
            throw new RuntimeException(
                'Fail to open DB connection' . ($isLogDSNEnable ? " for DSN: `{$monitorTask['dsn']}`" : '') . '.'
            );
        }
    }

    /**
     * Check is enough disk space.
     * @param $monitorTask
     * @throws \Exception
     */
    private function taskIsEnoughDiskSpace($monitorTask): void
    {
        $BYTES_IN_GB = 1073741824;
        $mountPath = $monitorTask['mountPath'] ?? '/';
        $minFreeSpaceGB = $monitorTask['minFreeSpaceGB'] ?? 0;

        $freeSpaceBytes = disk_free_space($mountPath);

        if (($freeSpaceBytes / $BYTES_IN_GB) < $minFreeSpaceGB) {
            throw new RuntimeException(
                "Free space for the mounted `$mountPath` is lower than `$minFreeSpaceGB GB` required."
            );
        }
    }

    /**
     * Run monitor task with using of outer class.
     * Class must support `interface` or `abstract` class provided with this library.
     * @param array $monitorTask
     * @return void
     * @throws \Exception
     */
    private function taskScriptExtension(array $monitorTask): void
    {
        try {
            $className = $monitorTask['className'];
            if (!class_exists($className)) {
                throw new RuntimeException(
                    "Provided class `$className` is not available or not declared."
                );
            }
            /** @var XiMonitorScriptInterface $testClass */
            $testScriptClass = new $className($monitorTask['config'] ?? []);
            $testScriptClass->run();
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage());
        }
    }

    /**
     * Made calls for the provided URL's with validation of the response.
     * @param array $monitorTask
     * @return void
     * @throws \Exception|\GuzzleHttp\Exception\GuzzleException
     */
    private function taskApiCalls(array $monitorTask): void
    {
        try {
            $client = new Client($monitorTask['clientParameters'] ?? []);
            foreach ($monitorTask['urls'] as $url) {
                $response = $client->get($url, $monitorTask['clientRequestOptions'] ?? []);

                $responseValidation = $monitorTask['responseValidation'];
                foreach ($responseValidation as $validationParam => $validationValue) {
                    switch ($validationParam) {
                        case 'responseCode':
                            if ($response->getStatusCode() !== $validationValue) {
                                throw new RuntimeException(
                                    "Api call for `$url` returns `$validationParam: {$response->getStatusCode()}` which is different from expected value `$validationValue`."
                                );
                            }
                            break;
                        case 'content-type':
                            $responseValue = $response->getHeaderLine($validationParam);
                            if ($responseValue !== $validationValue) {
                                throw new RuntimeException(
                                    "Api call for `$url` returns `$validationParam: $responseValue` which is different from expected value `$validationValue`."
                                );
                            }
                            break;
                        case 'isBodyEmpty':
                            $bodyLength = strlen($response->getBody()->getContents());
                            if ($validationValue && !$bodyLength) {
                                throw new RuntimeException(
                                    "Api call for `$url` returns not empty result (length: $bodyLength) for the request."
                                );
                            }
                            break;
                    }
                }
            }
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage());
        }
    }

}
