Example APP
=

This is an app example how monitoring can be setup within your project.

The main idea is that `XiMonitor` core functionality is managed via composer.

User can add any script to implement any of its own monitoring functionality.


File description:

    \
    +- app\
    !   +- xi-monitor-cronjob.php           <-- script to run monitoring jobs
    +- config\
    !   +- xi-monitor.conf.json             <-- monitoring configuration
    +- public\
    !   +- monitoring.php                   <-- public script to output current monitoring state
    +- src\
    !   +- MonitorSomethingImportant.php    <-- script with custom monitoring job
    +- composer.json                        <-- just project composer
