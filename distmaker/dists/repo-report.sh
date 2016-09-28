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
REPORT="$DM_TARGETDIR/civicrm-$DM_VERSION-repos.json"

env \
  DM_VERSION="$DM_VERSION" \
  DM_SOURCEDIR="$DM_SOURCEDIR" \
  DM_REF_CORE="$DM_REF_CORE" \
  DM_REF_BACKDROP="$DM_REF_BACKDROP" \
  DM_REF_DRUPAL6="$DM_REF_DRUPAL6" \
  DM_REF_DRUPAL="$DM_REF_DRUPAL" \
  DM_REF_JOOMLA="$DM_REF_JOOMLA" \
  DM_REF_WORDPRESS="$DM_REF_WORDPRESS" \
  DM_REF_PACKAGES="$DM_REF_PACKAGES" \
  php "$DM_SOURCEDIR/distmaker/utils/repo-report.php" > "$REPORT"

