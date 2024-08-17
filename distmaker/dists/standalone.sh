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

SRC="$DM_SOURCEDIR"
TRG="$DM_TMPDIR/civicrm-standalone"

# copy all the stuff
dm_reset_dirs "$TRG" "$TRG/core"
dm_install_core "$SRC" "$TRG/core"
dm_install_coreext "$SRC" "$TRG/core" $(dm_core_exts)
dm_install_packages "$SRC/packages" "$TRG/core/packages"
dm_install_vendor "$SRC/vendor" "$TRG/core/vendor"
dm_install_bower "$SRC/bower_components" "$TRG/core/bower_components"
dm_install_cvext com.iatspayments.civicrm "$TRG/core/ext/iatspayments"
"$SRC/tools/standalone/bin/scaffold" "$TRG"

# gen tarball
cd "$DM_TMPDIR"
tar czf "$DM_TARGETDIR/civicrm-$DM_VERSION-standalone.tar.gz" civicrm-standalone

# clean up
rm -rf $TRG
