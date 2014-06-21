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

# make sure and clean up before
[ -d $TRG ] && rm -rf $TRG/*

# copy all the stuff
dm_install_l10n "$SRC/l10n" "$TRG/l10n"
dm_install_files "$SRC" "$TRG" {agpl-3.0,agpl-3.0.exception,gpl,README,CONTRIBUTORS}.txt

# copy selected sqls
[ ! -d $TRG/sql ] && mkdir $TRG/sql
for F in $SRC/sql/civicrm*.mysql $SRC/sql/case_sample*.mysql; do
	cp $F $TRG/sql
done

# gen tarball
cd $TRG/..
tar czf $DM_TARGETDIR/civicrm-$DM_VERSION-l10n.tar.gz --exclude '*.po' --exclude pot civicrm/l10n civicrm/sql/civicrm_*.??_??.mysql

# clean up
rm -rf $TRG
