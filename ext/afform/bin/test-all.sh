#!/bin/bash

## Run all afform-related tests
##
## Usage: ./bin/test-all.sh

set -e
EXIT=0

cv en afform afform_mock
AFF_CORE=$(cv path -x afform)
AFF_MOCK=$(cv path -x afform_mock)

pushd "$AFF_CORE" >> /dev/null
  if ! phpunit6 "$@" ; then
    EXIT=1
  fi
popd >> /dev/null

pushd "$AFF_MOCK" >> /dev/null
  if ! phpunit6 --group e2e "$@" ; then
    EXIT=1
  fi
popd >> /dev/null

pushd "$AFF_MOCK" >> /dev/null
  if ! phpunit6 --group headless "$@" ; then
    EXIT=1
  fi
popd >> /dev/null

exit "$EXIT"
