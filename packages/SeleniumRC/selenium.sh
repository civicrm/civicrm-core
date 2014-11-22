#!/usr/bin/env bash

# Run selenium server with custom browser profile.
echo; echo "Runnning selenium-server-2.35.0 "; echo;
java -jar selenium-server/selenium-server-standalone-2.35.0.jar -firefoxProfileTemplate BrowserProfiles/firefox/
