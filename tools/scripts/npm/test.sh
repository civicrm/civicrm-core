#!/bin/bash
if which node_modules/karma/bin/karma >> /dev/null; then
  if which nodejs >> /dev/null; then
    ## Debian
    nodejs node_modules/karma/bin/karma start --browsers PhantomJS_custom
  else
    ## Official
    node node_modules/karma/bin/karma start --browsers PhantomJS_custom
  fi
elif which karma >> /dev/null ; then
  karma start --browsers PhantomJS_custom
else
  echo "ERROR: Failed to find karma"
  exit 1
fi
