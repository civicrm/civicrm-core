#!/bin/sh

src=/opt/local/lib/php
dst=../packages

rsyncOptions="-avC --exclude=svn"
rsync="rsync $rsyncOptions"

for code in PEAR DB HTML Log Smarty Validate Pager PHP Archive System Console XML PhpDocumentor Mail Net/Socket Net/SMTP; do
  echo $code
  [ -a $src/$code.php ] && $rsync $src/$code.php $dst
  [ -d $src/$code ] && $rsync $src/$code $dst
done

[ -d ../PEAR/HTML ] && $rsync ../PEAR/HTML $dst
[ -d ../PEAR/DB   ] && $rsync ../PEAR/DB   $dst

