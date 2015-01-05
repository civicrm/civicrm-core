#!/bin/bash
if [ -d node_modules ]; then
  ## http://drupal.stackexchange.com/questions/126880/how-do-i-prevent-drupal-raising-a-segmentation-fault-when-using-a-node-js-themin
  ## https://www.drupal.org/node/2309023
  find node_modules/ -name '*.info' -type f -delete
fi
