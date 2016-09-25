#!/bin/bash
if which node_modules/karma/bin/karma >> /dev/null; then
  if which nodejs >> /dev/null; then
    ## Debian
    nodejs node_modules/karma/bin/karma start
  else
    ## Official
    node node_modules/karma/bin/karma start
  fi
elif which karma >> /dev/null ; then
  karma start
else
  echo "ERROR: Failed to find karma"
  exit 1
fi
