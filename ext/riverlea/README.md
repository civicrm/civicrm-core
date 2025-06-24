# RiverLea Theme Framework

This Framework separates CiviCRM's visual/UI CSS from structural CSS, using CSS variables. Installing it provides you with four new Riverlea Themes or "Streams" which are almost entirely created with CSS variables:
 - Minetta, named after the river that runs under Greenwich, NYC. It is based on Civi's default 'Greenwich' theme.
 - Walbrook, named after the river that runs under Shoreditch, London. It is based on Shoreditch/TheIsland theme.
 - Hackney, named after the river that runs under Finsbury Park, based on Finsbury Park theme.
 - Thames, named after the river that runs close to Artful Robot HQ, based on their Aah theme.

When you enable CiviCRM, you will see these themes in your usual CiviCRM theme options on the Display Settings page.

The extension is licensed under [AGPL-3.0](LICENSE.txt).

## Use in Front-End CiviCRM

**USE WITH CAUTION AND TESTING** While RiverLea has been widely tested in the backend of CiviCRM, be very careful to use in front-end. Given the wide number of themes and scenarios for front-end pages, for existing sites we recommend only applying it to an existing web front-end after comprehensive testing on a dev site.

Overwriting CSS variables for the front is straightforward (they can be nested within `.crm-container.crm-public` and there's a number of front-end specific variables, prefixed `--crm-f-`), but **testing is essential**.

## [Changelog](CHANGELOG.md)

- 1.4.x-6.2.alpha - ongoing fixes/changes against 6.2
- 1.3.x-6.0.alpha - Contrast ratios, responsive and Front-end changes, including new variables, plus fixes (see changelog)
- 1.2.x-5.81 - Regression fixes against 5.81 RC
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

This extension is bundled with CiviCRM core from version 5.82 onwards. You can install it from the Manage Extensions page.

### After installation

After installing the extension, go to Nav menu > Administer > Customize Data and Screens > Display Preferences, and select the stream you want.

You can also set your Dark Mode preference on this screen: either always use Light Mode, always use Dark Mode, or let the user's browser/OS decide.

## Extension Structure

### Core directory - `core`

The majority of the Riverlea extension is a layer of core CSS, which styles CiviCRM markup based on the value
of a number of CSS variables.

The list of all CSS variables with their default values can be found at `core/css/_variables.css`.

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

### Stream directories - `streams/[stream_name]`
Each stream in the extension has a subdirectory under `streams` which primarily contains CSS files for the stream: a `_variables.css`file and a `_dark.css` if darkmode is supported. Variables in this `_variables.css` file will overrule any variables in the core list above.

A stream could also include fonts, images and other CSS files, which can be loaded from the `_variables.css` file as an import.

Each stream also has a Managed Record file which is used to "install" the stream. These are found in the `managed` directory.

## Customising

Riverlea is designed to allow users to customise their UI by tweaking variables or creating new streams.

At this stage, small changes are still being made to the variable structure, so any customisations you make may require revisiting in a future upgrade.

The `core/css/_variables.css` file will give you idea of variables which can be altered.

A [Customiser tool](https://github.com/civicrm/civicrm-core/pull/32344) is also being developed to create and edit Streams more easily through the UI.

Use of the [ThemeTest extension](https://lab.civicrm.org/extensions/themetest) is recommended to more quickly identify which CSS variables match which UI element, and test multiple variations for each.

### 1. Add CSS variables to your parent theme

For instance, to give all contribution page buttons rounded corners, you could add to your CMS theme:

```
--crm-btn-radius: 2rem;
```

### 2. Add a custom CSS snippet in an extension

You can override variables by listening to `hook_civicrm_alterBundle`:

```
function my_ext_civicrm_alterBundle(CRM_Core_Resources_Bundle $bundle) {
  if ($bundle->name === 'coreResources') {
    $riverleaOverrides = <<<CSS
      :root {
        --crm-c-primary: green;
      }
    CSS;
    # weight should override river.css
    $bundle->addStyle($riverleaOverrides, ['weight' => 200]);
  }
}
```

### 3. Create a new stream (preview)

New streams can be created using CiviCRM's Managed Entity system. (This is the same mechanism as can be used for packaging SearchKits, CustomFields, ContactTypes etc).

The Customiser will provide tools to automate some of this process.

0. If not adding to an existing extension, [follow the docs to create one](https://docs.civicrm.org/dev/en/latest/extensions/civix/#generate-module).

1. Enable the `mgd-php` mixin in your extensions `info.xml`

```
  <mixins>
    <mixin>menu-xml@1.0.0</mixin>
    <mixin>mgd-php@1.0.0</mixin>
    ...
  </mixins>
```

2. Copy the template `*.mgd.php` from the `docs` folder of this extension into your extension's `managed` folder (create the managed folder if it doesn't exist). Remove the `.template` part so the file extension is `*.mgd.php`.

3. Update the `use CRM_riverlea_ExtensionUtil` line for the equivalent from your extension.

4. You can add variable declarations directly to the `vars` and `vars_dark` keys in the `*.mgd.php` file.

5. Or you can create `stream/main.css` and `stream/dark.css` files in your extension, and add your CSS to them. It's best to use variable declarations as much as possible to maintain compatibility with future versions
of CiviCRM.


## Troubleshooting
- Unless you really need it (e.g. applying an urgent fix, or running a test), delete the custom/ext version of RiverLea, once you are on CiviCRM 5.80 or later.
- After removing the custom/ext RiverLea directory, the civicrm/ext version should load automatically. It may appear to be enabled, but normally you will need to disable and re-enable it before RiverLea streams appear in Display Settings.
