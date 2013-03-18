#!/usr/bin/env /bin/bash

source `dirname $0`/setup.conf

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
MYSQLCMD="mysql -u$DBUSER $PASSWDSECTION $DBARGS $DBNAME"
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

mysql -u$DBUSER $PASSWDSECTION $DBARGS $DBNAME < civicrm.mysql
mysql -u$DBUSER $PASSWDSECTION $DBARGS $DBNAME < civicrm_data.mysql
mysql -u$DBUSER $PASSWDSECTION $DBARGS $DBNAME < civicrm_sample.mysql
mysql -u$DBUSER $PASSWDSECTION $DBARGS $DBNAME < zipcodes.mysql
php GenerateData.php

# run the cli script to build the menu and the triggers
cd $CIVISOURCEDIR
"$PHP5PATH"php bin/cli.php -e System -a flush --triggers 1 --session 1

mysql -u$DBUSER $PASSWDSECTION $DBARGS $DBNAME -e "DROP TABLE zipcodes; UPDATE civicrm_domain SET config_backend = NULL; UPDATE civicrm_setting SET value = NULL WHERE name = 'userFrameworkResourceURL'  OR name = 'imageUploadURL';"

cd $CIVISOURCEDIR/sql
mysqldump -cent --skip-triggers -u $DBUSER $PASSWDSECTION $DBARGS $DBNAME > civicrm_generated.mysql
#cat civicrm_sample_report.mysql >> civicrm_generated.mysql
cat civicrm_sample_custom_data.mysql >> civicrm_generated.mysql
#cat civicrm_devel_config.mysql >> civicrm_generated.mysql
cat ../CRM/Case/xml/configuration.sample/SampleConfig.mysql >> civicrm_generated.mysql
cat civicrm_dummy_processor.mysql >> civicrm_generated.mysql
mysqladmin -f -u$DBUSER $PASSWDSECTION $DBARGS drop $DBNAME
mysqladmin -u$DBUSER $PASSWDSECTION $DBARGS create $DBNAME
mysql -u$DBUSER $PASSWDSECTION $DBARGS $DBNAME < civicrm.mysql
mysql -u$DBUSER $PASSWDSECTION $DBARGS $DBNAME < civicrm_generated.mysql
popd
