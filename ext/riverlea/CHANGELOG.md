1.2.2-5.81beta
 - FIXED - major regression introduced in 1.2.1-5.81beta around Select2 sub-results: https://lab.civicrm.org/extensions/riverlea/-/issues/11#note_174883
 - FIXED - contact dashboard tags didn't wrap: https://lab.civicrm.org/extensions/riverlea/-/issues/97

1.2.1-5.81beta
 - FIXED - integrated this fix to rendered radio buttons: https://github.com/civicrm/civicrm-core/pull/31345
 - FIXED - text colour on selected rows illegible: https://lab.civicrm.org/extensions/riverlea/-/issues/94#note_174585
 - FIXED - accordions inside Select2 now work. Some small tidying of Select2 list borders/padding.
 - CHANGED - reduced size of 'not found' alerts in SearchKit tables: https://github.com/civicrm/civicrm-core/pull/31605

1.2.0-5.81.beta
 - CHANGED - version numbering (again!) ref
https://lab.civicrm.org/extensions/riverlea/-/issues/44#note_174132
 - FIXED - Thames, dropdown clipping, ref
https://lab.civicrm.org/extensions/riverlea/-/issues/90
 - FIXED - Backbone.js Profile edit via Event/Contribution page issues, ref https://lab.civicrm.org/extensions/riverlea/-/issues/92
 - ADDED - crm.designer.css into /core/css to allow for RL overrides. Added some simple integrations (button colour, spacing fixe)
 - CHANGED - dropdown items hover state now has a background color that should contrast the hover text colour - it was using a variable that was sometimes transparent, creating contrast ratio issues in Minetta

1.80.14
 - FIXED - regression caused by trying to reset clipping in Thames (ref: https://lab.civicrm.org/extensions/riverlea/-/issues/91)

1.80.13
 - FIXED - removed margin on ul.nav that's added by browser/CMS theme ul styling (seen on Message Template Afform)
 - FIXED - extra box-shadow from .panel-heading (was creating an odd dble shadow)
 - FIXED - clipped overflowing responsive tables in Thames (ref https://lab.civicrm.org/extensions/riverlea/-/issues/90)
 - FIXED - .crm-pager padding/positioning (ref https://lab.civicrm.org/extensions/riverlea/-/issues/11#note_173013) - also removed hidden top pager from some results.
 - FIXED - Joomla4+ Atum admin theme bug that adds underline on dropdown menu links
 - CHANGED - removed second drop-shadow on BS .panel inside a .panel (visible in Walbrook, e.g. Message Template Afform).
 - CHANGED - more balanced padding in panel-heading.
 - ADDED - .nav.nav-pills style based on buttons Message Template Afform.

1.80.12
 - CHANGED - CRM Status Update page - added drop-shadow to dropdowns, made dropdown button border transparent, made h3 text colour match background variable.
 - CHANGED - Minetta active accordion tab and panel bg colour now matches Greenwich
 - FIXED - Accordion summary label colour inaccessible - needed !important, plus margin reset.
 - FIXED - Accordion regression for Bootstrap accordions using .collapse, not summary/details (ref https://lab.civicrm.org/extensions/riverlea/-/issues/89). Also better namespaced Bootstrap collapse functions, and added support for BS4+ '.show'.
 - REPLACED - replaced Inter font with font downloaded from https://gwfh.mranftl.com/fonts (the same place as Lato for Thames) and updated Walbrook's reference to it.

1.80.11
 - FIXED - Wallbrook avatar image returned to 100px as had created a gap (ext/riverlea/#87)
 - FIXED - Double icon on .messages.crm-empty-table
 - FIXED - changed specificity of .hiddenElement (ext/riverlea/#11) to ensure unhidden elements are unhidden.
 - CHANGED - version numbering again. See note in ReadMe: the 2nd number represents the Civi version tested against, the 3rd number is the RL version number for that Civi version.
 - CHANGED - Alert colour bg for .messages.crm-empty-table now matches icon colour ('info' range)
 - CHANGED - streams/empty/_variables.css to match core variables.css
 - CHANGED - reduce verbose Bootstrap table styling css in tables.css
 - CHANGED - cascade order of table colours to put .crm-row-selected class last
 - ADDED - contact merge screen error/ok/selected background colours (ext/riverlea/#88)
 - ADDED - margin (`--crm-flex-gap`) to bottom of .description text.
 - ADDED - accordion with error text and border colour to fix contrast ratio issues
 - ADDED - D9 Claro .action-link margin reset

1.1.10 / 5.80.9
 - FIXED - D7 Seven theme .button style overwriting colours
 - CHANGED - D7 Seven, matched page-padding.
 - FIXED - Responsive contact dashboard, below 768px: wrapped contact-summary label/data, wrap action links.
 - FIXED - Responsive contact dashboard, below 500px: improve hidden text and sidetabs width.
 - MOVED - Responsive contact dashboard css from tabs.css to contactSummary.css
 - CHANGED - Walbrook avatar image - made a little larger and thiner border
 - FIXED - better name-spaced AFform padding for front-end vs backend

1.1.9 / 5.80.8
 - CHANGED - CiviLint - further lint adjustments (tabs for double space, missing semi-colons)
 - FIXED - SearchKit button group hierarchy wrapping button groups
 - FIXED - removed button styling for another (x)-type cancel-only button in SearchKit builder
 - FIXED - avatar positioning works to an extent (floats right in dashboard header for Walbrook / Hackney) (#87)
 - REMOVED - avatar flexbox positioning and simplified css, as not being applied
 - ADDED - dash image border in Walbrook
 - ADDED CSS VARIABLES (3)
    --crm-dash-image-right (distance from right for avatar)
    --crm-dash-image-top (distance from top)
    --crm-dash-image-border (optional border)
 - REMOVED CSS VARIABLES (2)
    --crm-dash-image-justify
    --crm-dash-image-direction

1.1.8 / 5.80.7
 - CHANGED - CiviLint - made Thames CSS more verbose (#84)
 - CHANGED - CiviLint - reduced four char spaces to two
 - CHANGED - CiviLint - changes to PHP files
 - CHANGED - CiviLint - no empty variables
 - CHANGED - .gitignore file updated
 - REMOVED - Duplicate BoostrapJS files

1.1.7 / 5.80.6
 - FIXED - trailing comma (merge_requests/42)
 - FIXED - FormBuilder customise options doesn't show icons on Wallbrook (#83)
 - FIXED - FormBuilder customise options grab region background doesn't show
 - CHANGED - Background colours for customise options alternating rows
 - CHANGED - CSS tidying around FormBuilder customise options
 - REMOVED - responsive tables - fix being used wasn't responsive and had some usability questions (#82)

1.1.6 / 5.80.5
 - FIXED - reset checkbox margin in checkbox lists (that shrunk the checkbox size)
 - FIXED - changed td.label to table-cell to address sizing inconsistencies (#68)
 - CHANGED - apply `--crm-c-page-background` to WordPress body, not only .crm-container (#77)
 - FIXED - right column inline edit on contact dashboard was positioned left (#76)

1.1.5 / 5.80.4
 - CHANGED - CSS Variable '--crm-flex-gap' moved from core css into variables (fixed scenarios where it wasn't loading)
 - ADDED - right/bottom margin to SK grid buttons to create space in SK displays (#81)
 - CHANGED - multi-select select2, use input padding variable
 - FIXES - action menu dropdown icon hover colour (#78)
 - FIXES - radio buttons and checkboxes had a min-width applied causing layout problems (#80)

1.1.4 / 5.80.3
 - FIXED tooltip dropdown: double shadow, gap next to arrow, padding/border on bottom (ref #74)
 - ADDED float: none for FormBuilder legends
 - FIXED added 'important' to 'hiddenElement' as it's getting lost in some cascades, (ref dev/core/#5598)
 - FIXED td.label width not being applied, creating various other quirks; changed display type to inline-table.
 - ADDED small inline margin to help icons to separate from label text
 - ADDED min-height of 100vh to avoid block of white-space below #crm-container (same as Thames)
 - CHANGED Readme - simplified some wording, expanded description, removed roadmap, changed order, created 'customisation' section.
 - ADDED instructions for creating a subtheme/stream extension pointing to RiverLea.

1.1.3 / 5.80.2
 - FIXED z-index for date-picker in modals
 - FIXED Open Street Map tiles not loading

1.1.2 / 5.80.1
 - FIXED padding in event config dropdown
 - CHANGED padding in HackneyBrook dialogs from 0 to --crm-s.
 - FIXED reset of table-scrolling with dropdowns (e.g. Event dashboard dropdown was clipping the dropdown)
 - FIXED dropdown link width (reset in WordPress)
 - FIXED contact dashboard action links dropdown delete icon color
 - FIXED contact dashboard inline name edit overflow hidden reset
 - FIXED contact dashboard white text on white bg for contact name inline edit in Walbrook
 - CHANGED crm-accordion-settings body padding changed from 0 to match crm-accordion-bold
 - FIXED focus colour on Select2 now should display on tab/focus (github.com/31433)

1.1.1 / 5.80.0
 - FIXED metadata - RiverLea version numbering in variables file & info.xml

1.1.0 / 5.80.0
 - CHANGED info.xml version to 5.80 to synch with CiviCRM core (github.com/31389)
 - FIXED clipping of dropdown on sidescroll tables (#73).
 - FIXED Wallbrook, table header bg, should be white.
 - CHANGED readme.
 - ADDED front-end type across all Streams is reset to 'inherit', over-riding the Stream's font to instead use the CMS front-end theme font(s). For Standalone, inherit will default to the System font stack.

1.0.12
 - ADDED z-index to sticky table headers: ref github/#31396
 - ADDED crm-accordion-settings ref github/#31293
 - CHANGED accordion file layout (order/headings)
 - ADDED 5.80 version compatability to info.xml
 - FIXED Font Awesome icon spinner off-center
 - FIXED missing bg images in Form Builder dropdown (#71)
 - FIXED front-end select2 dropdown input search too wide
 - FIXED front-end date/time wrapping issue (#72)
 - FIXED front-end date/time height inconsistent
 - FIXED front-end FB fieldset titles beaten by Bootstrap - colour & padding
 - FIXED stops FB flexbox front-end for inline column collapsing
 - FIXED dropdown table select checkbox wrapping in Walbrook

1.0.11
 - ADDED inline block for code tags inside paragraphs (#69)
 - ADDED fixed width for WordPress checkbox/radios (#68)
 - CHANGED checkbox list alignment to use grid align rather than pixels
 - CHANGED font-weight on input labels in Minetta to 600, not 'bold' (#66)
 - ADDED gap between two inline buttons
 - ADDED credit card FA icons for contribution pages (originally image sprites)
 - FIXED padding on table form label cells didn't match form input cells
 - FIXED flexbox with help icons breaking table structure (#68)
 - ADDED bg colour and border for pay later options in events/contribution creation to help differentiate
 - CHANGED bootstrap small button icon height is fixed to keep button groups matching heights
 - FIXED hover issue on cancel icon buttons (#67)

1.0.10
 - CHANGED sort icons on SK tables to sit inline for wrapping labels.
 - FIXED checkbox for SK table header padding so shouldn't wrap.
 - CHANGED explicitly set text-decoration to none for buttons
 - REMOVED broken link to _font.css in Walbrook.

1.0.9
 - FIXES text-wrapping on recently viewed block (D7)
 - ADDED opacity / hover for view/edit links on recently viewed block
 - FIXED Garland/D7 li margin reset blocking tab formatting.
 - FIXED Garland/D7 input label width reset
 - FIXED Contact summary label width on narrower screens.
 - ADDED More table overflow scrolls (e.g. Membership table on contact dashboard).
 - FIXED Link colour for some primary/secondary background coloured regions.

1.0.8
 - FIXES further regressions caused by 1.0.4: removes new #bootstrap-theme applications on input fields
 - ADDED background colour for SearchKit admin panel to match active tab.
 - ADDED matches height for SearchKit admin title
 - ADDED inline flexbox for FormBuilder form-inline.
 - CHANGED footer status link underline.

1.0.7
 - FIXES further regressions caused by 1.0.4.

1.0.6
 - FIXED regressions caused by 1.0.4.

1.0.5
 - ADDED nowrap to action links (#64)

1.0.4
 - ADDED and swapped #bootstrap-theme prefix to button, background and input elements in response to issues with front-end specificity against 3rd party themes #61

1.0.3
 - FIXED WordPress FormBuilder checkboxes weren't square
 - CHANGED WordPress checkbox and radio alignment no longer reset with other WP resets
 - ADDED flex wrapping to FormBuilder for responsive.
 - FIXED regression from 1.0.2 on select.form-control elements.
 - FIXED FormBuilder dropdown arrow alignment.
 - FIXED FormBuilder flex wrap and input overflow. Various other FormBuider front-end responsive tweaks (ref #62)
 - CHANGED Multiple Thames fixes, including darkmode and alerts (see !34 and !35)

1.0.2
 - REMOVED WordPress select list appearance reset (https://lab.civicrm.org/extensions/riverlea/-/issues/60)

1.0.1
 - CHANGED updated streams/empty/_variables.css to reflect latest variables list.

1.0.0
 - CHANGED status to stable

1.0.0beta3
 - REMOVED narrow front-end form width for all Streams other than Hackney
 - FIXED inline edit button alignment for SearchKit output
 - CHANGED readme.
 - REMOVED border from form builder fieldset
 - CHANGED Hackney button icon background to tint to support button colouring
 - CHANGED Minetta tab body and active state to another value than --crm-page-background

1.0.0beta2
 - ADDED padding to FormBuilder dashlets
 - ADDED tabs border fixes to FormBuilder, and SK/FB listings
 - FIXED inconsistent tabs display
 - FIXED bold font setting for extensions list accordion
 - FIXED transparent background for tabs
 - ADDED separate styling for the 'danger' delete links in dropdowns, created Thames override for default
 - NEW CSS VARIABLE
    --crm-tabs-radius
    --crm-dropdown-alert-bg

1.0.0beta
 - CHANGED fonts are in shared directory rather than streams' directory
 - REMOVED duplicate parts of Thames

0.10.20
 - NEW STREAM - Thames, by Rich Lott, @artfulrobot.

0.10.19
 - FIXED FormBuilder List to match SearchKit list (better fix would harmonise markup)
 - FIXED padding on SearchKit Dashlets
 - ADDED front-end Legend realignment to SK/FB fieldset/legends (all three issues in #5)
 - FIXED contribution page tabs/button alignment/offset

0.10.18
 - CHANGED better front-end handling of form-builder pages
 - FIXED alert message handling (all was treated as .help, including .error and .warning)
 - CHANGED alignment of icons in alerts
 - FIXED - inline edit alignment, styling (https://lab.civicrm.org/extensions/riverlea/-/issues/5#note_170713)

0.10.17
 - ADDED civicrm.css to core with default settings. This is primarily to support separate theme extensions using RiverLea as a parent theme.
 - REMOVED civicrm.css from streams where not custom (e.g. Minetta but not Walbrook)
 - CHANGED front-end form layout, including hiding Stream name/version on front-end

0.10.16
 - ADDED CiviMail custom CSS override (civi_mail-ang/crmMailing.css)
 - CHANGED CiviMail custom CSS to integrate with override file, replace fixed values with variables
 - ADDED crmStatusPage custom CSS override ang/crmStatusPage.css
 - CHANGED Status page customisations and integration (e.g. colours, dropdwon settings), plus inline flexbox

0.10.15
 - REMOVED Wallbrook SemiBoldItalic font (unused)
 - REMOVED Wallbrook font.css - moved font family definitions into civicrm.css
 - FIXED min width issue in contact dashboard tabs
 - CHANGED Wallbrook fonts file removed
 - REMOVED 2 CSS VARIABLES  (simplification / drops Blackberry, iOS1 support)
    --crm-italic-style
    --crm-bold-weight

0.10.14
 - FIXED table header/sort colour issues #57
 - FIXED drop shadown css variable in alpha filter not used
 - ADDED label colour
 - ADDED label bold/weight variables to contact dashboard label
 - ADDED CSS VARIABLE:
    --crm-input-label-color
    --crm-btn-margin (for spacing between buttons)

0.10.13
 - CHANGED radio/checkbox handling for WordPress to inherit more of the WordPress styling.
 - REMOVED unused CSS variable
 - ADDED joomla.css into /core/css - and removed Joomla specific css from _cms.css (shrinking the two together)

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
