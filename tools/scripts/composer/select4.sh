#!/bin/bash

## This renames the 4.x version of Select2 to "select4"

## str_replace in a file
function str_replace() {
  php -r 'file_put_contents($argv[1], str_replace($argv[2], $argv[3], file_get_contents($argv[1])));' "$@"
}

# For some reason CRM_Core_Resources won't add css files with the same name, even from different directories,
# so rename to avoid conflicts with the v3 select2.css files.
mv bower_components/select2-4.x/dist/css/select2.css bower_components/select2-4.x/dist/css/select4.css
mv bower_components/select2-4.x/dist/css/select2.min.css bower_components/select2-4.x/dist/css/select4.min.css

for file in bower_components/select2-4.x/dist/css/*.css ; do
  str_replace "$file" 'select2' 'select4'
done
for file in bower_components/select2-4.x/dist/js/*.js ; do
  str_replace "$file" 'select2' 'select4'
done
