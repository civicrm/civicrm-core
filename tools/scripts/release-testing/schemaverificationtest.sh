#!/bin/bash

./upgrader.sh run-checks
./upgrader.sh cleanup-dbs
./upgrader.sh cleanup-files

./upgrader.sh install /home/michau/Downloads/civicrm-3.4.1-drupal.tar.gz
./upgrader.sh dumpschema-cividb 3.4.1-clean.mysql

./upgrader.sh cleanup-dbs
./upgrader.sh cleanup-files

./upgrader.sh install /home/michau/Downloads/civicrm-3.3.0-drupal.tar.gz
./upgrader.sh upgrade /home/michau/Downloads/civicrm-3.4.1-drupal.tar.gz
./upgrader.sh dumpschema-cividb 3.4.1-upgraded.mysql

diff 3.4.1-clean.mysql 3.4.1-upgraded.mysql