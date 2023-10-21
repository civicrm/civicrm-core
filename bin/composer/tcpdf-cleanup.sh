#!/bin/bash

## Cleanup the vendor tree. The main issue here is that civi Civi is
## deployed as a module inside a CMS, so all its source-code gets published.
## Some libraries distribute admin tools and sample files which should not
## be published.
##
## This script should be idempotent -- if you rerun it several times, it
## should always produce the same post-condition.

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

##############################################################################
## Remove example/CLI scripts. They're not needed and increase the attack-surface.
safe_delete vendor/tecnickcom/tcpdf/examples
safe_delete vendor/tecnickcom/tcpdf/tools

## Remove all fonts not included before CRM-18098.
safe_delete vendor/tecnickcom/tcpdf/fonts/a*
safe_delete vendor/tecnickcom/tcpdf/fonts/ci*
safe_delete vendor/tecnickcom/tcpdf/fonts/courierb*
safe_delete vendor/tecnickcom/tcpdf/fonts/courieri*
safe_delete vendor/tecnickcom/tcpdf/fonts/dejavu-fonts-ttf-2.33
safe_delete vendor/tecnickcom/tcpdf/fonts/dejavusansb*
safe_delete vendor/tecnickcom/tcpdf/fonts/dejavusansc*
safe_delete vendor/tecnickcom/tcpdf/fonts/dejavusanse*
safe_delete vendor/tecnickcom/tcpdf/fonts/dejavusansi*
safe_delete vendor/tecnickcom/tcpdf/fonts/dejavusansm*
safe_delete vendor/tecnickcom/tcpdf/fonts/dejavuserif*
safe_delete vendor/tecnickcom/tcpdf/fonts/free*
safe_delete vendor/tecnickcom/tcpdf/fonts/helveticab*
safe_delete vendor/tecnickcom/tcpdf/fonts/helveticai*
safe_delete vendor/tecnickcom/tcpdf/fonts/k*
safe_delete vendor/tecnickcom/tcpdf/fonts/m*
safe_delete vendor/tecnickcom/tcpdf/fonts/p*
safe_delete vendor/tecnickcom/tcpdf/fonts/s*
safe_delete vendor/tecnickcom/tcpdf/fonts/u*
safe_delete vendor/tecnickcom/tcpdf/fonts/z*
