<?php

/**
 * Auto-register "**.mgd.php" files.
 *
 * @mixinName mgd-php
 * @mixinVersion 1.0.0
 *
 * @param CRM_Extension_MixInfo $mixInfo
 *   On newer deployments, this will be an instance of MixInfo. On older deployments, Civix may polyfill with a work-a-like.
 * @param \CRM_Extension_BootCache $bootCache
 *   On newer deployments, this will be an instance of MixInfo. On older deployments, Civix may polyfill with a work-a-like.
 */
return function ($mixInfo, $bootCache) {

  /**
   * @param \Civi\Core\Event\GenericHookEvent $e
   * @see CRM_Utils_Hook::managed()
   */
  Civi::dispatcher()->addListener('hook_civicrm_managed', function ($event) use ($mixInfo) {
    // When deactivating on a polyfill/pre-mixin system, listeners may not cleanup automatically.
    if (!$mixInfo->isActive()) {
      return;
    }

    $mgdFiles = CRM_Utils_File::findFiles($mixInfo->getPath(), '*.mgd.php');
    sort($mgdFiles);
    foreach ($mgdFiles as $file) {
      $es = include $file;
      foreach ($es as $e) {
        if (empty($e['module'])) {
          $e['module'] = $mixInfo->longName;
        }
        if (empty($e['params']['version'])) {
          $e['params']['version'] = '3';
        }
        $event->entities[] = $e;
      }
    }
  });

};
