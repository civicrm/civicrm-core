#!/bin/sh

phpdoc -f Log.php -d Log -t docs/api -p -ti "Log Package API" -dn Log -dc Log -ed examples -i CVS/*
