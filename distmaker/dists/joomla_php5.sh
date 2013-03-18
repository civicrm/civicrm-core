#!/bin/bash
set -ex

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
pushd "$DM_SOURCEDIR/joomla"
git checkout "$DM_REF_JOOMLA"
popd

# make sure and clean up before
if [ -d $TRG ] ; then
	rm -rf $TRG/*
fi

# copy all the rest of the stuff
for CODE in css i install js packages PEAR templates bin joomla CRM api extern Reports settings; do
  echo $CODE
  [ -d $SRC/$CODE ] && $RSYNCCOMMAND $SRC/$CODE $TRG
done

# delete any setup.sh or setup.php4.sh if present
if [ -d $TRG/bin ] ; then
  rm -f $TRG/bin/setup.sh
  rm -f $TRG/bin/setup.php4.sh
  rm -f $TRG/bin/setup.bat
fi

# copy selected sqls
if [ ! -d $TRG/sql ] ; then
	mkdir $TRG/sql
fi
for F in $SRC/sql/civicrm*.mysql $SRC/sql/counties.US.sql.gz $SRC/sql/case_sample*.mysql; do
	cp $F $TRG/sql
done

set +e
rm -rf $TRG/sql/civicrm_*.??_??.mysql
set -e

# copy docs
cp $SRC/agpl-3.0.txt $TRG
cp $SRC/gpl.txt $TRG
cp $SRC/README.txt $TRG
cp $SRC/Sponsors.txt $TRG
cp $SRC/agpl-3.0.exception.txt $TRG
cp $SRC/civicrm.config.php $TRG

# final touch
echo "<?php
function civicrmVersion( ) {
  return array( 'version'  => '$DM_VERSION',
                'cms'      => 'Joomla',
                'revision' => '$DM_REVISION' );
}
" > $TRG/civicrm-version.php

# gen zip file
cd $DM_TMPDIR;

mkdir com_civicrm
mkdir com_civicrm/admin
mkdir com_civicrm/site
mkdir com_civicrm/site/elements
mkdir com_civicrm/admin/civicrm
mkdir com_civicrm/admin/language
mkdir com_civicrm/admin/language/en-GB
mkdir com_civicrm/admin/helpers
mkdir com_civicrm/admin/plugins

# copying back end code to admin folder
cp civicrm/joomla/script.civicrm.php             com_civicrm/
cp civicrm/joomla/admin/admin.civicrm.php        com_civicrm/admin
cp civicrm/joomla/admin/config.xml               com_civicrm/admin
cp civicrm/joomla/admin/configure.php            com_civicrm/admin
cp civicrm/joomla/admin/license.civicrm.txt      com_civicrm/admin
cp civicrm/joomla/admin/toolbar.civicrm.php      com_civicrm/admin
cp civicrm/joomla/admin/toolbar.civicrm.html.php com_civicrm/admin
cp -r -p civicrm/joomla/admin/helpers/*          com_civicrm/admin/helpers
cp -r -p civicrm/joomla/admin/plugins/*          com_civicrm/admin/plugins
cp civicrm/joomla/admin/language/en-GB/*         com_civicrm/admin/language/en-GB

# joomla 3.0 like admin.civicrm.php to be called civicrm.php
# lets keep both versions there
cp com_civicrm/admin/admin.civicrm.php com_civicrm/admin/civicrm.php

# copying front end code
cp civicrm/joomla/site/civicrm.html.php      com_civicrm/site
cp civicrm/joomla/site/civicrm.php           com_civicrm/site
cp -r civicrm/joomla/site/views              com_civicrm/site
cp -r -p civicrm/joomla/site/elements/*      com_civicrm/site/elements

# copy civicrm code
cp -r -p civicrm/* com_civicrm/admin/civicrm

# generate alt version of civicrm.xml
$DM_PHP $DM_SOURCEDIR/distmaker/utils/joomlaxml.php $DM_SOURCEDIR com_civicrm $DM_VERSION alt

# generate alt version of package
$DM_ZIP -q -r -9 $DM_TARGETDIR/civicrm-$DM_VERSION-joomla-alt.zip com_civicrm

# delete the civicrm directory
rm -rf com_civicrm/admin/civicrm

# generate zip version of civicrm.xml
$DM_PHP $DM_SOURCEDIR/distmaker/utils/joomlaxml.php $DM_SOURCEDIR com_civicrm $DM_VERSION zip

$DM_ZIP -q -r -9 com_civicrm/admin/civicrm.zip civicrm

# generate zip within zip file
$DM_ZIP -q -r -9 $DM_TARGETDIR/civicrm-$DM_VERSION-joomla.zip com_civicrm -x 'com_civicrm/admin/civicrm'

# clean up
rm -rf com_civicrm
rm -rf $TRG
