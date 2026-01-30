#!/usr/bin/env bash
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
D7PACK=0
D7DIR=0
J4PACK=0
J5PACKBC=0
WPPACK=0
PATCHPACK=0
STANDALONEPACK=0
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
  echo "  all               - generate all available tarballs"
  echo "  l10n              - generate internationalization data"
  echo "  Backdrop|bd       - generate Backdrop module"
  echo "  Drupal|d7         - generate Drupal7 module"
  echo "  d7_dir            - generate Drupal7 module, but output to a directory, no tarball"
  echo "  Joomla4|Joomla|j4 - generate Joomla 4 module"
  echo "  Joomla5bc|j5bc    - generate Joomla 5 module requiring the Back Compatibility plugin"
  echo "  WordPress|wp      - generate Wordpress module"
  echo "  patchset          - generate a tarball with patch files"
  echo "  standalone        - generate CiviCRM Standalone"
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
    dm_note "Setting source dir to $DM_SOURCEDIR";
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
    export DM_SOURCEDIR DM_GENFILESDIR DM_TMPDIR DM_TARGETDIR DM_PHP DM_RSYNC DM_ZIP DM_VERSION DM_REF_CORE DM_REF_DRUPAL DM_REF_DRUPAL8 DM_REF_JOOMLA DM_REF_WORDPRESS DM_REF_STANDALONE DM_REF_PACKAGES
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
  # L10N
  l10n)
  dm_note "Enable CiviCRM L10N"
  L10NPACK=1
  ;;

  # BACKDROP
  bd|Backdrop)
  dm_note "Enable CiviCRM-Backdrop"
  BPACK=1
  ;;

  # DRUPAL7
  d7|Drupal)
  dm_note "Enable CiviCRM-Drupal 7"
  D7PACK=1
  ;;

  # Drupal 7 - Output to directory
  d7_dir)
  dm_note "Enable CiviCRM-Drupal 7 (directory)"
  D7DIR=1
  ;;

  # DRUPAL7 StarterKit package
  sk)
  dm_note "Skip CiviCRM-Drupal 7 (StarterKit no longer provided)"
  ;;

  # JOOMLA4
  j4|Joomla4|Joomla)
  dm_note "Enable CiviCRM-Joomla 4"
  J4PACK=1
  ;;

  # JOOMLA5BC
  j5bc|Joomla5bc)
  dm_note "Enable CiviCRM-Joomla 5 requiring Back Compatibility plugin"
  J5PACKBC=1
  ;;

  # WORDPRESS
  wp|WordPress)
  dm_note "Enable CiviCRM-Wordpress"
  WPPACK=1
  ;;

  # STANDALONE
  standalone|Standalone)
  dm_note "Enable CiviCRM (Standalone)"
  STANDALONEPACK=1
  ;;

  ## PATCHSET export
  patchset)
  dm_note "Enable CiviCRM (Patchset)"
  PATCHPACK=1
  ;;

  # REPO REPORT
  report)
  dm_note "Enable repo report"
  REPOREPORT=1
  ;;

  # ALL
  all)
  dm_note "Enable all the tarballs we've got (not the directories). "
  BPACK=1
  D7PACK=1
  J4PACK=1
  J5PACKBC=1
  WPPACK=1
  PATCHPACK=1
  STANDALONEPACK=1
  L10NPACK=1
  REPOREPORT=1
  ;;

  # USAGE
  *)
  display_usage
  exit 0
  ;;

esac

dm_title "Update source"
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

if [ -z "$DM_KEEP_DEPS" ]; then
  ## Get fresh dependencies
  [ -d "$DM_SOURCEDIR/vendor" ] && rm -rf $DM_SOURCEDIR/vendor
  [ -d "$DM_SOURCEDIR/bower_components" ] && rm -rf $DM_SOURCEDIR/bower_components
  dm_generate_vendor "$DM_SOURCEDIR"
fi

cd "$DM_SOURCEDIR/xml"
"${DM_PHP:-php}" GenCode.php schema/Schema.xml "$DM_VERSION" "$GENCODE_CMS"

cd $ORIGPWD

if [ "$L10NPACK" = 1 ]; then
  dm_title "Build CiviCRM-L10N"
  bash $P/dists/l10n.sh
fi

if [ "$BPACK" = 1 ]; then
  dm_title "Build CiviCRM-Backdrop"
  dm_git_checkout "$DM_SOURCEDIR/backdrop" "$DM_REF_BACKDROP"
  bash $P/dists/backdrop.sh
fi

if [ "$D7PACK" = 1 ]; then
  dm_title "Build CiviCRM-Drupal 7"
  dm_git_checkout "$DM_SOURCEDIR/drupal" "$DM_REF_DRUPAL"
  bash $P/dists/drupal7.sh
fi

if [ "$D7DIR" = 1 ]; then
  dm_title "Build CiviCRM-Drupal 7 (directory)"
  dm_git_checkout "$DM_SOURCEDIR/drupal" "$DM_REF_DRUPAL"
  bash $P/dists/drupal7_dir.sh
fi

if [ "$J4PACK" = 1 ]; then
  dm_title "Build CiviCRM-Joomla 4"
  dm_git_checkout "$DM_SOURCEDIR/joomla" "$DM_REF_JOOMLA"
  bash $P/dists/joomla4.sh
fi

if [ "$J5PACKBC" = 1 ]; then
  dm_title "Build CiviCRM-Joomla 5 (Back Compatibility)"
  dm_git_checkout "$DM_SOURCEDIR/joomla" "$DM_REF_JOOMLA"
  bash $P/dists/joomla5bc.sh
fi

if [ "$WPPACK" = 1 ]; then
  dm_title "Build CiviCRM-Wordpress"
  dm_git_checkout "$DM_SOURCEDIR/WordPress" "$DM_REF_WORDPRESS"
  bash $P/dists/wordpress.sh
fi

if [ "$STANDALONEPACK" = 1 ]; then
  dm_title "Build CiviCRM (Standalone)"
  bash $P/dists/standalone.sh
fi

if [ "$PATCHPACK" = 1 ]; then
  dm_title "Build CiviCRM (Patchset)"
  bash $P/dists/patchset.sh
fi

if [ "$REPOREPORT" = 1 ]; then
  dm_title "Prepare repository report"
  env \
    L10NPACK="$L10NPACK" \
    BPACK="$BPACK" \
    D7PACK="$D7PACK" \
    D7DIR="$D7DIR" \
    J4PACK="$J4PACK" \
    J5PACKBC="$J5PACKBC" \
    WPPACK="$WPPACK" \
    STANDALONEPACK="$STANDALONEPACK" \
    bash $P/dists/repo-report.sh
fi

unset DM_SOURCEDIR DM_GENFILESDIR DM_TARGETDIR DM_TMPDIR DM_PHP DM_RSYNC DM_VERSION DM_ZIP
dm_title "DISTMAKER Done."
