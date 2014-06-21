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
pushd "$DM_SOURCEDIR/joomla"
git checkout "$DM_REF_JOOMLA"
popd

# copy all the rest of the stuff
dm_reset_dirs "$TRG"
cp $SRC/civicrm.config.php $TRG
dm_generate_version "$TRG/civicrm-version.php" Joomla
dm_install_core "$SRC" "$TRG"
dm_install_packages "$SRC/packages" "$TRG/packages"
dm_install_joomla "$SRC/joomla" "$TRG/joomla"

# gen zip file
cd $DM_TMPDIR;

mkdir com_civicrm
mkdir com_civicrm/admin
mkdir com_civicrm/site
mkdir com_civicrm/site/elements
mkdir com_civicrm/admin/civicrm
mkdir com_civicrm/admin/language
mkdir com_civicrm/admin/language/en-GB
mkdir com_civicrm/admin/helpers
mkdir com_civicrm/admin/plugins

# copying back end code to admin folder
cp civicrm/joomla/script.civicrm.php             com_civicrm/
cp civicrm/joomla/admin/admin.civicrm.php        com_civicrm/admin
cp civicrm/joomla/admin/config.xml               com_civicrm/admin
cp civicrm/joomla/admin/configure.php            com_civicrm/admin
cp civicrm/joomla/admin/license.civicrm.txt      com_civicrm/admin
cp civicrm/joomla/admin/toolbar.civicrm.php      com_civicrm/admin
cp civicrm/joomla/admin/toolbar.civicrm.html.php com_civicrm/admin
cp -r -p civicrm/joomla/admin/helpers/*          com_civicrm/admin/helpers
cp -r -p civicrm/joomla/admin/plugins/*          com_civicrm/admin/plugins
cp civicrm/joomla/admin/language/en-GB/*         com_civicrm/admin/language/en-GB

# joomla 3.0 like admin.civicrm.php to be called civicrm.php
# lets keep both versions there
cp com_civicrm/admin/admin.civicrm.php com_civicrm/admin/civicrm.php

# copying front end code
cp civicrm/joomla/site/civicrm.html.php      com_civicrm/site
cp civicrm/joomla/site/civicrm.php           com_civicrm/site
cp -r civicrm/joomla/site/views              com_civicrm/site
cp -r -p civicrm/joomla/site/elements/*      com_civicrm/site/elements

# generate alt version of package
cp -r -p civicrm/* com_civicrm/admin/civicrm
$DM_PHP $DM_SOURCEDIR/distmaker/utils/joomlaxml.php $DM_SOURCEDIR com_civicrm $DM_VERSION alt
$DM_ZIP -q -r -9 $DM_TARGETDIR/civicrm-$DM_VERSION-joomla-alt.zip com_civicrm
rm -rf com_civicrm/admin/civicrm

# generate zip version of civicrm.xml
$DM_PHP $DM_SOURCEDIR/distmaker/utils/joomlaxml.php $DM_SOURCEDIR com_civicrm $DM_VERSION zip
$DM_ZIP -q -r -9 com_civicrm/admin/civicrm.zip civicrm
$DM_ZIP -q -r -9 $DM_TARGETDIR/civicrm-$DM_VERSION-joomla.zip com_civicrm -x 'com_civicrm/admin/civicrm'

# clean up
rm -rf com_civicrm
rm -rf $TRG
