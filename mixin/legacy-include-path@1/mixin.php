<?php

/**
 * Add extension to PHP's include_path.
 *
 * Historically, civix added every extension to PHP's include_path via
 * `_civix_civicrm_config()`. This mixin provides backward compatibility
 * for extensions that still rely on include_path (e.g. for legacy PEAR-style
 * class loading, CiviCRM API v3, or relative require/include statements).
 *
 * @mixinName legacy-include-path
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
