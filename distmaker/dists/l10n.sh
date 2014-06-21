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

SRC=$DM_SOURCEDIR
TRG=$DM_TMPDIR/civicrm

# make sure and clean up before
if [ -d $TRG ] ; then
	rm -rf $TRG/*
fi

# copy all the stuff
dm_install_l10n "$SRC/l10n" "$TRG/l10n"

# copy selected sqls
if [ ! -d $TRG/sql ] ; then
	mkdir $TRG/sql
fi

for F in $SRC/sql/civicrm*.mysql $SRC/sql/counties.US.sql.gz $SRC/sql/case_sample*.mysql; do
	cp $F $TRG/sql
done

# copy docs
cp $SRC/agpl-3.0.txt $TRG
cp $SRC/gpl.txt $TRG
cp $SRC/README.txt $TRG
cp $SRC/CONTRIBUTORS.txt $TRG
cp $SRC/agpl-3.0.exception.txt $TRG

# gen tarball
cd $TRG/..
tar czf $DM_TARGETDIR/civicrm-$DM_VERSION-l10n.tar.gz --exclude '*.po' --exclude pot civicrm/l10n civicrm/sql/civicrm_*.??_??.mysql

# clean up
rm -rf $TRG
