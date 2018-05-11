#!/bin/bash

## Install/Update the node and bower libraries which are deployed under civicrm

source "bin/setup.lib.sh"

set -x

if has_commands bower karma ; then
  ## dev dependencies have been installed globally; don't force developer to redownload
  npm install --production
else
  npm install
fi

BOWER=$(pickcmd node_modules/bower/bin/bower bower);
if [ -f "$BOWER" ]; then
  NODE=$(pickcmd node nodejs)
  BOWER="$NODE $BOWER"
fi
# Without the force flag, bower may not check for new versions or verify that installed software matches version specified in bower.json
# With the force flag, bower will ignore all caches and download all deps.
if [ -n "$OFFLINE" ]; then
  BOWER_OPT=
elif [ ! -f "bower_components/.setupsh.ts" ]; then
  ## First run -- or cleanup from failed run
  BOWER_OPT=-f
elif [ "bower.json" -nt "bower_components/.setupsh.ts" ]; then
  ## Bower.json has changed since last run
  BOWER_OPT=-f
fi
[ -f "bower_components/.setupsh.ts" ] && rm -f "bower_components/.setupsh.ts"
$BOWER install $BOWER_OPT
touch bower_components/.setupsh.ts
