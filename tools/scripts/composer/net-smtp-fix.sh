#!/bin/bash

## Cleanup the vendor tree. The main issue here is that civi Civi is
## deployed as a module inside a CMS, so all its source-code gets published.
## Some libraries distribute admin tools and sample files which should not
## be published.
##
## This script should be idempotent -- if you rerun it several times, it
## should always produce the same post-condition.

## Replace a line in a file
## This is a bit like 'sed -i', but dumber and more cross-platform.

##############################################################################
## usage: safe_delete <relpath...>
function safe_delete() {
  for file in "$@" ; do
    if [ -z "$file" ]; then
      echo "Skip: empty file name"
    elif [ -e "$file" ]; then
      rm -rf "$file"
    fi
  done
}

safe_delete vendor/pear/net_smtp/{README.rst,examples,phpdoc.sh,tests}
