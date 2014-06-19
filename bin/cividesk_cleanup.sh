#! /bin/bash

if [[ !( -d bin && -d api && -d CRM && -d templates && -d xml ) ]] ; then
  echo "ERROR: You must execute this script from the CiviCRM root";
  exit;
fi

if [[ !( -d ../civicrm-l10n ) ]] || find ../civicrm-l10n -maxdepth 0 -mtime +1 | egrep '.*'; then
  echo -e "\nRefreshing l10n directory";
  pushd .. > /dev/null
  wget -q -O - http://download.civicrm.org/civicrm-l10n-core/archives/civicrm-l10n-daily.tar.gz | tar xfz -
  mv civicrm-l10n civicrm-l10n-old
  mv l10n civicrm-l10n
  touch civicrm-l10n
  rm -Rf civicrm-l10n-old
  popd > /dev/null
fi
if [[ !( -d l10n && -L l10n ) ]] ; then
  echo -e "\nLinking l10n directory";
  ln -s ../civicrm-l10n l10n
fi

echo -e "\nRegenerating files"
pushd xml > /dev/null
php GenCode.php
popd > /dev/null

echo -e "\nDeleting devel directories"
rm -Rf distmaker tests tools
