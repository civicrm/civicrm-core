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
dm_install_core "$SRC" "$TRG/civicrm/civicrm"
dm_install_packages "$SRC/packages" "$TRG/civicrm/civicrm/packages"

for F in $SRC/WordPress/*; do
	cp $F $TRG/civicrm
done
rm -f $TRG/civicrm/civicrm.config.php.wordpress

# copy docs
cp $SRC/WordPress/civicrm.config.php.wordpress $TRG/civicrm/civicrm/civicrm.config.php

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
