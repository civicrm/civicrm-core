0.10.12
 - CHANGED - empty.css file to reflect recent changes, removed empty variables from darkmode
 - FIXED - --crm-table-sort-active-col is set but wasn't being called, ref #57 2
 - REMOVED 1 CSS VARIABLE:
    --crm-tabs-2-border
 - ADDED 1 CSS VARIABLE:
    --crm-table-header-col (to set a custom colour for a table header, ref #57 1)

0.10.11
 - CHANGED - major tabs refactor and simplify (see - https://lab.civicrm.org/extensions/riverlea/-/issues/54)
 - REMOVED - quite a bit of now redundant tab-related css.
 - REMOVED 8 CSS VARIABLES:
    --crm-dashlet-tabs-bg
    --crm-dashlet-tab-bg
    --crm-dashlet-tab-border
    --crm-dashlet-tab-color
    --crm-dashlet-tab-active
    --crm-dashlet-tab-border-active
    --crm-dashlet-tab-body-border
    --crm-dashlet-tab-body-padding  (NB - it was adding these variables that forced the tab rethink)

0.10.10
 - FIXED - date time input simplified
 - CHANGED - Select2 dropdown advanced: border line between list elements, smaller description, simplified padding.
 - FIXED - various issues flagged in https://lab.civicrm.org/extensions/riverlea/-/issues/46

0.10.9
 - FIXED - issue 46.1 - configuration checklist contrast ratio
 - CHANGED - issue 46.3 - radio and checkbox labels are no longer bold if 'label' is bold
 - FIXED - issue 46.4 - legibility issue
 - FIXED - issue 46.5 - positioning issue

0.10.8
- CHANGED - Button height handling to allow wrapping double line buttons to keep their padding, but also protect streams that have full-height icon backgrounds (ref: https://lab.civicrm.org/extensions/riverlea/-/issues/55).
- FIXED - Issue with icon backgrounds on buttons not stretching full height on double-height buttons.
- NEW CSS VARIABLE -
    added: --crm-btn-icon-padding (defaults to 'var(--crm-btn-padding-block)'). NB: fine to ignore this variable unless you want distinct button icon backgrounds, when you should set it to `0px`.

0.10.7
- FIXED - Number field is narrower than browser width.
- CHANGED - WordPress font-size not set to 100%, padding variable moved.
- ADDED - line-height to .crm-container
- REMOVED - removed prefers-color-scheme setting from variables (as it seems to be unncessary from the packaged dark mode approach)
- CHANGED - WordPress settings made more specific

0.10.6
- ADDED - HackneyBrook primary & secondary hover text variables (ref 0.10.4)
- ADDED - Advanced search layout optimisations (brings closer to original Greenwich/Shoreditch)
- ADDED - table select-icon padding (https://lab.civicrm.org/artfulrobot/riverlea/-/blob/thames/streams/thames/css/civicrm.css#L363)
- FIXED - focus state causes layout shift: https://lab.civicrm.org/extensions/riverlea/-/issues/49
- FIXED - table cell alignment issue for form layouts
- FIXED - hover state for even table rows in SearchKit: https://lab.civicrm.org/extensions/riverlea/-/issues/50
- NEW CSS VARIABLE - for alpha-filter spacing (Thames inspired)
    added: --crm-filter-spacing (defaults 'start')

0.10.5
- FIXED - issue with CSS rewrite happening even if the theme isn't selected: https://lab.civicrm.org/extensions/riverlea/-/merge_requests/26

0.10.4
- NEW CSS VARIABLES to support different text hover colour, and allow contact dashboard roundness of tabs to work left/right as well as top/bottom
    added: --crm-c-primary-hover-text
    added: --crm-c-secondary-hover-text
    added: --crm-dash-tabs-roundness - for the tabs group radius on the contact dashboard
    added: --crn-dash-panel-radius - for the tab panel radius on the contact dashboard
- FIXED text area 100% width

0.10.3
 - ADDED - JQueery spinner styling (number counter with arrows used on pagination)
 - FIXED - contact tooltip position in search results
 - FIXED - pagination/etc on search results
 - FIXED - button wrapping on dialogs https://lab.civicrm.org/extensions/riverlea/-/issues/5
 - ADDED - hover state for contact dashboard main name
 - NEW CSS VARIABLES - for alpha-filter (Wellow inspired)
    added: --crm-filter-bg
    added: --crm-filter-padding
    added: --crm-filter-item-bg
    added: --crm-filter-item-shadow

0.10.2
 - FIXES - alignment of table sort headers
 - ADDED - min height to body (via Thames)
 - CHANGED - Significant change to variables (described https://lab.civicrm.org/extensions/riverlea/-/issues/43). Minetta variables put in core as default variables. Stream variables loaded only when they are different to Minetta, shrinking those files. Empty ('starter') stream variables now copy of core _variables.css, but with everything commented out (build a stream by gradually uncommenting).

0.10.1
 - ADDED - accordion summary background colour changes if it contains a required input error message (to help find input errors on forms with multiple accordions, such as add new contact)
 - FIXES - overflow scroll on import screens due to margin resets on import screens
 - FIXES - padding on Mailing screen wizard (walbrook)
 - FIXES - next/previous button order on dashboard (https://lab.civicrm.org/extensions/riverlea/-/issues/48)
 - FIXES - inlines contact dashboard editor button, handles hover state better
 - FIXES - icon bg colour for Hackney.
 - CHANGED - buttons from flex to inline-flex with related cleanups (https://lab.civicrm.org/extensions/riverlea/-/issues/45)

0.10.0
 - version jump to reflect functional maturity, shift to focus on integrating two new streams, and preparation for stable 1.0.0 release.
 - REMOVED - contact dashboard page padding distinct from other pages
 - CHANGED - Walbrook buttons text no longer uppercase.
 - ADDED - styling for the extensions dbse upgrade 'queue runner' screen
 - FIXES - missing action button styling for links on contact dashboard header (e.g. contact dashboard editor)

0.9.56
 - CHANGED - reworking of dashlets, modals and tabs to support more style variations
 - NEW CSS VARIABLES (11)
    added: --crm-dashlet-header-border
    added: --crm-dashlet-header-border-width
    added: --crm-dashlet-header-font-size
    added: --crm-dashlet-box-shadow
    added: --crm-dashlet-dashlets-bg
    added: --crm-dialog-inner-shadow
    added: --crm-dropdown-radius
    added: --crm-dashlet-tab-color
    added: --crm-dashlet-tab-body-border
    added: --crm-dashlet-tab-body-padding
    added: --crm-dashlet-tabs-border

0.9.55
 - ADDED - functional darkmode settings screen for front and backend. This includes multiple changes, described on the MR: https://lab.civicrm.org/extensions/riverlea/-/merge_requests/22
 - ADDED - each stream has a _dark.css file for darkmode variables, which no longer appear in _variables.css
 - FIXED - page title on Drupal 10 doesn't match dark-mode.

0.9.54
 - CHANGED - reworking of accordions with new variables to support full .crm-accordion-light styling.
 - CHANGED - listing of new CSS variables in changelog
 - NEW CSS VARIABLES (8 new, 6 renamed):
    added: --crm-expand-radius (border radius for accordion)
    added: --crm-expand-header-font (for setting bold/font pair)
    added: --crm-expand2-header-bg-active
    added: --crm-expand2-header-font
    added: --crm-expand2-header-border
    added: --crm-expand2-header-border-width
    added: --crm-expand2-header-padding
    added: --crm-expand2-border-width
    renamed: --crm-expand-header2-bg => --crm-expand2-header-bg
    renamed: --crm-expand-header2-weight => --crm-expand2-header-weight
    renamed: --crm-expand-header2-color => --crm-expand2-header-color
    renamed: --crm-expand-2-border => --crm-expand2-border
    renamed: --crm-expand-body2-bg => --crm-expand2-body-bg
    renamed: --crm-expand-2-body-padding => --crm-expand2-body-padding

0.9.53
 - ADDED - BG colour for standalone login screen
 - FIXED - Hackney icon alignment on .button, z-index on tabs.
 - FIXED - dashlet close icon inheriting wrong color
 - REMOVED - resets on input in base.css
 - FIXED - colour of lists on notify alerts matches alert colour
 - FIXED - restored list bullets to notification lists
 - ADDED - margin reset to top element in notification text, to remove extra padding.
 - ADDED - block padding to crm-section divs to separate form elements

0.9.52
 - FIXED - contact summary dashboard, hidden popup - https://lab.civicrm.org/extensions/riverlea/-/issues/41. Removed unnecessary definition and adjusted caret colour.
 - FIXED - Mosaico wizard uses custom markup, but the wizard now matches the stream wizard settings - https://lab.civicrm.org/extensions/riverlea/-/issues/12. Also makes the hover buttons always visible for accessibility.

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
 - NEW CSS VARIABLES (5) - 5 new variables for above fix:
    added: --crm-btn-cancel-text
    added: --crm-btn-info-text
    added: --crm-btn-warning-text
    added: --crm-btn-success-text
    added: --crm-btn-alert-text

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
 - NEW CSS VARIBLES:
    added: --crm-c-code-background
    added: --crm-dash-image-size
    added: --crm-dash-image-radius
    added: --crm-dash-image-justify
    added: --crm-dash-image-direction
    added: --crm-version (metadata)
    added: --crm-release (meetadata)

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
