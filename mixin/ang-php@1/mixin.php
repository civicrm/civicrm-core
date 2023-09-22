<?php

/**
 * Auto-register "ang/*.ang.php" files.
 *
 * @mixinName ang-php
 * @mixinVersion 1.0.0
 * @since 5.45
 *
 * @param CRM_Extension_MixInfo $mixInfo
 *   On newer deployments, this will be an instance of MixInfo. On older deployments, Civix may polyfill with a work-a-like.
 * @param \CRM_Extension_BootCache $bootCache
 *   On newer deployments, this will be an instance of MixInfo. On older deployments, Civix may polyfill with a work-a-like.
 */
return function ($mixInfo, $bootCache) {

  /**
   * @param \Civi\Core\Event\GenericHookEvent $e
   * @see CRM_Utils_Hook::angularModules()
   */
  Civi::dispatcher()->addListener('hook_civicrm_angularModules', function ($e) use ($mixInfo) {
    // When deactivating on a polyfill/pre-mixin system, listeners may not cleanup automatically.
    if (!$mixInfo->isActive() || !is_dir($mixInfo->getPath('ang'))) {
      return;
    }

    $files = (array) glob($mixInfo->getPath('ang/*.ang.php'));
    foreach ($files as $file) {
      $name = basename($file, '.ang.php');
      $module = include $file;
      if (empty($module['ext'])) {
        $module['ext'] = $mixInfo->longName;
      }
      $e->angularModules[$name] = $module;
    }
  });

};
