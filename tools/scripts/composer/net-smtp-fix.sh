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
# Add in CiviCRM custom error message for CRM-8744.
if ! grep -q 'CRM-8744' vendor/pear/net_smtp/Net/SMTP.php; then
  patch vendor/pear/net_smtp/Net/SMTP.php < tools/scripts/composer/patches/net-smtp-patch.txt
fi
if ! grep -q '@STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT' vendor/pear/net_smtp/Net/SMTP.php; then
  patch vendor/pear/net_smtp/Net/SMTP.php < tools/scripts/composer/patches/net-smtp-tls-patch.txt
fi
if ! grep -q 'function __construct' vendor/pear/net_smtp/Net/SMTP.php; then
  patch vendor/pear/net_smtp/Net/SMTP.php < tools/scripts/composer/patches/net-smtp-php7-patch.txt
fi
if grep -q '&Auth_SASL::factory' vendor/pear/net_smtp/Net/SMTP.php; then
  patch vendor/pear/net_smtp/Net/SMTP.php < tools/scripts/composer/patches/net-smtp-ref-patch.txt
fi

safe_delete vendor/pear/net_smtp/{README.rst,examples,phpdoc.sh,tests}
