#!/bin/bash
if [ -d node_modules ]; then
  find node_modules/ -name '*.info' -type f -delete
fi
