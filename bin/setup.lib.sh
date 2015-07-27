function _mysql_vars() {
  # someone might want to use empty password for development,
  # let's make it possible - we asked before.
  if [ -z $DBPASS ]; then # password still empty
    PASSWDSECTION=""
  else
    PASSWDSECTION="-p$DBPASS"
  fi

  HOSTSECTTION=""
  if [ ! -z "$DBHOST" ]; then
    HOSTSECTION="-h $DBHOST"
  fi

  PORTSECTION=""
  if [ ! -z "$DBPORT" ]; then
    PORTSECTION="-P $DBPORT"
  fi
}

function mysql_cmd() {
  _mysql_vars
  echo "mysql -u$DBUSER $PASSWDSECTION $HOSTSECTION $PORTSECTION $DBARGS $DBNAME"
}

function mysqladmin_cmd() {
  _mysql_vars
  echo "mysqladmin -u$DBUSER $PASSWDSECTION $HOSTSECTION $PORTSECTION $DBARGS"
}

function mysqldump_cmd() {
  _mysql_vars
  echo "mysqldump -u$DBUSER $PASSWDSECTION $HOSTSECTION $PORTSECTION $DBARGS"
}

## Pick the first available command. If none, then abort.
## example: COMPOSER=$(pickcmd composer composer.phar)
function pickcmd() {
  for name in "$@" ; do
    if which $name >> /dev/null ; then
      echo $name
      return
    fi
  done
  echo "ERROR: Failed to find any of these commands: $@"
  exit 1
}

## usage: has_commands <cmd1> <cmd2> ...
function has_commands() {
  for cmd in "$@" ; do
    if ! which $cmd >> /dev/null ; then
      return 1
    fi
  done
  return 0
}
