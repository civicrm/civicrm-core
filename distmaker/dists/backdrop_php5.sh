#!/bin/bash
set -e

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

dm_h1 "Prepare files (civicrm-*-backdrop.tar.gz)"
dm_reset_dirs "$TRG"
cp $SRC/backdrop/civicrm.config.php.backdrop $TRG/civicrm.config.php
dm_generate_version "$TRG/civicrm-version.php" Backdrop
dm_install_core "$SRC" "$TRG"
dm_install_coreext "$SRC" "$TRG" $(dm_core_exts)
dm_install_packages "$SRC/packages" "$TRG/packages"
dm_install_vendor "$SRC/vendor" "$TRG/vendor"
dm_install_bower "$SRC/bower_components" "$TRG/bower_components"
dm_install_drupal "$SRC/backdrop" "$TRG/backdrop"
dm_install_cvext com.iatspayments.civicrm "$TRG/ext/iatspayments"

dm_h1 "Generate archive (civicrm-*-backdrop.tar.gz)"
cd $TRG/..
dm_assert_no_symlinks civicrm
tar czf $DM_TARGETDIR/civicrm-$DM_VERSION-backdrop.tar.gz civicrm

dm_h1 "Clean up"
rm -rf $TRG
