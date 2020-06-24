#!/bin/bash -v
set -e

# This is distmaker script for CiviCRM
# Author: michau
# "Protected by an electric fence and copyright control."
# Thanks to Kleptones for moral support when writing this.

# You need to ensure the following variables are defined. They are traditionally defined
# in distmaker.conf but you can also defined them as an environment variable or by
# calling the script with env DM_REF_CORE=4.7 (for example).
#
# DM_SOURCEDIR=/home/user/svn/civicrm           <- sources
# DM_GENFILESDIR=/home/user/generated           <- generated files
# DM_TMPDIR=/tmp                                <- temporary files (will be deleted afterwards)
# DM_TARGETDIR=/tmp/outdir                      <- target dir for tarballs
# DM_REF_CORE=master                            <- Git branch/tag name
#
# Optional variables
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
source "$P/dists/common.sh"

# Set no actions by default
BPACK=0
D5PACK=0
D56PACK=0
D7DIR=0
J5PACK=0
WP5PACK=0
SK5PACK=0
L10NPACK=0
REPOREPORT=0

# Display usage
display_usage()
{
  echo
  echo "Usage: "
  echo "  distmaker.sh OPTION"
  echo
  echo "Options available:"
  echo "  all            - generate all available tarballs"
  echo "  l10n           - generate internationalization data"
  echo "  Backdrop       - generate Backdrop PHP5 module"
  echo "  Drupal|d5      - generate Drupal7 PHP5 module"
  echo "  Drupal6|d5.6   - generate Drupal6 PHP5 module"
  echo "  d7_dir         - generate Drupal7 PHP5 module, but output to a directory, no tarball"
  echo "  Joomla|j5      - generate Joomla PHP5 module"
  echo "  WordPress|wp5  - generate Wordpress PHP5 module"
  echo "  sk             - generate Drupal StarterKit module"
  echo
  echo "You also need to have distmaker.conf file in place."
  echo "See distmaker.conf.dist for example contents."
  echo "Alternatively you can set the required variables as "
  echo "environment variables, eg. for your machine or by calling "
  echo "this script using "
  echo
  echo "env DM_TARGETDIR=/path/to/output/dir/for/files/or/tarball distmaker.sh all"
  echo
  echo "optional environmentals:"
  echo "   DM_SOURCEDIR "
  echo "   DM_GENFILESDIR (default $TMPDIR/genfiles)"
  echo "   DM_TARGETDIR= (default $TMPDIR/civicrm)"
  echo "   DM_OUTPUTDIR= (default $DM_TARGETDIR/civicrm_files)"
  echo

}

THIS_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )";

# Check if config is ok.
check_conf()
{
  if [ -f $P/distmaker.conf ] ; then
    source "$P/distmaker.conf"
  fi

  if [ -z $DM_SOURCEDIR ] ; then
    DM_SOURCEDIR="$THIS_DIR/..";
    echo "Setting source dir to $DM_SOURCEDIR";
  fi

  if [ -z $DM_TMPDIR ] && [ -e $TMPDIR ] ; then
    DM_TMPDIR=$TMPDIR/civicrm
  fi

  if [ -z $DM_GENFILESDIR ] && [ -e $TMPDIR ] ; then
    DM_GENFILESDIR=$TMPDIR/genfiles
  fi

  if [ -z $DM_PACKAGESDIR ] ; then
    DM_PACKAGESDIR="$DM_SOURCEDIR/packages"
  fi

  if [ -z $DM_OUTPUTDIR ] ; then
    DM_OUTPUTDIR="$DM_TARGETDIR/civicrm_files"
  fi

  # Test for distmaker.conf file availability, cannot proceed without it anyway
  if [ -z $DM_GENFILESDIR ] || [ -z $DM_TMPDIR ] || [ -z $DM_TARGETDIR ]; then
    echo; echo "Required variables not defined!"; echo;
    display_usage
    echo "your variables"
    echo "DM_SOURCEDIR : $DM_SOURCEDIR";
    echo "DM_TARGETDIR : $DM_TARGETDIR (required)";
    echo "DM_TMPDIR : $DM_TMPDIR";
    echo "DM_GENFILESDIR : $DM_GENFILESDIR";
    echo "DM_PACKAGESDIR : $DM_PACKAGESDIR";
    echo "Current directory is : $THIS_DIR";
    exit 1
  else
    export DM_SOURCEDIR DM_GENFILESDIR DM_TMPDIR DM_TARGETDIR DM_PHP DM_RSYNC DM_ZIP DM_VERSION DM_REF_CORE DM_REF_DRUPAL DM_REF_DRUPAL6 DM_REF_DRUPAL8 DM_REF_JOOMLA DM_REF_WORDPRESS DM_REF_PACKAGES
    if [ ! -d "$DM_SOURCEDIR" ]; then
      echo; echo "ERROR! " DM_SOURCEDIR "directory not found!"; echo "(if you get empty directory name, it might mean that one of necessary variables is not set)"; echo;
    fi
    for k in "$DM_GENFILESDIR" "$DM_TARGETDIR" "$DM_TMPDIR"; do
      if [ -z "$k" ] ; then
        echo; echo "ERROR! " $k "directory not found!"; echo "(if you get empty directory name, it might mean that one of necessary variables is not set)"; echo;
        exit 1
      fi
      if [ ! -d "$k" ]; then
        mkdir -p "$k"
      fi
    done
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

  # BACKDROP PHP5
  Backdrop)
  echo; echo "Generating Backdrop PHP5 module"; echo;
  BPACK=1
  ;;

  # DRUPAL7 PHP5
  d5|Drupal)
  echo; echo "Generating Drupal7 PHP5 module"; echo;
  D5PACK=1
  ;;

  # Drupal 7 - Output to directory
  d7_dir)
  echo; echo "Generating Drupal7 Directory"; echo;
  D7DIR=1
  ;;

  # DRUPAL7 PHP5 StarterKit package
  sk)
  echo; echo "Generating Drupal7 PHP5 starter kit minimal module"; echo;
  SKPACK=1
  ;;

  # DRUPAL6 PHP5
  d5.6|Drupal6)
  echo; echo "Generating Drupal6 PHP5 module"; echo;
  D56PACK=1
  ;;

  # JOOMLA PHP5
  j5|Joomla)
  echo; echo "Generating Joomla PHP5 module"; echo;
  J5PACK=1
  ;;

  # WORDPRESS PHP5
  wp5|WordPress)
  echo; echo "Generating Wordpress PHP5 module"; echo;
  WP5PACK=1
  ;;

  # REPO REPORT PHP5
  report)
  echo; echo "Generating repo report module"; echo;
  REPOREPORT=1
  ;;

  # ALL
  all)
  echo; echo "Generating all the tarballs we've got (not the directories). "; echo;
  BPACK=1
  D5PACK=1
  D56PACK=1
  J5PACK=1
  WP5PACK=1
  SKPACK=1
  L10NPACK=1
  REPOREPORT=1
  ;;

  # USAGE
  *)
  display_usage
  exit 0
  ;;

esac

## Make sure we have the right branch or tag
dm_git_checkout "$DM_SOURCEDIR" "$DM_REF_CORE"
dm_git_checkout "$DM_PACKAGESDIR" "$DM_REF_PACKAGES"

## in theory, this shouldn't matter, but GenCode is CMS-dependent, and we've been doing our past builds based on D7
GENCODE_CMS=
if [ -d "$DM_SOURCEDIR/backdrop" ]; then
  dm_git_checkout "$DM_SOURCEDIR/backdrop" "$DM_REF_BACKDROP"
  GENCODE_CMS=Backdrop
fi
if [ -d "$DM_SOURCEDIR/drupal" ]; then
  dm_git_checkout "$DM_SOURCEDIR/drupal" "$DM_REF_DRUPAL"
  GENCODE_CMS=Drupal
fi
if [ -d "$DM_SOURCEDIR/drupal-8" ]; then
  dm_git_checkout "$DM_SOURCEDIR/drupal-8" "$DM_REF_DRUPAL8"
fi

## Get fresh dependencies
[ -d "$DM_SOURCEDIR/vendor" ] && rm -rf $DM_SOURCEDIR/vendor
[ -d "$DM_SOURCEDIR/bower_components" ] && rm -rf $DM_SOURCEDIR/bower_components
dm_generate_vendor "$DM_SOURCEDIR"

# Before anything - regenerate DAOs

cd $DM_SOURCEDIR/xml
${DM_PHP:-php} GenCode.php schema/Schema.xml $DM_VERSION $GENCODE_CMS

cd $ORIGPWD

if [ "$L10NPACK" = 1 ]; then
  echo; echo "Packaging for L10N"; echo;
  bash $P/dists/l10n.sh
fi

if [ "$BPACK" = 1 ]; then
  echo; echo "Packaging for Backdrop, PHP5 version"; echo;
  dm_git_checkout "$DM_SOURCEDIR/backdrop" "$DM_REF_BACKDROP"
  bash $P/dists/backdrop_php5.sh
fi

if [ "$D56PACK" = 1 ]; then
  echo; echo "Packaging for Drupal6, PHP5 version"; echo;
  dm_git_checkout "$DM_SOURCEDIR/drupal" "$DM_REF_DRUPAL6"
  bash $P/dists/drupal6_php5.sh
fi

if [ "$D5PACK" = 1 ]; then
  echo; echo "Packaging for Drupal7, PHP5 version"; echo;
  dm_git_checkout "$DM_SOURCEDIR/drupal" "$DM_REF_DRUPAL"
  bash $P/dists/drupal_php5.sh
fi

if [ "$D7DIR" = 1 ]; then
  echo; echo "Packaging for Drupal7, Directory not tarball, PHP5 version"; echo;
  dm_git_checkout "$DM_SOURCEDIR/drupal" "$DM_REF_DRUPAL"
  bash $P/dists/drupal7_dir_php5.sh
fi

if [ "$SKPACK" = 1 ]; then
  echo; echo "Packaging for Drupal7, PHP5 StarterKit version"; echo;
  dm_git_checkout "$DM_SOURCEDIR/drupal" "$DM_REF_DRUPAL"
  bash $P/dists/drupal_sk_php5.sh
fi

if [ "$J5PACK" = 1 ]; then
  echo; echo "Packaging for Joomla, PHP5 version"; echo;
  dm_git_checkout "$DM_SOURCEDIR/joomla" "$DM_REF_JOOMLA"
  bash $P/dists/joomla_php5.sh
fi

if [ "$WP5PACK" = 1 ]; then
  echo; echo "Packaging for Wordpress, PHP5 version"; echo;
  dm_git_checkout "$DM_SOURCEDIR/WordPress" "$DM_REF_WORDPRESS"
  bash $P/dists/wordpress_php5.sh
fi

if [ "$REPOREPORT" = 1 ]; then
  echo; echo "Preparing repository report"; echo;
  env \
    L10NPACK="$L10NPACK" \
    BPACK="$BPACK" \
    D56PACK="$D56PACK" \
    D5PACK="$D5PACK" \
    D7DIR="$D7DIR" \
    SKPACK="$SKPACK" \
    J5PACK="$J5PACK" \
    WP5PACK="$WP5PACK" \
    bash $P/dists/repo-report.sh
fi

unset DM_SOURCEDIR DM_GENFILESDIR DM_TARGETDIR DM_TMPDIR DM_PHP DM_RSYNC DM_VERSION DM_ZIP
echo;echo "DISTMAKER Done.";echo;
