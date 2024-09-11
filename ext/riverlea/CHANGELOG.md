0.9.51
 - CHANGED - cleans-up & merges two Bootstrap files: removes some un-used/duplicates, adds CSS variables, sorts into sub-headings
 - REMOVES - bootstrap3.css file

0.9.50
 - FIXES - more icon button padding issues
 - ADDED - text colour to headings so H1, H2, etc display correctly in darkmode
 - FIXES - WordPress override
 - FIXES - Epic empty line spacing 'pink shears' cleanup https://lab.civicrm.org/extensions/riverlea/-/merge_requests/19

0.9.49
 - CHANGED - Button icon colour scope broaden - for any stream that sets them, e.g. Hackney Brook (https://lab.civicrm.org/extensions/riverlea/-/issues/37)
 - FIXES - multiple button padding & alignment fixes, all streams
 - FIXES - multiple button icon padding/alignment fixes, all streams

0.9.48
 - CHANGED - Minetta input styles to be closer to Bootstrap (ref https://lab.civicrm.org/extensions/riverlea/-/issues/18)
 - ADDED - input size modifiers (e.g. for shorter inputs, such as 'prefix')

0.9.47
 - ADDED - page padding for database upgrade screens. minor type fixes to upgrade complete screen
 - FIXES - missing bg on dashlet selector, table padding for dashlet filter table, grab cursor on dashlet header.
 - FIXES - notifcation alert type colour.
 - FIXES - removes text decoration from notification icon links
 - ADDED - icon spinner for select2 loading (https://lab.civicrm.org/extensions/riverlea/-/issues/35#note_169442)

0.9.46
 - ADDED - basics for dark-mode settings screen (menu link, navigation menu / setting.php, etc). Not yet functioning
 - REVERTS - button icon spacing fix from 0.9.43 as it caused new problems.

0.9.45
 - FIXES - missing delete icon in SK admin table editor
 - CHANGED - multiple SK admin editor UI improvements
 - FIXES - crm-iconPicker.css isn't being loaded by theme, so overwrites added to _icon.css

0.9.44
 - CHANGED logo icon in navbar to SVG image (higher resolution).
 - CHANGED Standalone inline padding set to 3vw, matching default (https://lab.civicrm.org/extensions/riverlea/-/issues/26#note_169213).
 - ADDED alert background colour to delete link on contact summary action dropdown (https://lab.civicrm.org/dev/user-interface/-/issues/54#note_169299).
 - ADDED matching bg colour for SK 'select all' checkbox and dropdown
 - REMOVES column line for contact summary action dropdown
 - REMOVES bounding border for SearchKit tables, other than the main SK admin list (https://lab.civicrm.org/extensions/riverlea/-/issues/36)
 - FIXES double border on SK admin list (https://lab.civicrm.org/extensions/riverlea/-/issues/36)
 - FIXES SK table head 'select all' checkbox padding (differed for checked/unchecked)
 - ADDED crm-iconPicker.css theme override
 - FIXES icon picker layout
 - CHANGED SK editor layout view table: align drag icons, add padding to details body
 - FIXES multiple contrast issues with info/success/warning/danger button + background colour/text combos (https://lab.civicrm.org/extensions/riverlea/-/issues/38)
 - NEW CSS VARIABLES (5) - 5 new variables for above fix:  --crm-btn-cancel-text, --crm-btn-info-text, --crm-btn-warning-text, --crm-btn-success-text, --crm-btn-alert-text

0.9.43
 - ADDED Operating System font fallbacks for Hackney Brook
 - FIXES Second conditional dropdown not showing: https://lab.civicrm.org/extensions/riverlea/-/issues/35
 - FIXES JQuery UI background images appearing on some UI icon classes. NB - this fix remvoes all JQuery UI icon background images so if icons vanish, it'll be linked to this, and need further work.
 - FIXES select2 inplace search too wide
 - FIXES notification dialog 'restore' button matches theme style rather than JQueryUI.
 - FIXES removes webkit font-smoothing on .button in Drupal8+
 - FIXES icon padding when button has an internal span
 - CHANGED .pull-right handling: removes from Bootstrap & adds to base, creates Flexbox override for SearchKit: https://lab.civicrm.org/extensions/riverlea/-/issues/5#note_169180.
 - FIXES Mosaico: wizard button padding, inactive colour and thumbnail bg (last two fix darkmode issue: https://lab.civicrm.org/extensions/riverlea/-/issues/34)

0.9.42
 - CHANGED metadata files (readme, info.xml). Package released
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
