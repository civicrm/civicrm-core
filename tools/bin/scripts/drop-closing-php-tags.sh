#!/bin/sh
for i in `find CRM api bin distmaker drupal extern joomla js sql standalone test test-new tools xml -name '*.php'`; do
  echo $i;
  perl -pi -e 's/^\?>$//' $i;
done
