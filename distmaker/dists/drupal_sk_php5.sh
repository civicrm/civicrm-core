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

# delete packages that distributions on Drupal.org repalce if present
# also delete stuff that we dont really use and should not be included
rm -rf $TRG/packages/dompdf
rm -rf $TRG/packages/IDS
rm -rf $TRG/packages/jquery
rm -rf $TRG/packages/ckeditor
rm -rf $TRG/packages/tinymce
rm -rf $TRG/joomla
rm -rf $TRG/WordPress

# copy docs
cp $SRC/drupal/civicrm.config.php.drupal $TRG/civicrm.config.php
dm_generate_version "$TRG/civicrm-version.php" Drupal

# gen tarball
cd $TRG/..
tar czf $DM_TARGETDIR/civicrm-$DM_VERSION-starterkit.tgz civicrm

# clean up
rm -rf $TRG
