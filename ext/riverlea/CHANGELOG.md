1.4.7-6.5
 - ADDED - fallback font for iframe pages using Minetta, was previously inheriting browser default (https://github.com/civicrm/civicrm-core/pull/33052)
 - ADDED - improvement to display of inline FormBuilder edit dropdown button (https://github.com/civicrm/civicrm-core/pull/33024)
 - FIXED - overdue activity not listed in red/'danger' (https://github.com/civicrm/civicrm-core/pull/33028)

1.4.6-6.4
 - FIXED - multiuple issues on Contact Summary inline-edit resolved and improved (https://github.com/civicrm/civicrm-core/pull/32936)
 - FIXED - multiple input-size modifiers (ie .four) that weren't loading, now do (https://github.com/civicrm/civicrm-core/pull/32942), 6.3 port
 - FIXED - multiple issues with rendering of ol & ul lists (https://github.com/civicrm/civicrm-core/pull/32934), 6.3 port
 - FIXED - clipping of dropdown-menus on SearchKit Table displays (https://github.com/civicrm/civicrm-core/pull/33016)
 - FIXED - regressions of Bootstrap's btn-block and panel-body, an issue in deduper extension (https://github.com/civicrm/civicrm-core/pull/33023)
 - FIXED - layout issues with other link on SearchKit admin table (https://github.com/civicrm/civicrm-core/pull/32975)
 - FIXED - typo on CSS variable name preventing padding between multiple accordions (https://github.com/civicrm/civicrm-core/pull/32969)
 - FIXED - reverted details fix that had some CMS issues (https://github.com/civicrm/civicrm-core/pull/32959)

1.4.5-6.3beta
 - FIXED 'text-danger' in FormBuilder drop-down to cover tabset dropdown 'delete tab'.
 - CHANGED restored internal scrolling in FormBuilder for each panel to support longer forms (https://lab.civicrm.org/extensions/riverlea/-/issues/114)
 - ADDED CSS VARIABLE '--crm-panel-head-height' to handle different stream panel head / tab-bar heights for the internal scrolling calculation.
 - FIXED - close icon on community message floating left not right (https://github.com/civicrm/civicrm-core/pull/32821)
 - FIXED - recentres' spinning Civi logo (https://github.com/civicrm/civicrm-core/pull/32819) from 1.4.4.

1.4.4-6.3alpha
 - FIXED inline checkbox regression (https://lab.civicrm.org/extensions/riverlea/-/merge_requests/51) ht @yashodha
 - FIXED loading animation, re-using nav-bar spinning logo svg, set as a css variable. (ref https://lab.civicrm.org/dev/user-interface/-/issues/84)
 - FIXED invisible select2 selected item when in a dropdown (ref https://lab.civicrm.org/dev/core/-/issues/5870)
 - FIXED PrettyPrint code blocks: restored indents lost in 1.3.8-6.0alpha change.
 - FIXED ol.linenums line-numbering restored.
 - CHANGED improved contrast of tag check-boxes (https://github.com/civicrm/civicrm-core/pull/32810).

1.4.3-6.2alpha
This release makes a series of changes to how emphasis colours (ie primary/success/info/etc) are handled across RiverLea. The main changes:
 - ADDED - alert border colours are auto-generated using `hsl(from var(--crm-alert-background-X) h s calc(l - Y))`
 - ADDED - notification icon colours are auto-generated using `hsl(from var(--crm-c-X) h s calc(l + Y))`
 - ADDED auto primary/secondary hover darken cols via HSL.
 - CHANGED - 'crm-c-alert' to 'crm-c-danger' (https://github.com/civicrm/civicrm-core/pull/32409)
 - CHANGED - Walbrook info alert made consistent with other stream and emphasis alerts (no more inverse bg colour)
 - CHANGED - names of alert emphasis variables made consistent with other names (help becomes 'success', background becomes bg, and the variable targe goes at the end not middle)
 - CHANGED - made heading bg colours match info colour scheme to simplify dark-mode flip.
 - CHANGED - variable for danger/red/cancel colours to --crm-c-danger-on-page-bg, for better dark-mode contrast.
 - CHANGED - variable for form 'required' marker from --crm-c-danger to --crm-notify-danger for a brighter red shade in light and dark mode.
 - REMOVED - many literal colours from core CSS (ie green/red/etc), particularly Bootstrap, swapping to pairs that should maintain contrast in dark-mode.
 - REMOVED - most literal colour names in dark modes that inverse, ie light green becomes dark. Instead emphasis variable points to a different colour (Bg cols still inverse, plus some blues).
 - FIXED - colour contrast issue for `text-X` in dark-mode on all streams.
 - FIXED - extension manager even row / enabled extension hover was grey, now is darker green
 - FIXED - icons on extensions page to notify icon colours to stand out more…
 - FIXED - contrast ratio of AAA added to multiple emphasis colour/text interactions - many were AA or worse (e.g. Minetta Warning/amber, Walbrook blue/primary/info, success, danger).
 - ADDED CSS VARIABLES
    --crm-c-info-light - lighter shade of info colour from --crm-alert-info-bg
    --crm-c-info-on-page-bg - new value for 'text-info'
    --crm-c-warning-light - lighter shade of warning from --crm-alert-warning-bg
    --crm-c-warning-on-page-bg - new value for 'text-warning'
    --crm-c-danger-light - lighter shade of danger from --crm-alert-danger-bg
    --crm-c-danger-on-page-bg - new value for 'text-danger'
    --crm-c-success-light - lighter shade of success from --crm-alert-success-bg
    --crm-c-success-on-page-bg - new value for 'text-success'
    --crm-c-primary-on-page-bg - new value for 'text-primary' - SearchKit outputs this
    --crm-c-secondary-on-page-bg - new value for 'text-secondary' - SearchKit outputs this

1.4.2-6.2alpha
 - FIXED - today's date background on date-picker (https://lab.civicrm.org/dev/core/-/issues/5807).
 - FIXED - restore .nowrap class (https://lab.civicrm.org/extensions/riverlea/-/issues/125).
 - FIXED - inconsistent padding around 'add address' block on contact dashboard (https://lab.civicrm.org/extensions/riverlea/-/issues/122).
 - FIXED - stange alignment on summary modals (https://lab.civicrm.org/extensions/riverlea/-/issues/121).

1.4.1-6.2alpha
 - FIXED - start/end date appears inline (https://lab.civicrm.org/extensions/riverlea/-/issues/120)
 - FIXED - float of prev/next on contact dashboard on WordPress in some contexts (https://lab.civicrm.org/extensions/riverlea/-/issues/118).
 - ADDED - new multi-buttons wrapper `.crm-buttons` to provide uniform gap and wrap around multiple buttons, as no other class provides this - and instead it's handled on a case-by-case basis which is a waste of css. Not used anywhere currently, but referenced in discussion: https://github.com/civicrm/civicrm-core/pull/32344 & https://lab.civicrm.org/extensions/riverlea/-/issues/101.

1.4.0-6.2alpha
 - FIXED - right-align of event participant contact details removed (https://lab.civicrm.org/extensions/riverlea/-/issues/119).
 - FIXED - FormBuilder left tabs squash and become illegible when too many items (https://lab.civicrm.org/extensions/riverlea/-/issues/116)
 - CHANGED - only bottom align delete button when there are a limited number of custom activities (default list plus up to three more custom activities), otherwise keep inline (https://lab.civicrm.org/extensions/riverlea/-/issues/117)

1.3.8-6.0alpha
 - FIXED - solves various issues around the naming of crm-text-light and crm-text-dark (https://github.com/civicrm/civicrm-core/pull/31994);
 - FIXED - Bootstrap Time input fields limit width, not 100%.
 - FIXED - PrettyPrint code blocks  (e.g. on API4) should wrap when in limited space.
 - FIXED - Contact Dashboard inline edit name - reset position and add drop-shadow to distinguish
 - FIXED - All Dark - forces a light bg colour to 'prettyprint' code blocks as inverting the colours would require many replacements, also addresses some API3 code blocks.
 - FIXED - All Dark - FormBuilder input text illegible, changed colour to 'crm-c-text'
 - FIXED - ALL Dark - FomrBuilder GUI bar inline span colour illegible, changed colour to 'crm-c-text'
 - FIXED - All Dark - Select2 'disabled' list items illegible, set bg colour and changed cursor icon to 'not-allowed'.
 - FIXED - All Dark - FormBuilder crm-ui-editable region hover illegible, changed text colour from inherit to 'crm-c-text-dark' - created RL version of ang/crmUI.css to achieve.
 - FIXED - ALL Dark - FormBuilder settings / gears icon was an illegible colour.
 - FIXED - All Dark - SearchKit 'where' 'and' labels illegible. Changed colour to 'crm-primary-text' to match 'crm-primary' background.
 - FIXED - All Dark - pie chart legend text made legible, setting text fill colour to 'crm-c-text'
 - FIXED - All Dark - '.alert-warning' paragraph text given explicit colour to resolve clash with '.alert' paragraph colour.
 - FIXED - Minetta & Hackney Dark illegible info alerts: 'crm-alert-info-text' changed to '-—crm-c-text-light' from '-—crm-c-blue-light'
 - FIXED - Minetta & Hackney Dark - illegible alert buttons: removed '--crm-c-alert-text' & '-—crm-c-alert' from dark.css
 - FIXED - Hackney * Thames Dark - warning alert text colour setting removed to make legible.
 - FIXED - Minetta Dark - primary/primary hover darkened with dark.css variables to stand out on tab region navbar.
 - FIXED - Walbrook Dark - Crm-c-success-text & crm-c-warning-text - change from dark to light as bg colours have changed.
 - ADDED CSS VARIABLES - connected to first item at top
    --crm-checkbox-list-bg:
    --crm-checkbox-list-bg2:

1.3.7-6.0alpha
 - FIXED - inline code/pre block issue. Also applied to keyboard elements. Override created for td > code. (#113)
 - FIXED - API3 - illegible colours on Select2 description
 - CHANGED - Version numbers on streams -> updated to latest.

1.3.6-6.0alpha
 - FIXED - Responsive: make dashlets stack on Civi Dashboard under 990px (same as Greenwich)
 - FIXED - Show Add Address without hover on Contact Dashboard (#109)
 - FIXED - code/pre formatting is wrapping on one line (#108)
 - FIXED - HackneyBrook/DarkMode: Notification text colour illegible (https://github.com/civicrm/civicrm-core/pull/31994)
 - CHANGED - Select2 search box now fills width of Select, neatens padding.
 - CHANGED - Swapped edit icon for add icon on add address on Contact Dashboard & tidied appearance (#109)

1.3.5-6.0alpha
 - ADDED - padding to contact dashboard inline edit div (#107)
 - FIXED - Contact Layout Editor extension: double padding; broken right float (#107)
 - FIXED - Contact dashboard inline edit responsive handling.
 - CHANGED - Contact dashboard inline edit - add sidebar link width to positioning to keep inline
 - FIXED - Delete icon on delete buttons inherits the button text colour not the delete icon colour.

1.3.4-6.0alpha
 - FIXED - Responsive: add wrap to Contact Form Contact Name inline edit (thanks Artful Robot)
 - CHANGED - Thames, A11y: darkened default blue.
 - CHANGED - Thames, tweaks (removes redundant css, adds box-shadow on part of contact dashboard)
 - CHANGED - Thames, improves QuickSearch appearance

1.3.3-6.0alpha
 - FIXED - FrontEnd: restored padding on alert boxes
 - FIXED - FrontEnd: front-end-compressed table display form
 - FIXED - Alert buttons link colour reset. Alert border colour mis-match for .alert.status.crm-ok
 - FIXED - Front-end alignment issue for some blocks
 - CHANGED - Minetta front-end label and inputs are inline, similar to Greenwich (others are stacked)
 - CHANGED - FrontEnd: Added an 800px default width, similar to Greenwich, which impacts Minetta and Walbrook (forms are no longer 100% width).
 - FIXED - Select lists match width of content
 - FIXED - Select list padding on front-end doesn't hide selected value
 - FIXED - Select list inline alignment for description
 - FIXED - Dark mode for alpha list Contrast Ratio across all streams, plus extra fixes in Walbrook and Thames.
 - FIXED - AFGuiEditor - button group wrapping
 - FIXED - alignment of buttons above some SearchKit table displays corrected
 - ADDED CSS VARIABLES -
    --crm-wizard-box-shadow
    --crm-wizard-arrow-thickness (and increased Walbrook's to 2px)
    --crm-btn-weight
    --crm-btn-font
    --crm-f-fieldset-box-shadow
    --crm-f-legend-position (for moving legend between on and inside fieldset border)
    --crm-f-legend-padding
    --crm-f-label-margin (for vertical spacing between label and input)
    --crm-f-label-gap (for horizontal spacing between label and input)
    --crm-f-label-color

1.3.2-6.0alpha
 - FIXED - re-added .crm-c-blue-darker which had been removed, breaking primary hover bg in Walbrook.
 - FIXED - crmIconPicker custom CSS not loading so moved selectors
 - FIXED - SearchKit Tree fixes: alignment, padding, wrap & bg colour for inline edit cancel.
 - CHANGED - SearchKit Tree adjusted the bordering to make the hierarchy a little more clear.
 - FIXED - Responsive: contact dashboard two columns collapse to one at 768px.
 - CHANGED - Responsive: Contact dashboard side tabs were triggering at 500px, widened to 768px for legibility.
 - ADDED - Responsive: Wrap for Action Link multiple buttons.
 - ADDED - Responsive: better handling of dialogs under 768px.
 - FIXED - Responive: made min/max media queries consistent.

1.3.1-6.0alpha
 - CHANGED - Upgrade success box colours to match 'alert-help/success' style (fixes some contrast ratio issues).
 - CHANGED Updated Civi core CSS files with changes in core from last six months: admin.css, dashboard.css, searchForm.css.
 - ADDED sticky table header css.
 - ADDED dark-mode handling of SVG charts text labels.
 - FIXED Joomla '.disabled' opacity fix.
 - FIXED regression from contact dashboard ID change of `contact-summary` to `contact-0`.
 - ADDED tr.error danger bg & text colour (e.g. on import contacts summary table).
 - ADDED text colour to JQuery date picker (to handle inverse darkmode).
 - ADDED provisional support for SearchKit Tree Display.
 - FIXED wrong text colour on footer status label.
 - FIXED image on contact dashboard can overlap multiple tags: https://lab.civicrm.org/extensions/riverlea/-/issues/99.
 - CHANGED Minetta colours for Contrast Ratio WCAG AAA: success, danger & info alert bg & text;
 - CHANGED Minetta DarkMode colours for Contrast Ratio WCAG AAA: alerts, backgrounds, input description, border, tabs.
 - CHANGED Walbrook colours for Contrast Ratio WCAG AAA: darker text, lighter success & warning button bg, lighter danger alert bg, darker link + hover, alert-status
 - CHANGED Walbrook DarkMode colours for Contrast Ratio WCAG AAA: brighter links, darker column rows, wizard, buttons, bg regions, alerts, accordions, heading bg color
 - CHANGED HackneyBrook colours for Contrast Ratio WCAG AAA: darker text, some alerts.
 - CHANGED HackneyBrook DarkMode colours for Contrast Ratio WCAG AAA: brighter links, darker column rows, wizard, buttons, bg regions, alerts, accordions, heading bg color.
 - ADDED CSS VARIABLES (4) - WCAG AA compliance on notification icons is impossible without this (because of different notify bg color):
     --crm-notify-alert
     --crm-notify-warning
     --crm-notify-success
     --crm-notify-info

1.3.0-5.83.alpha
 - ADDED - padding to body of Afform accordions
 - REMOVED - Afform multiple buttons margin (https://github.com/civicrm/civicrm-core/pull/31739)
 - CHANGED - SearchKit table cell with buttons reverts from flex to `table-cell` with only one button to resolve alignment issue for single buttons (not fixed for multiple buttons) (https://github.com/civicrm/civicrm-core/pull/31728#issuecomment-2580090556)
 - CHANGED - alignment of searchkit drag handle & delete icon responds better across streams
 - CHANGED - Reset border and margin on Afform search result select-all checkbox (mostly impacted Hackney Stream)
 - FIXED - Small Bootstrap buttons with icons use small button padding (bug meant they had regular button padding)
 - FIXED - SearchKit admin View Display button overlapped help text
 - FIXED - Non-visible trash icon in some scenarios (https://github.com/civicrm/civicrm-core/pull/31769)
 - FIXED - Thames accordion icons offset in SearchKit admin (https://lab.civicrm.org/extensions/riverlea/-/issues/102).
 - FIXED - Thames spacing on contributions dashboard tabs and top buttons.
 - FIXED - SearchKit Tokens extension overlaps (https://lab.civicrm.org/extensions/riverlea/-/issues/11#note_175631)
 - LINT - spacing on crm.designer.css.

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
