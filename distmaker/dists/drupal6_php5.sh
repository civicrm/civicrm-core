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

. "$P/common.sh"

RSYNCOPTIONS="-avC $DM_EXCLUDES_RSYNC --include=core"
RSYNCCOMMAND="$DM_RSYNC $RSYNCOPTIONS"
SRC=$DM_SOURCEDIR
TRG=$DM_TMPDIR/civicrm


# checkout the right code revisions
pushd "$DM_SOURCEDIR/drupal"
git checkout .
git checkout "$DM_REF_DRUPAL6"
popd

# make sure and clean up before
if [ -d $TRG ] ; then
	rm -rf $TRG/*
fi

# copy all the stuff
dm_install_core "$SRC" "$TRG"
dm_install_packages "$SRC/packages" "$TRG/packages"
for CODE in drupal; do
  echo $CODE
  [ -d $SRC/$CODE ] && $RSYNCCOMMAND $SRC/$CODE $TRG
done

# copy docs
cp $SRC/drupal/civicrm.config.php.drupal $TRG/civicrm.config.php

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
