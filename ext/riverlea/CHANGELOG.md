0.9.42
 - CHANGED metadata files (readme, info.xml)
 - ADDED history to changelog

0.9.41
 - ADDED changelog
 - ADDED Drupal 7 Garland support

0.9.0 - 0.9.40
 - ADDED overwrites for civi core CSS, e.g. SearchKit & FormBuilder. (Works on 5.75+ only as it uses Angular css overwrites added in https://github.com/civicrm/civicrm-core/pull/30397 to replace the css for Search Kit, Form Builder and some other files, so that CSS variables can be applied to them, fewer '!important' tags used and file size is shurnk). 
 - ADDED avatar support on contact dashboard
 - ADDED visible version-numbering to support development
 - FIXED many issues. Adds on-screen commit-version-numbering. 
 - NEW CSS VARIBLES: crm-c-code-background, crm-dash-image-size, crm-dash-image-radius, crm-dash-image-justify, crm-dash-image-direction, & metadata variables - crm-version, crm-release.

0.8

Front-end layouts. Adds front-end support for each stream.  Adds CiviCRM logo for front-end pages as inline SVG. Adds CSS Variables for front end with --crm-f prefix, including to choose between inline and stacked label/inputs, to create a focus background, to limit the form width and adjust the logo size.Some fixes. NB v0.8 will be the last major version of RiverLea to work on CiviCRM < 5.75, due to file structure changes enabled by that release. Small fixes can be added as v0.8.1, v0.8.2, etc. NEW VARIBLES

0.7

Dark-mode. Improves dark-mode across all three streams. Improves Hackney Brook. More responsive tables. Many small fixes. Thanks SarahFG, Rich Lott & Guillaume Sorel. NEW VARIBLES

0.6 

Adds third stream (Finsbury Park / Hackney Brook). Adds basic dark-mode support. Adds new stream: Hackney Brook, based on Finsbury Park (~90% port). Adds new CSS variables to all streams to support Finsbury Park's two main differences to Shoreditch and Greenwich: button icon styling (unique colours, background, border), and contact dashboard side tabs with active/hover border, similar to the 5.72 SearchKit UI, as well as some extra useful variables (e.g. dialog header border, notification border radius, etc). NEW VARIBLES

0.5

Extensive UI and accessibility fixes following testing in CiviCamp Hamburg - with many thanks to Guillaume Sorel, Thomas Renner, Rositza Dikova, Luciano Spiegel & Peter Reck and the organisers - as well as Rich Lott and the Core Team. Instances of #crm-container removed or replaced with .crm-container which changes some cascade order. Preparation for overwriting other core CiviCRM CSS files from v0.6 which will require CiviCRM 5.75. Version compatibility raised to 5.72 from 5.69 because of Search Kit Builder interface changes: if that interface isn't used then compatibility before 5.69 should be fine.

0.4

CSS files restructure into /core/css and /streams/[stream-name]/css/ with stream variables defined in [stream-name]/css/_variables.css. Variables files are version-numbered - 0.4 with this version. Version numbers should only increase when the CSS Variables in these files change name, are removed or added.

0.3

Two streams, 6 CMS setups tested: Backdrop, Drupal7 + Seven, Drupal9 + Claro/Seven, Joomla 4, Standalone, WordPress. Loads with two theme variations/streams: Minetta and Walbrook. Does not cover: front-end layouts, < 1000px screens, Joomla 3, other Drupal admin themes, light/dark modes.

0.2

Establishes structure, adds a bunch of css variables for testing/dev, adds the entirity of the current Greenwich Bootstrap 3 build to start cutting it back, and adds a components directory with initial component 'accordions' (with animated exapnd/close + CSS variables). Separate components files will likely be merged when the extension is moving to testing, to reduce http requests.

0.1

Proof-of-concept, Brunswick, empty theme structure doing just two things: for older CMS interfaces enforces a 100% font-size default to cascade the browser default font-size, and demonstrates a 1rem variable on top of that for some Civi body text sizes. The computed font-size of Civi paragraph and table text should show as 16px in Inspector (for standard setups).
