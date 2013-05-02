#!/bin/bash

./upgrader.sh run-checks

./upgrader.sh cleanup-dbs
./upgrader.sh cleanup-files
./upgrader.sh install /home/tests/devel-suite/tools/scripts/release-testing/res/civicrm-3.2.5-drupal.tar.gz
./upgrader.sh upgrade /home/tests/devel-suite/tools/scripts/release-testing/res/civicrm-3.4.3-drupal.tar.gz