#!/bin/bash -v
set -e

# This is distmaker script for CiviCRM
# Author: michau
# "Protected by an electric fence and copyright control."
# Thanks to Kleptones for moral support when writing this.

# Make sure that you have distmaker.conf file
# in the same directory containing following lines:
#
# DM_SOURCEDIR=/home/user/svn/civicrm           <- sources
# DM_GENFILESDIR=/home/user/generated           <- generated files
# DM_TMPDIR=/tmp                                <- temporary files (will be deleted afterwards)
# DM_TARGETDIR=/tmp/outdir                      <- target dir for tarballs
# DM_PHP=/opt/php5/bin/php                      <- php5 binary
# DM_RSYNC=/usr/bin/rsync                       <- rsync binary
# DM_VERSION=trunk.r1234                        <- what the version number should be
# DM_ZIP=/usr/bin/zip                           <- zip binary
#
#
# ========================================================
# DO NOT MODIFY BELOW
# ========================================================


# Where are we called from?
P=`dirname $0`
# Current dir
ORIGPWD=`pwd`

# List of files to exclude from all tarballs
DM_EXCLUDES=".git .svn packages/_ORIGINAL_ packages/SeleniumRC packages/PHPUnit packages/PhpDocumentor packages/SymfonyComponents packages/amavisd-new"
for DM_EXCLUDE in $DM_EXCLUDES ; do
  DM_EXCLUDES_RSYNC="--exclude=${DM_EXCLUDE} ${DM_EXCLUDES_RSYNC}"
done
## Note: These small folders have items that previously were not published,
## but there's no real cost to including them, and excluding them seems
## likely to cause confusion as the codebase evolves:
##   packages/Files packages/PHP packages/Text
export DM_EXCLUDES DM_EXCLUDES_RSYNC

# Set no actions by default
D5PACK=0
D56PACK=0
J5PACK=0
WP5PACK=0
SK5PACK=0
L10NPACK=0

# Display usage
display_usage()
{
	echo
	echo "Usage: "
	echo "  distmaker.sh OPTION"
	echo
	echo "Options available:"
	echo "  all  - generate all available tarballs"
	echo "  l10n - generate internationalization data"
	echo "  d5   - generate Drupal7 PHP5 module"
	echo "  d5.6 - generate Drupal6 PHP5 module"
	echo "  j5   - generate Joomla PHP5 module"
	echo "  wp5  - generate Wordpress PHP5 module"
	echo "  sk - generate Drupal StarterKit module"
	echo
	echo "You also need to have distmaker.conf file in place."
	echo "See distmaker.conf.dist for example contents."
	echo
}


# Check if config is ok.
check_conf()
{
	# Test for distmaker.conf file availability, cannot proceed without it anyway
	if [ ! -f $P/distmaker.conf ] ; then
		echo; echo "ERROR! No distmaker.conf file available!"; echo;
		display_usage
		exit 1
	else
		source "$P/distmaker.conf"
		export DM_SOURCEDIR DM_GENFILESDIR DM_TMPDIR DM_TARGETDIR DM_PHP DM_RSYNC DM_ZIP DM_VERSION DM_REF_CORE DM_REF_DRUPAL DM_REF_DRUPAL6 DM_REF_JOOMLA DM_REF_WORDPRESS DM_REF_PACKAGES
		for k in "$DM_SOURCEDIR" "$DM_GENFILESDIR" "$DM_TARGETDIR" "$DM_TMPDIR"; do
			if [ ! -d "$k" ] ; then
				echo; echo "ERROR! " $k "directory not found!"; echo "(if you get empty directory name, it might mean that one of necessary variables is not set)"; echo;
				exit 1
			fi
		done
	fi
}

# Check if PHP4 converstion happened
check_php4()
{
	if [ ! $PHP4GENERATED = 1 ]; then
		echo; echo "ERROR! Cannot package PHP4 version without running conversion!"; echo;
		exit 1
	fi
}

# Let's go.

check_conf

# Figure out what to do
case $1 in
	# L10N PHP5
	l10n)
	echo; echo "Generating L10N module"; echo;
	L10NPACK=1
	;;

	# DRUPAL7 PHP5
	d5)
	echo; echo "Generating Drupal7 PHP5 module"; echo;
	D5PACK=1
	;;

	# DRUPAL7 PHP5 StarterKit package
	sk)
	echo; echo "Generating Drupal7 PHP5 starter kit minimal module"; echo;
	SKPACK=1
	;;

	# DRUPAL6 PHP5
	d5.6)
	echo; echo "Generating Drupal6 PHP5 module"; echo;
	D56PACK=1
	;;

	# JOOMLA PHP5
	j5)
	echo; echo "Generating Joomla PHP5 module"; echo;
	J5PACK=1
	;;

	# WORDPRESS PHP5
	wp5)
	echo; echo "Generating Wordpress PHP5 module"; echo;
	WP5PACK=1
	;;

	# ALL
	all)
	echo; echo "Generating all we've got."; echo;
	D5PACK=1
	D56PACK=1
	J5PACK=1
	WP5PACK=1
	SKPACK=1
	L10NPACK=1
	;;

	# USAGE
	*)
	display_usage
	exit 0
	;;

esac

## Make sure we have the right branch or tag
pushd "$DM_SOURCEDIR"
git checkout "$DM_REF_CORE"
popd
pushd "$DM_SOURCEDIR/packages"
git checkout "$DM_REF_PACKAGES"
popd
## in theory, this shouldn't matter, but GenCode is CMS-dependent, and we've been doing our past builds based on D7
pushd "$DM_SOURCEDIR/drupal"
git checkout "$DM_REF_DRUPAL"
popd

# Before anything - regenerate DAOs

cd $DM_SOURCEDIR/xml
$DM_PHP GenCode.php schema/Schema.xml $DM_VERSION

cd $ORIGPWD

if [ "$L10NPACK" = 1 ]; then
	echo; echo "Packaging for L10N"; echo;
	bash $P/dists/l10n.sh
fi

if [ "$D56PACK" = 1 ]; then
	echo; echo "Packaging for Drupal6, PHP5 version"; echo;
	bash $P/dists/drupal6_php5.sh
fi

if [ "$D5PACK" = 1 ]; then
	echo; echo "Packaging for Drupal7, PHP5 version"; echo;
	bash $P/dists/drupal_php5.sh
fi

if [ "$SKPACK" = 1 ]; then
	echo; echo "Packaging for Drupal7, PHP5 StarterKit version"; echo;
	bash $P/dists/drupal_sk_php5.sh
fi

if [ "$J5PACK" = 1 ]; then
	echo; echo "Packaging for Joomla, PHP5 version"; echo;
	bash $P/dists/joomla_php5.sh
fi

if [ "$WP5PACK" = 1 ]; then
	echo; echo "Packaging for Wordpress, PHP5 version"; echo;
	bash $P/dists/wordpress_php5.sh
fi

unset DM_SOURCEDIR DM_GENFILESDIR DM_TARGETDIR DM_TMPDIR DM_PHP DM_RSYNC DM_VERSION DM_ZIP
echo;echo "DISTMAKER Done.";echo;
