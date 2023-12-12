# xi-monitor
# XiMonitor

`XiMonitor` allows you to set up `monitoring` issue for your PHP projects.

`XiMonitor` have already defined popular monitoring tasks and also provide functionality
to extend it by creation of your own scripts with your scenario implemented within such classes. 


## Setup

### Config

Define monitoring tasks in the config [xi-monitor-cronjob.php](example-app%2Fapp%2Fxi-monitor-cronjob.php)


### Monitoring tasks executor

Example of executable script [test-cronjob.php](test-cronjob.php)

This script must be suited inside project scope. It will execute all task described in the config.

For constant monitoring you can add this script to the `cronjobs`.



### Monitoring state results

Example of monitoring script [test-public.php](test-public.php)

This script must be suited inside project scope. It must be `public` if you use it to get results via `http` protocol.



## Example Application

You can find project usage in the [example application](/example-app).   
