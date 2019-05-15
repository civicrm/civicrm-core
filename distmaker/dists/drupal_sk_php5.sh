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
cp $SRC/drupal/civicrm.config.php.drupal $TRG/civicrm.config.php
dm_generate_version "$TRG/civicrm-version.php" Drupal
dm_install_core "$SRC" "$TRG"
dm_install_packages "$SRC/packages" "$TRG/packages"
dm_install_vendor "$SRC/vendor" "$TRG/vendor"
dm_install_bower "$SRC/bower_components" "$TRG/bower_components"
dm_install_drupal "$SRC/drupal" "$TRG/drupal"
dm_install_cvext org.civicrm.api4 "$TRG/ext/api4"
dm_install_cvext com.iatspayments.civicrm "$TRG/ext/iatspayments"

# delete packages that distributions on Drupal.org repalce if present
# also delete stuff that we dont really use and should not be included
rm -rf $TRG/packages/dompdf
rm -rf $TRG/packages/IDS
rm -rf $TRG/packages/jquery
rm -rf $TRG/packages/ckeditor
rm -rf $TRG/packages/tinymce

# gen tarball
cd $TRG/..
tar czf $DM_TARGETDIR/civicrm-$DM_VERSION-starterkit.tgz civicrm

# clean up
rm -rf $TRG
