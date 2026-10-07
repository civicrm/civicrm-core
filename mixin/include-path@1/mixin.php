<?php

/**
 * Add the extension to PHP's `include_path`.
 *
 * The PHP `include_path` is an old-school way to load code from different packages,
 * and it used to be central piece of glue in CiviCRM. At time of writing, you may find
 * that `include_path` is still used/required for some features in CiviCRM:
 *
 *  - __APIv3 Loader__: Files like `api/v3/**.php` are loaded from the `include_path`.
 *  - __Class Loader__: If your extension has PHP class-files but does NOT declare
 *    them with `<psr0>`/`<psr4>`, then you probably rely on `include_path`.
 *  - __Class Overrides__: If your extension overrides a class from civicrm-core,
 *    then you probably rely on `include_path`.
 *  - __PHP Require__: If you have literal calls to PHP's `require` or `require_once`,
 *    then you may be reliant on `include_path`.
 *
 * These are mostly old patterns, and new CiviCRM extensions rarely require them.
 *
 * NOTE: Historically, `include_path` registration was handled by civix templates.
 * Going forward, these templates are being simplified. If you still need the PHP
 * `include_path`, then you should enable the mixin instead.
 *
 * @mixinName include-path
 * @mixinVersion 1.0.0
 * @since 6.19
 *
 * @param CRM_Extension_MixInfo $mixInfo
 *   On newer deployments, this will be an instance of MixInfo. On older deployments, Civix may polyfill with a work-a-like.
 * @param \CRM_Extension_BootCache $bootCache
 *   On newer deployments, this will be an instance of MixInfo. On older deployments, Civix may polyfill with a work-a-like.
 */
return function ($mixInfo, $bootCache) {
  $extRoot = $mixInfo->getPath() . DIRECTORY_SEPARATOR;
  $include_path = $extRoot . PATH_SEPARATOR . get_include_path();
  set_include_path($include_path);
};
