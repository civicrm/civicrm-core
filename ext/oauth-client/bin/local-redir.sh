#!/bin/bash
set -e

## Most OAuth2 providers allow you to register local dev sites if the "Redirect URL"
## looks like "http://localhost:NNNN" or "http://127.0.0.1:NNNN".
##
## It is common in the CiviCRM community to develop on local virtual-hosts like "http://example.local".
## These URLs cannot be directly registered with most OAuth2 providers.
##
## To resolve this either (1) enable HTTPS locally or (2) setup an intermediate redirect, e.g.
##
##   http://localhost:3000/my-return ==> http://example.local/civicrm/oauth/return
##   https://public.example.com/my-return ==> http://example.local/civicrm/oauth/return
##
## The script "local-redir.sh" can help you setup an intermediate redirect. It will:
##
## 1. Launch a temporary HTTP service on "http://localhost:3000".
## 2. Configure CiviCRM to work with "http://localhost:3000".
##
################################################################################

## usage:      local-redir.sh [ip-or-host[:port]]
##
## example#1:  local-redir.sh
## example#2:  local-redir.sh 127.0.0.1
## example#3:  local-redir.sh localhost:8000

###############################################################################
## Bootstrap

## Determine the absolute path of the directory with the file
## usage: absdirname <file-path>
function absdirname() {
  pushd $(dirname $0) >> /dev/null
    pwd
  popd >> /dev/null
}

BINDIR=$(absdirname "$0")
REDIRPHP="$BINDIR/local-redir.php"

###############################################################################
## Main

BIND=${1:-localhost:3000}
DEST=$(cv url -I civicrm/oauth-client/return)

echo "local-redir.sh: Setup redirect proxy"
echo
echo "Intermediate URL: http://$BIND"
echo "Canonical URL:    $DEST"
echo
echo "Update CiviCRM settings:"
cv api setting.create oauthClientRedirectUrl="http://$BIND"

export DEST
php -S "$BIND" "$REDIRPHP"

echo "Shutting down"
echo
echo "Reverting CiviCRM settings: oauthClientRedirectUrl"
cv ev 'Civi::settings()->revert("oauthClientRedirectUrl");'
