#!/usr/bin/env bash
set -e
set -x

CALLEDPATH=`dirname $0`

# Convert to an absolute path if necessary
case "$CALLEDPATH" in
  .*)
    CALLEDPATH="$PWD/$CALLEDPATH"
    ;;
esac

if [ ! -f "$CALLEDPATH/setup.conf" ]; then
  echo
  echo "Missing configuration file. Please copy $CALLEDPATH/setup.conf.txt to $CALLEDPATH/setup.conf and edit it."
  exit 1
fi

source "$CALLEDPATH/setup.conf"
source "$CALLEDPATH/setup.lib.sh"

if [ "$1" = '-h' ] || [ "$1" = '--help' ]; then
  echo; echo Usage: setup.sh [schema file] [database data file] [database name] [database user] [database password] [database host] [database port] [additional args]; echo
  exit 0
fi


# fetch command line arguments if available
if [ ! -z "$1" ] ; then SCHEMA="$1"; fi
if [ ! -z "$2" ] ; then DBLOAD="$2"; fi
if [ ! -z "$3" ] ; then DBNAME="$3"; fi
if [ ! -z "$4" ] ; then DBUSER="$4"; fi
if [ ! -z "$5" ] ; then DBPASS="$5"; fi
if [ ! -z "$6" ] ; then DBHOST="$6"; fi
if [ ! -z "$7" ] ; then DBPORT="$7"; fi

# verify if we have at least DBNAME given
if [ -z $DBNAME ] ; then
  echo "No database name defined!"
  exit 1
fi
if [ -z $DBUSER ] ; then
  echo "No database username defined!"
  exit 1
fi
if [ -z $DBPASS ] ; then
  read -p "Database password:"
  DBPASS=$REPLY
fi

# run code generator if it's there - which means it's
# checkout, not packaged code
if [ -d "$CALLEDPATH/../xml" ]; then
  cd "$CALLEDPATH/../xml"
  if [ -z "$DBPORT" ]; then
    PHP_MYSQL_HOSTPORT="$DBHOST"
  else
    PHP_MYSQL_HOSTPORT="$DBHOST:$DBPORT"
  fi
  "$PHP5PATH"php -d mysql.default_host="$PHP_MYSQL_HOSTPORT" -d mysql.default_user=$DBUSER -d mysql.default_password=$DBPASS GenCode.php $SCHEMA '' ${GENCODE_CMS}
fi

cd "$CALLEDPATH/../sql"
echo; echo "Dropping civicrm_* tables from database $DBNAME"
# mysqladmin -f -u $DBUSER $PASSWDSECTION $DBARGS drop $DBNAME
MYSQLCMD=$(mysql_cmd)
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


echo; echo Creating database structure
$MYSQLCMD < civicrm.mysql

# load civicrm_generated.mysql sample data unless special DBLOAD is passed
if [ -z $DBLOAD ]; then
    echo; echo Populating database with example data - civicrm_generated.mysql
    $MYSQLCMD < civicrm_generated.mysql
else
    echo; echo Populating database with required data - civicrm_data.mysql
    $MYSQLCMD < civicrm_data.mysql
    echo; echo Populating database with $DBLOAD data
    $MYSQLCMD < $DBLOAD
fi

# load additional script if DBADD defined
if [ ! -z $DBADD ]; then
    echo; echo Loading $DBADD
    $MYSQLCMD < $DBADD
fi

# run the cli script to build the menu and the triggers
cd "$CALLEDPATH/.."
"$PHP5PATH"php bin/cli.php -e System -a flush --triggers 1 --session 1

# reset config_backend and userFrameworkResourceURL which gets set
# when config object is initialized
$MYSQLCMD -e "UPDATE civicrm_domain SET config_backend = NULL; UPDATE civicrm_setting SET value = NULL WHERE name = 'userFrameworkResourceURL';"

echo; echo "Setup Complete. Logout from your CMS to avoid session conflicts."

