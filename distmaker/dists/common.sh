#!/bin/bash

## Copy files from one dir into another dir
## usage: dm_install_dir <from-dir> <to-dir>
function dm_install_dir() {
  local from="$1"
  local to="$2"

  if [ ! -d "$to" ]; then
    mkdir -p "$to"
  fi
  rsync -avC --exclude=.git --exclude=.svn "$from/./"  "$to/./"
}

## Copy listed files
## usage: dm_install_files <from-dir> <to-dir> <file1> <file2>...
function dm_install_files() {
  local from="$1"
  shift
  local to="$1"
  shift

  for file in "$@" ; do
    [ -f "$from/$file" ] && cp -f "$from/$file" "$to/$file"
  done
}

## usage: dm_remove_files <directory> <file1> <file2>...
function dm_remove_files() {
  local tgt="$1"
  shift

  for file in "$@" ; do
    [ -f "$tgt/$file" ] && rm -f "$tgt/$file"
  done
}

## Copy all core files
## usage: dm_install_core <core_repo_path> <to_path>
function dm_install_core() {
  local repo="$1"
  local to="$2"

  for dir in css i js PEAR templates bin CRM api extern Reports install settings Civi ; do
    [ -d "$repo/$dir" ] && dm_install_dir "$repo/$dir" "$to/$dir"
  done

  dm_install_files "$repo" "$to" {agpl-3.0,agpl-3.0.exception,gpl,README,CONTRIBUTORS}.txt

  mkdir -p "$to/sql"
  pushd "$repo" >> /dev/null
    dm_install_files "$repo" "$to" sql/civicrm*.mysql sql/case_sample*.mysql sql/counties.US.sql.gz
    ## TODO: for master, remove counties.US.SQL.gz
  popd >> /dev/null

  if [ -d $to/bin ] ; then
    rm -f $to/bin/setup.sh
    rm -f $to/bin/setup.php4.sh
    rm -f $to/bin/setup.bat
  fi

  set +e
  rm -rf $to/sql/civicrm_*.??_??.mysql
  set -e
}

## Copy all packages
## usage: dm_install_packages <packages_repo_path> <to_path>
function dm_install_packages() {
  local repo="$1"
  local to="$2"

  local excludes_rsync=""
  for exclude in .git .svn _ORIGINAL_ SeleniumRC PHPUnit PhpDocumentor SymfonyComponents amavisd-new git-footnote ; do
    excludes_rsync="--exclude=${exclude} ${excludes_rsync}"
  done

  [ ! -d "$to" ] && mkdir "$to"
  rsync -avC $excludes_rsync --include=core "$repo/./" "$to/./"
}

## Copy Drupal-integration module
## usage: dm_install_drupal <drupal_repo_path> <to_path>
function dm_install_drupal() {
  local repo="$1"
  local to="$2"
  dm_install_dir "$repo" "$to"
}

## TODO: Merge this into dm_install_drupal; use on all Drupal releases
## usage: dm_install_drupal_info <to_path>
function dm_install_drupal_info() {
  local to="$1"

  # set full version in .info files
  local MODULE_DIRS=`find "$to" -type f -name "*.info"`
  for INFO in $MODULE_DIRS; do
    if [ $(uname) = "Darwin" ]; then
      ## BSD sed
      sed -i '' "s/version = [1-9.]*/version = $DM_VERSION/g" $INFO
    else
      ## GNU sed
      sed -i'' "s/version = [1-9.]*/version = $DM_VERSION/g" $INFO
    fi
  done
}

## Copy Joomla-integration module
## usage: dm_install_joomla <joomla_repo_path> <to_path>
function dm_install_joomla() {
  local repo="$1"
  local to="$2"
  dm_install_dir "$repo" "$to"
}

## usage: dm_install_l10n <l10n_repo_path> <to_path>
function dm_install_l10n() {
  local repo="$1"
  local to="$2"
  dm_install_dir "$repo" "$to"
}

## Generate civicrm-version.php
## usage: dm_generate_version <file> <ufname>
function dm_generate_version() {
  local to="$1"
  local ufname="$2"

  # final touch
  echo "<?php
function civicrmVersion( ) {
  return array( 'version'  => '$DM_VERSION',
                'cms'      => '$ufname',
                'revision' => '$DM_REVISION' );
}
" > "$to"
}
