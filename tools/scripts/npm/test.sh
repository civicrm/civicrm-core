#!/bin/bash
if which node_modules/karma/bin/karma >> /dev/null; then
  node node_modules/karma/bin/karma start tests/karma.conf.js
elif which karma >> /dev/null ; then
  karma start tests/karma.conf.js
else
  echo "ERROR: Failed to find karma"
  exit 1
fi
