#!/bin/sh
for j in CRM bin api test distmaker drupal joomla xml; do
  cd ../$j;
  for i in `find . -name \*.php`; do
    echo $i;
    sed -i '' -e '/\@author Donald/d' $i
  done
done


