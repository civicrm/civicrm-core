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