#!/bin/bash
set -e

# This script assumes
# that DAOs are generated
# and all the necessary conversions had place!

P=`dirname $0`
CFFILE=$P/../distmaker.conf

if [ ! -f $CFFILE ] ; then
	echo "NO DISTMAKER.CONF FILE!"
	exit 1
else
	. $CFFILE
fi

RSYNCOPTIONS="-avC $DM_EXCLUDES_RSYNC --include=core"
RSYNCCOMMAND="$DM_RSYNC $RSYNCOPTIONS"
SRC=$DM_SOURCEDIR
TRG=$DM_TMPDIR/civicrm

# checkout the right code revisions
pushd "$DM_SOURCEDIR/WordPress"
git checkout "$DM_REF_WORDPRESS"
popd

# make sure and clean up before
if [ -d $TRG ] ; then
	rm -rf $TRG/*
fi

if [ ! -d $TRG ] ; then
	mkdir $TRG
fi

if [ ! -d $TRG/civicrm ] ; then
	mkdir $TRG/civicrm
fi

if [ ! -d $TRG/civicrm/civicrm ] ; then
	mkdir $TRG/civicrm/civicrm
fi

# copy all the stuff
for CODE in css i js packages PEAR templates bin CRM api extern Reports install settings; do
  echo $CODE
  [ -d $SRC/$CODE ] && $RSYNCCOMMAND $SRC/$CODE $TRG/civicrm/civicrm
done

# delete any setup.sh or setup.php4.sh if present
if [ -d $TRG/civicrm/civicrm/bin ] ; then
  rm -f $TRG/civicrm/civicrm/bin/setup.sh
  rm -f $TRG/civicrm/civicrm/bin/setup.php4.sh
  rm -f $TRG/civicrm/civicrm/bin/setup.bat
fi

# copy selected sqls
if [ ! -d $TRG/civicrm/civicrm/sql ] ; then
	mkdir $TRG/civicrm/civicrm/sql
fi

for F in $SRC/sql/civicrm*.mysql $SRC/sql/counties.US.sql.gz $SRC/sql/case_sample*.mysql; do
	cp $F $TRG/civicrm/civicrm/sql
done

set +e
rm -rf $TRG/civicrm/civicrm/sql/civicrm_*.??_??.mysql
set -e

for F in $SRC/WordPress/*; do
	cp $F $TRG/civicrm
done

# copy docs
cp $SRC/agpl-3.0.txt $TRG/civicrm/civicrm
cp $SRC/gpl.txt $TRG/civicrm/civicrm
cp $SRC/README.txt $TRG/civicrm/civicrm
cp $SRC/civicrm.config.php $TRG/civicrm/civicrm

# final touch
echo "<?php
function civicrmVersion( ) {
  return array( 'version'  => '$DM_VERSION',
                'cms'      => 'Wordpress',
                'revision' => '$DM_REVISION' );
}
" > $TRG/civicrm/civicrm/civicrm-version.php

# gen tarball
cd $TRG
$DM_ZIP -r -9 $DM_TARGETDIR/civicrm-$DM_VERSION-wordpress.zip *
# clean up
rm -rf $TRG
