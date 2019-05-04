#!/usr/bin/env bash
set -e

EXTROOT=$(cd `dirname $0`/..; pwd)
EXTKEY="org.civicrm.afform-html"

##############################
function do_help() {
  echo "usage: $0 [options]"
  echo "example: $0"
  echo "  -h     (Help)           Show this help screen"
  echo "  -D     (Download)       Download dependencies"
  echo "  -z     (Zip)            Build installable ZIP file"
}

##############################
function use_civiroot() {
  if [ -z "$CIVIROOT" ]; then
    CIVIROOT=$(cv ev 'echo $GLOBALS["civicrm_root"];')
    if [ -z "$CIVIROOT" -o ! -d "$CIVIROOT" ]; then
      do_help
      echo ""
      echo "ERROR: invalid civicrm-dir: [$CIVIROOT]"
      exit
    fi
  fi
}

##############################
function cleanup() {
  use_civiroot
  ## No DAOs or XML build to cleanup
}

##############################
function do_download() {
  pushd "$EXTROOT" >> /dev/null
    npm install
  popd >> /dev/null
}

##############################
## Build installable ZIP file
function do_zipfile() {
  local canary="$EXTROOT/node_modules/monaco-editor/package.json"
  if [ ! -f "$canary" ]; then
    echo "Error: File $canary missing. Are you sure the build is ready?"
    exit 1
  fi

  local zipfile="$EXTROOT/build/$EXTKEY.zip"
  [ -f "$zipfile" ] && rm -f "$zipfile"
  [ ! -d "$EXTROOT/build" ] && mkdir "$EXTROOT/build"
  pushd "$EXTROOT" >> /dev/null
    ## Build a list of files to include.
    ## Put the files into the *.zip, using a $EXTKEY as a prefix.
    {
       ## Get any files in the project root, except for dotfiles.
       find . -mindepth 1 -maxdepth 1 -type f -o -type d | grep -v '^\./\.'
       ## Get any files in the main subfolders.
       #find CRM/ ang/ api/ bin/ css/ js/ sql/ sass/ settings/ templates/ tests/ xml/ -type f -o -type d
       find bin/ xml/ -type f -o -type d
       ## Get the distributable files for Monaco.
       find node_modules/monaco-editor/LICENSE node_modules/monaco-editor/min -type f -o -type d
    } \
      | grep -v '~$' \
      | php bin/add-zip-regex.php "$zipfile" ":^:" "$EXTKEY/"
  popd >> /dev/null
  echo "Created: $zipfile"
}

##############################
## Main
HAS_ACTION=

while getopts "aDghz" opt; do
  case $opt in
    h)
      do_help
      HAS_ACTION=1
      ;;
    D)
      do_download
      HAS_ACTION=1
      ;;
    z)
      do_zipfile
      HAS_ACTION=1
      ;;
    \?)
      do_help
      echo "Invalid option: -$OPTARG" >&2
      exit 1
      ;;
    :)
      echo "Option -$OPTARG requires an argument." >&2
      exit 1
      ;;
  esac
done

if [ -z "$HAS_ACTION" ]; then
  do_help
  exit 2
fi
