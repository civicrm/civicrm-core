# RiverLea Theme Framework

Theme architecture for CiviCRM that separates visual/UI CSS from structural CSS using CSS variables. It currently has two variations, or 'streams': Minetta and Walbrook, named after streams that run under Greenwich, NYC and Shoreditch, London.

The extension is licensed under [AGPL-3.0](LICENSE.txt).

## Changelog

- 0.1 - proof-of-concept, Brunswick, empty theme structure doing just two things: for older CMS interfaces enforces a 100% font-size default to cascade the browser default font-size, and demonstrates a 1rem variable on top of that for some Civi body text sizes. The computed font-size of Civi paragraph and table text should show as 16px in Inspector (for standard setups).
- 0.2 - adds a bunch of css variables for testing/dev, adds the entirity of the current Greenwich Bootstrap 3 build to start cutting it back, and adds a components directory with initial component 'accordions' (with animated exapnd/close + CSS variables). Separate components files will likely be merged when the extension is moving to testing, to reduce http requests.
- 0.3 - Backdrop, Drupal7 + Seven, Drupal9 + Claro/Seven, Joomla 4, Standalone, WordPress. Loads with two theme variations/streams: Minetta and Walbrook. Does not cover: front-end layouts, < 1000px screens, Joomla 3, other Drupal admin themes, light/dark modes. 
- 0.4 - CSS files restructure into `/core/css` and `/streams/[stream-name]/css/` with stream variables defined in `[stream-name]/css/_variables.css`. Variables files are version-numbered - 0.4 with this version. *Version numbers should only increase when the CSS Variables in these files change name, are removed or added*.
- 0.5 - Over 50 UI and accessibility issues fixed following testing in CiviCamp Hamburg - with many thanks to Guillaume Sorel, Thomas Renner, Rositza Dikova, Luciano Spiegel & Peter Reck and the organisers - as well as Rich Lott and the Core Team. Instances of `#crm-container` removed or replaced with `.crm-container` which changes some cascade order. Preparation for overwriting other core CiviCRM CSS files from v0.6 which will require CiviCRM 5.75. Version compatibility raised to 5.72 from 5.69 because of Search Kit Builder interface changes: if that interface isn't used then compatibility before 5.69 should be fine.
- 0.6 - NEW VARIABLES. Adds basic dark-mode support. Adds new stream: Hackney Brook, based on Finsbury Park (~90% port). Adds new CSS variables to all streams to support Finsbury Park's two main differences to Shoreditch and Greenwich: button icon styling (unique colours, background, border), and contact dashboard side tabs with active/hover border, similar to the 5.72 SearchKit UI, as well as some extra useful variables (e.g. dialog header border, notification border radius, etc).
- 0.7 - NEW VARIBLES. Improves dark-mode across all three streams. Improves Hackney Brooke. More responsive tables. Many small fixes. Thanks SarahFG, Rich Lott & Guillaume Sorel.
- 0.8 - NEW VARIABLES. Adds front-end support for each stream. Adds CSS Variables for front end with `--crm-f` prefix, including to choose between inline and stacked label/inputs, to create a focus background, to limit the form width. Also some fixes. NB v0.8 will be the last major version of RiverLea to work on CiviCRM < 5.75, due to file structure changes enabled by that release. Small fixes can be added as v0.8.1, v0.8.2, etc.

## Installation (CLI, Zip)

Sysadmins and developers may download the `.zip` file for this extension and
install it with the command-line tool [cv](https://github.com/civicrm/cv).

```bash
cd <extension-dir>
wget https://lab.civicrm.org/extensions/riverlea/-/archive/main/riverlea-main.zip
unzip riverlea-main.zip
```

## Installation (CLI, Git)

Sysadmins and developers may clone the [Git](https://en.wikipedia.org/wiki/Git) repo for this extension and
install it with the command-line tool [cv](https://github.com/civicrm/cv).

```bash
git clone https://lab.civicrm.org/extensions/riverlea.git
cv en riverlea
```

## Usage

After installing the extension, go to Nav menu > Administer > Customize Data and Screens > Display Preferences, and select which theme variation/stream you want (they start with the name 'Riverlea').

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
- Also in the **core/css** directory are over-rides for core CiviCRM CSS files. such as `admin.css` or `dashboard.css`;

## Roadmap

- Restructure files to overwrite CiviCRM core & angular module CSS Frontend following (https://github.com/civicrm/civicrm-core/pull/30397). This should reduce overall CSS loads/file size and the use of `!Important` declarations. Will require CiviCRM 5.75.
- ~~Front-end testing & fixes.~~
- Document streams.
- ~~Darkmode~~.
- ~~Migrate Finsbury Park~~ (and others) to a stream.

## Creating new 'streams'

NB: Streams will be deleted when you upgrade an extension so keep a copy of your changes. Streams are also going to keep changing during alpha stage, so branched streams will go out of sync with the core variables - so don't use other than for testing/exploration, and always compare the version number of the _variables.css file.

1. Duplicate the directory 'empty' in /streams/ and rename it the name of the stream.
2. In riverlea.php add a theme array to the function `riverlea_civicrm_themes(&$themes)`.
3. Edit /streams/[streamname]/css/_variables.css with your custom css variables. You can link to other CSS files in this file.

E.g. to add a stream called "Vimur", you would name the directory 'vimur', and add the following:

```
 function riverlea_civicrm_themes(&$themes) {
  $themes['minetta'] = array(
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
