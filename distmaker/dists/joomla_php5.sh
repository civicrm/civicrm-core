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

dm_h1 "Prepare files (civicrm-*-joomla.zip)"
dm_reset_dirs "$TRG" "$DM_TMPDIR/com_civicrm"
cp $SRC/civicrm.config.php $TRG
dm_generate_version "$TRG/civicrm-version.php" Joomla
dm_install_core "$SRC" "$TRG"
dm_install_coreext "$SRC" "$TRG" $(dm_core_exts)
dm_install_packages "$SRC/packages" "$TRG/packages"
dm_install_vendor "$SRC/vendor" "$TRG/vendor"
dm_install_bower "$SRC/bower_components" "$TRG/bower_components"
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
if [ -z "$DM_SKIP_ALT" ]; then
  cp -R -p civicrm com_civicrm/admin/civicrm
  ${DM_PHP:-php} $DM_SOURCEDIR/distmaker/utils/joomlaxml.php $DM_SOURCEDIR com_civicrm $DM_VERSION alt
  dm_zip $DM_TARGETDIR/civicrm-$DM_VERSION-joomla-alt.zip com_civicrm
  rm -rf com_civicrm/admin/civicrm
fi

# generate zip version of civicrm.xml
${DM_PHP:-php} $DM_SOURCEDIR/distmaker/utils/joomlaxml.php $DM_SOURCEDIR com_civicrm $DM_VERSION zip
dm_zip com_civicrm/admin/civicrm.zip civicrm
dm_zip $DM_TARGETDIR/civicrm-$DM_VERSION-joomla.zip com_civicrm -x 'com_civicrm/admin/civicrm'

# clean up
rm -rf com_civicrm
rm -rf $TRG
