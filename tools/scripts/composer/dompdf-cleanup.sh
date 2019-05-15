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
cat <<EOFONT
<?php return array (
  'sans-serif' =>
  array (
    'normal' => DOMPDF_DIR . '/lib/fonts/Helvetica',
    'bold' => DOMPDF_DIR . '/lib/fonts/Helvetica-Bold',
    'italic' => DOMPDF_DIR . '/lib/fonts/Helvetica-Oblique',
    'bold_italic' => DOMPDF_DIR . '/lib/fonts/Helvetica-BoldOblique',
  ),
  'times' =>
  array (
    'normal' => DOMPDF_DIR . '/lib/fonts/Times-Roman',
    'bold' => DOMPDF_DIR . '/lib/fonts/Times-Bold',
    'italic' => DOMPDF_DIR . '/lib/fonts/Times-Italic',
    'bold_italic' => DOMPDF_DIR . '/lib/fonts/Times-BoldItalic',
  ),
  'times-roman' =>
  array (
    'normal' => DOMPDF_DIR . '/lib/fonts/Times-Roman',
    'bold' => DOMPDF_DIR . '/lib/fonts/Times-Bold',
    'italic' => DOMPDF_DIR . '/lib/fonts/Times-Italic',
    'bold_italic' => DOMPDF_DIR . '/lib/fonts/Times-BoldItalic',
  ),
  'courier' =>
  array (
    'normal' => DOMPDF_DIR . '/lib/fonts/Courier',
    'bold' => DOMPDF_DIR . '/lib/fonts/Courier-Bold',
    'italic' => DOMPDF_DIR . '/lib/fonts/Courier-Oblique',
    'bold_italic' => DOMPDF_DIR . '/lib/fonts/Courier-BoldOblique',
  ),
  'helvetica' =>
  array (
    'normal' => DOMPDF_DIR . '/lib/fonts/Helvetica',
    'bold' => DOMPDF_DIR . '/lib/fonts/Helvetica-Bold',
    'italic' => DOMPDF_DIR . '/lib/fonts/Helvetica-Oblique',
    'bold_italic' => DOMPDF_DIR . '/lib/fonts/Helvetica-BoldOblique',
  ),
  'zapfdingbats' =>
  array (
    'normal' => DOMPDF_DIR . '/lib/fonts/ZapfDingbats',
    'bold' => DOMPDF_DIR . '/lib/fonts/ZapfDingbats',
    'italic' => DOMPDF_DIR . '/lib/fonts/ZapfDingbats',
    'bold_italic' => DOMPDF_DIR . '/lib/fonts/ZapfDingbats',
  ),
  'symbol' =>
  array (
    'normal' => DOMPDF_DIR . '/lib/fonts/Symbol',
    'bold' => DOMPDF_DIR . '/lib/fonts/Symbol',
    'italic' => DOMPDF_DIR . '/lib/fonts/Symbol',
    'bold_italic' => DOMPDF_DIR . '/lib/fonts/Symbol',
  ),
  'serif' =>
  array (
    'normal' => DOMPDF_DIR . '/lib/fonts/Times-Roman',
    'bold' => DOMPDF_DIR . '/lib/fonts/Times-Bold',
    'italic' => DOMPDF_DIR . '/lib/fonts/Times-Italic',
    'bold_italic' => DOMPDF_DIR . '/lib/fonts/Times-BoldItalic',
  ),
  'monospace' =>
  array (
    'normal' => DOMPDF_DIR . '/lib/fonts/Courier',
    'bold' => DOMPDF_DIR . '/lib/fonts/Courier-Bold',
    'italic' => DOMPDF_DIR . '/lib/fonts/Courier-Oblique',
    'bold_italic' => DOMPDF_DIR . '/lib/fonts/Courier-BoldOblique',
  ),
  'fixed' =>
  array (
    'normal' => DOMPDF_DIR . '/lib/fonts/Courier',
    'bold' => DOMPDF_DIR . '/lib/fonts/Courier-Bold',
    'italic' => DOMPDF_DIR . '/lib/fonts/Courier-Oblique',
    'bold_italic' => DOMPDF_DIR . '/lib/fonts/Courier-BoldOblique',
  ),
) ?>
EOFONT
}

function make_font_readme() {
cat <<EOREADME
To save space in the final distribution we have not included the DejaVu family of fonts. You can get these fonts from:

http://code.google.com/p/dompdf/

Download the latest version and copy the font files from the lib/fonts directories to this directory.
EOREADME
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

# Remove DejaVu fonts. They add 12mb.
safe_delete vendor/dompdf/dompdf/lib/fonts/DejaVu*
make_font_cache > vendor/dompdf/dompdf/lib/fonts/dompdf_font_family_cache.dist.php
make_font_readme > vendor/dompdf/dompdf/lib/fonts/README.DejaVuFonts.txt

# Remove debug_print_backtrace(), which can leak system details. Put backtrace in log.
simple_replace vendor/dompdf/dompdf/lib/html5lib/TreeBuilder.php 'debug_print_backtrace();' 'CRM_Core_Error::backtrace("backTrace", TRUE);'

if ! grep -q 'CRM-21395' vendor/dompdf/dompdf/src/Dompdf.php; then
  patch vendor/dompdf/dompdf/src/Dompdf.php < tools/scripts/composer/patches/dompdf_no_block_level_parent_fix.patch
fi
