# CiviCRM and ECMAScript Module Loading

(*This page serves as draft documentation for functionality introduced circa v5.63. It can be migrated to devdocs later.*)

ECMAScript Modules (ESMs) allow you to load a JS file based on a physical-path or a logical-path.  Compare:

```js
import { TableWidget } from 'https://example.com/sites/all/modules/civicrm/js/table-widget.js';
import { TableWidget } from 'civicrm/js/tab-widget.js';
```

Writing `import` statements is easier with logical-paths -- they're short, clean, and adaptable.
(Recall that CiviCRM is deployed with a variety of web-hosts, UFs, and configuration-options. This
means that the physical-paths change frequently and dramatically.)

Logical-paths must be defined with an `importmap`. For native browser-based imports, it looks like:

```html
<script type="importmap">
{
  "import": {
    "civicrm/": "https://example.com/sites/all/modules/civicrm"
  }
}
</script>
```

The `importmap` data-structure is described further at:

* https://developer.mozilla.org/en-US/docs/Web/HTML/Element/script/type/importmap
* https://github.com/WICG/import-maps

Below, we consider a few perspectives on this functionality.

## Extension development

If you develop an extension which includes ESM files, then you may want to add items to the import-map:

```php
function myext_civicrm_esmImportMap(\Civi\Esm\ImportMap $importMap): void {
  $importMap->addPrefix('foo/', E::LONG_NAME, 'js/foo/');
  $importMap->addPrefix('bar/', E::LONG_NAME, 'packages/bar/dist/');
}
```

Then, you can write JS code which imports from these logical-paths, e.g.

```php
Civi::resources()->addModule('
  import { FooClass } from "foo/foo.dist.js"; // Maps to MY_EXTENSION/js/foo/foo.dist.js
  import { barFunc } from "bar/bar.funcs.js"; // Maps to MY_EXTENSION/packages/bar/dist/bar.funcs.js
  barFunc("Hello", new FooClass());
');
```

## Loader development

The *loader* is responsible for reading the `$importMap` and making it available to the browser.  There are
currently two loader implementations.  The future may require more.  But before discussing details, let's consider why.

### State of the ecosystem

At time of writing (early/mid-2023), adoption of browser-based imports/import-maps is in a middle stage:

* We're coming out of a period where browser support was negligble.  Heretofore, module-loading has been implemented by
  a rotating cast of third-party loaders (Webpack, Rollup, SystemJS, RequireJS, etc).  These have been awkward to
  integrate with PHP application-frameworks (CiviCRM, Drupal, WordPress, etc) due to dependency/build/workflow issues.

* All major browsers now publish stable-releases with native support for `module` and `importmap`.  This provides a
  better basis for PHP application-frameworks to use ESM.  This is cause for optimism.  The `importmap` model should
  have good (and improving) support over time. However, older browsers are still around.

* The PHP application-frameworks that we support (Drupal, WordPress, Joomla, Backdrop) have not yet defined services or
  conventions for `importmap`s.  Over time, each may adopt slightly different conventions.  Additionally, these
  frameworks are pluggable -- in absence of a framework-convention, other (third-party) plugins may enact new conventions.

* The browser standards provide a common model, and we should expect this model to influence future updates throughout
  the ecosystem.  But it doesn't guarantee interoperability within the PHP ecosystem -- future releases (of any framework
  or any plugin) could introduce incompatibilities.  We cannot give good solutions for incompatibilities that don't
  exist yet.

* To my mind, the major risks are:
    * Multiple parties may output `<script type="importmap">` tags. The browser only loads one of them.
    * Multiple parties may define the same logical-prefix. For example, `lodash/` might be mapped to contradictory versions.
    * Multiple parties may enable shims (polyfills). These shims may use different versions or contradictory options.

In this middle stage, there is both promise and risk: We can now implement a simple mechanism for loading ESM that is
extension-friendly and that works across all our environments.  But going-forward, it may require more tuning to
maintain compatibility with different environments.

### Definition

The *loader* is responsible for two tasks:

1. Given that a specific page-view needs a specific ECMAScript Module, render the HTML necessary to load it. For example:
    ```php
    echo "<script type=\"module\" src=\"$specificModule\"></script>\n";
    ```
2. Given that CiviCRM (as a whole; core+extensions) has defined an import-map, ensure that the import-map is properly
   loaded so that recursive-dependencies may be resolved. For example:
    ```php
    $civicrmImportMap = json_encode(Civi::service('esm.import_map')->get());
    echo "<script type=\"importmap\">\n{$civicrmImportMap>}</script>\n";
    ```

There are multiple loaders, which perform these tasks in slightly different ways.

Every loader defines a service (`esm.loader.XXX`) and class (`Civi\Esm\XXX`).  For example:

* `esm.loader.browser` (`Civi\Esm\BrowserLoader`): Use pure, browser-based loading with `<script type="module">` and `<script type="importmap">`.
* `esm.loader.shim-fast` (`Civi\Esm\ShimLoader`): Use [es-module-shims](https://github.com/guybedford/es-module-shims) as dynamic polyfill (with preference for browser-based loading).
* `esm.loader.shim-slow` (`Civi\Esm\ShimLoader`): Use [es-module-shims](https://github.com/guybedford/es-module-shims) with more guarantees of cross-browser functionality.

Only one loader is _active_ (based on local settings/defaults). You can access it by calling `Civi::service('esm.loader')`.

> Note: There are some trade-offs between `esm.loader.browser` (more performant) and `esm.loader.shim` (more compatible).  However, this
> is not why the system has two implementations.  To understand that, see "Conflict playbook" below.)

### Conflict playbook

At time of writing, no real conflicts have been identified between Civi ESM and UFs.  However, as mentioned in "State
of the ecosystem", there is a risk of future conflict.

If a conflict a rises, here are some interventions to consider:

1. Switch any affected site to the *other* loader (whichever one you aren't using), e.g.
    * Via `civicrm.setting.php`: `$civicrm_setting['domain']['esm_loader'] = 'browser';`
    * Via `civicrm.setting.php`: `$civicrm_setting['domain']['esm_loader'] = 'shim';`
    * Via `cv`: `cv vset esm_loader=browser`
    * Via `cv`: `cv vset esm_loader=shim`
    * Via browser console: `CRM.api3('Setting', 'create', {esm_loader: "browser"})`
    * Via browser console: `CRM.api3('Setting', 'create', {esm_loader: "shim"})`
2. Develop another loader designed for this new environment.

For example:

* Suppose that, in Jan 2024, Drupal releases an update which adds a vanilla `importmap` (relying on standard browser support).  We now have
  a conflict between `esm.loader.browser` and Drupal's new map.  Ideally, we would develop a new loader specifically for Drupal
  (`esm.loader.drupal`), but that may take a few weeks or months.  In the interim, Drupal admin's can set `CIVICRM_ESM_LOADER` to
  `esm.loader.shim`.

* Suppose that, in Feb 2024, WordPress releases an update which adds an `importmap` with `es-module-shims`.  We now have web-pages which
  load two different versions of `es-module-shims.js` (one from Civi; one from WP), which sounds like a conflict.  Ideally, we would
  develop a new loader specifically for WP (`esm.loader.wordpress`), but that may take a few weeks or months.  In the interim, WP admin's
  can set `CIVICRM_ESM_LOADER` to `esm.loader.browser`.

### Future loaders

Let's continue the hypothetical above: In Jan 2024, Drupal adds a vanilla `importmap`. Knowing Drupal, it will also add a variety of APIs
(hooks, annotations, YAML, etc) for registering items in their `importmap`. Here are some key steps that you may need to take:

* Review `Civi\Esm\BrowserLoader` (`esm.loader.browser`) for reference.
* Review Drupal's new APIs and workflows for reference.
* Define a new implementation (`Civi\Esm\DrupalLoader`).
    * Be sure to provide a service name (eg `@service esm.loader.drupal`)
    * Be sure to provide a `renderModule()` method.
* Update the `esm_loader` setting to list the `drupal` option.
* Update the Civi-Drupal integration to use the `drupal` loader by default, e.g.
    ```php
    // FILE: CRM/Utils/System/Drupal.php
    public function initialize() {
      parent::initialize();
      Civi::dispatcher()->addListener('civi.esm.loader.default', function ($e) {
        if (version_compare(DRUPAL_VERSION', '12.34', '>=')) {
          $e->default = 'drupal';
        }
      });
    }
    ```
* Update the Civi-Drupal integration to relay the import-map. For example, if you were very lucky, this might be as simple as:

    ```php
    // FILE: civicrm.module

    /**
     * Implements hook_import_map_alter().
     */
    function civicrm_import_map_alter(&$drupalImportMaps) {
      $civiImportMaps = Civi::service('esm.import_map')->get();
      $drupalImportMaps = array_merge($drupalImportMaps, $civiImportMaps);
    }
    ```

    Of course, it will probably be more involved.  You will need to review the actual contracts on the Drupal side
    (e.g. `hook_import_map_alter`) and the Civi side (e.g. `Civi::service('esm.import_map')->get()`) before deciding
    how to merge them.
