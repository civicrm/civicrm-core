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
if ! grep -q 'CRM_Utils_Mail::logger' vendor/pear/mail/Mail/mail.php; then
  patch -d vendor/pear/mail/Mail/ < tools/scripts/composer/patches/mail-logger-patch.txt
fi
if ! grep -q 'CRM-5946' vendor/pear/mail/Mail/mail.php; then
  patch -d vendor/pear/mail/Mail/ < tools/scripts/composer/patches/mail-from-address-patch.txt
fi
if ! grep -q 'Groupname:;' vendor/pear/mail/Mail/RFC822.php; then
  patch vendor/pear/mail/Mail/RFC822.php < tools/scripts/composer/patches/mail-RFC822-patch.txt
fi
if ! grep -q 'CRM-1367' vendor/pear/mail/Mail.php; then
  patch vendor/pear/mail/Mail.php < tools/scripts/composer/patches/mail-crm-1367-patch.txt
fi

safe_delete vendor/pear/mail/{examples,phpdoc.sh,tests}
