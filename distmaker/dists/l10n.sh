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

dm_h1 "Prepare files (civicrm-*-l10n.tar.gz)"
dm_reset_dirs "$TRG"
dm_install_l10n "$SRC/l10n" "$TRG/l10n"

[ ! -d $TRG/sql ] && mkdir $TRG/sql
for F in $SRC/sql/civicrm_*.??_??.mysql; do
	cp $F $TRG/sql
done

dm_h1 "Generate archive (civicrm-*-l10n.tar.gz)"
cd $TRG/..
tar czf $DM_TARGETDIR/civicrm-$DM_VERSION-l10n.tar.gz --exclude '*.po' --exclude pot civicrm

dm_h1 "Clean up"
rm -rf $TRG
