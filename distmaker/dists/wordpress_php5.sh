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

# checkout the right code revisions
pushd "$DM_SOURCEDIR/WordPress"
git checkout "$DM_REF_WORDPRESS"
popd

# make sure and clean up before
[ -d $TRG ] && rm -rf $TRG/*
[ ! -d $TRG/civicrm/civicrm ] && mkdir -p $TRG/civicrm/civicrm

# copy all the stuff
dm_install_core "$SRC" "$TRG/civicrm/civicrm"
dm_install_packages "$SRC/packages" "$TRG/civicrm/civicrm/packages"
dm_install_wordpress "$SRC/WordPress" "$TRG/civicrm"

# copy docs
cp $SRC/WordPress/civicrm.config.php.wordpress $TRG/civicrm/civicrm/civicrm.config.php
dm_generate_version "$TRG/civicrm/civicrm/civicrm-version.php" Wordpress

# gen tarball
cd $TRG
$DM_ZIP -r -9 $DM_TARGETDIR/civicrm-$DM_VERSION-wordpress.zip *
# clean up
rm -rf $TRG
