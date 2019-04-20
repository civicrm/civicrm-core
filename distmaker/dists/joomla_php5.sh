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

# copy all the rest of the stuff
dm_reset_dirs "$TRG" "$DM_TMPDIR/com_civicrm"
cp $SRC/civicrm.config.php $TRG
dm_generate_version "$TRG/civicrm-version.php" Joomla
dm_install_core "$SRC" "$TRG"
dm_install_packages "$SRC/packages" "$TRG/packages"
dm_install_vendor "$SRC/vendor" "$TRG/vendor"
dm_install_bower "$SRC/node_modules" "$TRG/node_modules"
dm_install_cvext org.civicrm.api4 "$TRG/ext/api4"
dm_install_cvext com.iatspayments.civicrm "$TRG/ext/iatspayments"

## WTF: It's so good we'll install it twice!
## (The first is probably extraneous, but there could be bugs dependent on it.)
dm_install_joomla "$SRC/joomla" "$TRG/joomla"
dm_install_joomla "$SRC/joomla" "$DM_TMPDIR/com_civicrm"

## joomla 3.0 likes admin.civicrm.php to be called civicrm.php; keep both names
cp "$SRC/joomla/admin/admin.civicrm.php" "$DM_TMPDIR/com_civicrm/admin/civicrm.php"

# gen zip file
cd $DM_TMPDIR;

# generate alt version of package
cp -r -p civicrm com_civicrm/admin/civicrm
${DM_PHP:-php} $DM_SOURCEDIR/distmaker/utils/joomlaxml.php $DM_SOURCEDIR com_civicrm $DM_VERSION alt
${DM_ZIP:-zip} -q -r -9 $DM_TARGETDIR/civicrm-$DM_VERSION-joomla-alt.zip com_civicrm
rm -rf com_civicrm/admin/civicrm

# generate zip version of civicrm.xml
${DM_PHP:-php} $DM_SOURCEDIR/distmaker/utils/joomlaxml.php $DM_SOURCEDIR com_civicrm $DM_VERSION zip
${DM_ZIP:-zip} -q -r -9 com_civicrm/admin/civicrm.zip civicrm
${DM_ZIP:-zip} -q -r -9 $DM_TARGETDIR/civicrm-$DM_VERSION-joomla.zip com_civicrm -x 'com_civicrm/admin/civicrm'

# clean up
rm -rf com_civicrm
rm -rf $TRG
