#!/bin/bash
if which node_modules/karma/bin/karma >> /dev/null; then
  node node_modules/karma/bin/karma start
elif which karma >> /dev/null ; then
  karma start
else
  echo "ERROR: Failed to find karma"
  exit 1
fi
