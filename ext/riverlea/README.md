# RiverLea Theme Framework

Theme architecture for CiviCRM that separates visual/UI CSS from structural CSS using CSS variables. Installing it provides you with four subthemes or 'Streams':
 - Minetta, named after the river that runs under Greenwich, NYC. It is based on Civi's default 'Greenwich' theme.
 - Walbrook, named after the river that runs under Shoreditch, London. It is based on Shoreditch/TheIsland theme.
 - Hackney, named after the river that runs under Finsbury Park, based on Finsbury Park theme.
 - Thames, named after the river that runs close to Artful Robot HQ, based on their Aah theme.
 You can chose between these subthemes via Display Settings, where you can also set dark-mode preferences.

 ## Use in Front-End CiviCRM

**USE WITH CAUTION AND TESTING** While RiverLea has been widely tested in the backend of CiviCRM, given the wide number of themes and scenarios for front-end pages, for existing sites we recommend only applying it on a dev site, or after extensive testing of your front-end Civi layouts.

Overwriting CSS variables for the front is straightforward (they can be nested within `.crm-container.crm-public` and there's a number of front-end specific variables, prefixed `--crm-f-`), but **testing is essential**.

The extension is licensed under [AGPL-3.0](LICENSE.txt).

## [Changelog](CHANGELOG.md)

- 1.1 (5.80) - Release for packaging with CiviCRM core, v5.80.
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

## File Structure

### Stream directories
Each ‘stream’ directory must contain afurther directory `css` which must contain `civicrm.css` and `_variables.css` as well as custom files such as fonts or images.

### Core directory

Contains CSS files in:
- In the **core/css** directory are theme files marked with an underscore:
  - core/css/_base.css – resets, basic type, colours, links, positioning
  - core/css/_bootstrap.css – a Bootstrap subset
  - core/css/_bootstrap3.css – Bootstrap3, currently being migrated to other parts of the theme
  - core/css/_cms.css – resets and fixes specific to different CMSs
  - core/css/_fixes.css - CSS that’s necessary *for now* but one day could go.
  - core/css/_core.css - links to the UI components in the components directory:
- in the **components** directory are reusable anywhere UI elements, such as `_accordions` or `_tables.css`;
- Also in the **core/css** directory are over-rides for core CiviCRM CSS files. such as `admin.css` or `dashboard.css` and in **core** are CSS overwrites for css used in Civi extensions, such as FormBuilder and SearchKit.

## Roadmap

- ~~Restructure files to overwrite CiviCRM core & angular module CSS Frontend~~
- ~~Front-end testing & fixes.~~
- ~~Darkmode~~.
- ~~Migrate Finsbury Park~~ (and others) to a stream.
- ~~Tidy/simplify Bootstrap~~
- ~~Merge Thames~~
- ~~Test creating separate extension with RiverLea as parent theme.~~
- Integrate Wellow/Radstock
- Customiser
- Better documentation

## Creating new 'streams'

NB: Streams will be deleted when you upgrade an extension so keep a copy of your changes. Streams are also going to keep changing during alpha stage, so branched streams will go out of sync with the core variables - so don't use other than for testing/exploration, and always compare the version number of the _variables.css file.

1. Duplicate the directory 'empty' in /streams/ and rename it the name of the stream.
2. In riverlea.php add a theme array to the function `riverlea_civicrm_themes(&$themes)`.
3. Edit /streams/[streamname]/css/_variables.css with your custom css variables. You can link to other CSS files in this file.

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