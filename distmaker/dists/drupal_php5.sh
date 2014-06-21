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

SRC=$DM_SOURCEDIR
TRG=$DM_TMPDIR/civicrm

# checkout the right code revisions
pushd "$DM_SOURCEDIR/drupal"
git checkout .
git checkout "$DM_REF_DRUPAL"
popd

# make sure and clean up before
if [ -d $TRG ] ; then
	rm -rf $TRG/*
fi

# copy all the stuff
dm_install_core "$SRC" "$TRG"
dm_install_packages "$SRC/packages" "$TRG/packages"
dm_install_drupal "$SRC/drupal" "$TRG/drupal"
dm_install_drupal_info "$DM_SOURCEDIR/drupal"

cp $SRC/drupal/civicrm.config.php.drupal $TRG/civicrm.config.php
dm_generate_version "$TRG/civicrm-version.php" Drupal

# gen tarball
cd $TRG/..
tar czf $DM_TARGETDIR/civicrm-$DM_VERSION-drupal.tar.gz civicrm

# clean up
rm -rf $TRG
