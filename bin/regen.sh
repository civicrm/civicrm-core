#!/usr/bin/env /bin/bash
set -e
set -x

source `dirname $0`/setup.conf
source `dirname $0`/setup.lib.sh

# someone might want to use empty password for development,
# let's make it possible - we asked before.
if [ -z $DBPASS ]; then # password still empty
  PASSWDSECTION=""
else
  PASSWDSECTION="-p$DBPASS"
fi

pushd .
cd $CIVISOURCEDIR
# svn up .
cd $CIVISOURCEDIR/bin
./setup.sh
cd $CIVISOURCEDIR/sql

echo; echo "Dropping civicrm_* tables from database $DBNAME"
# mysqladmin -f -u $DBUSER $PASSWDSECTION $DBARGS drop $DBNAME
MYSQLCMD=$(mysql_cmd)
MYSQLADMCMD=$(mysqladmin_cmd)
MYSQLDUMP=$(mysqldump_cmd)
echo "SELECT table_name FROM information_schema.TABLES  WHERE TABLE_SCHEMA='${DBNAME}' AND TABLE_TYPE = 'VIEW'" \
  | $MYSQLCMD \
  | grep '^\(civicrm_\|log_civicrm_\)' \
  | awk -v NOFOREIGNCHECK='SET FOREIGN_KEY_CHECKS=0;' 'BEGIN {print NOFOREIGNCHECK}{print "drop view " $1 ";"}' \
  | $MYSQLCMD
echo "SELECT table_name FROM information_schema.TABLES  WHERE TABLE_SCHEMA='${DBNAME}' AND TABLE_TYPE = 'BASE TABLE'" \
  | $MYSQLCMD \
  | grep '^\(civicrm_\|log_civicrm_\)' \
  | awk -v NOFOREIGNCHECK='SET FOREIGN_KEY_CHECKS=0;' 'BEGIN {print NOFOREIGNCHECK}{print "drop table " $1 ";"}' \
  | $MYSQLCMD

$MYSQLCMD < civicrm.mysql
$MYSQLCMD < civicrm_data.mysql
$MYSQLCMD < civicrm_sample.mysql
$MYSQLCMD < zipcodes.mysql
php GenerateData.php

# run the cli script to build the menu and the triggers
cd $CIVISOURCEDIR
"$PHP5PATH"php bin/cli.php -e System -a flush --triggers 1 --session 1

$MYSQLCMD -e "DROP TABLE zipcodes; UPDATE civicrm_domain SET config_backend = NULL; UPDATE civicrm_setting SET value = NULL WHERE name = 'userFrameworkResourceURL'  OR name = 'imageUploadURL';"

cd $CIVISOURCEDIR/sql
$MYSQLDUMP -cent --skip-triggers $DBNAME > civicrm_generated.mysql
#cat civicrm_sample_report.mysql >> civicrm_generated.mysql
cat civicrm_sample_custom_data.mysql >> civicrm_generated.mysql
#cat civicrm_devel_config.mysql >> civicrm_generated.mysql
cat civicrm_dummy_processor.mysql >> civicrm_generated.mysql
$MYSQLADMCMD -f drop $DBNAME
$MYSQLADMCMD create $DBNAME
$MYSQLCMD < civicrm.mysql
$MYSQLCMD < civicrm_generated.mysql
popd
