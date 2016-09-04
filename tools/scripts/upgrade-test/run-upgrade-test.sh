#!/bin/bash

SELF=$(cd $(dirname $0); pwd -P)/$(basename $0)

# define your database name here, will be overriden by
# FIRST command line argument if given
DBNAME=
# define your database usernamename here, will be overriden by
# SECOND command line argument if given
DBUSER=
# define your database password here, will be overriden by
# THIRD command line argument if given
DBPASS=

DRUPALLOCATION=/var/www/drupal.tests.dev.civicrm.org/public/


if [ ! -r $DRUPALLOCATION ] ; then
    echo Drupal directory does not exist or not writable.
fi


if [ -z $1 ] ; then
    echo You need to pass version number as argument.
fi

echo "*** Checking for test tarballs"

echo "Found following tarballs:"

# fetch command line argument - version
if [ ! -z $1 ] ; then VERSION=$1; fi

echo Kicking off upgrade test for version: $VERSION

