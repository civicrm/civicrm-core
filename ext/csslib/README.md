# csslib

The `csslib` provides APIs for use by themes/extensions to manage (S)CSS content.

## Example (PHP)

```php
$cssContent = Civi::service('csslib.scss_compiler')->compile(
  '#bootstrap-theme { @import "bootstrap"; }', 
  [ E::path('extern/bootstrap3/assets/stylesheets')]
);
```

## Example (CLI)

```bash
cv ev 'return Civi::service("csslib.scss_compiler")->compile(".foo { .bar::placeholder { color: blue; } }");'
```

## Toolchain

By default, `csslib` compiles SCSS using:

* [scssphp](https://scssphp.github.io/scssphp/)
* [padaliyajay/php-autoprefixer](https://github.com/padaliyajay/php-autoprefixer)

This combination is excellent because it works automatically on most PHP environments. However, there are some
trade-offs in using this toolchain.

To configure csslib, use these settings:

* `csslib_srcmap`:
    * `none`: CSS file will not include source-mapping. (Note: This may add a few seconds to the generation time.)
    * `inline`: Source mappings will be generated and bundled directly into the CSS file.
* `csslib_autoprefixer`: This determines how newer/standardized CSS elements (e.g. `::placeholder`) are mapped to older/backward-compatible CSS elements (e.g. `::-webkit-input-placeholder`).
    * `none`: CSS elements are not mapped for compatibility. May limit support for older browsers.
    * `php-autoprefixer` (*default*): CSS elements mapped via [padaliyajay/php-autoprefixer](https://github.com/padaliyajay/php-autoprefixer) (Note: If there is a source-map, this may interfere with it.)
    * `autoprefixer-cli`: CSS elements mapped via [autoprefixer-cli](https://www.npmjs.com/package/autoprefixer-cli)). (Note: Requires manual/global installation.)
