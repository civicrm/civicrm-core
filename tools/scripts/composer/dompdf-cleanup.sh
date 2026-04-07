#!/bin/bash

## Cleanup the vendor tree. The main issue here is that civi Civi is
## deployed as a module inside a CMS, so all its source-code gets published.
## Some libraries distribute admin tools and sample files which should not
## be published.
##
## This script should be idempotent -- if you rerun it several times, it
## should always produce the same post-condition.

##############################################################################
## usage: safe_delete <relpath...>
function safe_delete() {
  for file in "$@" ; do
    if [ -z "$file" ]; then
      echo "Skip: empty file name"
    elif [ -e "$file" ]; then
      rm -rf "$file"
    fi
  done
}

##############################################################################
## usage: make_font_cache > font-cache.php
function make_font_cache() {
php -r "echo json_encode(array (
  'sans-serif' =>
  array (
    'normal' => 'DejaVuSans',
    'bold' => 'DejaVuSans-Bold',
    'italic' => 'DejaVuSans-Oblique',
    'bold_italic' => 'DejaVuSans-BoldOblique',
  ),
  'DejaVu Sans' =>
    array (
      'normal' => 'DejaVuSans',
      'bold' => 'DejaVuSans-Bold',
      'italic' => 'DejaVuSans-Oblique',
      'bold_italic' => 'DejaVuSans-BoldOblique',
    ),
  'times' =>
  array (
    'normal' => 'Times-Roman',
    'bold' => 'Times-Bold',
    'italic' => 'Times-Italic',
    'bold_italic' => 'Times-BoldItalic',
  ),
  'times-roman' =>
  array (
    'normal' => 'Times-Roman',
    'bold' => 'Times-Bold',
    'italic' => 'Times-Italic',
    'bold_italic' => 'Times-BoldItalic',
  ),
  'courier' =>
  array (
    'normal' => 'Courier',
    'bold' => 'Courier-Bold',
    'italic' => 'Courier-Oblique',
    'bold_italic' => 'Courier-BoldOblique',
  ),
  'helvetica' =>
  array (
    'normal' => 'Helvetica',
    'bold' => 'Helvetica-Bold',
    'italic' => 'Helvetica-Oblique',
    'bold_italic' => 'Helvetica-BoldOblique',
  ),
  'zapfdingbats' =>
  array (
    'normal' => 'ZapfDingbats',
    'bold' => 'ZapfDingbats',
    'italic' => 'ZapfDingbats',
    'bold_italic' => 'ZapfDingbats',
  ),
  'symbol' =>
  array (
    'normal' => 'Symbol',
    'bold' => 'Symbol',
    'italic' => 'Symbol',
    'bold_italic' => 'Symbol',
  ),
  'serif' =>
  array (
    'normal' => 'DejaVuSerif',
    'bold' => 'DejaVuSerif-Bold',
    'italic' => 'DejaVuSerif-Italic',
    'bold_italic' => 'DejaVuSerif-BoldItalic',
  ),
  'monospace' =>
  array (
    'normal' => 'DejaVuMono',
    'bold' => 'DejaVuMono-Bold',
    'italic' => 'DejaVuMono-Oblique',
    'bold_italic' => 'DejaVuMono-BoldOblique',
  ),
  'fixed' =>
  array (
    'normal' => 'Courier',
    'bold' => 'Courier-Bold',
    'italic' => 'Courier-Oblique',
    'bold_italic' => 'Courier-BoldOblique',
  ),
), JSON_PRETTY_PRINT);"
}

## usage: simple_replace <filename> <old-string> <new-string>
## This is a bit like 'sed -i', but dumber and more cross-platform.
function simple_replace() {
  php -r 'file_put_contents($argv[1], str_replace($argv[2], $argv[3], file_get_contents($argv[1])));' "$@"
}

##############################################################################
## Remove example/CLI scripts. They're not needed and increase the attack-surface.
safe_delete vendor/dompdf/dompdf/dompdf.php
safe_delete vendor/dompdf/dompdf/load_font.php
safe_delete vendor/dompdf/dompdf/www
safe_delete vendor/phenx/php-font-lib/www

make_font_cache > vendor/dompdf/dompdf/lib/fonts/installed-fonts.dist.json
