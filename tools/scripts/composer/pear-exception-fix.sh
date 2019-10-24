#!/bin/bash

## Cleanup the vendor tree. The main issue here is that civi Civi is
## deployed as a module inside a CMS, so all its source-code gets published.
## Some libraries distribute admin tools and sample files which should not
## be published.
##
## This script should be idempotent -- if you rerun it several times, it
## should always produce the same post-condition.

## Replace a line in a file
## This is a bit like 'sed -i', but dumber and more cross-platform.
function simple_replace() {
  php -r 'file_put_contents($argv[1], preg_replace($argv[2], $argv[3], file_get_contents($argv[1])));' "$@"
}


# add in class_exists test as per CRM-8921.
if ! grep -q 'CRM-8921' vendor/pear/pear_exception/PEAR/Exception.php; then
simple_replace vendor/pear/pear_exception/PEAR/Exception.php '^\<\?php^' '<?php if (class_exists("'"PEAR_Exception"'")) return; /* CRM-8921 */'
fi
