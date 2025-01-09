# RiverLea Theme Framework

This Framework separates CiviCRM's visual/UI CSS from structural CSS, using CSS variables. Installing it provides you with four subthemes or 'Streams' which are entirely created with CSS variables (other than Thames, which uses a little bit of CSS as well):
 - Minetta, named after the river that runs under Greenwich, NYC. It is based on Civi's default 'Greenwich' theme.
 - Walbrook, named after the river that runs under Shoreditch, London. It is based on Shoreditch/TheIsland theme.
 - Hackney, named after the river that runs under Finsbury Park, based on Finsbury Park theme.
 - Thames, named after the river that runs close to Artful Robot HQ, based on their Aah theme.
 You can chose between these subthemes via Display Settings, where you can also set dark-mode preferences.

 The extension is licensed under [AGPL-3.0](LICENSE.txt).

 ## Use in Front-End CiviCRM

**USE WITH CAUTION AND TESTING** While RiverLea has been widely tested in the backend of CiviCRM, be very careful to use in front-end. Given the wide number of themes and scenarios for front-end pages, for existing sites we recommend only applying it to an existing web front-end after comprehensive testing on a dev site.

Overwriting CSS variables for the front is straightforward (they can be nested within `.crm-container.crm-public` and there's a number of front-end specific variables, prefixed `--crm-f-`), but **testing is essential**.

## [Changelog](CHANGELOG.md)

- 1.2.x-5.81 - Regression fixes against 5.81 RC  (see note on numbering below).
- 1.1 (5.80) - Release for packaging with CiviCRM core, v5.80
- 1.0 - **Release candidate**, with ongoing testing and fixes.
- 0.10 - **Adds fourth stream**. Thames (Aah), as well as extensive fixes & adjustments.
- 0.9 - **Overwrites civi core CSS**. 5.75 only - overwrites core css like SearchKit & FormBuilder with extensive work on both. D7 Garland support.
- 0.8 - **Front-end layouts**. Front-end support for each stream.
- 0.7 - **Dark-mode**. Dark-mode working across all three streams.
- 0.6 - **Adds third stream** Hackney Brook (Finsbury Park).
- 0.5 - **Extensive UI and accessibility fixes** following testing in/around CiviCamp Hamburg.
- 0.4 - **CSS files restructure** core and stream directories, version numbering of variables files with new variables.
- 0.3 - **Two streams, 6 CMS setups tested:** adds Minetta and Walbrook streams. Backdrop, D7 (Seven), D9 (Claro + Seven), Joomla 4, Standalone & WordPress.
- 0.2 - **Establishes structure**, adds Bootstrap3, components - accordion.
- 0.1 - **Proof-of-concept**, basic variables.

### Version numbering
RiverLea has its own version number and confusingly this has changed a few times while we figured out the best approach. It currently takes the form `[River Lea version]-[CiviCRM version built on]`.

This means there might be simultaneously versions `1.2.1-5.81.beta` and `1.3.0-5.82.alpha`.

Please ignore previous numbering patterns.

## Installation

### With (CLI, Zip)

Sysadmins and developers may download the `.zip` file for this extension and
install it with the command-line tool [cv](https://github.com/civicrm/cv).

```bash
cd <extension-dir>
wget https://lab.civicrm.org/extensions/riverlea/-/archive/main/riverlea-main.zip
unzip riverlea-main.zip
```

### With (CLI, Git)

Sysadmins and developers may clone the [Git](https://en.wikipedia.org/wiki/Git) repo for this extension and
install it with the command-line tool [cv](https://github.com/civicrm/cv).

```bash
git clone https://lab.civicrm.org/extensions/riverlea.git
cv en riverlea
```

### After installation

After installing the extension, go to Nav menu > Administer > Customize Data and Screens > Display Preferences, and select which subtheme/stream you want.

## Extension Structure

### Core variables
A list of all base variables used on all streams is at `core/css/_variables.css`.

### Stream/subtheme directories
Each ‘stream’ or subtheme directory must contain a further directory `css` with a `_variables.css`file and a `_dark.css` if darkmode is supported. Variables in this `_variables.css` file will overrule any variables in the core list above. The subtheme can also include fonts, images and other CSS files, which can be loaded from the `_variables.css` file as an import.

### Core directory
Contains CSS files in:
- In the **core/css** directory are theme files marked with an underscore:
  - core/css/_base.css – resets, basic type, colours, links, positioning
  - core/css/_bootstrap.css – a Bootstrap subset
  - core/css/_cms.css – resets and fixes specific to different CMSs
  - core/css/_core.css - links to the UI components in the components directory:
  - core/css/_fixes.css - CSS that’s necessary *for now* but one day could go.
  - core/css/_variables.css - a list of all base variables
- in the **components** directory are reusable  UI elements, such as `_accordions` or `_tables.css`;
- civicrm.css - the core theme css file which loads the other files
- other files here without underscore (`admin.css`, `api4-explorer.css`, `contactSummary.css` etc) overrides civicrm's CSS core directory with files of the same name that are called by templates and only load in certain parts of Civi. E.g. `dashboard.css` loads on the CiviCRM main dashboard, and no-where else.
- three directories: `org.civicrm.afform-ang` for Afform output, `org.civicrm.afform_admin-ang` for FormBuilder and `org.civicrm.search_kit-css` for SearchKit replace css files in core Civi extensions.

## Customising

Adding a customiser is on the roadmap, with a working prototype, but until its issues are resolved customising can be done through one of the three following methods:

### 1. Add CSS variables to your parent theme

For instance, to give all contribution page buttons rounded corners, you could add to your CMS theme:

```
--crm-btn-radius: 2rem;
```

Exploring the _variables.css file will give you idea of how much can be overwritten.

### 2. Create a subtheme 'stream'

1. Inside the `/streams/` directory is an example stream called `empty`. Duplicate this and rename it the name of your stream.
2. In riverlea.php add a theme array to the function `riverlea_civicrm_themes(&$themes)`.
3. Edit `/streams/[streamname]/css/_variables.css` with your custom css variables. You can link to other CSS files, fonts or images in this file - inside the stream.

E.g. to add a stream called "Vimur", you would name the directory 'vimur', and add the following:

```
 function riverlea_civicrm_themes(&$themes) {
  $themes['vimur'] = array(
    'ext' => 'riverlea',
    'title' => 'Riverlea: Vimur',
    'prefix' => 'streams/vimur/',
  );
  $themes['minetta'] = array(
    'ext' => 'riverlea',
    'title' => 'Riverlea: Minetta (~Greenwich)',
    'prefix' => 'streams/minetta/',
  );
  …
 }
```

Use of the [ThemeTest extension](https://lab.civicrm.org/extensions/themetest) is recommended to more quickly identify which CSS variables match which UI element, and test multiple variations for each.

IMPORTANT NOTE: Every time you upgrade RiverLea you will need to add your Stream again. This is obviously less than ideal, so for produciton you may prefer option 3:

### 3. Create a subtheme extension

NB: this approach has had very limited testing

1. Create a theme extension using Civix, following the [instructions in the CiviCRM Developer Guide](https://docs.civicrm.org/dev/en/latest/framework/theme/).
2. Create a subtheme of RiverLea using the instructions in **2. Create a subtheme 'stream'** above.
3. Copy the subtheme into the root of your new theme extenion.
4. Edit its main php file, enable both extensions and select your stream.
E.g. for a stream called 'styx', with a theme extension called 'ocean', then in ocean.php you would write:

```
function ocean_civicrm_themes(&$themes) {
  $themes['styx'] = array(
    'ext' => 'ocean',
    'title' => 'River Styx',
    'prefix' => 'styx/',
    'search_order' => array('_riverlea_core_', 'styx',  '_fallback_'),
  );
}

## Troubleshooting
- Unless you really need it (e.g. applying an urgent fix, or running a test), delete the custom/ext version of RiverLea, once you are on CiviCRM 5.80 or later.
- After removing the custom/ext RiverLea directory, the civicrm/ext version should load automatically. It may appear to be enabled, but normally you will need to disable and re-enable it before RiverLea streams appear in Display Settings.
