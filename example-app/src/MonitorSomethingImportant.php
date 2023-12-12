<?php

namespace CustomMonitor;

use Exception;
use PDO;
use RuntimeException;
use XiMonitor\XiMonitorScriptAbstract;

class MonitorSomethingImportant extends XiMonitorScriptAbstract
{
    /**
     * @inheritDoc
     */
    public function run(): void
    {
        try {
            $config = $this->getConfig();
            $db = new PDO($config['dsn']);
            $query = <<<SQL
SELECT count(1) AS updateditemscount FROM sometable WHERE updated_at > NOW() - INTERVAL '24 HOURS'
SQL;

            $queryResult = $db->query($query);
            if (!$queryResult) {
                throw new RuntimeException('Fail to get valid answer for the query.');
            }

            $result = $queryResult->fetch(PDO::FETCH_ASSOC);

            if (!$result || !$result['updateditemscount']) {
                throw new RuntimeException('Items were not updated for the last 24 hours.');
            }
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage());
        }
    }
}
