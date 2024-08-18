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

dm_h1 "Prepare files (civicrm-*-drupal.tar.gz)"
dm_reset_dirs "$TRG"
cp $SRC/drupal/civicrm.config.php.drupal $TRG/civicrm.config.php
dm_generate_version "$TRG/civicrm-version.php" Drupal
dm_install_core "$SRC" "$TRG"
dm_install_coreext "$SRC" "$TRG" $(dm_core_exts)
dm_install_packages "$SRC/packages" "$TRG/packages"
dm_install_vendor "$SRC/vendor" "$TRG/vendor"
dm_install_bower "$SRC/bower_components" "$TRG/bower_components"
dm_install_drupal "$SRC/drupal" "$TRG/drupal"
dm_install_cvext com.iatspayments.civicrm "$TRG/ext/iatspayments"

dm_h1 "Generate archive (civicrm-*-drupal.tar.gz)"
cd $TRG/..
dm_assert_no_symlinks civicrm
tar czf $DM_TARGETDIR/civicrm-$DM_VERSION-drupal.tar.gz civicrm

dm_h1 "Cleanup"
rm -rf $TRG
