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

## For first boot on fresh DB, boot CMS before CRM.
cms_eval 'civicrm_initialize();'

php GenerateData.php

## Prune local data
$MYSQLCMD -e "DROP TABLE IF EXISTS civicrm_install_canary; DELETE FROM civicrm_cache; DELETE FROM civicrm_setting;"
$MYSQLCMD -e "DELETE FROM civicrm_extension WHERE full_name NOT IN ('sequentialcreditnotes', 'eventcart', 'greenwich', 'org.civicrm.search_kit', 'org.civicrm.afform', 'authx', 'org.civicrm.flexmailer', 'financialacls', 'contributioncancelactions', 'recaptcha', 'ckeditor4', 'legacycustomsearches', 'civiimport', 'message_admin', 'legacydedupefinder') and full_name NOT LIKE 'civi_%';"
TABLENAMES=$( echo "show tables like 'civicrm_%'" | $MYSQLCMD | grep ^civicrm_ | xargs )

cd $CIVISOURCEDIR/sql

$MYSQLDUMP -cent --skip-triggers $DBNAME $TABLENAMES > civicrm_generated.mysql
#cat civicrm_sample_report.mysql >> civicrm_generated.mysql
cat civicrm_sample_custom_data.mysql >> civicrm_generated.mysql
#cat civicrm_devel_config.mysql >> civicrm_generated.mysql
cat civicrm_dummy_processor.mysql >> civicrm_generated.mysql
# adapted from https://bugs.mysql.com/bug.php?id=65465
sed -i -e 's/VALUES (/VALUES\n (/g' civicrm_generated.mysql
sed -i -e 's/),(\|), (/),\n (/g' civicrm_generated.mysql
$MYSQLADMCMD -f drop $DBNAME
$MYSQLADMCMD create $DBNAME
$MYSQLCMD < civicrm.mysql
$MYSQLCMD < civicrm_generated.mysql
popd
