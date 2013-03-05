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
pushd "$DM_SOURCEDIR/drupal"
git checkout "$DM_REF_DRUPAL6"
popd

# make sure and clean up before
if [ -d $TRG ] ; then
	rm -rf $TRG/*
fi

# copy all the stuff
for CODE in css i js packages PEAR templates bin CRM api drupal extern Reports install settings; do
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
cp $SRC/drupal/civicrm.config.php.drupal $TRG/civicrm.config.php

# copy over drupal6 directory from svn for 3.4
if [ -d $TRG/drupal ]; then
  rm -rf $TRG/drupal
  svn export -q http://svn.civicrm.org/civicrm/branches/v3.4/drupal $TRG/drupal
  mv $TRG/drupal/civicrm.config.php.drupal $TRG/civicrm.config.php
fi

cd $TRG/drupal
if [ $(uname) = "Darwin" ]; then
  ## BSD sed
  grep -lr -e 'version = 3.4' * | xargs sed -i '' 's/version = 3.4/version = '$DM_VERSION'/g'
else
  ## GNU sed
  grep -lr -e 'version = 3.4' * | xargs sed -i'' 's/version = 3.4/version = '$DM_VERSION'/g'
fi


# final touch
echo "<?php
function civicrmVersion( ) {
  return array( 'version'  => '$DM_VERSION',
                'cms'      => 'Drupal6',
                'revision' => '$DM_REVISION' );
}
" > $TRG/civicrm-version.php

# gen tarball
cd $TRG/..
tar czf $DM_TARGETDIR/civicrm-$DM_VERSION-drupal6.tar.gz civicrm

# clean up
rm -rf $TRG
