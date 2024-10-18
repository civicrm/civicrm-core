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
JPKG=$DM_TMPDIR/com_civicrm

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

## Different parts of the repo need to appear in different places
## It's small so just duplicate the files for now.
dm_install_joomla "$SRC/joomla" "$TRG/joomla"
dm_install_joomla "$SRC/joomla" "$JPKG"

## joomla 3.0 likes admin.civicrm.php to be called civicrm.php; keep both names
cp "$JPKG/admin/admin.civicrm.php" "$JPKG/admin/civicrm.php"

# gen zip file
mv "$TRG" "$JPKG/admin/civicrm"
cd $DM_TMPDIR;
## generate the civicrm.xml (files in package) and access.xml (permissions)
${DM_PHP:-php} $DM_SOURCEDIR/distmaker/utils/joomlaxml.php $DM_SOURCEDIR com_civicrm $DM_VERSION alt
dm_zip $DM_TARGETDIR/civicrm-$DM_VERSION-joomla.zip com_civicrm

# clean up
rm -rf "$JPKG"
