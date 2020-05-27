#!/usr/bin/env bash
set -e

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
  echo
  echo "Usage: setup.sh [options] [schema file] [database data file] [database name] [database user] [database password] [database host] [database port] [additional args]"
  echo "[options] is a mix of zero or more of following:"
  echo "  -a     (All)            Implies -Dgsef (default)"
  echo "  -D     (Download)       Download dependencies"
  echo "  -g     (GenCode)        Generate DAO files, SQL files, etc"
  echo "  -s     (Schema)         Load new schema in DB"
  echo "  -d     (Data-Plain)     Load basic dataset in DB"
  echo "  -e     (Data-Examples)  Load example dataset in DB"
  echo "  -f     (Flush)          Flush caches and settings"
  echo
  echo "Example: Perform a full setup"
  echo "   setup.sh -a"
  echo
  echo "Example: Setup all the code but leave the DB alone"
  echo "   setup.sh -Dg"
  echo
  echo "Example: Keep the existing code but reset the DB"
  echo "   setup.sh -sef"
  exit 0
fi

###############################################################################
## Parse command line options

FOUND_ACTION=
DO_DOWNLOAD=
DO_GENCODE=
DO_SCHEMA=
DO_DATA=
DO_FLUSH=
DEFAULT_DATA=

while getopts "aDgsdef" opt; do
  case $opt in
    a)
      DO_DOWNLOAD=1
      DO_GENCODE=1
      DO_SCHEMA=1
      DO_DATA=1
      DEFAULT_DATA=civicrm_generated.mysql
      DO_FLUSH=1
      FOUND_ACTION=1
      ;;
    D)
      DO_DOWNLOAD=1
      FOUND_ACTION=1
      ;;
    g)
      DO_GENCODE=1
      FOUND_ACTION=1
      ;;
    s)
      DO_SCHEMA=1
      FOUND_ACTION=1
      ;;
    d)
      DO_DATA=1
      DEFAULT_DATA=civicrm_data.mysql
      FOUND_ACTION=1
      ;;
    e)
      DO_DATA=1
      DEFAULT_DATA=civicrm_generated.mysql
      FOUND_ACTION=1
      ;;
    f)
      DO_FLUSH=1
      FOUND_ACTION=1
      ;;
    \?)
      echo "Invalid option: -$OPTARG" >&2
      exit 1
      ;;
    :)
      echo "Option -$OPTARG requires an argument." >&2
      exit 1
      ;;
  esac
done
if [ -z "$FOUND_ACTION" ]; then
  DO_DOWNLOAD=1
  DO_GENCODE=1
  DO_SCHEMA=1
  DO_DATA=1
  DO_FLUSH=1
  DEFAULT_DATA=civicrm_generated.mysql
fi

shift $((OPTIND-1))

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

MYSQLCMD=$(mysql_cmd)
###############################################################################
## Execute tasks
set -x

if [ -n "$DO_DOWNLOAD" ]; then
  pushd "$CALLEDPATH/.."
    if [ "$GENCODE_CMS" != "Drupal8" ]; then
      COMPOSER=$(pickcmd composer composer.phar)
      $COMPOSER install
    fi

    if has_commands karma ; then
      ## dev dependencies have been installed globally; don't force developer to redownload
      npm install --production
    else
      npm install
    fi
  popd
fi

# run code generator if it's there - which means it's
# checkout, not packaged code
if [ -n "$DO_GENCODE" -a -d "$CALLEDPATH/../xml" ]; then
  pushd "$CALLEDPATH/../xml"
    if [ -z "$DBPORT" ]; then
      PHP_MYSQL_HOSTPORT="$DBHOST"
    else
      PHP_MYSQL_HOSTPORT="$DBHOST:$DBPORT"
    fi
    "$PHP5PATH"php -d mysql.default_host="$PHP_MYSQL_HOSTPORT" -d mysql.default_user=$DBUSER -d mysql.default_password=$DBPASS GenCode.php $SCHEMA '' ${GENCODE_CMS}
  popd
fi

if [ -n "$DO_SCHEMA" ]; then
  pushd "$CALLEDPATH/../sql"
    echo; echo "Dropping civicrm_* tables from database $DBNAME"
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
  popd
fi

if [ -n "$DO_DATA" ]; then
  pushd "$CALLEDPATH/../sql"
    # load default data set unless system is configured with override
    if [ -z $DBLOAD ]; then
        echo;
        echo "Populating database with dataset - $DEFAULT_DATA"
        $MYSQLCMD < "$DEFAULT_DATA"
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
  popd
fi

if [ -n "$DO_FLUSH" ]; then
  pushd "$CALLEDPATH/.."
    # reset userFrameworkResourceURL which gets set
    # when config object is initialized
    $MYSQLCMD -e "UPDATE civicrm_setting SET value = NULL WHERE name = 'userFrameworkResourceURL';"
  popd
fi

echo; echo "NOTE: Logout from your CMS to avoid session conflicts."

