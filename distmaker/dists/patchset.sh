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

DM_MAJMIN=$(echo "$DM_VERSION" | cut -f1,2 -d\. )
REFTAG=$(grep -h "^${DM_MAJMIN}:" "$P/../patchset-baselines.txt" | cut -f2 -d: )
if [ -z "$REFTAG" ]; then
  echo "The branch ${DM_MAJMIN} does not have a reference version. No patchset to generate."
  exit 0
fi

SRC="$DM_SOURCEDIR"
TRG="$DM_TMPDIR/civicrm-$DM_VERSION"

# export patch files for each repo
dm_reset_dirs "$TRG"
mkdir -p "$TRG"/civicrm-{core,drupal-6,drupal-7,drupal-8,backdrop,packages,joomla,wordpress}
dm_export_patches "$SRC"            "$TRG/civicrm-core"       $REFTAG..$DM_REF_CORE
# dm_export_patches "$SRC/drupal"     "$TRG/civicrm-drupal-6"   6.x-$REFTAG..$DM_REF_DRUPAL6
dm_export_patches "$SRC/drupal"     "$TRG/civicrm-drupal-7"   7.x-$REFTAG..$DM_REF_DRUPAL
dm_export_patches "$SRC/drupal-8"   "$TRG/civicrm-drupal-8"   $REFTAG..$DM_REF_DRUPAL8
dm_export_patches "$SRC/backdrop"   "$TRG/civicrm-backdrop"   1.x-$REFTAG..$DM_REF_BACKDROP
dm_export_patches "$SRC/packages"   "$TRG/civicrm-packages"   $REFTAG..$DM_REF_PACKAGES
dm_export_patches "$SRC/joomla"     "$TRG/civicrm-joomla"     $REFTAG..$DM_REF_JOOMLA
dm_export_patches "$SRC/wordpress"  "$TRG/civicrm-wordpress"  $REFTAG..$DM_REF_WORDPRESS


# gen tarball
cd "$DM_TMPDIR"
tar czf $DM_TARGETDIR/civicrm-$DM_VERSION-patchset.tar.gz civicrm-$DM_VERSION

# clean up
rm -rf $TRG
