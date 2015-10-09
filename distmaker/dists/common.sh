#!/bin/bash

## Delete/create a dir
## usage: dm_reset_dirs <path1> <path2> ...
function dm_reset_dirs() {
  for d in "$@" ; do
    [ -d "$d" ] && rm -rf "$d"
  done

  mkdir -p "$@"
}

## Copy files from one dir into another dir
## usage: dm_install_dir <from-dir> <to-dir>
function dm_install_dir() {
  local from="$1"
  local to="$2"

  if [ ! -d "$to" ]; then
    mkdir -p "$to"
  fi
  ${DM_RSYNC:-rsync} -avC --exclude=.git --exclude=.svn "$from/./"  "$to/./"
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

## Copy all bower dependencies
function dm_install_bower() {
  local repo="$1"
  local to="$2"

  local excludes_rsync=""
  for exclude in .git .svn {T,t}est{,s} {D,d}oc{,s} {E,e}xample{,s} ; do
    excludes_rsync="--exclude=${exclude} ${excludes_rsync}"
  done

  [ ! -d "$to" ] && mkdir "$to"
  ${DM_RSYNC:-rsync} -avC $excludes_rsync "$repo/./" "$to/./"
}

## Copy all core files
## usage: dm_install_core <core_repo_path> <to_path>
function dm_install_core() {
  local repo="$1"
  local to="$2"

  for dir in ang css i js PEAR templates bin CRM api extern Reports install settings Civi partials ; do
    [ -d "$repo/$dir" ] && dm_install_dir "$repo/$dir" "$to/$dir"
  done

  dm_install_files "$repo" "$to" {agpl-3.0,agpl-3.0.exception,gpl,README,CONTRIBUTORS}.txt
  dm_install_files "$repo" "$to" composer.json composer.lock bower.json package.json Civi.php

  mkdir -p "$to/sql"
  pushd "$repo" >> /dev/null
    dm_install_files "$repo" "$to" sql/civicrm*.mysql sql/case_sample*.mysql
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
  for exclude in .git .svn _ORIGINAL_ SeleniumRC PHPUnit PhpDocumentor SymfonyComponents amavisd-new git-footnote PHP/CodeCoverage ; do
    excludes_rsync="--exclude=${exclude} ${excludes_rsync}"
  done

  ## Note: These small folders have items that previously were not published,
  ## but there's no real cost to including them, and excluding them seems
  ## likely to cause confusion as the codebase evolves:
  ##   packages/Files packages/PHP packages/Text

  [ ! -d "$to" ] && mkdir "$to"
  ${DM_RSYNC:-rsync} -avC $excludes_rsync --include=core "$repo/./" "$to/./"
}

## Copy Drupal-integration module
## usage: dm_install_drupal <drupal_repo_path> <to_path>
function dm_install_drupal() {
  local repo="$1"
  local to="$2"
  dm_install_dir "$repo" "$to"

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

  for f in "$to/.gitignore" "$to/.toxic.json" ; do
    if [ -f "$f" ]; then
      rm -f "$f"
    fi
  done
}

## Copy Joomla-integration module
## usage: dm_install_joomla <joomla_repo_path> <to_path>
function dm_install_joomla() {
  local repo="$1"
  local to="$2"
  dm_install_dir "$repo" "$to"

  ## Before this change, the zip file included the joomla-integration
  ## modules twice. The two were basically identical -- except that
  ## one included .gitignore and the omitted it. We'll now omit it
  ## consistently.

  for f in "$to/.gitignore" "$to/.toxic.json" ; do
    if [ -f "$f" ]; then
      rm -f "$f"
    fi
  done
}

## usage: dm_install_l10n <l10n_repo_path> <to_path>
function dm_install_l10n() {
  local repo="$1"
  local to="$2"
  dm_install_dir "$repo" "$to"
}

## Copy composer's "vendor" folder
## usage: dm_install_vendor <from_path> <to_path>
function dm_install_vendor() {
  local repo="$1"
  local to="$2"

  local excludes_rsync=""
  for exclude in .git .svn {T,t}est{,s} {D,d}oc{,s} {E,e}xample{,s} ; do
    excludes_rsync="--exclude=${exclude} ${excludes_rsync}"
  done

  [ ! -d "$to" ] && mkdir "$to"
  ${DM_RSYNC:-rsync} -avC $excludes_rsync "$repo/./" "$to/./"
}

##  usage: dm_install_wordpress <wp_repo_path> <to_path>
function dm_install_wordpress() {
  local repo="$1"
  local to="$2"

  if [ ! -d "$to" ]; then
    mkdir -p "$to"
  fi
  ${DM_RSYNC:-rsync} -avC \
    --exclude=.git \
    --exclude=.svn \
    --exclude=civicrm.config.php.wordpress \
    --exclude=.toxic.json \
    --exclude=.gitignore \
    --exclude=civicrm \
    "$repo/./"  "$to/./"
  ## Need --exclude=civicrm for self-building on WP site
}


## Generate the "bower_components" folder.
## usage: dm_generate_bower <repo_path>
function dm_generate_bower() {
  local repo="$1"
  pushd "$repo"
    ${DM_NPM:-npm} install
    ${DM_NODE:-node} node_modules/bower/bin/bower install
  popd
}

## Generate the composer "vendor" folder
## usage: dm_generate_vendor <repo_path>
function dm_generate_vendor() {
  local repo="$1"
  pushd "$repo"
    ${DM_COMPOSER:-composer} install
  popd
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

## Perform a hard checkout on a given report
## usage: dm_git_checkout <repo_path> <tree-ish>
function dm_git_checkout() {
  pushd "$1"
    git checkout .
    git checkout "$2"
  popd
}
