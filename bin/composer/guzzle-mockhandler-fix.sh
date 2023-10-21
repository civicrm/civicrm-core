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


# php 8.1 compatibility
if ! grep -q ': int' vendor/guzzlehttp/guzzle/src/Handler/MockHandler.php; then
  simple_replace vendor/guzzlehttp/guzzle/src/Handler/MockHandler.php '#public function count\(\)$#m' 'public function count(): int'
fi
