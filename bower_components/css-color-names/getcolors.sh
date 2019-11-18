#!/usr/bin/env bash
curl -sS 'https://www.w3schools.com/colors/colors_names.asp' \
	| tr -cd '\11\12\15\40-\176' \
	| egrep '\?(color|hex)=' \
	| sed -e 's/\r//g' -e 's/<[^>]*>//g' -e 's/\&nbsp;.*$//g' \
	| awk 'NR%2 {color=$1} !(NR%2) {print color, $1}' \
	| tr '[:upper:]' '[:lower:]' \
	| sort
