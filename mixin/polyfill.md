# Mixin Polyfill

Mixins will have built-in support in a future version of CiviCRM (vTBD). However,
for an extension to use a mixin on an older version of CiviCRM, it should
include the polyfill ([mixin/polyfill.php](../mixin/polyfill.php)).

## Usage

The polyfill will be enabled in `civix` (vTBD).  To activate the polyfill in
a bespoke extension (`myext`, `org.example.myextension`), copy `mixin/polyfill.php`.
Load this file during a few key moments:

```php
function _myext_mixin_polyfill() {
  if (!class_exists('CRM_Extension_MixInfo')) {
    $polyfill = __DIR__ . '/mixin/polyfill.php';
    (require $polyfill)('org.example.myextension', 'myext', __DIR__);
  }
}

function myext_civicrm_config() {
  _myext_mixin_polyfill();
}

function myext_civicrm_install() {
  _myext_mixin_polyfill();
}

function myext_civicrm_enable() {
  _myext_mixin_polyfill();
}
```

## Limitations / Comparison

The polyfill loader is not as sophisticated as the core loader. Here's a comparison to highlight some of the limitations:

| Feature | Core Loader | Polyfill Loader |
| -- | -- | -- |
| Load mixins from files (`mixin/*.mixin.php`) | Yes | Yes |
| Load mixins from subdirectories (`mixin/*/mixin.php`) | Yes | No |
| Read annotations from mixin files (eg `@mixinVersion`) | Yes | No |
| Activation - How does it decide to activate a mixin? | Read `info.xml` | Read `mixin/*.mixin.php` |
| Boot cache - How does it store boot-cache? | All boot-caches combined into one file | Each extension has separate boot-cache file |
| Deduplication - If two extensions include the same mixin, then only load one copy. | Yes | Partial - for exact version matches. |
| Upgrade - If two extensions include different-but-compatible versions, always load the newer version. | Yes | No |
