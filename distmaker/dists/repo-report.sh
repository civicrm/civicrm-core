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
REPORT="$DM_TARGETDIR/civicrm-$DM_VERSION-repos.txt"

echo '# <repo> <branch> <commit>' > "$REPORT"
dm_repo_report "civicrm-core"		"$DM_SOURCEDIR"			"$DM_REF_CORE"		>> $REPORT
dm_repo_report "civicrm-backdrop@1.x"	"$DM_SOURCEDIR/backdrop"	"$DM_REF_BACKDROP"	>> $REPORT
dm_repo_report "civicrm-drupal@6.x"	"$DM_SOURCEDIR/drupal"		"$DM_REF_DRUPAL6"	>> $REPORT
dm_repo_report "civicrm-drupal@7.x"	"$DM_SOURCEDIR/drupal"		"$DM_REF_DRUPAL"	>> $REPORT
#dm_repo_report "civicrm-drupal@8.x"	"$DM_SOURCEDIR/drupal"		"$DM_REF_DRUPAL8"	>> $REPORT
dm_repo_report "civicrm-joomla" 	"$DM_SOURCEDIR/joomla"		"$DM_REF_JOOMLA"	>> $REPORT
dm_repo_report "civicrm-wordpress"	"$DM_SOURCEDIR/WordPress"	"$DM_REF_WORDPRESS"	>> $REPORT
dm_repo_report "civicrm-packages"	"$DM_SOURCEDIR/packages"	"$DM_REF_PACKAGES"	>> $REPORT
