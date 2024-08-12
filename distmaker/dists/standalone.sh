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
dm_reset_dirs "$TRG" "$TRG/civicrm/web/core"
dm_install_core "$SRC" "$TRG/civicrm/web/core"
dm_install_coreext "$SRC" "$TRG/civicrm/web/core" $(dm_core_exts)
dm_install_packages "$SRC/packages" "$TRG/civicrm/web/core/packages"
dm_install_vendor "$SRC/vendor" "$TRG/civicrm/web/core/vendor"
dm_install_bower "$SRC/bower_components" "$TRG/civicrm/web/core/bower_components"
dm_install_cvext com.iatspayments.civicrm "$TRG/civicrm/web/core/ext/iatspayments"
$SRC/tools/standalone/bin/scaffold $TRG/civicrm/web

# gen tarball
cd $TRG/civicrm
tar czf $DM_TARGETDIR/civicrm-$DM_VERSION-standalone.tar.gz web

# clean up
rm -rf $TRG
