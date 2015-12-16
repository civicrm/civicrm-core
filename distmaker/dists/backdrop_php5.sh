#!/bin/bash
set -ex

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

# copy all the stuff
dm_reset_dirs "$TRG"
cp $SRC/drupal/civicrm.config.php.backdrop $TRG/civicrm.config.php
dm_generate_version "$TRG/civicrm-version.php" Backdrop
dm_install_core "$SRC" "$TRG"
dm_install_packages "$SRC/packages" "$TRG/packages"
dm_install_vendor "$SRC/vendor" "$TRG/vendor"
dm_install_bower "$SRC/bower_components" "$TRG/bower_components"
dm_install_drupal "$SRC/backdrop" "$TRG/backdrop"

# gen tarball
cd $TRG/..
tar czf $DM_TARGETDIR/civicrm-$DM_VERSION-backdrop-unstable.tar.gz civicrm

# clean up
rm -rf $TRG
